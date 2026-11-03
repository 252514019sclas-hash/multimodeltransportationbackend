<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$user_id = filter_var($_GET["user_id"] ?? null, FILTER_VALIDATE_INT);

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

try {
    // 1. Calculate real CO2 savings and travel metrics from completed journeys
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(co2_saved_kg), 0) AS total_co2_saved,
            COALESCE(SUM(distance_km), 0) AS total_distance_km,
            COALESCE(SUM(fare), 0) AS total_spent,
            COUNT(*) AS completed_count
        FROM journeys 
        WHERE user_id = ? AND status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $metrics = $stmt->fetch();

    $totalCo2 = round((float)$metrics["total_co2_saved"], 1);
    $totalDistance = round((float)$metrics["total_distance_km"], 1);
    $completedCount = (int)$metrics["completed_count"];

    // 2. Count upcoming trips
    $upcomingStmt = $pdo->prepare("SELECT COUNT(*) FROM journeys WHERE user_id = ? AND status = 'upcoming'");
    $upcomingStmt->execute([$user_id]);
    $upcomingCount = (int)$upcomingStmt->fetchColumn();

    // 3. Count saved places
    $savedPlacesStmt = $pdo->prepare("SELECT COUNT(*) FROM saved_places WHERE user_id = ?");
    $savedPlacesStmt->execute([$user_id]);
    $savedPlacesCount = (int)$savedPlacesStmt->fetchColumn();

    // 4. Count saved routes
    $savedRoutesStmt = $pdo->prepare("SELECT COUNT(*) FROM saved_routes WHERE user_id = ?");
    $savedRoutesStmt->execute([$user_id]);
    $savedRoutesCount = (int)$savedRoutesStmt->fetchColumn();

    // 5. Get default payment method
    $payStmt = $pdo->prepare("SELECT title FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC, payment_id ASC LIMIT 1");
    $payStmt->execute([$user_id]);
    $paymentMethod = $payStmt->fetchColumn() ?: "None added";

    // 6. Get travel preferences summary string
    $prefStmt = $pdo->prepare("SELECT modes, low_carbon, notifications_enabled FROM user_profiles WHERE user_id = ? LIMIT 1");
    $prefStmt->execute([$user_id]);
    $pref = $prefStmt->fetch();

    $prefSummary = "Low-carbon · Walk, cycle, bus & metro";
    $notificationsText = "On";
    if ($pref) {
        $prefix = ($pref["low_carbon"] ? "Low-carbon · " : "");
        $prefSummary = $prefix . ($pref["modes"] ?: "Walk, cycle, bus & metro");
        $notificationsText = $pref["notifications_enabled"] ? "On" : "Off";
    }

    $co2ImpactText = number_format($totalCo2, 1) . " kg CO₂ avoided";
    $co2Subtitle = $completedCount > 0 
        ? "From {$completedCount} completed journeys · Keep moving smarter" 
        : "Complete your first journey to start saving carbon";

    echo json_encode([
        "success" => true,
        "stats" => [
            "co2_impact" => $co2ImpactText,
            "co2_subtitle" => $co2Subtitle,
            "total_co2_kg" => $totalCo2,
            "total_distance_km" => $totalDistance,
            "completed_trips" => $completedCount,
            "upcoming_trips" => $upcomingCount,
            "saved_places_count" => $savedPlacesCount . " " . ($savedPlacesCount === 1 ? "place" : "places"),
            "saved_routes_count" => $savedRoutesCount,
            "payment_method" => $paymentMethod,
            "travel_preferences_summary" => $prefSummary,
            "notifications_text" => $notificationsText
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
