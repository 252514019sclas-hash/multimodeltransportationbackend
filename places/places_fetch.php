<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$user_id = filter_var($_GET["user_id"] ?? 0, FILTER_VALIDATE_INT);
$city = trim($_GET["city"] ?? "");
$category = trim($_GET["category"] ?? "");
$search = trim($_GET["search"] ?? "");

try {
    // 1. Fetch user travel preferences if user_id is provided
    $touristRecEnabled = true;
    $travelPurpose = "Daily Commute";
    $preferredDestTypes = [];
    $distancePref = "Within City";

    if ($user_id > 0) {
        $prefStmt = $pdo->prepare("
            SELECT tourist_recommendations, travel_purpose, destination_types, distance_preference 
            FROM user_profiles 
            WHERE user_id = ? 
            LIMIT 1
        ");
        $prefStmt->execute([$user_id]);
        if ($pref = $prefStmt->fetch()) {
            $touristRecEnabled = (bool)($pref["tourist_recommendations"] ?? 1);
            $travelPurpose = $pref["travel_purpose"] ?? "Daily Commute";
            $distPrefRaw = $pref["distance_preference"] ?? "Within City";
            $distancePref = $distPrefRaw;
            $rawTypes = $pref["destination_types"] ?? "Parks, Museums";
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

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $places = $stmt->fetchAll();

    // 3. Process personalization & scoring based on user travel preferences
    $formatted = array_map(function($p) use ($touristRecEnabled, $travelPurpose, $preferredDestTypes, $distancePref) {
        $categoryLower = strtolower($p["category"]);
        $descLower = strtolower($p["description"] . " " . $p["name"]);
        $dist = (float)$p["distance_km"];

        $score = 0;
        $matchReasons = [];

        if ($touristRecEnabled && !empty($preferredDestTypes)) {
            foreach ($preferredDestTypes as $type) {
                if ($type === "") continue;
                if ((strpos($type, 'histor') !== false && strpos($categoryLower, 'histor') !== false) ||
                    (strpos($type, 'temple') !== false && strpos($categoryLower, 'temple') !== false) ||
                    (strpos($type, 'beach') !== false && strpos($categoryLower, 'beach') !== false) ||
                    (strpos($type, 'park') !== false && (strpos($categoryLower, 'park') !== false || strpos($categoryLower, 'nature') !== false)) ||
                    (strpos($type, 'museum') !== false && strpos($categoryLower, 'museum') !== false) ||
                    (strpos($type, 'nature') !== false && strpos($categoryLower, 'nature') !== false) ||
                    (strpos($type, 'adventure') !== false && (strpos($categoryLower, 'nature') !== false || strpos($descLower, 'trail') !== false))) {
                    $score += 30;
                    $matchReasons[] = ucwords($type);
                }
            }

            // Distance preference scoring
            if ($distancePref === "Nearby" && $dist <= 3.5) {
                $score += 20;
                $matchReasons[] = "Nearby ($dist km)";
            } elseif ($distancePref === "Within City" && $dist <= 10.0) {
                $score += 15;
            } elseif ($distancePref === "Long Distance" && $dist >= 6.0) {
                $score += 20;
                $matchReasons[] = "Excursion ($dist km)";
            }

            // Purpose alignment
            if (strcasecmp($travelPurpose, "Adventure") === 0 && (strpos($categoryLower, "nature") !== false || strpos($categoryLower, "park") !== false)) {
                $score += 25;
                $matchReasons[] = "Adventure Friendly";
            } elseif (strcasecmp($travelPurpose, "Tourism") === 0 && (strpos($categoryLower, "histor") !== false || strpos($categoryLower, "museum") !== false)) {
                $score += 25;
                $matchReasons[] = "Sightseeing Pick";
            }
        }

        $isRec = ($touristRecEnabled && $score >= 30);
        $reasonText = null;
        if ($isRec && !empty($matchReasons)) {
            $reasonText = "AI Pick · " . implode(" · ", array_unique(array_slice($matchReasons, 0, 2)));
        }

        return [
            "place_id" => (int)$p["place_id"],
            "name" => $p["name"],
            "city" => $p["city"],
            "category" => $p["category"],
            "description" => $p["description"],
            "image_url" => $p["image_url"],
            "location_address" => $p["location_address"],
            "visiting_hours" => $p["visiting_hours"],
            "entry_fee" => $p["entry_fee"],
            "rating" => (float)$p["rating"],
            "distance_km" => $dist,
            "duration_visit" => $p["duration_visit"],
            "is_saved" => (bool)$p["is_saved"],
            "is_recommended" => $isRec,
            "ai_reason" => $reasonText,
            "_score" => $score + ((float)$p["rating"] * 5)
        ];
    }, $places);

    // Sort by recommendation score first, then rating
    usort($formatted, function($a, $b) {
        if ($b["_score"] != $a["_score"]) {
            return $b["_score"] <=> $a["_score"];
        }
        return $b["rating"] <=> $a["rating"];
    });

    // Remove internal _score key
    $cleanList = array_map(function($item) {
        unset($item["_score"]);
        return $item;
    }, $formatted);

    echo json_encode([
        "success" => true,
        "tourist_recommendations_active" => $touristRecEnabled,
        "travel_purpose" => $travelPurpose,
        "places" => $cleanList
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
