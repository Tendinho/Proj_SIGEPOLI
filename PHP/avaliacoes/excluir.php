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

// Buscar avaliação antes de excluir para auditoria
$query = "SELECT * FROM avaliacoes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);

if ($avaliacao) {
    // Verificar se o professor pode excluir esta avaliação
    $usuario_id = $_SESSION['usuario_id'];
    $query_verifica = "SELECT COUNT(*) as total 
                       FROM avaliacoes a
                       JOIN professores p ON a.professor_id = p.id
                       JOIN funcionarios f ON p.funcionario_id = f.id
                       WHERE a.id = :id AND f.usuario_id = :usuario_id";
    $stmt_verifica = $db->prepare($query_verifica);
    $stmt_verifica->bindParam(":id", $id);
    $stmt_verifica->bindParam(":usuario_id", $usuario_id);
    $stmt_verifica->execute();
    $resultado = $stmt_verifica->fetch(PDO::FETCH_ASSOC);

    if ($resultado['total'] == 0 && $_SESSION['nivel_acesso'] < 7) {
        $_SESSION['mensagem'] = "Você não tem permissão para excluir esta avaliação!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
    
    $query = "DELETE FROM avaliacoes WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Exclusão', 'avaliacoes', $id, json_encode($avaliacao), null);
        
        $_SESSION['mensagem'] = "Avaliação excluída com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } else {
        $_SESSION['mensagem'] = "Erro ao excluir avaliação!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
} else {
    $_SESSION['mensagem'] = "Avaliação não encontrada!";
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>