<?php
// api/auth.php — Helpers per autenticazione
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function jsonOk(array $data): void {
    echo json_encode(['ok' => true, ...$data]);
    exit;
}
function jsonErr(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
function getToken(): ?string {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (str_starts_with($h, 'Bearer ')) return substr($h, 7);
    return null;
}
function requireAuth(): array {
    $token = getToken();
    if (!$token) jsonErr('Non autenticato', 401);
    $pdo = getDB();
    $st = $pdo->prepare("SELECT u.* FROM users u JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at > NOW()");
    $st->execute([$token]);
    $user = $st->fetch();
    if (!$user) jsonErr('Sessione scaduta', 401);
    return $user;
}
function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
