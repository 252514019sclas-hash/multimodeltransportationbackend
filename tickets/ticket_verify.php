<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$input = json_decode(file_get_contents("php://input"), true);
$data = is_array($input) ? $input : array_merge($_GET, $_POST);

$rawCode = trim($data["qr_data"] ?? ($data["ticket_code"] ?? ($data["booking_reference"] ?? "")));
$markUsed = filter_var($data["mark_used"] ?? false, FILTER_VALIDATE_BOOLEAN);

if (empty($rawCode)) {
    http_response_code(400);
    exit(json_encode([
        "success" => false,
        "status" => "error",
        "message" => "No QR code or ticket reference was provided."
    ]));
}

// Support raw JSON QR codes or plain text references
$lookupRef = $rawCode;
$decoded = json_decode($rawCode, true);
if (is_array($decoded) && !empty($decoded["ticket_ref"])) {
    $lookupRef = trim($decoded["ticket_ref"]);
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            journey_id,
            user_id,
            origin,
            destination,
            mode,
            route_title,
            duration_minutes,
            fare,
            status,
            journey_date,
            booking_reference,
            payment_status,
            transaction_id,
            payment_date,
            verification_status,
            verified_at,
            passenger_name,
            created_at
        FROM journeys
        WHERE booking_reference = ? OR journey_id = ?
        LIMIT 1
    ");
    $journeyIdParam = is_numeric($lookupRef) ? (int)$lookupRef : -1;
    $stmt->execute([$lookupRef, $journeyIdParam]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        http_response_code(404);
        exit(json_encode([
            "success" => false,
            "status" => "invalid",
            "message" => "Invalid Ticket: No matching ticket record exists on the MoveSmart transit network.",
            "ticket" => null
        ]));
    }

    $fareVal = (float)$ticket["fare"];
    $bookingRef = $ticket["booking_reference"];
    $currentStatus = strtolower($ticket["status"]);
    $currentVerification = strtolower($ticket["verification_status"] ?? "valid");
    $passenger = !empty($ticket["passenger_name"]) ? $ticket["passenger_name"] : "MoveSmart Traveler";

    $ticketSummary = [
        "ticket_id" => (int)$ticket["journey_id"],
        "booking_reference" => $bookingRef,
        "passenger_name" => $passenger,
        "origin" => $ticket["origin"],
        "destination" => $ticket["destination"],
        "mode" => $ticket["mode"],
        "route_title" => $ticket["route_title"],
        "duration_minutes" => (int)$ticket["duration_minutes"],
        "fare" => $fareVal,
        "fare_text" => "₹" . number_format($fareVal, 0),
        "status" => $currentStatus,
        "journey_date" => $ticket["journey_date"],
        "payment_status" => $ticket["payment_status"] ?? "successful",
        "verification_status" => $currentVerification,
        "verified_at" => $ticket["verified_at"]
    ];

    // Status Check 1: Cancelled
    if ($currentStatus === "cancelled") {
        echo json_encode([
            "success" => false,
            "status" => "cancelled",
            "message" => "Ticket Cancelled: This booking was cancelled and is no longer valid for boarding.",
            "ticket" => $ticketSummary
        ]);
        exit;
    }

    // Status Check 2: Already Used
    if ($currentVerification === "used" || $currentStatus === "completed") {
        echo json_encode([
            "success" => false,
            "status" => "used",
            "message" => "Already Used Ticket: This digital pass has already been scanned and verified.",
            "ticket" => $ticketSummary
        ]);
        exit;
    }

    // Status Check 3: Expired
    if ($currentVerification === "expired") {
        echo json_encode([
            "success" => false,
            "status" => "expired",
            "message" => "Expired Ticket: The validity period for this travel ticket has lapsed.",
            "ticket" => $ticketSummary
        ]);
        exit;
    }

    // Status Check 4: Payment clearance
    if (strtolower($ticket["payment_status"] ?? "") !== "successful") {
        echo json_encode([
            "success" => false,
            "status" => "invalid",
            "message" => "Payment Not Confirmed: Payment status is " . ($ticket["payment_status"] ?? "pending") . ".",
            "ticket" => $ticketSummary
        ]);
        exit;
    }

    // Status Check 5: Valid Ticket
    if ($markUsed) {
        $updateStmt = $pdo->prepare("
            UPDATE journeys 
            SET verification_status = 'used', status = 'completed', verified_at = NOW() 
            WHERE journey_id = ?
        ");
        $updateStmt->execute([$ticket["journey_id"]]);
        $ticketSummary["verification_status"] = "used";
        $ticketSummary["status"] = "completed";
        $ticketSummary["verified_at"] = date("Y-m-d H:i:s");
    }

    echo json_encode([
        "success" => true,
        "status" => "valid",
        "message" => "Valid Ticket: Verified by MoveSmart Transit Server. Clearance granted for " . $ticket["origin"] . " ➔ " . $ticket["destination"] . ".",
        "ticket" => $ticketSummary
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
