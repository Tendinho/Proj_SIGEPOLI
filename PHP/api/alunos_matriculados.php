<?php
require_once '../config.php';
verificarLogin();

$database = new Database();
$db = $database->getConnection();

$disciplina_id = isset($_GET['disciplina_id']) ? $_GET['disciplina_id'] : null;
$turma_id = isset($_GET['turma_id']) ? $_GET['turma_id'] : null;

if ($disciplina_id && $turma_id) {
    $query = "SELECT a.id, a.nome_completo 
              FROM alunos a
              JOIN matriculas m ON a.id = m.aluno_id
              WHERE m.turma_id = :turma_id
              AND m.status = 'Ativa'
              ORDER BY a.nome_completo";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(":turma_id", $turma_id);
    $stmt->execute();
    
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($alunos);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Parâmetros disciplina_id e turma_id são obrigatórios']);
}
?>