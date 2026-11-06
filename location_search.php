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

$query = trim($_GET["q"] ?? $_POST["q"] ?? "");

if (strlen($query) < 1) {
    echo json_encode([
        "success" => true,
        "query" => "",
        "locations" => []
    ]);
    exit;
}

try {
    $searchTerm = "%$query%";

    // 1. Search cached locations table first (Fastest & Verified)
    $stmt = $pdo->prepare("
        SELECT 
            location_id AS id,
            name,
            city,
            state,
            category,
            display_name,
            latitude,
            longitude
        FROM cached_locations
        WHERE name LIKE ? OR city LIKE ? OR display_name LIKE ?
        ORDER BY 
            CASE 
                WHEN name LIKE ? THEN 1
                WHEN city LIKE ? THEN 2
                ELSE 3
            END,
            name ASC
        LIMIT 10
    ");
    $startPrefix = "$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $startPrefix, $startPrefix]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Also search tourist_places table to include attractions as search targets
    $placeStmt = $pdo->prepare("
        SELECT 
            place_id AS id,
            name,
            city,
            'Tamil Nadu' AS state,
            'Tourist Attraction' AS category,
            CONCAT(name, ', ', city, ' (', category, ')') AS display_name,
            latitude,
            longitude
        FROM tourist_places
        WHERE name LIKE ? OR city LIKE ? OR location_address LIKE ?
        LIMIT 6
    ");
    $placeStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $placeResults = $placeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge unique locations
    $seen = [];
    $combined = [];

    foreach ($locations as $loc) {
        $key = strtolower(trim($loc['name']));
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $combined[] = $loc;
        }
    }

    foreach ($placeResults as $p) {
        $key = strtolower(trim($p['name']));
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $combined[] = $p;
        }
    }

    // 3. Fallback: If fewer than 2 results and query has >= 3 chars, query OSM Nominatim for Tamil Nadu
    if (count($combined) < 2 && strlen($query) >= 3) {
        $osmUrl = "https://nominatim.openstreetmap.org/search?" . http_build_query([
            "q" => $query . ", Tamil Nadu",
            "format" => "json",
            "addressdetails" => 1,
            "limit" => 4,
            "countrycodes" => "in",
            "viewbox" => "76.2,13.6,80.4,8.0",
            "bounded" => 1
        ]);

        $opts = [
            "http" => [
                "header" => "User-Agent: MoveSmartAI-TamilNadu/2.0 (movesmart@simats.ac.in)\r\n",
                "timeout" => 3
            ]
        ];
        $context = stream_context_create($opts);
        $osmResponse = @file_get_contents($osmUrl, false, $context);

        if ($osmResponse) {
            $osmData = json_decode($osmResponse, true);
            if (is_array($osmData)) {
                $cacheStmt = $pdo->prepare("
                    INSERT INTO cached_locations (name, city, state, category, display_name, latitude, longitude)
                    VALUES (?, ?, 'Tamil Nadu', ?, ?, ?, ?)
                ");

                foreach ($osmData as $item) {
                    $osmName = $item['name'] ?: $query;
                    $osmDisplay = $item['display_name'];
                    $osmLat = (float)$item['lat'];
                    $osmLon = (float)$item['lon'];
                    $osmCity = $item['address']['city'] ?? $item['address']['town'] ?? $item['address']['county'] ?? 'Tamil Nadu';
                    $osmType = ucwords(str_replace('_', ' ', $item['type'] ?? 'Location'));

                    $key = strtolower($osmName);
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $entry = [
                            "id" => null,
                            "name" => $osmName,
                            "city" => $osmCity,
                            "state" => "Tamil Nadu",
                            "category" => $osmType,
                            "display_name" => $osmDisplay,
                            "latitude" => $osmLat,
                            "longitude" => $osmLon
                        ];
                        $combined[] = $entry;

                        try {
                            $cacheStmt->execute([$osmName, $osmCity, $osmType, $osmDisplay, $osmLat, $osmLon]);
                        } catch (Exception $e) {}
                    }
                }
            }
        }
    }

    echo json_encode([
        "success" => true,
        "query" => $query,
        "total" => count($combined),
        "locations" => $combined
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Location search failed: " . $e->getMessage(),
        "locations" => []
    ]);
}
