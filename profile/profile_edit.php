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
$full_name = trim($data["full_name"] ?? "");
$phone = trim($data["phone"] ?? "");
$gender = trim($data["gender"] ?? "");
$address = trim($data["address"] ?? "");
$bio = trim($data["bio"] ?? "");

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

try {
    $userCheck = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $userCheck->execute([$user_id]);
    if (!$userCheck->fetch()) {
        http_response_code(404);
        exit(json_encode(["success" => false, "message" => "User not found"]));
    }

    // Update users table (full_name and/or phone)
    $userUpdates = [];
    $userParams = [];
    if ($full_name !== "") {
        $userUpdates[] = "full_name = ?";
        $userParams[] = $full_name;
    }
    if (isset($data["phone"])) {
        $userUpdates[] = "phone = ?";
        $userParams[] = $phone !== "" ? $phone : null;
    }
    if (!empty($userUpdates)) {
        $userParams[] = $user_id;
        $updateUserStmt = $pdo->prepare("UPDATE users SET " . implode(", ", $userUpdates) . " WHERE user_id = ?");
        $updateUserStmt->execute($userParams);
    }

    // Check if profiles row exists (Upsert)
    $profileCheck = $pdo->prepare("SELECT profile_id FROM profiles WHERE user_id = ?");
    $profileCheck->execute([$user_id]);
    $existingProfile = $profileCheck->fetch();

    if ($existingProfile) {
        $stmt = $pdo->prepare(
            "UPDATE profiles SET gender = ?, address = ?, bio = ? WHERE user_id = ?"
        );
        $stmt->execute([
            $gender ?: null,
            $address ?: null,
            $bio ?: null,
            $user_id
        ]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO profiles (user_id, gender, address, bio) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $user_id,
            $gender ?: null,
            $address ?: null,
            $bio ?: null
        ]);
    }

    echo json_encode([
        "success" => true,
        "message" => "Profile updated successfully"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
