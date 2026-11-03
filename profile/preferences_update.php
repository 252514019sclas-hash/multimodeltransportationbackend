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

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

$modes = isset($data["modes"]) ? trim((string)$data["modes"]) : "Walking, Cycling, Bus, Metro";
$low_carbon = isset($data["low_carbon"]) ? (int)(bool)$data["low_carbon"] : 1;
$travel_purpose = isset($data["travel_purpose"]) ? trim((string)$data["travel_purpose"]) : "Daily Commute";
$destination_types = isset($data["destination_types"]) ? trim((string)$data["destination_types"]) : "Parks, Museums";
$distance_preference = isset($data["distance_preference"]) ? trim((string)$data["distance_preference"]) : "Within City";
$tourist_recommendations = isset($data["tourist_recommendations"]) ? (int)(bool)$data["tourist_recommendations"] : 1;

$budget_tier = isset($data["budget_tier"]) ? trim((string)$data["budget_tier"]) : "Moderate";
$max_duration = isset($data["max_duration"]) ? trim((string)$data["max_duration"]) : "Under 45 min";

$notifications_enabled = isset($data["notifications_enabled"]) ? (int)(bool)$data["notifications_enabled"] : 1;
$journey_reminders = isset($data["journey_reminders"]) ? (int)(bool)$data["journey_reminders"] : 1;
$booking_updates = isset($data["booking_updates"]) ? (int)(bool)$data["booking_updates"] : 1;
$travel_alerts = isset($data["travel_alerts"]) ? (int)(bool)$data["travel_alerts"] : 1;

try {
    $userCheck = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $userCheck->execute([$user_id]);
    if (!$userCheck->fetch()) {
        http_response_code(404);
        exit(json_encode(["success" => false, "message" => "User not found"]));
    }

    $check = $pdo->prepare("SELECT profile_id FROM user_profiles WHERE user_id = ?");
    $check->execute([$user_id]);
    $existing = $check->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE user_profiles
            SET modes = ?,
                low_carbon = ?,
                travel_purpose = ?,
                destination_types = ?,
                distance_preference = ?,
                tourist_recommendations = ?,
                budget_tier = ?,
                max_duration = ?,
                notifications_enabled = ?,
                journey_reminders = ?,
                booking_updates = ?,
                travel_alerts = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $modes,
            $low_carbon,
            $travel_purpose,
            $destination_types,
            $distance_preference,
            $tourist_recommendations,
            $budget_tier,
            $max_duration,
            $notifications_enabled,
            $journey_reminders,
            $booking_updates,
            $travel_alerts,
            $user_id
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO user_profiles
            (user_id, modes, low_carbon, travel_purpose, destination_types, distance_preference, tourist_recommendations,
             budget_tier, max_duration, notifications_enabled, journey_reminders, booking_updates, travel_alerts)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $modes,
            $low_carbon,
            $travel_purpose,
            $destination_types,
            $distance_preference,
            $tourist_recommendations,
            $budget_tier,
            $max_duration,
            $notifications_enabled,
            $journey_reminders,
            $booking_updates,
            $travel_alerts
        ]);
    }

    echo json_encode([
        "success" => true,
        "message" => "Travel preferences saved successfully",
        "preferences" => [
            "modes" => $modes,
            "low_carbon" => (bool)$low_carbon,
            "travel_purpose" => $travel_purpose,
            "destination_types" => $destination_types,
            "distance_preference" => $distance_preference,
            "tourist_recommendations" => (bool)$tourist_recommendations,
            "budget_tier" => $budget_tier,
            "max_duration" => $max_duration
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
