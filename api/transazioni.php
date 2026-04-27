<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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
        // Storico transazioni con JOIN a categorie e conti
        $stmt = $pdo->prepare("
            SELECT
                t.id,
                t.importo,
                t.data,
                t.descrizione,
                c.nome_conto,
                c.colore AS conto_colore,
                cat.nome AS categoria_nome,
                cat.tipo AS categoria_tipo
            FROM transazioni t
            JOIN conti c       ON t.conto_id     = c.id
            JOIN categorie cat ON t.categoria_id = cat.id
            WHERE t.user_id = :uid
            ORDER BY t.data DESC, t.id DESC
        ");
        $stmt->execute([':uid' => $userId]);
        $transazioni = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $transazioni]);

    } elseif ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);

        $contoId     = isset($body['conto_id'])     ? (int) $body['conto_id']     : 0;
        $categoriaId = isset($body['categoria_id']) ? (int) $body['categoria_id'] : 0;
        $importo     = isset($body['importo'])      ? (float) $body['importo']    : 0.0;
        $data        = trim($body['data']           ?? '');
        $descrizione = trim($body['descrizione']    ?? '');

        if ($contoId <= 0 || $categoriaId <= 0 || $importo <= 0 || $data === '') {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'I campi conto_id, categoria_id, importo e data sono obbligatori e devono essere validi',
            ]);
            exit;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !checkdate(
            (int) substr($data, 5, 2),
            (int) substr($data, 8, 2),
            (int) substr($data, 0, 4)
        )) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Formato data non valido (atteso YYYY-MM-DD)']);
            exit;
        }

        // Verifica che il conto appartenga all'utente
        $stmtCheck = $pdo->prepare("SELECT id FROM conti WHERE id = :cid AND user_id = :uid");
        $stmtCheck->execute([':cid' => $contoId, ':uid' => $userId]);
        if (!$stmtCheck->fetch()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Conto non trovato o non autorizzato']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO transazioni (user_id, conto_id, categoria_id, importo, data, descrizione)
            VALUES (:uid, :conto, :cat, :importo, :data, :desc)
        ");
        $stmt->execute([
            ':uid'    => $userId,
            ':conto'  => $contoId,
            ':cat'    => $categoriaId,
            ':importo'=> $importo,
            ':data'   => $data,
            ':desc'   => $descrizione,
        ]);

        $newId = (int) $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Transazione aggiunta con successo',
            'data'    => ['id' => $newId],
        ]);

    } elseif ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parametro id mancante o non valido']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM transazioni WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $userId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Transazione non trovata o non autorizzata']);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => 'Transazione eliminata con successo']);

    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
