<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$user_id = filter_var($_GET["user_id"] ?? null, FILTER_VALIDATE_INT);

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

try {
    $stmt = $pdo->prepare("
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
            s.created_at AS saved_at,
            1 AS is_saved
        FROM saved_places s
        JOIN tourist_places p ON s.place_id = p.place_id
        WHERE s.user_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $places = $stmt->fetchAll();

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
            "saved_at" => $p["saved_at"],
            "is_saved" => true
        ];
    }, $places);

    echo json_encode([
        "success" => true,
        "saved_places" => $formatted
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>

