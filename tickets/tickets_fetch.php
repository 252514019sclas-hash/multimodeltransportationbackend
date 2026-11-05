<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$userId = filter_var($_GET["user_id"] ?? ($_POST["user_id"] ?? null), FILTER_VALIDATE_INT);
$statusFilter = strtolower(trim($_GET["status"] ?? ($_POST["status"] ?? "all")));

if (!$userId) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "user_id is required."]));
}

try {
    // Check if the user exists
    $uStmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE user_id = ?");
    $uStmt->execute([$userId]);
    $userRow = $uStmt->fetch();
    $userName = $userRow ? $userRow["full_name"] : "MoveSmart Traveler";

    $sql = "
        SELECT 
            journey_id,
            user_id,
            origin,
            destination,
            mode,
            route_title,
            duration_minutes,
            distance_km,
            fare,
            transfers,
            co2_saved_kg,
            co2_emitted_kg,
            status,
            journey_date,
            booking_reference,
            payment_status,
            transaction_id,
            payment_date,
            verification_status,
            verified_at,
            qr_data,
            passenger_name,
            created_at
        FROM journeys
        WHERE user_id = ?
    ";
    $params = [$userId];

    if ($statusFilter !== "all" && !empty($statusFilter)) {
        $sql .= " AND status = ?";
        $params[] = $statusFilter;
    }

    $sql .= " ORDER BY journey_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $tickets = array_map(function($t) use ($userName) {
        $fareVal = (float)$t["fare"];
        $ref = $t["booking_reference"];
        $pStatus = strtolower($t["payment_status"] ?? "successful");
        $vStatus = strtolower($t["verification_status"] ?? "valid");
        $txnId = !empty($t["transaction_id"]) ? $t["transaction_id"] : ("TXN-" . $ref);
        $passName = !empty($t["passenger_name"]) ? $t["passenger_name"] : $userName;

        $qrPayload = !empty($t["qr_data"]) ? $t["qr_data"] : json_encode([
            "ticket_ref" => $ref,
            "journey_id" => (int)$t["journey_id"],
            "user_id" => (int)$t["user_id"],
            "origin" => $t["origin"],
            "destination" => $t["destination"],
            "mode" => $t["mode"],
            "status" => $t["status"]
        ]);

        return [
            "ticket_id" => (int)$t["journey_id"],
            "booking_reference" => $ref,
            "user_id" => (int)$t["user_id"],
            "passenger_name" => $passName,
            "origin" => $t["origin"],
            "destination" => $t["destination"],
            "mode" => $t["mode"],
            "route_title" => $t["route_title"],
            "duration_minutes" => (int)$t["duration_minutes"],
            "distance_km" => (float)$t["distance_km"],
            "fare" => $fareVal,
            "fare_text" => "₹" . number_format($fareVal, 0),
            "transfers" => (int)$t["transfers"],
            "status" => $t["status"],
            "journey_date" => $t["journey_date"],
            "payment_status" => $pStatus,
            "transaction_id" => $txnId,
            "payment_date" => $t["payment_date"] ?? $t["created_at"],
            "verification_status" => $vStatus,
            "verified_at" => $t["verified_at"],
            "qr_data" => $qrPayload,
            "created_at" => $t["created_at"]
        ];
    }, $rows);

    echo json_encode([
        "success" => true,
        "total" => count($tickets),
        "tickets" => $tickets
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>

