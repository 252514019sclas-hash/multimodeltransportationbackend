<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit(json_encode(["success" => false, "message" => "Use POST method"]));
}

$input = json_decode(file_get_contents("php://input"), true);
$data = is_array($input) ? $input : $_POST;

$user_id = filter_var($data["user_id"] ?? null, FILTER_VALIDATE_INT);
$origin = trim($data["origin"] ?? "");
$destination = trim($data["destination"] ?? "");
$mode = trim($data["mode"] ?? "Metro");
$duration_text = trim($data["duration_text"] ?? "");
$cost_text = trim($data["cost_text"] ?? "");
$co2_text = trim($data["co2_text"] ?? "");
$action = strtolower(trim($data["action"] ?? "save")); // "save" or "remove"
$route_id = filter_var($data["route_id"] ?? null, FILTER_VALIDATE_INT);

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

try {
    if ($action === "remove" && $route_id) {
        $del = $pdo->prepare("DELETE FROM saved_routes WHERE route_id = ? AND user_id = ?");
        $del->execute([$route_id, $user_id]);
        echo json_encode(["success" => true, "message" => "Route removed from saved list"]);
        exit;
    }

    if ($origin === "" || $destination === "") {
        http_response_code(400);
        exit(json_encode(["success" => false, "message" => "Origin and destination are required"]));
    }

    $stmt = $pdo->prepare("
        INSERT INTO saved_routes (user_id, origin, destination, mode, duration_text, cost_text, co2_text)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $origin,
        $destination,
        $mode,
        $duration_text ?: "35 min",
        $cost_text ?: "$3.00",
        $co2_text ?: "0.8 kg CO₂ saved"
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Route saved successfully",
        "route_id" => (int)$pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
