<?php
// /painel/api/submissions/history.php
header('Content-Type: application/json; charset=UTF-8');
require __DIR__ . '/../../db.php';
require __DIR__ . '/../../auth.php'; // isso já deve iniciar a sessão


require_login();


$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(422); echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

$sql = "SELECT h.acao, h.obs, h.by_user, h.changed_at, u.nome AS user_name
        FROM submissions_history h
        LEFT JOIN admin_users u ON u.id = h.by_user
        WHERE h.submission_id = ?
        ORDER BY h.changed_at ASC";
$st = $pdo->prepare($sql);
$st->execute([$id]);
echo json_encode(['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
