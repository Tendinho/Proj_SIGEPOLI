<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para RH

$database = new Database();
$db = $database->getConnection();

$colaborador_id = (int)$_GET['id'];
$colaborador = null;

// Buscar dados do colaborador e usuário associado
$stmt = $db->prepare("SELECT f.*, u.id AS usuario_id 
                     FROM funcionarios f
                     LEFT JOIN usuarios u ON f.usuario_id = u.id
                     WHERE f.id = ?");
$stmt->execute([$colaborador_id]);
$colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$colaborador) {
    $_SESSION['mensagem'] = "Colaborador não encontrado";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

try {
    // Iniciar transação
    $db->beginTransaction();

    // Se o colaborador tem usuário associado, desativá-lo
    if ($colaborador['usuario_id']) {
        $stmt = $db->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
        $stmt->execute([$colaborador['usuario_id']]);
        
        registrarAuditoria('Desativou usuário do colaborador', 'usuarios', $colaborador['usuario_id'], 
                         "Colaborador ID: $colaborador_id");
    }

    // Registrar auditoria antes da desativação
    registrarAuditoria('Desativou colaborador', 'funcionarios', $colaborador_id, 
                      "Nome: {$colaborador['nome_completo']}");

    // Commit da transação
    $db->commit();

    $_SESSION['mensagem'] = "Colaborador desativado com sucesso!";
    $_SESSION['tipo_mensagem'] = "sucesso";
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['mensagem'] = "Erro ao desativar colaborador: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>