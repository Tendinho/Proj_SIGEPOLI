<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Buscar curso antes de excluir para auditoria
$query = "SELECT * FROM cursos WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$curso = $stmt->fetch(PDO::FETCH_ASSOC);

if ($curso) {
    // Verificar se existem turmas vinculadas
    $query_turmas = "SELECT COUNT(*) as total FROM turmas WHERE curso_id = :curso_id AND ativo = 1";
    $stmt_turmas = $db->prepare($query_turmas);
    $stmt_turmas->bindParam(":curso_id", $id);
    $stmt_turmas->execute();
    $turmas = $stmt_turmas->fetch(PDO::FETCH_ASSOC);
    
    if ($turmas['total'] > 0) {
        $_SESSION['mensagem'] = "Não é possível excluir o curso pois existem turmas ativas vinculadas a ele!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
    
    // Verificar se existem disciplinas vinculadas
    $query_disciplinas = "SELECT COUNT(*) as total FROM disciplinas WHERE curso_id = :curso_id AND ativo = 1";
    $stmt_disciplinas = $db->prepare($query_disciplinas);
    $stmt_disciplinas->bindParam(":curso_id", $id);
    $stmt_disciplinas->execute();
    $disciplinas = $stmt_disciplinas->fetch(PDO::FETCH_ASSOC);
    
    if ($disciplinas['total'] > 0) {
        $_SESSION['mensagem'] = "Não é possível excluir o curso pois existem disciplinas ativas vinculadas a ele!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
    
    // Marcar como inativo em vez de excluir fisicamente
    $query = "UPDATE cursos SET ativo = 0 WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Exclusão', 'cursos', $id, json_encode($curso), null);
        
        $_SESSION['mensagem'] = "Curso marcado como inativo com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } else {
        $_SESSION['mensagem'] = "Erro ao marcar curso como inativo!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
} else {
    $_SESSION['mensagem'] = "Curso não encontrado!";
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>