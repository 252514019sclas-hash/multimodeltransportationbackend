<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$input = json_decode(file_get_contents("php://input"), true);
$data = is_array($input) ? $input : array_merge($_GET, $_POST);

$ref = trim($data["booking_reference"] ?? ($data["reference"] ?? ($data["ticket_code"] ?? "")));
$journey_id = filter_var($data["journey_id"] ?? null, FILTER_VALIDATE_INT);
$txn_id = trim($data["transaction_id"] ?? "");

if (empty($ref) && !$journey_id && empty($txn_id)) {
    http_response_code(400);
    exit(json_encode([
        "success" => false,
        "message" => "booking_reference, journey_id, or transaction_id is required"
    ]));
}

try {
    $sql = "
        SELECT 
            journey_id,
            user_id,
            origin,
            destination,
            mode,
            route_title,
            fare,
            booking_reference,
            payment_status,
            transaction_id,
            payment_date,
            passenger_name,
            status,
            created_at
        FROM journeys
        WHERE 1=1
    ";
    $params = [];

    if (!empty($ref)) {
        $sql .= " AND booking_reference = ?";
        $params[] = $ref;
    } elseif ($journey_id) {
        $sql .= " AND journey_id = ?";
        $params[] = $journey_id;
    } elseif (!empty($txn_id)) {
        $sql .= " AND transaction_id = ?";
        $params[] = $txn_id;
    }

    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        exit(json_encode([
            "success" => false,
            "payment_status" => "failed",
            "message" => "Transaction not found. No payment record matches the provided details."
        ]));
    }

    $fareVal = (float)$row["fare"];
    $pStatus = strtolower($row["payment_status"] ?? "successful");

    echo json_encode([
        "success" => true,
        "payment_status" => $pStatus,
        "transaction_id" => $row["transaction_id"] ?? ("TXN-" . $row["booking_reference"]),
        "booking_reference" => $row["booking_reference"],
        "journey_id" => (int)$row["journey_id"],
        "passenger_name" => $row["passenger_name"] ?? "MoveSmart Traveler",
        "amount" => $fareVal,
        "amount_text" => "₹" . number_format($fareVal, 0),
        "payment_date" => $row["payment_date"] ?? $row["created_at"],
        "route_title" => $row["route_title"],
        "origin" => $row["origin"],
        "destination" => $row["destination"],
        "mode" => $row["mode"],
        "gateway_provider" => "MoveSmart Secure Transit Gateway",
        "message" => $pStatus === "successful" 
            ? "Payment verified by MoveSmart transit server. Transaction confirmed."
            : ($pStatus === "pending" ? "Payment is pending clearance." : "Payment failed or was refunded.")
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>

