<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Per semplicità usiamo user_id = 1 (utente demo)
$userId = 1;

try {
    $pdo = Database::getInstance()->getConnection();

    // Saldo totale: somma saldi iniziali di tutti i conti + entrate - uscite
    $stmtSaldo = $pdo->prepare("
        SELECT
            COALESCE(
                (SELECT SUM(c.saldo_iniziale) FROM conti c WHERE c.user_id = :uid),
                0
            )
            +
            COALESCE(
                (SELECT SUM(CASE WHEN cat.tipo = 'entrata' THEN t.importo ELSE -t.importo END)
                 FROM transazioni t
                 JOIN categorie cat ON t.categoria_id = cat.id
                 WHERE t.user_id = :uid2),
                0
            ) AS saldo_totale
    ");
    $stmtSaldo->execute([':uid' => $userId, ':uid2' => $userId]);
    $saldoTotale = (float) $stmtSaldo->fetchColumn();

    // Entrate del mese corrente
    $stmtEntrate = $pdo->prepare("
        SELECT COALESCE(SUM(t.importo), 0) AS totale
        FROM transazioni t
        JOIN categorie cat ON t.categoria_id = cat.id
        WHERE t.user_id = :uid
          AND cat.tipo = 'entrata'
          AND YEAR(t.data) = YEAR(CURDATE())
          AND MONTH(t.data) = MONTH(CURDATE())
    ");
    $stmtEntrate->execute([':uid' => $userId]);
    $entrateDelMese = (float) $stmtEntrate->fetchColumn();

    // Uscite del mese corrente
    $stmtUscite = $pdo->prepare("
        SELECT COALESCE(SUM(t.importo), 0) AS totale
        FROM transazioni t
        JOIN categorie cat ON t.categoria_id = cat.id
        WHERE t.user_id = :uid
          AND cat.tipo = 'uscita'
          AND YEAR(t.data) = YEAR(CURDATE())
          AND MONTH(t.data) = MONTH(CURDATE())
    ");
    $stmtUscite->execute([':uid' => $userId]);
    $usciteDelMese = (float) $stmtUscite->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'saldo_totale'    => $saldoTotale,
            'entrate_mese'    => $entrateDelMese,
            'uscite_mese'     => $usciteDelMese,
        ],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
