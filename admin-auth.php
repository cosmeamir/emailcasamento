<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=UTF-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim((string)($_GET['action'] ?? 'status'));

$adminUser = 'admin';
$adminPassword = 'joseth.2026';

if ($method === 'POST' && $action === 'login') {
    $payload = json_decode((string)file_get_contents('php://input'), true);
    $username = trim((string)($payload['username'] ?? ''));
    $password = trim((string)($payload['password'] ?? ''));

    if ($username === $adminUser && hash_equals($adminPassword, $password)) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_user'] = $adminUser;
        echo json_encode(['ok' => true, 'logged_in' => true]);
        exit;
    }

    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Credenciais inválidas.']);
    exit;
}

if ($method === 'POST' && $action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    echo json_encode(['ok' => true, 'logged_in' => false]);
    exit;
}

if ($method === 'GET' && $action === 'status') {
    $loggedIn = !empty($_SESSION['is_admin']);
    echo json_encode([
        'ok' => true,
        'logged_in' => $loggedIn,
        'user' => $loggedIn ? (string)($_SESSION['admin_user'] ?? '') : ''
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'message' => 'Método ou ação não suportado.']);
