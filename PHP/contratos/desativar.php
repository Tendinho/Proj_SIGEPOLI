<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso necessário para desativar contratos

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    $_SESSION['mensagem'] = "Contrato não especificado";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/contratos/index.php');
}

$contrato_id = (int)$_GET['id'];

// Buscar dados do contrato antes de desativar para auditoria
$stmt = $db->prepare("SELECT numero_contrato FROM contratos WHERE id = ?");
$stmt->execute([$contrato_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contrato) {
    $_SESSION['mensagem'] = "Contrato não encontrado";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/contratos/index.php');
}

// Verificar se existem pagamentos pendentes
$stmt = $db->prepare("SELECT COUNT(*) FROM pagamentos_empresas WHERE contrato_id = ? AND status = 'Pendente'");
$stmt->execute([$contrato_id]);
$pagamentos_pendentes = $stmt->fetchColumn();

if ($pagamentos_pendentes > 0) {
    $_SESSION['mensagem'] = "Não é possível desativar este contrato pois existem pagamentos pendentes associados a ele";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/contratos/index.php');
}

// Desativar contrato
$stmt = $db->prepare("UPDATE contratos SET ativo = 0 WHERE id = ?");
if ($stmt->execute([$contrato_id])) {
    // Registrar auditoria
    registrarAuditoria("Desativou o contrato: {$contrato['numero_contrato']}", 'contratos', $contrato_id);
    
    $_SESSION['mensagem'] = "Contrato desativado com sucesso!";
    $_SESSION['tipo_mensagem'] = "sucesso";
} else {
    $_SESSION['mensagem'] = "Erro ao desativar contrato";
    $_SESSION['tipo_mensagem'] = "erro";
}

redirect('/PHP/contratos/index.php');