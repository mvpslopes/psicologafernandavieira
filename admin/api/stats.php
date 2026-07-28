<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/ga.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Não autenticado']);
    exit;
}

$config = require __DIR__ . '/../includes/config.php';
$period = $_GET['period'] ?? '30d';
$allowed = ['today', '7d', '30d', '90d', 'all'];
if (!in_array($period, $allowed, true)) {
    $period = '30d';
}

try {
    $data = fv_fetch_dashboard($config, $period);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'hint' => 'Confira se a service account tem acesso de Leitor na propriedade GA4 e se o arquivo service-account.json está em admin/data/.',
    ], JSON_UNESCAPED_UNICODE);
}
