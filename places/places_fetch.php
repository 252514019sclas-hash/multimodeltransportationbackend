<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$user_id = filter_var($_GET["user_id"] ?? 0, FILTER_VALIDATE_INT);
$city = trim($_GET["city"] ?? "");
$category = trim($_GET["category"] ?? "");
$search = trim($_GET["search"] ?? "");

try {
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

    $sql .= " ORDER BY p.rating DESC, p.place_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $places = $stmt->fetchAll();

    // Map types
    $formatted = array_map(function($p) {
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
            "distance_km" => (float)$p["distance_km"],
            "duration_visit" => $p["duration_visit"],
            "is_saved" => (bool)$p["is_saved"]
        ];
    }, $places);

    echo json_encode([
        "success" => true,
        "places" => $formatted
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
