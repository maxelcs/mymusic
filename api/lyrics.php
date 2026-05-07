<?php
// api/lyrics.php — Proxy per testi canzoni (Lyrics.ovh)
require_once __DIR__ . '/auth.php';

requireAuth(); // solo utenti loggati

$artist = trim($_GET['artist'] ?? '');
$title  = trim($_GET['title']  ?? '');

if (!$artist || !$title) jsonErr('artist e title richiesti');

// Lyrics.ovh è gratuita e non richiede API key
$url = 'https://api.lyrics.ovh/v1/' . urlencode($artist) . '/' . urlencode($title);

$ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
$raw = @file_get_contents($url, false, $ctx);

if ($raw === false) jsonErr('Impossibile recuperare il testo');

$data = json_decode($raw, true);
if (!empty($data['lyrics'])) {
    jsonOk(['lyrics' => $data['lyrics']]);
} else {
    jsonErr('Testo non trovato per questo brano', 404);
}
