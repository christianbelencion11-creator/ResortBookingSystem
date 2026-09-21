<?php
require_once __DIR__ . '/database.php';

function getResortSettings() {
    $filePath = ROOT_DIR . '/resort-settings.json';
    $defaultSettings = [
        'ResortName' => 'ResortBooking',
        'ResortSubtitle' => 'Management System',
        'LogoImage' => '/uploads/logo-20260821164822.jpg',
        'Address' => '123 Beach Road, Philippines',
        'Phone' => '(02) 8123-4567',
        'Email' => 'info@paradiseresort.com',
        'CheckInTime' => '14:00',
        'CheckOutTime' => '12:00',
        'DownpaymentPercent' => 50,
        'VatRate' => 12,
        'ServiceCharge' => 10
    ];

    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $json = json_decode($content, true);
        if (is_array($json)) {
            return array_merge($defaultSettings, $json);
        }
    }
    return $defaultSettings;
}

function saveResortSettings($newSettings) {
    $filePath = ROOT_DIR . '/resort-settings.json';
    $current = getResortSettings();
    $merged = array_merge($current, $newSettings);
    return file_put_contents($filePath, json_encode($merged, JSON_PRETTY_PRINT));
}
