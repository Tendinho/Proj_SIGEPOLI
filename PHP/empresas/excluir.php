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

// Buscar empresa antes de excluir para auditoria
$query = "SELECT * FROM empresas WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if ($empresa) {
    // Verificar se existem contratos vinculados
    $query_contratos = "SELECT COUNT(*) as total FROM contratos WHERE empresa_id = :empresa_id AND ativo = 1";
    $stmt_contratos = $db->prepare($query_contratos);
    $stmt_contratos->bindParam(":empresa_id", $id);
    $stmt_contratos->execute();
    $contratos = $stmt_contratos->fetch(PDO::FETCH_ASSOC);
    
    if ($contratos['total'] > 0) {
        $_SESSION['mensagem'] = "Não é possível excluir a empresa pois existem contratos ativos vinculados a ela!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
    
    // Marcar como inativo em vez de excluir fisicamente
    $query = "UPDATE empresas SET ativo = 0 WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Exclusão', 'empresas', $id, json_encode($empresa), null);
        
        $_SESSION['mensagem'] = "Empresa marcada como inativa com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } else {
        $_SESSION['mensagem'] = "Erro ao marcar empresa como inativa!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
} else {
    $_SESSION['mensagem'] = "Empresa não encontrada!";
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>