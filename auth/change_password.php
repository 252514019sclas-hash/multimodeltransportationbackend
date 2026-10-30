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
$current_password = $data["current_password"] ?? "";
$new_password = $data["new_password"] ?? "";

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

if ($current_password === "" || $new_password === "") {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Current password and new password are required"]));
}

if (strlen($new_password) < 8) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "New password must be at least 8 characters long"]));
}

try {
    $stmt = $pdo->prepare("SELECT user_id, password FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        exit(json_encode(["success" => false, "message" => "User not found"]));
    }

    if (!password_verify($current_password, $user["password"])) {
        http_response_code(400);
        exit(json_encode(["success" => false, "message" => "Current password is incorrect"]));
    }

    $hashedNewPassword = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $updateStmt->execute([$hashedNewPassword, $user_id]);

    echo json_encode([
        "success" => true,
        "message" => "Password changed successfully"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>

