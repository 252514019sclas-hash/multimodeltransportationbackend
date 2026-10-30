
<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$user_id = filter_var($_GET["user_id"] ?? null, FILTER_VALIDATE_INT);

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Provide a valid user_id"]));
}

try {
    $stmt = $pdo->prepare(
        "SELECT u.user_id, u.full_name, u.email, u.phone,
                p.profile_id, p.gender, p.address, p.bio,
                p.profile_image, p.created_at, p.updated_at
         FROM users u
         LEFT JOIN profiles p ON p.user_id = u.user_id
         WHERE u.user_id = ?"
    );

    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();

    if (!$profile) {
        http_response_code(404);
        exit(json_encode(["success" => false, "message" => "User not found"]));
    }

    echo json_encode([
        "success" => true,
        "message" => "Profile fetched successfully",
        "profile" => $profile
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
