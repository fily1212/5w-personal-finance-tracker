<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Per semplicità usiamo user_id = 1 (utente demo)
$userId = 1;

try {
    $pdo    = Database::getInstance()->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Lista conti con saldo aggiornato
        $stmt = $pdo->prepare("
            SELECT
                c.id,
                c.nome_conto,
                c.saldo_iniziale,
                c.colore,
                c.saldo_iniziale + COALESCE(
                    (SELECT SUM(CASE WHEN cat.tipo = 'entrata' THEN t.importo ELSE -t.importo END)
                     FROM transazioni t
                     JOIN categorie cat ON t.categoria_id = cat.id
                     WHERE t.conto_id = c.id),
                    0
                ) AS saldo_attuale
            FROM conti c
            WHERE c.user_id = :uid
            ORDER BY c.id
        ");
        $stmt->execute([':uid' => $userId]);
        $conti = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $conti]);

    } elseif ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);

        $nomeConto     = trim($body['nome_conto'] ?? '');
        $saldoIniziale = isset($body['saldo_iniziale']) ? (float) $body['saldo_iniziale'] : 0.00;
        $colore        = trim($body['colore'] ?? '#007bff');

        if ($nomeConto === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Il campo nome_conto è obbligatorio']);
            exit;
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $colore)) {
            $colore = '#007bff';
        }

        $stmt = $pdo->prepare("
            INSERT INTO conti (user_id, nome_conto, saldo_iniziale, colore)
            VALUES (:uid, :nome, :saldo, :colore)
        ");
        $stmt->execute([
            ':uid'    => $userId,
            ':nome'   => $nomeConto,
            ':saldo'  => $saldoIniziale,
            ':colore' => $colore,
        ]);

        $newId = (int) $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Conto creato con successo',
            'data'    => ['id' => $newId],
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
