<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['is_admin'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Não autenticado.']);
    exit;
}

$dataDir = __DIR__ . '/data';
$submissionsFile = $dataDir . '/gift-submissions.json';
$statusFile = $dataDir . '/gift-status.json';

if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Falha ao preparar pasta de dados.']);
    exit;
}

function readJsonArrayFile(string $filePath): array
{
    if (!is_file($filePath)) {
        return [];
    }

    $raw = file_get_contents($filePath);
    if ($raw === false) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function saveJsonFile(string $filePath, array $data): bool
{
    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

function normalizeBlockedReferences(array $input): array
{
    $set = [];
    foreach ($input as $reference) {
        $value = trim((string)$reference);
        if ($value !== '') {
            $set[$value] = true;
        }
    }

    return array_values(array_keys($set));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $submissions = readJsonArrayFile($submissionsFile);
    $status = readJsonArrayFile($statusFile);
    $blocked = normalizeBlockedReferences((array)($status['blocked_references'] ?? []));

    echo json_encode([
        'ok' => true,
        'items' => $submissions,
        'blocked_references' => $blocked
    ]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método não permitido.']);
    exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
$action = trim((string)($payload['action'] ?? ''));

if ($action === 'reactivate_reference' || $action === 'deactivate_reference') {
    $reference = trim((string)($payload['reference'] ?? ''));
    if ($reference === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Referência inválida.']);
        exit;
    }

    $status = readJsonArrayFile($statusFile);
    $blocked = normalizeBlockedReferences((array)($status['blocked_references'] ?? []));
    $set = [];
    foreach ($blocked as $blockedReference) {
        $set[$blockedReference] = true;
    }

    if ($action === 'reactivate_reference') {
        unset($set[$reference]);
    } else {
        $set[$reference] = true;
    }

    $status['blocked_references'] = array_values(array_keys($set));
    if (!saveJsonFile($statusFile, $status)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Falha ao atualizar estado do presente.']);
        exit;
    }

    echo json_encode(['ok' => true, 'blocked_references' => $status['blocked_references']]);
    exit;
}

if ($action === 'delete_submission') {
    $id = trim((string)($payload['id'] ?? ''));
    if ($id === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'ID inválido.']);
        exit;
    }

    $submissions = readJsonArrayFile($submissionsFile);
    $found = false;
    $updated = [];

    foreach ($submissions as $item) {
        $itemId = trim((string)($item['id'] ?? ''));
        if ($itemId !== '' && hash_equals($itemId, $id)) {
            $found = true;
            continue;
        }
        $updated[] = $item;
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Registo não encontrado.']);
        exit;
    }

    if (!saveJsonFile($submissionsFile, $updated)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Falha ao apagar registo.']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(422);
echo json_encode(['ok' => false, 'message' => 'Ação inválida.']);
