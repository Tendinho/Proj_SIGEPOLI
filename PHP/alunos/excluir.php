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

// Buscar aluno antes de excluir para auditoria
$query = "SELECT * FROM alunos WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if ($aluno) {
    // Verificar se o aluno tem matrículas ativas
    $query_matriculas = "SELECT COUNT(*) as total FROM matriculas WHERE aluno_id = :aluno_id AND status = 'Ativa'";
    $stmt_matriculas = $db->prepare($query_matriculas);
    $stmt_matriculas->bindParam(":aluno_id", $id);
    $stmt_matriculas->execute();
    $matriculas = $stmt_matriculas->fetch(PDO::FETCH_ASSOC);
    
    if ($matriculas['total'] > 0) {
        $_SESSION['mensagem'] = "Não é possível excluir o aluno pois existem matrículas ativas vinculadas a ele!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
    
    // Marcar como inativo em vez de excluir fisicamente
    $query = "UPDATE alunos SET ativo = 0 WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Exclusão', 'alunos', $id, json_encode($aluno), null);
        
        $_SESSION['mensagem'] = "Aluno marcado como inativo com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } else {
        $_SESSION['mensagem'] = "Erro ao marcar aluno como inativo!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
} else {
    $_SESSION['mensagem'] = "Aluno não encontrado!";
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>