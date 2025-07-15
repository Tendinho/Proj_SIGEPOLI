<?php
// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'sigepoli';
$username = 'root';
$password = '2001';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

header('Content-Type: application/json');

$turma_id = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;

if ($turma_id <= 0) {
    echo json_encode([]);
    exit();
}

$query = "SELECT a.id, a.nome_completo 
          FROM alunos a
          JOIN matriculas m ON a.id = m.aluno_id
          WHERE m.turma_id = :turma_id
          AND a.ativo = 1
          AND m.status = 'Ativa'
          ORDER BY a.nome_completo";
$stmt = $db->prepare($query);
$stmt->bindParam(":turma_id", $turma_id);
$stmt->execute();

$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($alunos);
?>