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
        SELECT payment_id, user_id, method_type, title, subtitle, is_default, created_at
        FROM payment_methods
        WHERE user_id = ?
        ORDER BY is_default DESC, payment_id ASC
    ");
    $stmt->execute([$user_id]);
    $methods = $stmt->fetchAll();

    $formatted = array_map(function($m) {
        return [
            "payment_id" => (int)$m["payment_id"],
            "method_type" => $m["method_type"],
            "title" => $m["title"],
            "subtitle" => $m["subtitle"],
            "is_default" => (bool)$m["is_default"],
            "created_at" => $m["created_at"]
        ];
    }, $methods);

    echo json_encode([
        "success" => true,
        "payment_methods" => $formatted
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
