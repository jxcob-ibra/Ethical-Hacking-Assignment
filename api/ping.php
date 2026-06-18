<?php
require_once '../app/config/config.php';
require_once '../app/security/functions.php';

enforceApiTransportPolicy();

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'mode' => isVulnerabilityEnabled('http_api_communication') ? 'http' : 'https',
    'transport' => isRequestHttps() ? 'https' : 'http',
    'timestamp' => date('c')
]);
