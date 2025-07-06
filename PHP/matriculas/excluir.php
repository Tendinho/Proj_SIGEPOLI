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

// Buscar matrícula antes de excluir para auditoria
$query = "SELECT * FROM matriculas WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$matricula = $stmt->fetch(PDO::FETCH_ASSOC);

if ($matricula) {
    // Verificar se existem avaliações vinculadas
    $query_avaliacoes = "SELECT COUNT(*) as total FROM avaliacoes WHERE aluno_id = :aluno_id AND turma_id = :turma_id";
    $stmt_avaliacoes = $db->prepare($query_avaliacoes);
    $stmt_avaliacoes->bindParam(":aluno_id", $matricula['aluno_id']);
    $stmt_avaliacoes->bindParam(":turma_id", $matricula['turma_id']);
    $stmt_avaliacoes->execute();
    $avaliacoes = $stmt_avaliacoes->fetch(PDO::FETCH_ASSOC);
    
    if ($avaliacoes['total'] > 0) {
        $_SESSION['mensagem'] = "Não é possível excluir a matrícula pois existem avaliações vinculadas a ela!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
    
    $query = "DELETE FROM matriculas WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Exclusão', 'matriculas', $id, json_encode($matricula), null);
        
        $_SESSION['mensagem'] = "Matrícula excluída com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } else {
        $_SESSION['mensagem'] = "Erro ao excluir matrícula!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
} else {
    $_SESSION['mensagem'] = "Matrícula não encontrada!";
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>