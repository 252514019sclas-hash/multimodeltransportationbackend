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
        SELECT 
            notification_id,
            user_id,
            title,
            message,
            type,
            is_read,
            action_data,
            created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC, notification_id DESC
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    $formatted = array_map(function($n) {
        return [
            "notification_id" => (int)$n["notification_id"],
            "user_id" => (int)$n["user_id"],
            "title" => $n["title"],
            "message" => $n["message"],
            "type" => $n["type"],
            "is_read" => (bool)$n["is_read"],
            "action_data" => $n["action_data"],
            "created_at" => $n["created_at"]
        ];
    }, $notifications);

    $unreadCount = 0;
    foreach ($formatted as $n) {
        if (!$n["is_read"]) $unreadCount++;
    }

    echo json_encode([
        "success" => true,
        "unread_count" => $unreadCount,
        "notifications" => $formatted
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
