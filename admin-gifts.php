<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método não permitido.']);
    exit;
}

if (empty($_SESSION['is_admin'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Não autenticado.']);
    exit;
}

$dataFile = __DIR__ . '/data/gift-submissions.json';
if (!is_file($dataFile)) {
    echo json_encode(['ok' => true, 'items' => []]);
    exit;
}

$content = file_get_contents($dataFile);
if ($content === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Falha ao ler os dados.']);
    exit;
}

$items = json_decode($content, true);
if (!is_array($items)) {
    echo json_encode(['ok' => true, 'items' => []]);
    exit;
}

echo json_encode(['ok' => true, 'items' => $items]);
