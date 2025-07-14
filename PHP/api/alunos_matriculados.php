<?php
// api/alunos_matriculados.php
require_once __DIR__ . '/../config.php';

$database = new Database();
$db = $database->getConnection();

header('Content-Type: application/json');

if (!isset($_GET['turma_id']) || !is_numeric($_GET['turma_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID da turma inválido']);
    exit;
}

$turma_id = $_GET['turma_id'];

try {
    $stmt = $db->prepare("
        SELECT a.id, a.nome_completo
        FROM alunos a
        JOIN matriculas m ON a.id = m.aluno_id
        WHERE m.turma_id = :turma_id
        AND a.ativo = 1
        AND m.status = 'Ativa'
        ORDER BY a.nome_completo
    ");
    $stmt->bindParam(":turma_id", $turma_id);
    $stmt->execute();
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($alunos);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar alunos: ' . $e->getMessage()]);
}