<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$user_id = filter_var($_GET["user_id"] ?? null, FILTER_VALIDATE_INT);
$status = strtolower(trim($_GET["status"] ?? "all"));

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
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
            duration_minutes,
            distance_km,
            fare,
            transfers,
            co2_saved_kg,
            co2_emitted_kg,
            status,
            journey_date,
            booking_reference,
            created_at
        FROM journeys
        WHERE user_id = ?
    ";
    $params = [$user_id];

    if ($status === "upcoming" || $status === "completed" || $status === "cancelled") {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY journey_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $trips = $stmt->fetchAll();

    $formatted = array_map(function($t) {
        return [
            "journey_id" => (int)$t["journey_id"],
            "user_id" => (int)$t["user_id"],
            "origin" => $t["origin"],
            "destination" => $t["destination"],
            "mode" => $t["mode"],
            "route_title" => $t["route_title"],
            "duration_minutes" => (int)$t["duration_minutes"],
            "distance_km" => (float)$t["distance_km"],
            "fare" => (float)$t["fare"],
            "fare_text" => "$" . number_format((float)$t["fare"], 2),
            "transfers" => (int)$t["transfers"],
            "co2_saved_kg" => (float)$t["co2_saved_kg"],
            "co2_emitted_kg" => (float)$t["co2_emitted_kg"],
            "status" => $t["status"],
            "journey_date" => $t["journey_date"],
            "booking_reference" => $t["booking_reference"],
            "created_at" => $t["created_at"]
        ];
    }, $trips);

    echo json_encode([
        "success" => true,
        "trips" => $formatted
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
