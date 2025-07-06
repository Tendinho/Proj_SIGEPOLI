<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

$chefes = $pdo->query("SELECT cod_funcionario, nome FROM Funcionario WHERE tipo IN ('admin', 'coordenador')")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = adicionarDepartamento(
            $_POST['nome'],
            $_POST['orcamento'],
            $_POST['chefe_id'] ?: null
        );
        
        $success = "Departamento adicionado com sucesso!";
        $_POST = []; // Limpar formulário
    } catch (Exception $e) {
        $error = "Erro ao adicionar departamento: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <?php include '../../includes/head.php'; ?>
    <title>Adicionar Departamento - <?= SITE_NAME ?></title>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <main class="container">
        <h1>Adicionar Departamento</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form-card">
            <div class="form-group">
                <label for="nome">Nome do Departamento:</label>
                <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="orcamento">Orçamento Anual:</label>
                <input type="number" id="orcamento" name="orcamento" step="0.01" min="0" required 
                       value="<?= htmlspecialchars($_POST['orcamento'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="chefe_id">Chefe do Departamento:</label>
                <select id="chefe_id" name="chefe_id">
                    <option value="">Selecione um chefe</option>
                    <?php foreach ($chefes as $chefe): ?>
                        <option value="<?= $chefe['cod_funcionario'] ?>" 
                            <?= isset($_POST['chefe_id']) && $_POST['chefe_id'] == $chefe['cod_funcionario'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($chefe['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Salvar Departamento</button>
        </form>
    </main>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>