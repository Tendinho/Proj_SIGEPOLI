<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

verificarLogin();
verificarAcesso(3); // Nível 3 para exclusão

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar se o aluno existe
    $stmt = $db->prepare("SELECT id FROM alunos WHERE id = :id AND ativo = 1");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['mensagem'] = "Aluno não encontrado ou já excluído";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
    
    // Exclusão lógica
    $query = "UPDATE alunos SET ativo = 0 WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Excluir', 'alunos', $id);
        $_SESSION['mensagem'] = "Aluno excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } else {
        $_SESSION['mensagem'] = "Erro ao excluir aluno";
        $_SESSION['tipo_mensagem'] = "erro";
    }
} catch(PDOException $e) {
    $_SESSION['mensagem'] = "Erro no banco de dados: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>