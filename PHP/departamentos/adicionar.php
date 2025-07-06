<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

verificarLogin();
verificarAcesso(5);

$db = (new Database())->getConnection();

// Carrega lista de possíveis chefes (funcionários ativos)
$chefes = $db
    ->query("SELECT id, nome_completo FROM funcionarios WHERE ativo = 1 AND cargo_id IN (1,2)")
    ->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome']);
    $orcamento = floatval($_POST['orcamento']);
    $chefe_id  = $_POST['chefe_id'] !== '' ? intval($_POST['chefe_id']) : null;

    try {
        $stmt = $db->prepare("
            INSERT INTO departamentos (nome, orcamento_anual, chefe_id)
            VALUES (:nome, :orcamento, :chefe)
        ");
        $stmt->bindParam(':nome',      $nome);
        $stmt->bindParam(':orcamento', $orcamento);
        $stmt->bindParam(':chefe',     $chefe_id, PDO::PARAM_INT);
        $stmt->execute();

        registrarAuditoria('Criação', 'departamentos', $db->lastInsertId());
        $_SESSION['mensagem']      = "Departamento adicionado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['mensagem']      = "Erro ao adicionar departamento: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Departamento - SIGEPOLI</title>
  <link rel="stylesheet" href="/Context/CSS/styles.css">
</head>
<body>
  <h1>Adicionar Departamento</h1>
  <?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?=$_SESSION['tipo_mensagem']?>">
      <?= $_SESSION['mensagem']; unset($_SESSION['mensagem'],$_SESSION['tipo_mensagem']); ?>
    </div>
  <?php endif; ?>
  <form method="post">
    <label for="nome">Nome do Departamento *</label><br>
    <input type="text" id="nome" name="nome" required><br><br>

    <label for="orcamento">Orçamento Anual *</label><br>
    <input type="number" id="orcamento" name="orcamento" step="0.01" min="0" required><br><br>

    <label for="chefe_id">Chefe do Departamento</label><br>
    <select id="chefe_id" name="chefe_id">
      <option value="">-- Nenhum --</option>
      <?php foreach ($chefes as $c): ?>
        <option value="<?=$c['id']?>"><?=htmlspecialchars($c['nome_completo'])?></option>
      <?php endforeach; ?>
    </select><br><br>

    <button type="submit" class="btn btn-primary">Salvar Departamento</button>
    <a href="index.php" class="btn btn-secondary">Cancelar</a>
  </form>
</body>
</html>
