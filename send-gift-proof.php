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
$smtpHost = 'smtp.josethemarco.com';
$smtpPort = 587;
$smtpTimeout = 12;

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
$messageId = sprintf(
    '<%s@%s>',
    bin2hex(random_bytes(8)),
    preg_replace('/[^a-zA-Z0-9.\-]/', '', (string)parse_url(('https://' . ($_SERVER['HTTP_HOST'] ?? 'josethemarco.com')), PHP_URL_HOST) ?: 'josethemarco.com')
);
$dateHeader = gmdate('D, d M Y H:i:s') . ' +0000';

$headers = [
    'Date: ' . $dateHeader,
    'Message-ID: ' . $messageId,
    'MIME-Version: 1.0',
    'From: Joseth e Marco <' . $mailboxEmail . '>',
    'To: ' . $recipient,
    'Reply-To: ' . $mailboxEmail,
    'X-Mailer: PHP/' . PHP_VERSION,
    'Content-Type: multipart/mixed; boundary="' . $boundary . '"'
];

$message = "--{$boundary}\r\n";
$message .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$message .= $bodyText . "\r\n";
$message .= "--{$boundary}\r\n";
$message .= "Content-Type: application/pdf; name=\"{$fileName}\"\r\n";
$message .= "Content-Transfer-Encoding: base64\r\n";
$message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n\r\n";
$message .= chunk_split(base64_encode($fileContent)) . "\r\n";
$message .= "--{$boundary}--\r\n";

$rawEmailData = implode("\r\n", [
    'From: Joseth e Marco <' . $mailboxEmail . '>',
    'To: ' . $recipient,
    'Subject: ' . $subject,
    implode("\r\n", $headers),
    '',
    $message
]);

/**
 * @return array{ok:bool,error?:string}
 */
function smtpSend(
    string $host,
    int $port,
    string $username,
    string $password,
    string $from,
    string $to,
    string $rawMessage,
    int $timeout
): array {
    $remote = sprintf('tcp://%s:%d', $host, $port);
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true
        ]
    ]);
    $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if ($socket === false) {
        return ['ok' => false, 'error' => 'Ligação SMTP falhou: ' . $errstr];
    }

    stream_set_timeout($socket, $timeout);

    $read = static function ($stream): string {
        $response = '';
        while (($line = fgets($stream, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^\d{3}\s/', $line) === 1) {
                break;
            }
        }
        return $response;
    };

    $write = static function ($stream, string $command): bool {
        return fwrite($stream, $command . "\r\n") !== false;
    };

    $expect = static function (string $response, array $acceptedCodes): bool {
        foreach ($acceptedCodes as $code) {
            if (strpos($response, (string)$code) === 0) return true;
        }
        return false;
    };

    $response = $read($socket);
    if (!$expect($response, [220])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP rejeitou conexão: ' . trim($response)];
    }

    $localHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $write($socket, 'EHLO ' . $localHost);
    $response = $read($socket);
    if (!$expect($response, [250])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'EHLO falhou: ' . trim($response)];
    }

    $write($socket, 'STARTTLS');
    $response = $read($socket);
    if (!$expect($response, [220])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'STARTTLS falhou: ' . trim($response)];
    }

    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        fclose($socket);
        return ['ok' => false, 'error' => 'Não foi possível ativar TLS.'];
    }

    $write($socket, 'EHLO ' . $localHost);
    $response = $read($socket);
    if (!$expect($response, [250])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'EHLO após TLS falhou: ' . trim($response)];
    }

    $write($socket, 'AUTH LOGIN');
    $response = $read($socket);
    if (!$expect($response, [334])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'AUTH LOGIN falhou: ' . trim($response)];
    }

    $write($socket, base64_encode($username));
    $response = $read($socket);
    if (!$expect($response, [334])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP username rejeitado: ' . trim($response)];
    }

    $write($socket, base64_encode($password));
    $response = $read($socket);
    if (!$expect($response, [235])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP password rejeitada: ' . trim($response)];
    }

    $write($socket, 'MAIL FROM:<' . $from . '>');
    $response = $read($socket);
    if (!$expect($response, [250])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'MAIL FROM falhou: ' . trim($response)];
    }

    $write($socket, 'RCPT TO:<' . $to . '>');
    $response = $read($socket);
    if (!$expect($response, [250, 251])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'RCPT TO falhou: ' . trim($response)];
    }

    $write($socket, 'DATA');
    $response = $read($socket);
    if (!$expect($response, [354])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'DATA falhou: ' . trim($response)];
    }

    $messageData = preg_replace("/(?m)^\./", '..', $rawMessage);
    fwrite($socket, $messageData . "\r\n.\r\n");
    $response = $read($socket);
    if (!$expect($response, [250])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'Envio DATA falhou: ' . trim($response)];
    }

    $write($socket, 'QUIT');
    fclose($socket);
    return ['ok' => true];
}

$smtpResult = smtpSend($smtpHost, $smtpPort, $mailboxEmail, $mailboxPassword, $mailboxEmail, $recipient, $rawEmailData, $smtpTimeout);
$sent = $smtpResult['ok'] ?? false;

if (!$sent) {
    // fallback para mail() caso SMTP falhe no servidor atual
    ini_set('sendmail_from', $mailboxEmail);
    $sent = mail($recipient, $subject, $message, implode("\r\n", $headers), '-f' . $mailboxEmail);
}

if (!$sent) {
    error_log('Gift proof mail failure: ' . ($smtpResult['error'] ?? 'SMTP e fallback mail() falharam.'));
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Não foi possível enviar o email neste momento. Verifique a configuração SMTP do domínio.'
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Enviado com sucesso.'
]);
