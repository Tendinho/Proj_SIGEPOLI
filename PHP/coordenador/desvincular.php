<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(6);

$curso_id = isset($_GET['curso_id']) ? $_GET['curso_id'] : null;

if (!$curso_id) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Buscar curso antes de desvincular para auditoria
$query = "SELECT c.*, f.nome_completo as coordenador 
          FROM cursos c
          JOIN professores p ON c.coordenador_id = p.id
          JOIN funcionarios f ON p.funcionario_id = f.id
          WHERE c.id = :curso_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":curso_id", $curso_id);
$stmt->execute();
$curso = $stmt->fetch(PDO::FETCH_ASSOC);

if ($curso) {
    $dados_anteriores = json_encode($curso);
    
    $query = "UPDATE cursos SET coordenador_id = NULL WHERE id = :curso_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":curso_id", $curso_id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Desvinculação', 'cursos', $curso_id, $dados_anteriores, json_encode(['coordenador_id' => null]));
        
        $_SESSION['mensagem'] = "Coordenador desvinculado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } else {
        $_SESSION['mensagem'] = "Erro ao desvincular coordenador!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
} else {
    $_SESSION['mensagem'] = "Curso não encontrado!";
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>