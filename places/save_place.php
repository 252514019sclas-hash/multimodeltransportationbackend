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
$place_id = filter_var($data["place_id"] ?? null, FILTER_VALIDATE_INT);
$action = strtolower(trim($data["action"] ?? "toggle")); // "save", "remove", "toggle"

if (!$user_id || $user_id < 1 || !$place_id || $place_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id and place_id are required"]));
}

try {
    // Check current state
    $stmt = $pdo->prepare("SELECT saved_id FROM saved_places WHERE user_id = ? AND place_id = ?");
    $stmt->execute([$user_id, $place_id]);
    $saved = $stmt->fetch();

    $isSaved = false;

    if ($action === "remove" || ($action === "toggle" && $saved)) {
        if ($saved) {
            $delStmt = $pdo->prepare("DELETE FROM saved_places WHERE user_id = ? AND place_id = ?");
            $delStmt->execute([$user_id, $place_id]);
        }
        $isSaved = false;
        $message = "Place removed from saved places";
    } else {
        if (!$saved) {
            $insStmt = $pdo->prepare("INSERT INTO saved_places (user_id, place_id) VALUES (?, ?)");
            $insStmt->execute([$user_id, $place_id]);
        }
        $isSaved = true;
        $message = "Place added to saved places";
    }

    echo json_encode([
        "success" => true,
        "is_saved" => $isSaved,
        "message" => $message
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
