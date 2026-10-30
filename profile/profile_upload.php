<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit(json_encode(["success" => false, "message" => "Use POST method"]));
}

$user_id = filter_var($_POST["user_id"] ?? null, FILTER_VALIDATE_INT);

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

if (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Select an image file"]));
}

$file = $_FILES["image"];

if ($file["size"] > 5 * 1024 * 1024) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Maximum image size is 5 MB"]));
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file["tmp_name"]);

$allowed = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

if (!isset($allowed[$mime])) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Only JPG, PNG and WEBP images are allowed"]));
}

try {
    $userCheck = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $userCheck->execute([$user_id]);
    if (!$userCheck->fetch()) {
        http_response_code(404);
        exit(json_encode(["success" => false, "message" => "User not found"]));
    }

    $uploadDir = dirname(__DIR__) . "/upload/profile/";

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException("Cannot create upload directory");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        throw new RuntimeException("Could not save image");
    }

    $relativePath = "upload/profile/" . $filename;

    $profileCheck = $pdo->prepare("SELECT profile_id FROM profiles WHERE user_id = ?");
    $profileCheck->execute([$user_id]);
    $existing = $profileCheck->fetch();

    if ($existing) {
        $stmt = $pdo->prepare(
            "UPDATE profiles SET profile_image = ? WHERE user_id = ?"
        );
        $stmt->execute([$relativePath, $user_id]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO profiles (user_id, profile_image) VALUES (?, ?)"
        );
        $stmt->execute([$user_id, $relativePath]);
    }

    echo json_encode([
        "success" => true,
        "message" => "Profile image uploaded successfully",
        "profile_image" => $relativePath
    ]);

} catch (Throwable $e) {
    if (isset($destination) && is_file($destination)) {
        unlink($destination);
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Image upload failed: " . $e->getMessage()]);
}
?>
