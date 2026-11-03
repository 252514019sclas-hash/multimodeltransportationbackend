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
        SELECT route_id, user_id, origin, destination, mode, duration_text, cost_text, co2_text, created_at
        FROM saved_routes
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $routes = $stmt->fetchAll();

    $formatted = array_map(function($r) {
        return [
            "route_id" => (int)$r["route_id"],
            "origin" => $r["origin"],
            "destination" => $r["destination"],
            "mode" => $r["mode"],
            "duration_text" => $r["duration_text"],
            "cost_text" => $r["cost_text"],
            "co2_text" => $r["co2_text"],
            "created_at" => $r["created_at"]
        ];
    }, $routes);

    echo json_encode([
        "success" => true,
        "saved_routes" => $formatted
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
