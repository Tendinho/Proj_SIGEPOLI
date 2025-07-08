<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão acadêmica

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    $_SESSION['mensagem'] = "ID da turma não especificado";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/turmas/index.php');
}

try {
    // Verificar se a turma existe
    $stmt = $db->prepare("SELECT nome, codigo FROM turmas WHERE id = ?");
    $stmt->execute([$id]);
    $turma = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$turma) {
        throw new Exception("Turma não encontrada");
    }

    // Verificar se há alunos matriculados
    $stmt = $db->prepare("SELECT COUNT(*) FROM matriculas WHERE turma_id = ? AND status = 'Ativa'");
    $stmt->execute([$id]);
    $alunos_matriculados = $stmt->fetchColumn();

    if ($alunos_matriculados > 0) {
        throw new Exception("Não é possível inativar a turma pois há alunos matriculados");
    }

    // Inativar a turma
    $stmt = $db->prepare("UPDATE turmas SET ativo = 0 WHERE id = ?");
    $stmt->execute([$id]);

    // Registrar ação na auditoria
    registrarAuditoria("INATIVAR_TURMA", "turmas", $id, "Turma inativada: {$turma['nome']} ({$turma['codigo']})");

    $_SESSION['mensagem'] = "Turma inativada com sucesso!";
    $_SESSION['tipo_mensagem'] = "sucesso";
} catch (Exception $e) {
    $_SESSION['mensagem'] = $e->getMessage();
    $_SESSION['tipo_mensagem'] = "erro";
}

redirect('/PHP/turmas/index.php');