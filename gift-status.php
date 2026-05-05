<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método não permitido.']);
    exit;
}

$dataFile = __DIR__ . '/data/gift-status.json';
if (!is_file($dataFile)) {
    echo json_encode(['ok' => true, 'blocked_references' => []]);
    exit;
}

$content = file_get_contents($dataFile);
if ($content === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Falha ao ler o estado dos presentes.']);
    exit;
}

$decoded = json_decode($content, true);
if (!is_array($decoded)) {
    echo json_encode(['ok' => true, 'blocked_references' => []]);
    exit;
}

$blocked = $decoded['blocked_references'] ?? [];
if (!is_array($blocked)) {
    $blocked = [];
}

$normalized = [];
foreach ($blocked as $reference) {
    $value = trim((string)$reference);
    if ($value !== '') {
        $normalized[$value] = true;
    }
}

echo json_encode(['ok' => true, 'blocked_references' => array_values(array_keys($normalized))]);
