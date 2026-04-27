<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    $stmt = $pdo->query("SELECT id, nome, tipo FROM categorie ORDER BY tipo, nome");
    $all  = $stmt->fetchAll();

    $entrate = array_values(array_filter($all, fn($c) => $c['tipo'] === 'entrata'));
    $uscite  = array_values(array_filter($all, fn($c) => $c['tipo'] === 'uscita'));

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'entrate' => $entrate,
            'uscite'  => $uscite,
        ],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
