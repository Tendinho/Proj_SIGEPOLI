<?php
require_once __DIR__ . '/../../config.php';

$database = new Database();
$db = $database->getConnection();

$curso_id = $_GET['curso_id'] ?? null;

if ($curso_id) {
    $stmt = $db->prepare("SELECT id, nome FROM turmas WHERE curso_id = ? AND ativo = 1 ORDER BY nome");
    $stmt->execute([$curso_id]);
    $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $turmas = [];
}

header('Content-Type: application/json');
echo json_encode($turmas);