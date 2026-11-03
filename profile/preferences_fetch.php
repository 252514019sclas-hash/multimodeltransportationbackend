<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$user_id = filter_var($_GET["user_id"] ?? null, FILTER_VALIDATE_INT);

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

try {
    // Check if user exists
    $userCheck = $pdo->prepare("SELECT user_id, full_name, email FROM users WHERE user_id = ?");
    $userCheck->execute([$user_id]);
    $user = $userCheck->fetch();

    if (!$user) {
        http_response_code(404);
        exit(json_encode(["success" => false, "message" => "User not found"]));
    }

    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $pref = $stmt->fetch();

    if (!$pref) {
        // Return default preferences
        $pref = [
            "user_id" => $user_id,
            "modes" => "Walking, Cycling, Bus, Metro",
            "low_carbon" => 1,
            "travel_purpose" => "Daily Commute",
            "destination_types" => "Parks, Museums",
            "distance_preference" => "Within City",
            "tourist_recommendations" => 1,
            "budget_tier" => "Moderate",
            "max_duration" => "Under 45 min",
            "notifications_enabled" => 1,
            "journey_reminders" => 1,
            "booking_updates" => 1,
            "travel_alerts" => 1
        ];
    }

    echo json_encode([
        "success" => true,
        "preferences" => [
            "modes" => $pref["modes"] ?? "Walking, Cycling, Bus, Metro",
            "low_carbon" => (bool)($pref["low_carbon"] ?? 1),
            "travel_purpose" => $pref["travel_purpose"] ?? "Daily Commute",
            "destination_types" => $pref["destination_types"] ?? "Parks, Museums",
            "distance_preference" => $pref["distance_preference"] ?? "Within City",
            "tourist_recommendations" => (bool)($pref["tourist_recommendations"] ?? 1),
            "budget_tier" => $pref["budget_tier"] ?? "Moderate",
            "max_duration" => $pref["max_duration"] ?? "Under 45 min",
            "notifications_enabled" => (bool)($pref["notifications_enabled"] ?? 1),
            "journey_reminders" => (bool)($pref["journey_reminders"] ?? 1),
            "booking_updates" => (bool)($pref["booking_updates"] ?? 1),
            "travel_alerts" => (bool)($pref["travel_alerts"] ?? 1)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
