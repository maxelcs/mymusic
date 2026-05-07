<?php
// api/user.php — Registrazione, login, logout, profilo
require_once __DIR__ . '/auth.php';

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    // POST /api/user.php?action=register
    $b = body();
    $username = trim($b['username'] ?? '');
    $email    = trim($b['email'] ?? '');
    $password = $b['password'] ?? '';

    if (strlen($username) < 3)  jsonErr('Username troppo corto (min 3)');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonErr('Email non valida');
    if (strlen($password) < 6)  jsonErr('Password troppo corta (min 6)');

    $pdo = getDB();
    $st = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=?");
    $st->execute([$username, $email]);
    if ($st->fetch()) jsonErr('Username o email già in uso');

    $hash  = password_hash($password, PASSWORD_DEFAULT);
    $letter = strtoupper($username[0]);
    $ins = $pdo->prepare("INSERT INTO users (username,email,password_hash,avatar_letter) VALUES (?,?,?,?)");
    $ins->execute([$username, $email, $hash, $letter]);
    $uid = $pdo->lastInsertId();

    $token = bin2hex(random_bytes(32));
    $exp   = date('Y-m-d H:i:s', strtotime('+30 days'));
    $pdo->prepare("INSERT INTO sessions (token,user_id,expires_at) VALUES (?,?,?)")->execute([$token,$uid,$exp]);

    jsonOk(['token' => $token, 'user' => ['id'=>$uid,'username'=>$username,'avatar_letter'=>$letter]]);
}

if ($action === 'login') {
    // POST /api/user.php?action=login
    $b = body();
    $login    = trim($b['login'] ?? '');    // username o email
    $password = $b['password'] ?? '';

    $pdo = getDB();
    $st = $pdo->prepare("SELECT * FROM users WHERE username=? OR email=?");
    $st->execute([$login, $login]);
    $user = $st->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonErr('Credenziali non valide');
    }

    // rimuovi sessioni scadute
    $pdo->prepare("DELETE FROM sessions WHERE user_id=? AND expires_at < NOW()")->execute([$user['id']]);

    $token = bin2hex(random_bytes(32));
    $exp   = date('Y-m-d H:i:s', strtotime('+30 days'));
    $pdo->prepare("INSERT INTO sessions (token,user_id,expires_at) VALUES (?,?,?)")->execute([$token,$user['id'],$exp]);

    jsonOk(['token' => $token, 'user' => ['id'=>$user['id'],'username'=>$user['username'],'avatar_letter'=>$user['avatar_letter']]]);
}

if ($action === 'logout') {
    // POST /api/user.php?action=logout
    $token = getToken();
    if ($token) getDB()->prepare("DELETE FROM sessions WHERE token=?")->execute([$token]);
    jsonOk(['message' => 'Disconnesso']);
}

if ($action === 'me') {
    // GET /api/user.php?action=me
    $user = requireAuth();
    jsonOk(['user' => ['id'=>$user['id'],'username'=>$user['username'],'avatar_letter'=>$user['avatar_letter']]]);
}

jsonErr('Azione non valida');
