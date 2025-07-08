<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para RH

$database = new Database();
$db = $database->getConnection();

$erro = '';
$sucesso = '';

// Buscar funcionários para designar como chefes
$funcionarios = $db->query("SELECT f.id, f.nome_completo 
                           FROM funcionarios f
                           JOIN usuarios u ON f.usuario_id = u.id
                           WHERE u.ativo = 1
                           ORDER BY f.nome_completo")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome']);
        $orcamento = (float)str_replace(',', '.', $_POST['orcamento']);
        $chefe_id = !empty($_POST['chefe_id']) ? (int)$_POST['chefe_id'] : null;

        // Validar dados
        if (empty($nome) || $orcamento <= 0) {
            throw new Exception("Dados inválidos fornecidos");
        }

        // Inserir novo departamento
        $stmt = $db->prepare("INSERT INTO departamentos (nome, orcamento_anual, chefe_id) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $orcamento, $chefe_id]);

        registrarAuditoria('Criou departamento', 'departamentos', $db->lastInsertId(), "Orçamento: $orcamento");

        $_SESSION['mensagem'] = "Departamento criado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Novo Departamento - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        /* Estilos similares aos anteriores */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-building"></i> Novo Departamento</h1>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="post">
                <div class="form-group">
                    <label for="nome">Nome do Departamento:</label>
                    <input type="text" name="nome" required>
                </div>

                <div class="form-group">
                    <label for="orcamento">Orçamento Anual (Kz):</label>
                    <input type="text" name="orcamento" placeholder="0,00" required>
                </div>

                <div class="form-group">
                    <label for="chefe_id">Chefe do Departamento (opcional):</label>
                    <select name="chefe_id">
                        <option value="">Selecione um chefe</option>
                        <?php foreach ($funcionarios as $func): ?>
                            <option value="<?= $func['id'] ?>"><?= htmlspecialchars($func['nome_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Criar Departamento</button>
            </form>
        </div>
    </div>

    <script>
        // Formatar valor monetário
        document.querySelector('input[name="orcamento"]').addEventListener('blur', function() {
            let value = this.value.replace(/[^\d,]/g, '').replace(',', '.');
            value = parseFloat(value) || 0;
            this.value = value.toFixed(2).replace('.', ',');
        });
    </script>
</body>
</html>