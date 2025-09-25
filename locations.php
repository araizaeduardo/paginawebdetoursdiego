<?php
// Simple proxy endpoint to fetch airport/city suggestions from Amadeus Reference Data API
// Usage: GET /locations.php?q=bog

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// Load .env if present, to avoid exporting variables manually
function load_env($path)
{
    if (!is_file($path) || !is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(ltrim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        list($k, $v) = array_map('trim', explode('=', $line, 2));
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        putenv("$k=$v");
        $_ENV[$k] = $v;
    }
}

load_env(__DIR__ . '/.env');

define('AMADEUS_BASE_URL', getenv('AMADEUS_BASE_URL') ?: 'https://test.api.amadeus.com');

function amadeus_get_credentials() {
    // Prefer environment variables. If running via PHP Dev Server, export them before starting.
    $key = getenv('AMADEUS_API_KEY') ?: '';
    $secret = getenv('AMADEUS_API_SECRET') ?: '';
    return [$key, $secret];
}

function amadeus_get_token() {
    session_start();
    if (!empty($_SESSION['amadeus_token']) && !empty($_SESSION['amadeus_token_expires']) && time() < $_SESSION['amadeus_token_expires']) {
        return $_SESSION['amadeus_token'];
    }
    list($clientId, $clientSecret) = amadeus_get_credentials();
    if (empty($clientId) || empty($clientSecret)) {
        http_response_code(500);
        echo json_encode(['error' => 'Missing Amadeus credentials']);
        exit;
    }
    $url = AMADEUS_BASE_URL . '/v1/security/oauth2/token';
    $postFields = http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        http_response_code(502);
        echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
        curl_close($ch);
        exit;
    }
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $data = json_decode($resp, true);
    if ($status < 200 || $status >= 300 || empty($data['access_token'])) {
        http_response_code(502);
        echo json_encode(['error' => 'Auth failed', 'status' => $status, 'details' => $data]);
        exit;
    }
    $_SESSION['amadeus_token'] = $data['access_token'];
    $expiresIn = isset($data['expires_in']) ? (int)$data['expires_in'] : 1700;
    $_SESSION['amadeus_token_expires'] = time() + max(60, $expiresIn - 60);
    return $_SESSION['amadeus_token'];
}

function amadeus_search_locations($keyword) {
    $token = amadeus_get_token();
    $params = http_build_query([
        'keyword' => $keyword,
        'subType' => 'AIRPORT,CITY',
        'sort' => 'analytics.travelers.score',
        'view' => 'LIGHT',
        'page[limit]' => 10,
    ]);
    $url = AMADEUS_BASE_URL . '/v1/reference-data/locations?' . $params;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        http_response_code(502);
        echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
        curl_close($ch);
        exit;
    }
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $data = json_decode($resp, true);
    if ($status < 200 || $status >= 300) {
        http_response_code($status);
        echo json_encode(['error' => 'API error', 'status' => $status, 'details' => $data]);
        exit;
    }
    $out = [];
    foreach (($data['data'] ?? []) as $item) {
        $code = $item['iataCode'] ?? '';
        $name = $item['name'] ?? '';
        $country = $item['address']['countryCode'] ?? '';
        if (!$code) continue;
        $label = $name ? sprintf('%s (%s) - %s', $name, $code, $country) : $code;
        $out[] = ['code' => $code, 'label' => $label];
    }
    return $out;
}

try {
    $results = amadeus_search_locations($q);
    echo json_encode($results);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
