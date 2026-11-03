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
$origin = trim($data["origin"] ?? "Ferry Building");
$destination = trim($data["destination"] ?? "California Academy of Sciences");
$mode = trim($data["mode"] ?? "Metro");
$route_title = trim($data["route_title"] ?? "N Judah Line");
$duration_minutes = filter_var($data["duration_minutes"] ?? 42, FILTER_VALIDATE_INT);
$distance_km = (float)($data["distance_km"] ?? 6.8);
$fare = (float)($data["fare"] ?? 3.00);
$transfers = filter_var($data["transfers"] ?? 0, FILTER_VALIDATE_INT);
$co2_saved_kg = (float)($data["co2_saved_kg"] ?? 0.92);
$co2_emitted_kg = (float)($data["co2_emitted_kg"] ?? 0.24);
$journey_date = trim($data["journey_date"] ?? "Today, " . date("g:i A"));

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

// Generate unique booking reference MS-XXXXX
$refCode = "MS-" . strtoupper(bin2hex(random_bytes(3)));

try {
    $stmt = $pdo->prepare("
        INSERT INTO journeys
        (user_id, origin, destination, mode, route_title, duration_minutes, distance_km, fare, transfers, co2_saved_kg, co2_emitted_kg, status, journey_date, booking_reference)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming', ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $origin,
        $destination,
        $mode,
        $route_title,
        $duration_minutes,
        $distance_km,
        $fare,
        $transfers,
        $co2_saved_kg,
        $co2_emitted_kg,
        $journey_date,
        $refCode
    ]);

    $journeyId = (int)$pdo->lastInsertId();

    // Add journey reminder notification
    try {
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, action_data)
            VALUES (?, ?, ?, 'journey_reminder', 0, ?)
        ");
        $notifTitle = "Journey Confirmed · $refCode";
        $notifMsg = "Your trip from $origin to $destination via $route_title is ready. Ticket code: $refCode.";
        $notifStmt->execute([$user_id, $notifTitle, $notifMsg, $refCode]);
    } catch (Exception $ex) {}

    echo json_encode([
        "success" => true,
        "message" => "Journey booked successfully",
        "journey" => [
            "journey_id" => $journeyId,
            "booking_reference" => $refCode,
            "origin" => $origin,
            "destination" => $destination,
            "mode" => $mode,
            "route_title" => $route_title,
            "duration_minutes" => $duration_minutes,
            "fare" => $fare,
            "fare_text" => "$" . number_format($fare, 2),
            "status" => "upcoming",
            "journey_date" => $journey_date
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>

