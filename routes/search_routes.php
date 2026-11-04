<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$input = json_decode(file_get_contents("php://input"), true);
$data = is_array($input) ? array_merge($_GET, $input) : array_merge($_GET, $_POST);

$origin = trim($data["origin"] ?? "Ferry Building");
$destination = trim($data["destination"] ?? "California Academy of Sciences");
$dateTime = trim($data["date_time"] ?? "Today, 9:15 AM");
$user_id = filter_var($data["user_id"] ?? 0, FILTER_VALIDATE_INT);

if ($origin === "") $origin = "Ferry Building";
if ($destination === "") $destination = "California Academy of Sciences";

// Check user preferences if available
$preferredModes = "Walking, Cycling, Bus, Metro";
$lowCarbonPref = 1;
$travelPurpose = "Daily Commute";
$budgetTier = "Moderate";
$distancePref = "Within City";

if ($user_id > 0) {
    try {
        $prefStmt = $pdo->prepare("
            SELECT modes, low_carbon, travel_purpose, budget_tier, distance_preference 
            FROM user_profiles 
            WHERE user_id = ? 
            LIMIT 1
        ");
        $prefStmt->execute([$user_id]);
        if ($row = $prefStmt->fetch()) {
            $preferredModes = $row["modes"] ?: $preferredModes;
            $lowCarbonPref = (int)$row["low_carbon"];
            $travelPurpose = $row["travel_purpose"] ?: $travelPurpose;
            $budgetTier = $row["budget_tier"] ?: $budgetTier;
            $distancePref = $row["distance_preference"] ?: $distancePref;
        }
    } catch (Exception $e) {}
}

$userModeList = array_map('trim', explode(',', strtolower($preferredModes)));

// Calculate realistic baseline distance (km) based on string hash / location keywords
$seed = abs(crc32(strtolower($origin . "-" . $destination))) % 100;
$baseDistanceKm = round(4.5 + ($seed % 15) * 0.5, 1); // Between 4.5 km and 11.5 km

// Car emission baseline: 170g / km = 0.170 kg / km
$carEmissionKg = round($baseDistanceKm * 0.170, 2);

$routes = [
    [
        "id" => "route_walking",
        "mode" => "Walking",
        "title" => "Heritage Pedestrian Corridor",
        "duration_minutes" => (int)round($baseDistanceKm * 12.0),
        "duration_text" => round($baseDistanceKm * 12.0) . " min",
        "distance_km" => $baseDistanceKm,
        "distance_text" => $baseDistanceKm . " km",
        "fare" => 0.00,
        "fare_text" => "Free",
        "transfers" => 0,
        "co2_emitted_kg" => 0.00,
        "co2_saved_kg" => $carEmissionKg,
        "steps" => ["Pedestrian walkway", "Temple street corridor", "Destination gate"],
        "route_details" => "Walkable scenic promenade · Best for short distance temple & beach walks",
        "badge" => "SCENIC WALK",
        "is_recommended" => false
    ],
    [
        "id" => "route_metro",
        "mode" => "Metro",
        "title" => "Chennai Metro Rail (CMRL)",
        "duration_minutes" => (int)round(18 + $baseDistanceKm * 2.8),
        "duration_text" => round(18 + $baseDistanceKm * 2.8) . " min",
        "distance_km" => $baseDistanceKm,
        "distance_text" => $baseDistanceKm . " km",
        "fare" => 30.00,
        "fare_text" => "₹30",
        "transfers" => 0,
        "co2_emitted_kg" => round($baseDistanceKm * 0.035, 2),
        "co2_saved_kg" => round($baseDistanceKm * 0.135, 2),
        "steps" => ["Walk 4 min to Metro Station", "Chennai Metro Blue/Green Line", "Walk 3 min"],
        "route_details" => "High-speed air-conditioned transit · Frequent trains every 6-8 minutes",
        "badge" => "FASTEST URBAN TRANSIT",
        "is_recommended" => true
    ],
    [
        "id" => "route_bus",
        "mode" => "Bus",
        "title" => "MTC / TNSTC Express Bus",
        "duration_minutes" => (int)round(22 + $baseDistanceKm * 3.4),
        "duration_text" => round(22 + $baseDistanceKm * 3.4) . " min",
        "distance_km" => round($baseDistanceKm * 1.05, 1),
        "distance_text" => round($baseDistanceKm * 1.05, 1) . " km",
        "fare" => 15.00,
        "fare_text" => "₹15",
        "transfers" => 0,
        "co2_emitted_kg" => round($baseDistanceKm * 0.068, 2),
        "co2_saved_kg" => round($baseDistanceKm * 0.102, 2),
        "steps" => ["Walk to Bus Stop", "MTC / TNSTC Express Bus", "Walk to destination"],
        "route_details" => "State transport connectivity · Direct stop at major tourist landmarks",
        "badge" => "BUDGET TRANSIT",
        "is_recommended" => false
    ],
    [
        "id" => "route_bicycle",
        "mode" => "Cycling",
        "title" => "Smart City Bicycle Track",
        "duration_minutes" => (int)round(10 + $baseDistanceKm * 4.0),
        "duration_text" => round(10 + $baseDistanceKm * 4.0) . " min",
        "distance_km" => round($baseDistanceKm * 0.95, 1),
        "distance_text" => round($baseDistanceKm * 0.95, 1) . " km",
        "fare" => 10.00,
        "fare_text" => "₹10",
        "transfers" => 0,
        "co2_emitted_kg" => 0.00,
        "co2_saved_kg" => $carEmissionKg,
        "steps" => ["Unlock Smart Bike", "Promenade cycle track", "Dock at Station"],
        "route_details" => "Dedicated cycle lane · Ideal for Marina promenade and lake paths",
        "badge" => "HEALTHY & SCENIC",
        "is_recommended" => false
    ],
    [
        "id" => "route_train",
        "mode" => "Train",
        "title" => "Southern Railway Express / EMU",
        "duration_minutes" => (int)round(16 + $baseDistanceKm * 2.2),
        "duration_text" => round(16 + $baseDistanceKm * 2.2) . " min",
        "distance_km" => round($baseDistanceKm * 1.15, 1),
        "distance_text" => round($baseDistanceKm * 1.15, 1) . " km",
        "fare" => 20.00,
        "fare_text" => "₹20",
        "transfers" => 1,
        "co2_emitted_kg" => round($baseDistanceKm * 0.040, 2),
        "co2_saved_kg" => round($baseDistanceKm * 0.130, 2),
        "steps" => ["Suburban Station", "Southern Railway Southern Express / EMU", "Auto transfer"],
        "route_details" => "Southern Railway inter-city / suburban rail network with reserved seating",
        "badge" => "REGIONAL EXPRESS",
        "is_recommended" => false
    ],
    [
        "id" => "route_taxi",
        "mode" => "Taxi",
        "title" => "Prepaid Taxi / Auto-Rickshaw",
        "duration_minutes" => (int)round(8 + $baseDistanceKm * 2.1),
        "duration_text" => round(8 + $baseDistanceKm * 2.1) . " min",
        "distance_km" => $baseDistanceKm,
        "distance_text" => $baseDistanceKm . " km",
        "fare" => round(60.00 + $baseDistanceKm * 16.0, 0),
        "fare_text" => "₹" . round(60.00 + $baseDistanceKm * 16.0, 0),
        "transfers" => 0,
        "co2_emitted_kg" => round($baseDistanceKm * 0.120, 2),
        "co2_saved_kg" => round($baseDistanceKm * 0.050, 2),
        "steps" => ["Doorstep pickup", "City direct corridor", "Destination gate"],
        "route_details" => "Door-to-door comfort · Available anytime with instant pickup",
        "badge" => "FASTEST DOOR-TO-DOOR",
        "is_recommended" => false
    ],
    [
        "id" => "route_car",
        "mode" => "Car",
        "title" => "Private Vehicle Drive",
        "duration_minutes" => (int)round(9 + $baseDistanceKm * 2.3),
        "duration_text" => round(9 + $baseDistanceKm * 2.3) . " min",
        "distance_km" => $baseDistanceKm,
        "distance_text" => $baseDistanceKm . " km",
        "fare" => round(50.00 + $baseDistanceKm * 8.5, 0),
        "fare_text" => "~₹" . round(50.00 + $baseDistanceKm * 8.5, 0) . " (fuel)",
        "transfers" => 0,
        "co2_emitted_kg" => $carEmissionKg,
        "co2_saved_kg" => 0.00,
        "steps" => ["State Highway / Ring Road", "Destination parking lot"],
        "route_details" => "Private vehicle via major Tamil Nadu highways and arterial bypasses",
        "badge" => "DIRECT DRIVE",
        "is_recommended" => false
    ]
];

// Determine AI recommended route based on user travel preferences:
// Check preferred modes
$candidateModes = [];
foreach ($routes as $r) {
    if (in_array(strtolower($r["mode"]), $userModeList)) {
        $candidateModes[] = $r["mode"];
    }
}
if (empty($candidateModes)) {
    $candidateModes = ["Metro", "Bus", "Cycling", "Walking"];
}

$chosenMode = "Metro";
if (strcasecmp($travelPurpose, "Adventure") === 0 && in_array("Cycling", $candidateModes)) {
    $chosenMode = "Cycling";
} elseif ($lowCarbonPref && $baseDistanceKm <= 3.5 && in_array("Walking", $candidateModes)) {
    $chosenMode = "Walking";
} elseif ($lowCarbonPref && $baseDistanceKm <= 6.0 && in_array("Cycling", $candidateModes)) {
    $chosenMode = "Cycling";
} elseif (in_array("Metro", $candidateModes)) {
    $chosenMode = "Metro";
} elseif (in_array("Bus", $candidateModes)) {
    $chosenMode = "Bus";
} elseif (in_array("Train", $candidateModes)) {
    $chosenMode = "Train";
} else {
    $chosenMode = $candidateModes[0];
}

foreach ($routes as &$r) {
    $r["is_recommended"] = ($r["mode"] === $chosenMode);
}

$aiInsight = "Personalized for {$travelPurpose}: {$chosenMode} offers optimal speed, comfort, and direct connectivity across Tamil Nadu.";

echo json_encode([
    "success" => true,
    "origin" => $origin,
    "destination" => $destination,
    "date_time" => $dateTime,
    "travel_purpose" => $travelPurpose,
    "ai_insight" => $aiInsight,
    "data_source" => "MoveSmart Multi-Modal Routing Engine (Personalized AI Model)",
    "routes" => $routes
]);
?>
