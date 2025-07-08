<?php
require_once __DIR__ . '/../../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso necessário para excluir contratos

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    $_SESSION['mensagem'] = "Contrato não especificado";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/pagamentos_empresas/contratos/index.php');
}

$contrato_id = (int)$_GET['id'];

// Verificar se existem pagamentos associados
$stmt = $db->prepare("SELECT COUNT(*) FROM pagamentos_empresas WHERE contrato_id = ?");
$stmt->execute([$contrato_id]);
$tem_pagamentos = $stmt->fetchColumn() > 0;

if ($tem_pagamentos) {
    $_SESSION['mensagem'] = "Não é possível excluir este contrato pois existem pagamentos associados a ele";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/pagamentos_empresas/contratos/index.php');
}

// Buscar dados do contrato antes de excluir para auditoria
$stmt = $db->prepare("SELECT numero_contrato FROM contratos WHERE id = ?");
$stmt->execute([$contrato_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contrato) {
    $_SESSION['mensagem'] = "Contrato não encontrado";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/pagamentos_empresas/contratos/index.php');
}

// Excluir contrato
$stmt = $db->prepare("DELETE FROM contratos WHERE id = ?");
if ($stmt->execute([$contrato_id])) {
    // Registrar auditoria
    registrarAuditoria("Excluiu o contrato: {$contrato['numero_contrato']}", 'contratos', $contrato_id);
    
    $_SESSION['mensagem'] = "Contrato excluído com sucesso!";
    $_SESSION['tipo_mensagem'] = "sucesso";
} else {
    $_SESSION['mensagem'] = "Erro ao excluir contrato";
    $_SESSION['tipo_mensagem'] = "erro";
}

redirect('/PHP/pagamentos_empresas/contratos/index.php');