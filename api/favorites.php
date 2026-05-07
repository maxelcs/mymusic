<?php
// api/favorites.php — Gestione preferiti
require_once __DIR__ . '/auth.php';

$user   = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // GET /api/favorites.php — lista preferiti
    $st = getDB()->prepare("SELECT yt_id,title,channel,thumb FROM favorites WHERE user_id=? ORDER BY added_at DESC");
    $st->execute([$user['id']]);
    jsonOk(['favorites' => $st->fetchAll()]);
}

if ($method === 'POST') {
    // POST /api/favorites.php — aggiungi/rimuovi (toggle)
    $b = body();
    $ytId   = trim($b['yt_id']   ?? '');
    $title  = trim($b['title']   ?? '');
    $channel= trim($b['channel'] ?? '');
    $thumb  = trim($b['thumb']   ?? '');

    if (!$ytId) jsonErr('yt_id richiesto');
    $pdo = getDB();
    $st = $pdo->prepare("SELECT id FROM favorites WHERE user_id=? AND yt_id=?");
    $st->execute([$user['id'], $ytId]);

    if ($st->fetch()) {
        $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND yt_id=?")->execute([$user['id'], $ytId]);
        jsonOk(['action' => 'removed']);
    } else {
        $pdo->prepare("INSERT INTO favorites (user_id,yt_id,title,channel,thumb) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $ytId, $title, $channel, $thumb]);
        jsonOk(['action' => 'added']);
    }
}

jsonErr('Metodo non valido', 405);
