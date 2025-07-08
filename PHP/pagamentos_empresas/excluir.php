<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão operacional

$database = new Database();
$db = $database->getConnection();

$pagamento_id = (int)$_GET['id'];

// Verificar se pagamento existe
$stmt = $db->prepare("SELECT id FROM pagamentos_empresas WHERE id = ?");
$stmt->execute([$pagamento_id]);
$pagamento = $stmt->fetch();

if (!$pagamento) {
    $_SESSION['mensagem'] = "Pagamento não encontrado";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Registrar auditoria antes de excluir
        registrarAuditoria('Excluiu pagamento', 'pagamentos_empresas', $pagamento_id);
        
        // Excluir pagamento
        $stmt = $db->prepare("DELETE FROM pagamentos_empresas WHERE id = ?");
        $stmt->execute([$pagamento_id]);

        $_SESSION['mensagem'] = "Pagamento excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['mensagem'] = "Erro ao excluir pagamento: " . $e->getMessage();
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
    <title>Excluir Pagamento - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 20px auto;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 class="text-center">Confirmar Exclusão</h2>
            <p class="text-center">Tem certeza que deseja excluir este pagamento? Esta ação não pode ser desfeita.</p>
            
            <form method="post" class="text-center">
                <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</body>
</html>