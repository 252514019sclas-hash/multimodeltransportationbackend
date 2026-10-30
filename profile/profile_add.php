
<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit(json_encode(["success" => false, "message" => "Use POST method"]));
}

$user_id = filter_var($_POST["user_id"] ?? null, FILTER_VALIDATE_INT);
$gender = trim($_POST["gender"] ?? "");
$address = trim($_POST["address"] ?? "");
$bio = trim($_POST["bio"] ?? "");
$phone = trim($_POST["phone"] ?? "");

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

try {
    $check = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $check->execute([$user_id]);

    if (!$check->fetch()) {
        http_response_code(404);
        exit(json_encode(["success" => false, "message" => "User not found"]));
    }

    $check = $pdo->prepare("SELECT profile_id FROM profiles WHERE user_id = ?");
    $check->execute([$user_id]);

    if ($check->fetch()) {
        http_response_code(409);
        exit(json_encode(["success" => false, "message" => "Profile already exists. Use profile_edit.php"]));
    }

    $stmt = $pdo->prepare(
        "INSERT INTO profiles (user_id, gender, address, bio)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([
        $user_id,
        $gender ?: null,
        $address ?: null,
        $bio ?: null
    ]);

    if ($phone !== "") {
        $update = $pdo->prepare("UPDATE users SET phone = ? WHERE user_id = ?");
        $update->execute([$phone, $user_id]);
    }

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Profile added successfully",
        "profile_id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error. Check your table columns."]);
}
?>
