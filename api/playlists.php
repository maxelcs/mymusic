<?php
// api/playlists.php — Gestione playlist
require_once __DIR__ . '/auth.php';

$user   = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── Lista playlist utente ──────────────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $pdo = getDB();
    $st = $pdo->prepare("
        SELECT p.id, p.name, p.description, p.cover_thumb, p.created_at,
               COUNT(ps.id) AS song_count
        FROM playlists p
        LEFT JOIN playlist_songs ps ON ps.playlist_id = p.id
        WHERE p.user_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $st->execute([$user['id']]);
    jsonOk(['playlists' => $st->fetchAll()]);
}

// ── Brani di una playlist ─────────────────────────────────────────────────
if ($method === 'GET' && $action === 'songs') {
    $pid = (int)($_GET['id'] ?? 0);
    if (!$pid) jsonErr('id richiesto');
    $pdo = getDB();
    // verifica ownership
    $own = $pdo->prepare("SELECT id FROM playlists WHERE id=? AND user_id=?");
    $own->execute([$pid, $user['id']]);
    if (!$own->fetch()) jsonErr('Playlist non trovata', 404);

    $st = $pdo->prepare("SELECT yt_id,title,channel,thumb FROM playlist_songs WHERE playlist_id=? ORDER BY position ASC, added_at ASC");
    $st->execute([$pid]);
    jsonOk(['songs' => $st->fetchAll()]);
}

// ── Crea playlist ──────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'create') {
    $b    = body();
    $name = trim($b['name'] ?? '');
    $desc = trim($b['description'] ?? '');
    if (strlen($name) < 1) jsonErr('Nome richiesto');

    $pdo = getDB();
    $st  = $pdo->prepare("INSERT INTO playlists (user_id,name,description) VALUES (?,?,?)");
    $st->execute([$user['id'], $name, $desc]);
    jsonOk(['id' => (int)$pdo->lastInsertId(), 'name' => $name]);
}

// ── Rinomina / aggiorna playlist ───────────────────────────────────────────
if ($method === 'POST' && $action === 'update') {
    $b    = body();
    $pid  = (int)($b['id'] ?? 0);
    $name = trim($b['name'] ?? '');
    $desc = trim($b['description'] ?? '');
    if (!$pid || !$name) jsonErr('id e name richiesti');

    $pdo = getDB();
    $st  = $pdo->prepare("UPDATE playlists SET name=?, description=? WHERE id=? AND user_id=?");
    $st->execute([$name, $desc, $pid, $user['id']]);
    jsonOk(['updated' => (bool)$st->rowCount()]);
}

// ── Elimina playlist ───────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    $b   = body();
    $pid = (int)($b['id'] ?? 0);
    if (!$pid) jsonErr('id richiesto');

    getDB()->prepare("DELETE FROM playlists WHERE id=? AND user_id=?")->execute([$pid, $user['id']]);
    jsonOk(['deleted' => true]);
}

// ── Aggiungi brano a playlist ──────────────────────────────────────────────
if ($method === 'POST' && $action === 'add_song') {
    $b       = body();
    $pid     = (int)($b['playlist_id'] ?? 0);
    $ytId    = trim($b['yt_id']   ?? '');
    $title   = trim($b['title']   ?? '');
    $channel = trim($b['channel'] ?? '');
    $thumb   = trim($b['thumb']   ?? '');

    if (!$pid || !$ytId) jsonErr('playlist_id e yt_id richiesti');

    $pdo = getDB();
    $own = $pdo->prepare("SELECT id FROM playlists WHERE id=? AND user_id=?");
    $own->execute([$pid, $user['id']]);
    if (!$own->fetch()) jsonErr('Playlist non trovata', 404);

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM playlist_songs WHERE playlist_id=?");
    $cnt->execute([$pid]);
    $pos = (int)$cnt->fetchColumn();

    try {
        $pdo->prepare("INSERT INTO playlist_songs (playlist_id,yt_id,title,channel,thumb,position) VALUES (?,?,?,?,?,?)")
            ->execute([$pid, $ytId, $title, $channel, $thumb, $pos]);

        // aggiorna cover se è il primo brano
        $pdo->prepare("UPDATE playlists SET cover_thumb=CASE WHEN cover_thumb='' THEN ? ELSE cover_thumb END WHERE id=?")
            ->execute([$thumb, $pid]);

        jsonOk(['added' => true]);
    } catch (PDOException $e) {
        jsonErr('Brano già presente in questa playlist');
    }
}

// ── Rimuovi brano da playlist ──────────────────────────────────────────────
if ($method === 'POST' && $action === 'remove_song') {
    $b    = body();
    $pid  = (int)($b['playlist_id'] ?? 0);
    $ytId = trim($b['yt_id'] ?? '');
    if (!$pid || !$ytId) jsonErr('playlist_id e yt_id richiesti');

    $pdo = getDB();
    $own = $pdo->prepare("SELECT id FROM playlists WHERE id=? AND user_id=?");
    $own->execute([$pid, $user['id']]);
    if (!$own->fetch()) jsonErr('Playlist non trovata', 404);

    $pdo->prepare("DELETE FROM playlist_songs WHERE playlist_id=? AND yt_id=?")->execute([$pid, $ytId]);
    jsonOk(['removed' => true]);
}

jsonErr('Azione non valida');
