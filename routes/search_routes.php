<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/config.php";

$input = json_decode(file_get_contents("php://input"), true);
$data = is_array($input) ? $input : $_POST;

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
        "title" => "Urban Walking Corridor",
        "duration_minutes" => (int)round($baseDistanceKm * 12.0),
        "duration_text" => round($baseDistanceKm * 12.0) . " min",
        "distance_km" => $baseDistanceKm,
        "distance_text" => $baseDistanceKm . " km",
        "fare" => 0.00,
        "fare_text" => "Free",
        "transfers" => 0,
        "co2_emitted_kg" => 0.00,
        "co2_saved_kg" => $carEmissionKg,
        "steps" => ["Pedestrian sidewalk", "Protected crosswalks", "Direct access"],
        "route_details" => "Zero carbon · Healthy urban walking path · Best for short trips",
        "badge" => "100% GREEN",
        "is_recommended" => false
    ],
    [
        "id" => "route_metro",
        "mode" => "Metro",
        "title" => "N Judah Line",
        "duration_minutes" => (int)round(20 + $baseDistanceKm * 3.2),
        "duration_text" => round(20 + $baseDistanceKm * 3.2) . " min",
        "distance_km" => $baseDistanceKm,
        "distance_text" => $baseDistanceKm . " km",
        "fare" => 3.00,
        "fare_text" => "$3.00",
        "transfers" => 0,
        "co2_emitted_kg" => round($baseDistanceKm * 0.035, 2),
        "co2_saved_kg" => round($baseDistanceKm * 0.135, 2),
        "steps" => ["Walk 5 min", "Muni N Judah", "Walk 4 min"],
        "route_details" => "Direct rapid metro transit · Frequent service every 8 mins",
        "badge" => "LOW CARBON CHOICE",
        "is_recommended" => true
    ],
    [
        "id" => "route_bus",
        "mode" => "Bus",
        "title" => "Express Bus · Route 5",
        "duration_minutes" => (int)round(25 + $baseDistanceKm * 3.6),
        "duration_text" => round(25 + $baseDistanceKm * 3.6) . " min",
        "distance_km" => round($baseDistanceKm * 1.05, 1),
        "distance_text" => round($baseDistanceKm * 1.05, 1) . " km",
        "fare" => 2.50,
        "fare_text" => "$2.50",
        "transfers" => 0,
        "co2_emitted_kg" => round($baseDistanceKm * 0.068, 2),
        "co2_saved_kg" => round($baseDistanceKm * 0.102, 2),
        "steps" => ["Walk 6 min", "City Bus 5", "Walk 5 min"],
        "route_details" => "Direct urban line · Free onboard Wi-Fi · Dedicated bus lane",
        "badge" => "BUDGET FRIENDLY",
        "is_recommended" => false
    ],
    [
        "id" => "route_bicycle",
        "mode" => "Cycling",
        "title" => "Protected Bikeway / Bike Share",
        "duration_minutes" => (int)round(10 + $baseDistanceKm * 4.0),
        "duration_text" => round(10 + $baseDistanceKm * 4.0) . " min",
        "distance_km" => round($baseDistanceKm * 0.95, 1),
        "distance_text" => round($baseDistanceKm * 0.95, 1) . " km",
        "fare" => 4.50,
        "fare_text" => "$4.50",
        "transfers" => 0,
        "co2_emitted_kg" => 0.00,
        "co2_saved_kg" => $carEmissionKg,
        "steps" => ["Dock at Station A", "Scenic Cycleway", "Dock at Station B"],
        "route_details" => "Zero emissions · Scenic route through urban green corridors",
        "badge" => "ZERO EMISSIONS",
        "is_recommended" => false
    ],
    [
        "id" => "route_train",
        "mode" => "Train",
        "title" => "Regional Commuter Rail",
        "duration_minutes" => (int)round(18 + $baseDistanceKm * 2.5),
        "duration_text" => round(18 + $baseDistanceKm * 2.5) . " min",
        "distance_km" => round($baseDistanceKm * 1.15, 1),
        "distance_text" => round($baseDistanceKm * 1.15, 1) . " km",
        "fare" => 6.00,
        "fare_text" => "$6.00",
        "transfers" => 1,
        "co2_emitted_kg" => round($baseDistanceKm * 0.040, 2),
        "co2_saved_kg" => round($baseDistanceKm * 0.130, 2),
        "steps" => ["Walk 7 min", "Commuter Express Line", "Short Transfer"],
        "route_details" => "High speed regional connection · Air-conditioned cars",
        "badge" => "FASTEST TRANSIT",
        "is_recommended" => false
    ],
    [
        "id" => "route_taxi",
        "mode" => "Taxi",
        "title" => "Electric / Hybrid Rideshare",
        "duration_minutes" => (int)round(8 + $baseDistanceKm * 2.2),
        "duration_text" => round(8 + $baseDistanceKm * 2.2) . " min",
        "distance_km" => $baseDistanceKm,
        "distance_text" => $baseDistanceKm . " km",
        "fare" => round(8.00 + $baseDistanceKm * 2.20, 2),
        "fare_text" => "$" . number_format(8.00 + $baseDistanceKm * 2.20, 2),
        "transfers" => 0,
        "co2_emitted_kg" => round($baseDistanceKm * 0.120, 2),
        "co2_saved_kg" => round($baseDistanceKm * 0.050, 2),
        "steps" => ["Door-to-door pickup", "City direct route"],
        "route_details" => "Point-to-point service · Pick up in 3 minutes",
        "badge" => "FASTEST DOOR-TO-DOOR",
        "is_recommended" => false
    ],
    [
        "id" => "route_car",
        "mode" => "Car",
        "title" => "Private Vehicle Drive",
        "duration_minutes" => (int)round(10 + $baseDistanceKm * 2.4),
        "duration_text" => round(10 + $baseDistanceKm * 2.4) . " min",
        "distance_km" => $baseDistanceKm,
        "distance_text" => $baseDistanceKm . " km",
        "fare" => round(3.50 + $baseDistanceKm * 0.80, 2),
        "fare_text" => "~$" . number_format(3.50 + $baseDistanceKm * 0.80, 2) . " (fuel)",
        "transfers" => 0,
        "co2_emitted_kg" => $carEmissionKg,
        "co2_saved_kg" => 0.00,
        "steps" => ["Main expressway", "Street parking destination"],
        "route_details" => "Private car route · Standard traffic conditions",
        "badge" => "CONVENTIONAL",
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

$aiInsight = "Personalized for {$travelPurpose}: {$chosenMode} matches your preferred modes and avoids up to {$carEmissionKg} kg of CO₂.";

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
