<?php
require_once __DIR__ . '/../config.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT id, nome FROM turmas WHERE ativo = 1 ORDER BY nome");
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($turmas);