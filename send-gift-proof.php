<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Método não permitido.'
    ]);
    exit;
}

$senderName = trim((string)($_POST['sender_name'] ?? ''));
$productName = trim((string)($_POST['product_name'] ?? ''));
$productPrice = trim((string)($_POST['product_price'] ?? ''));
$productReference = trim((string)($_POST['product_reference'] ?? ''));

if ($senderName === '' || $productName === '' || $productPrice === '' || $productReference === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Dados obrigatórios em falta.'
    ]);
    exit;
}

if (!isset($_FILES['gift_proof']) || !is_array($_FILES['gift_proof'])) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Comprovativo não recebido.'
    ]);
    exit;
}

$proof = $_FILES['gift_proof'];
if (($proof['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Erro no upload do comprovativo.'
    ]);
    exit;
}

$tmpPath = (string)$proof['tmp_name'];
$fileSize = (int)$proof['size'];
$originalFileName = basename((string)$proof['name']);

if ($fileSize <= 0 || $fileSize > 1024 * 1024) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'O comprovativo deve ter no máximo 1MB.'
    ]);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath) ?: '';
if ($mimeType !== 'application/pdf') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Apenas ficheiros PDF são permitidos.'
    ]);
    exit;
}

$uploadDir = __DIR__ . '/uploads/gift-proofs';
$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/gift-submissions.json';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Não foi possível preparar a pasta de uploads.']);
    exit;
}

if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Não foi possível preparar a pasta de dados.']);
    exit;
}

$safeBaseName = preg_replace('/[^a-zA-Z0-9._-]/', '-', pathinfo($originalFileName, PATHINFO_FILENAME)) ?: 'comprovativo';
$storedFileName = sprintf('%s-%s.pdf', date('YmdHis'), bin2hex(random_bytes(4)) . '-' . $safeBaseName);
$storedPath = $uploadDir . '/' . $storedFileName;

if (!move_uploaded_file($tmpPath, $storedPath)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Não foi possível guardar o comprovativo.'
    ]);
    exit;
}

$submission = [
    'id' => bin2hex(random_bytes(8)),
    'submitted_at' => gmdate('c'),
    'sender_name' => preg_replace('/[\r\n]+/', ' ', $senderName),
    'product_name' => $productName,
    'product_price' => $productPrice,
    'product_reference' => $productReference,
    'proof_original_name' => $originalFileName,
    'proof_stored_name' => $storedFileName,
    'proof_url' => 'uploads/gift-proofs/' . rawurlencode($storedFileName),
    'proof_size' => $fileSize
];

$submissions = [];
if (is_file($dataFile)) {
    $existingJson = file_get_contents($dataFile);
    if ($existingJson !== false) {
        $decoded = json_decode($existingJson, true);
        if (is_array($decoded)) {
            $submissions = $decoded;
        }
    }
}

array_unshift($submissions, $submission);

if (file_put_contents($dataFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Não foi possível guardar os dados do presente.'
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Enviado com sucesso.'
]);
