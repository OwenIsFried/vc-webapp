<?php

function sanitizeCity($city) {
    $city = preg_replace("/[^a-zA-Z\s\-]/", "", $city);
    $city = trim($city);
    if ($city === "") {
        $city = "New York";
    }
    return $city;
}

function sanitizeUnit($unit) {
    return ($unit === 'metric') ? 'metric' : 'imperial';
}

function safeFileGetContents($url) {
    $parsed = parse_url($url);
    if (!isset($parsed['host']) || $parsed['host'] !== 'api.openweathermap.org') {
        return false;
    }
    return @file_get_contents($url);
}

function safeFloat($value) {
    return isset($value) ? floatval($value) : 0;
}

function safeInt($value) {
    return isset($value) ? intval($value) : 0;
}

function safeString($value) {
    return isset($value) ? htmlspecialchars($value) : '';
}
