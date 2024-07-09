<?php
$statsFile = 'stats.json';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['gamesPlayed']) && isset($data['gamesWon'])) {
    file_put_contents($statsFile, json_encode($data));
    echo json_encode(['success' => true, 'message' => 'Stats updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
}
?>