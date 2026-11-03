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
$method_type = strtolower(trim($data["method_type"] ?? "card")); // card, upi, wallet
$raw_input = trim($data["title"] ?? ""); // e.g. card number or upi id
$subtitle = trim($data["subtitle"] ?? "");

if (!$user_id || $user_id < 1) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Valid user_id is required"]));
}

if ($raw_input === "") {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Payment details are required"]));
}

// Strict PCI-DSS security compliance: NEVER store raw full card number or CVV
$cleanTitle = "";
if ($method_type === "card") {
    // Extract last 4 digits
    $digits = preg_replace("/\D/", "", $raw_input);
    if (strlen($digits) < 4) {
        http_response_code(400);
        exit(json_encode(["success" => false, "message" => "Please enter a valid card number"]));
    }
    $last4 = substr($digits, -4);
    $cardBrand = "Card";
    if (str_starts_with($digits, "4")) $cardBrand = "Visa";
    else if (str_starts_with($digits, "5")) $cardBrand = "Mastercard";
    else if (str_starts_with($digits, "3")) $cardBrand = "Amex";
    else if (str_starts_with($digits, "6")) $cardBrand = "RuPay";

    $cleanTitle = "$cardBrand •••• $last4";
    if ($subtitle === "") $subtitle = "Secure Tokenized Card";
} else if ($method_type === "upi") {
    if (!str_contains($raw_input, "@")) {
        http_response_code(400);
        exit(json_encode(["success" => false, "message" => "Please enter a valid UPI ID (e.g. name@okhdfcbank)"]));
    }
    $cleanTitle = "UPI · $raw_input";
    if ($subtitle === "") $subtitle = "Instant Bank Payment";
} else {
    $cleanTitle = $raw_input;
    if ($subtitle === "") $subtitle = "Digital Wallet";
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO payment_methods (user_id, method_type, title, subtitle, is_default)
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([$user_id, $method_type, $cleanTitle, $subtitle]);

    echo json_encode([
        "success" => true,
        "message" => "Payment method added successfully",
        "payment_id" => (int)$pdo->lastInsertId(),
        "title" => $cleanTitle
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
