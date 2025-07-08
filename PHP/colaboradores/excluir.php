<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para RH

$database = new Database();
$db = $database->getConnection();

$colaborador_id = (int)$_GET['id'];
$colaborador = null;

// Buscar dados do colaborador
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

// Verificar se o colaborador está vinculado como chefe de departamento
$stmt = $db->prepare("SELECT COUNT(*) FROM departamentos WHERE chefe_id = ?");
$stmt->execute([$colaborador['usuario_id']]);
$vinculo_chefe = $stmt->fetchColumn();

// Verificar se o colaborador é professor (se aplicável)
$vinculo_professor = 0;
if ($colaborador['usuario_id']) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM professores WHERE funcionario_id = ?");
    $stmt->execute([$colaborador['id']]);
    $vinculo_professor = $stmt->fetchColumn();
}

$tem_vinculos = ($vinculo_chefe > 0) || ($vinculo_professor > 0);

if ($tem_vinculos) {
    $mensagem = "Não é possível excluir o colaborador pois existem vínculos ativos: ";
    $vinculos = [];
    if ($vinculo_chefe > 0) $vinculos[] = "chefe de departamento";
    if ($vinculo_professor > 0) $vinculos[] = "professor";
    
    $_SESSION['mensagem'] = $mensagem . implode(" e ", $vinculos);
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transação
        $db->beginTransaction();

        // Registrar auditoria antes de excluir
        registrarAuditoria('Excluiu colaborador', 'funcionarios', $colaborador_id, "Nome: {$colaborador['nome_completo']}");

        // Se tiver usuário, desativar em vez de excluir
        if ($colaborador['usuario_id']) {
            $stmt = $db->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
            $stmt->execute([$colaborador['usuario_id']]);
        }

        // Excluir funcionário
        $stmt = $db->prepare("DELETE FROM funcionarios WHERE id = ?");
        $stmt->execute([$colaborador_id]);

        // Commit da transação
        $db->commit();

        $_SESSION['mensagem'] = "Colaborador excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = "Erro ao excluir colaborador: " . $e->getMessage();
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
    <title>Excluir Colaborador - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
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
            font-size: 14px;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .text-center {
            text-align: center;
        }
        .info-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .info-box p {
            margin: 5px 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 {
            color: #343a40;
            margin-bottom: 20px;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-times"></i> Excluir Colaborador</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="card">
            <h2 class="text-center">Confirmar Exclusão</h2>
            
            <div class="info-box">
                <p><strong>Nome:</strong> <?= htmlspecialchars($colaborador['nome_completo']) ?></p>
                <p><strong>BI:</strong> <?= htmlspecialchars($colaborador['bi']) ?></p>
                <p><strong>Data de Contratação:</strong> <?= date('d/m/Y', strtotime($colaborador['data_contratacao'])) ?></p>
                <?php if ($colaborador['usuario_id']): ?>
                    <p><strong>Possui acesso ao sistema:</strong> Sim</p>
                <?php endif; ?>
            </div>
            
            <p class="text-center" style="margin-bottom: 25px;">
                Tem certeza que deseja excluir este colaborador? Esta ação não pode ser desfeita.
                <?php if ($colaborador['usuario_id']): ?>
                    <br><small>O acesso ao sistema será desativado, mas o usuário não será removido.</small>
                <?php endif; ?>
            </p>
            
            <form method="post" class="text-center">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Confirmar Exclusão
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </form>
        </div>
    </div>
</body>
</html>