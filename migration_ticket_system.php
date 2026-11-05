<?php
header("Content-Type: application/json");
require_once __DIR__ . "/config/config.php";

// Safe column migrations on journeys table without touching existing data or tables
$columns = [
    "ALTER TABLE `journeys` ADD COLUMN IF NOT EXISTS `payment_status` ENUM('successful', 'pending', 'failed') NOT NULL DEFAULT 'successful'",
    "ALTER TABLE `journeys` ADD COLUMN IF NOT EXISTS `transaction_id` VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE `journeys` ADD COLUMN IF NOT EXISTS `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE `journeys` ADD COLUMN IF NOT EXISTS `verification_status` ENUM('valid', 'used', 'expired', 'cancelled') NOT NULL DEFAULT 'valid'",
    "ALTER TABLE `journeys` ADD COLUMN IF NOT EXISTS `verified_at` DATETIME DEFAULT NULL",
    "ALTER TABLE `journeys` ADD COLUMN IF NOT EXISTS `qr_data` TEXT DEFAULT NULL",
    "ALTER TABLE `journeys` ADD COLUMN IF NOT EXISTS `passenger_name` VARCHAR(100) DEFAULT NULL"
];

$results = [];
foreach ($columns as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ["sql" => $sql, "status" => "success"];
    } catch (PDOException $e) {
        $results[] = ["sql" => $sql, "status" => "error", "error" => $e->getMessage()];
    }
}

echo json_encode(["success" => true, "results" => $results]);
?>

