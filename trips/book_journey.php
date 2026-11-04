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
$origin = trim($data["origin"] ?? "Chennai Central");
$destination = trim($data["destination"] ?? "Marina Beach, Chennai");
$mode = trim($data["mode"] ?? "Metro");
$route_title = trim($data["route_title"] ?? "CMRL Blue Line Express");
$duration_minutes = filter_var($data["duration_minutes"] ?? 18, FILTER_VALIDATE_INT);
$distance_km = (float)($data["distance_km"] ?? 5.2);
$fare = (float)($data["fare"] ?? 30.00);
$transfers = filter_var($data["transfers"] ?? 0, FILTER_VALIDATE_INT);
$co2_saved_kg = (float)($data["co2_saved_kg"] ?? 1.4);
$co2_emitted_kg = (float)($data["co2_emitted_kg"] ?? 0.2);
$journey_date = trim($data["journey_date"] ?? "Today, " . date("g:i A"));

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

// Generate unique booking reference and transaction ID
$refCode = "TN-MS-" . strtoupper(bin2hex(random_bytes(3)));
$txnId = "TXN-" . strtoupper(bin2hex(random_bytes(4)));

// Lookup passenger name
$passenger_name = "MoveSmart Traveler";
try {
    $uStmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $uStmt->execute([$user_id]);
    $uRow = $uStmt->fetch();
    if ($uRow && !empty($uRow["full_name"])) {
        $passenger_name = $uRow["full_name"];
    }
} catch (Exception $e) {}

$qrPayload = json_encode([
    "ticket_ref" => $refCode,
    "user_id" => $user_id,
    "origin" => $origin,
    "destination" => $destination,
    "fare" => $fare,
    "issued_at" => time(),
    "signature" => hash("sha256", $refCode . $user_id . "MOVESMART_SECRET_KEY")
]);

try {
    $stmt = $pdo->prepare("
        INSERT INTO journeys
        (user_id, origin, destination, mode, route_title, duration_minutes, distance_km, fare, transfers, co2_saved_kg, co2_emitted_kg, status, journey_date, booking_reference, payment_status, transaction_id, verification_status, qr_data, passenger_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming', ?, ?, 'successful', ?, 'valid', ?, ?)
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
        $refCode,
        $txnId,
        $qrPayload,
        $passenger_name
    ]);

    $journeyId = (int)$pdo->lastInsertId();

    // Add journey reminder notification
    try {
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, action_data)
            VALUES (?, ?, ?, 'journey_reminder', 0, ?)
        ");
        $notifTitle = "Transit Pass Active · $refCode";
        $notifMsg = "Your $mode journey from $origin to $destination ($route_title) is confirmed. Present your QR code at gates.";
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
            "fare_text" => "₹" . number_format($fare, 0),
            "status" => "upcoming",
            "journey_date" => $journey_date,
            "payment_status" => "successful",
            "transaction_id" => $txnId,
            "passenger_name" => $passenger_name,
            "verification_status" => "valid",
            "qr_data" => $qrPayload
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
