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

$mailboxEmail = 'presentes@josethemarco.com';
$mailboxPassword = 'JosethMarco@2026';
$recipient = $mailboxEmail;

// A password é mantida aqui para configurações SMTP externas, quando aplicável.
// Neste endpoint é usado `mail()`, que depende do servidor de email já configurado no hosting.
if ($mailboxPassword === '') {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Configuração de email inválida.'
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
$fileName = basename((string)$proof['name']);

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

$safeSender = preg_replace('/[\r\n]+/', ' ', $senderName) ?: 'Convidado';
$subject = 'Comprovativo de Presente - ' . $productName;

$bodyText =
    "Foi submetido um novo comprovativo de presente.\n\n" .
    "Oferecedor: {$safeSender}\n" .
    "Produto: {$productName}\n" .
    "Valor: {$productPrice}\n" .
    "Referência: Presente de Casamento {$productReference}\n" .
    "Ficheiro: {$fileName}\n";

$fileContent = file_get_contents($tmpPath);
if ($fileContent === false) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Não foi possível ler o comprovativo.'
    ]);
    exit;
}

$boundary = '=_GiftProof_' . bin2hex(random_bytes(12));
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'From: Joseth e Marco <' . $mailboxEmail . '>';
$headers[] = 'Reply-To: ' . $mailboxEmail;
$headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
ini_set('sendmail_from', $mailboxEmail);

$message = "--{$boundary}\r\n";
$message .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$message .= $bodyText . "\r\n";

$message .= "--{$boundary}\r\n";
$message .= "Content-Type: application/pdf; name=\"{$fileName}\"\r\n";
$message .= "Content-Transfer-Encoding: base64\r\n";
$message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n\r\n";
$message .= chunk_split(base64_encode($fileContent)) . "\r\n";
$message .= "--{$boundary}--";

$sent = mail($recipient, $subject, $message, implode("\r\n", $headers), '-f' . $mailboxEmail);

if (!$sent) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Não foi possível enviar o email neste momento.'
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Enviado com sucesso.'
]);
