<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit(json_encode(["success" => false, "message" => "Use POST method"]));
}

$input = json_decode(file_get_contents("php://input"), true);
$data = is_array($input) ? $input : $_POST;

$journey_id = filter_var($data["journey_id"] ?? null, FILTER_VALIDATE_INT);
$user_id = filter_var($data["user_id"] ?? null, FILTER_VALIDATE_INT);
$status = strtolower(trim($data["status"] ?? "completed")); // "completed", "cancelled", "upcoming"

if (!$journey_id || $journey_id < 1 || !$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid journey_id and user_id are required"]));
}

$allowedStatuses = ["upcoming", "completed", "cancelled"];
if (!in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Invalid status. Use upcoming, completed, or cancelled"]));
}

try {
    $stmt = $pdo->prepare("UPDATE journeys SET status = ? WHERE journey_id = ? AND user_id = ?");
    $stmt->execute([$status, $journey_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Journey status updated to $status",
            "journey_id" => $journey_id,
            "status" => $status
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Journey status confirmed as $status",
            "journey_id" => $journey_id,
            "status" => $status
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
