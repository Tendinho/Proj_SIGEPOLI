<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para RH

$database = new Database();
$db = $database->getConnection();

$departamento_id = (int)$_GET['id'];

// Verificar se departamento existe
$stmt = $db->prepare("SELECT id FROM departamentos WHERE id = ?");
$stmt->execute([$departamento_id]);
$departamento = $stmt->fetch();

if (!$departamento) {
    $_SESSION['mensagem'] = "Departamento não encontrado";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

// Verificar se há colaboradores no departamento
$stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE departamento_id = ?");
$stmt->execute([$departamento_id]);
$tem_colaboradores = $stmt->fetchColumn() > 0;

if ($tem_colaboradores) {
    $_SESSION['mensagem'] = "Não é possível excluir o departamento pois existem colaboradores vinculados a ele";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Registrar auditoria antes de excluir
        registrarAuditoria('Excluiu departamento', 'departamentos', $departamento_id);
        
        // Excluir departamento
        $stmt = $db->prepare("DELETE FROM departamentos WHERE id = ?");
        $stmt->execute([$departamento_id]);

        $_SESSION['mensagem'] = "Departamento excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['mensagem'] = "Erro ao excluir departamento: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Excluir Departamento - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        /* Estilos similares aos anteriores */
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 class="text-center">Confirmar Exclusão</h2>
            <p class="text-center">Tem certeza que deseja excluir este departamento? Esta ação não pode ser desfeita.</p>
            
            <form method="post" class="text-center">
                <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</body>
</html>