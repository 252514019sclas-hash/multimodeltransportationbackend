<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit(json_encode(["success" => false, "message" => "Use POST method"]));
}

$input = json_decode(file_get_contents("php://input"), true);
$data = is_array($input) ? $input : $_POST;

$payment_id = filter_var($data["payment_id"] ?? null, FILTER_VALIDATE_INT);
$user_id = filter_var($data["user_id"] ?? null, FILTER_VALIDATE_INT);

if (!$payment_id || $payment_id < 1 || !$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid payment_id and user_id are required"]));
}

try {
    $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE payment_id = ? AND user_id = ?");
    $stmt->execute([$payment_id, $user_id]);

    echo json_encode([
        "success" => true,
        "message" => "Payment method removed successfully"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>

