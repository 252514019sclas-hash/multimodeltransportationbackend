<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$place_id = filter_var($_GET["place_id"] ?? null, FILTER_VALIDATE_INT);
$user_id = filter_var($_GET["user_id"] ?? 0, FILTER_VALIDATE_INT);

if (!$place_id || $place_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid place_id is required"]));
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            CASE WHEN s.saved_id IS NOT NULL THEN 1 ELSE 0 END AS is_saved
        FROM tourist_places p
        LEFT JOIN saved_places s ON p.place_id = s.place_id AND s.user_id = ?
        WHERE p.place_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $place_id]);
    $place = $stmt->fetch();

    if (!$place) {
        http_response_code(404);
        exit(json_encode(["success" => false, "message" => "Place not found"]));
    }

    $place["place_id"] = (int)$place["place_id"];
    $place["rating"] = (float)$place["rating"];
    $place["distance_km"] = (float)$place["distance_km"];
    $place["is_saved"] = (bool)$place["is_saved"];

    echo json_encode([
        "success" => true,
        "place" => $place
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>

