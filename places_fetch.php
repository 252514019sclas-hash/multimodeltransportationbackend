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

$user_id = filter_var($_GET["user_id"] ?? 0, FILTER_VALIDATE_INT);
$city = trim($_GET["city"] ?? "");
$category = trim($_GET["category"] ?? "");
$search = trim($_GET["search"] ?? "");
$userLat = isset($_GET["lat"]) ? (float)$_GET["lat"] : null;
$userLng = isset($_GET["lng"]) ? (float)$_GET["lng"] : null;

// Helper: Haversine distance in km
function calcDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return null;
    $earthRadius = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 1);
}

try {
    // 1. Fetch user travel preferences if available
    $touristRecEnabled = true;
    $travelPurpose = "Daily Commute";
    $preferredDestTypes = [];

    if ($user_id > 0) {
        $prefStmt = $pdo->prepare("
            SELECT tourist_recommendations, travel_purpose, destination_types 
            FROM user_profiles 
            WHERE user_id = ? 
            LIMIT 1
        ");
        $prefStmt->execute([$user_id]);
        if ($pref = $prefStmt->fetch()) {
            $touristRecEnabled = (bool)($pref["tourist_recommendations"] ?? 1);
            $travelPurpose = $pref["travel_purpose"] ?? "Daily Commute";
            $rawTypes = $pref["destination_types"] ?? "Temples, Beaches, Historical Places";
            $preferredDestTypes = array_map('trim', explode(',', strtolower($rawTypes)));
        }
    }

    // 2. Query tourist places
    $sql = "
        SELECT 
            p.place_id,
            p.name,
            p.city,
            p.category,
            p.description,
            p.image_url,
            p.location_address,
            p.visiting_hours,
            p.entry_fee,
            p.rating,
            p.distance_km,
            p.duration_visit,
            p.latitude,
            p.longitude,
            p.nearby_transit,
            CASE WHEN s.saved_id IS NOT NULL THEN 1 ELSE 0 END AS is_saved
        FROM tourist_places p
        LEFT JOIN saved_places s ON p.place_id = s.place_id AND s.user_id = ?
        WHERE 1=1
    ";

    $params = [$user_id];

    if ($city !== "" && strtolower($city) !== "all") {
        $sql .= " AND (p.city LIKE ? OR p.location_address LIKE ?)";
        $params[] = "%$city%";
        $params[] = "%$city%";
    }

    if ($category !== "" && strtolower($category) !== "all") {
        $sql .= " AND p.category LIKE ?";
        $params[] = "%$category%";
    }

    if ($search !== "") {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.location_address LIKE ? OR p.city LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY p.rating DESC, p.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Compute dynamic distance and recommendations
    $formatted = array_map(function($p) use ($touristRecEnabled, $preferredDestTypes, $userLat, $userLng) {
        $pLat = (float)$p["latitude"];
        $pLng = (float)$p["longitude"];

        // Compute real distance from user coordinates if available
        if ($userLat && $userLng && $pLat && $pLng) {
            $dist = calcDistance($userLat, $userLng, $pLat, $pLng);
            if ($dist !== null) {
                $p["distance_km"] = $dist;
            }
        }

        $categoryLower = strtolower($p["category"]);
        $score = 0;
        $matchReasons = [];

        if ($touristRecEnabled && !empty($preferredDestTypes)) {
            foreach ($preferredDestTypes as $type) {
                if ($type === "") continue;
                if (strpos($categoryLower, $type) !== false || strpos($type, $categoryLower) !== false) {
                    $score += 30;
                    $matchReasons[] = "Matches preferred " . ucwords($type);
                    break;
                }
            }
        }

        $score += (float)$p["rating"] * 10;
        $p["is_recommended"] = ($score >= 60);
        $p["recommendation_score"] = $score;
        $p["recommendation_reason"] = !empty($matchReasons) ? implode(" · ", $matchReasons) : "Top-rated Tamil Nadu destination";

        return $p;
    }, $places);

    echo json_encode([
        "success" => true,
        "total" => count($formatted),
        "city" => $city ?: "Tamil Nadu",
        "category" => $category ?: "All",
        "places" => $formatted
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error loading tourist places: " . $e->getMessage(),
        "places" => []
    ]);
}
