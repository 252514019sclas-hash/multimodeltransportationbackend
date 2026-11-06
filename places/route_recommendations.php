<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if (file_exists(__DIR__ . "/config/config.php")) {
    require_once __DIR__ . "/config/config.php";
} elseif (file_exists(__DIR__ . "/../config/config.php")) {
    require_once __DIR__ . "/../config/config.php";
} else {
    require_once "C:/Xamp/htdocs/multimodeltrans/config/config.php";
}

$origin = trim($_GET["origin"] ?? $_POST["origin"] ?? "Chennai Central");
$destination = trim($_GET["destination"] ?? $_POST["destination"] ?? "Mahabalipuram");

// Helper: Haversine distance in km
function haversineDist($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 9999.0;
    $earthRadius = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 1);
}

// Function to resolve coordinates of a place name
function resolveCoords($pdo, $name) {
    $stmt = $pdo->prepare("SELECT latitude, longitude FROM cached_locations WHERE name LIKE ? OR city LIKE ? OR display_name LIKE ? LIMIT 1");
    $term = "%$name%";
    $stmt->execute([$term, $term, $term]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        return [(float)$row['latitude'], (float)$row['longitude']];
    }
    $pStmt = $pdo->prepare("SELECT latitude, longitude FROM tourist_places WHERE name LIKE ? OR city LIKE ? LIMIT 1");
    $pStmt->execute([$term, $term]);
    if ($pRow = $pStmt->fetch(PDO::FETCH_ASSOC)) {
        return [(float)$pRow['latitude'], (float)$pRow['longitude']];
    }
    return [null, null];
}

try {
    list($origLat, $origLng) = resolveCoords($pdo, $origin);
    list($destLat, $destLng) = resolveCoords($pdo, $destination);

    $hasCoords = ($origLat !== null && $destLat !== null);
    $totalRouteDist = $hasCoords ? haversineDist($origLat, $origLng, $destLat, $destLng) : 50.0;

    // Fetch all Tamil Nadu tourist places
    $stmt = $pdo->query("
        SELECT 
            place_id,
            name,
            city,
            category,
            description,
            image_url,
            location_address,
            visiting_hours,
            entry_fee,
            rating,
            duration_visit,
            latitude,
            longitude,
            nearby_transit
        FROM tourist_places
        ORDER BY rating DESC
    ");
    $allPlaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $recommended = [];

    foreach ($allPlaces as $place) {
        $pLat = (float)$place['latitude'];
        $pLng = (float)$place['longitude'];

        $distToDest = $hasCoords ? haversineDist($pLat, $pLng, $destLat, $destLng) : 999.0;
        $distToOrig = $hasCoords ? haversineDist($pLat, $pLng, $origLat, $origLng) : 999.0;

        $isNearDest = ($distToDest <= 30.0);
        $isNearOrig = ($distToOrig <= 15.0);
        
        // Check if place is along the journey corridor
        // Ellipse test: distToOrig + distToDest <= 1.25 * totalRouteDist + 10 km
        $isAlongCorridor = false;
        $corridorOffset = 0.0;
        if ($hasCoords && $totalRouteDist > 10.0) {
            $sumDist = $distToOrig + $distToDest;
            if ($sumDist <= ($totalRouteDist * 1.35 + 15.0)) {
                $isAlongCorridor = true;
                // Approximate distance off the direct route line
                $corridorOffset = max(0.5, round(($sumDist - $totalRouteDist) * 0.7, 1));
            }
        }

        // City match fallback
        $cityMatch = (stripos($destination, $place['city']) !== false || stripos($place['city'], $destination) !== false);

        if ($isNearDest || $isAlongCorridor || $cityMatch) {
            $routeNote = "Near Destination";
            if ($distToDest <= 5.0) {
                $routeNote = "At Destination Area";
            } elseif ($isAlongCorridor && $distToDest > 15.0 && $distToOrig > 10.0) {
                $routeNote = "Along Route ({$corridorOffset} km off transit path)";
            } elseif ($isNearOrig) {
                $routeNote = "Near Origin Hub";
            }

            $place['distance_from_route_km'] = $isAlongCorridor ? $corridorOffset : $distToDest;
            $place['route_note'] = $routeNote;
            $place['is_along_route'] = $isAlongCorridor;
            $place['dist_to_destination_km'] = $distToDest;

            $recommended[] = $place;
        }
    }

    // Sort by proximity to route and rating
    usort($recommended, function($a, $b) {
        if ($a['is_along_route'] && !$b['is_along_route']) return -1;
        if (!$a['is_along_route'] && $b['is_along_route']) return 1;
        return $a['distance_from_route_km'] <=> $b['distance_from_route_km'];
    });

    // Limit to top 8 most relevant attractions
    $recommended = array_slice($recommended, 0, 8);

    echo json_encode([
        "success" => true,
        "origin" => $origin,
        "destination" => $destination,
        "corridor_distance_km" => $totalRouteDist,
        "total_attractions" => count($recommended),
        "places" => $recommended
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve route recommendations: " . $e->getMessage(),
        "places" => []
    ]);
}
