<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

verificarLogin();
verificarAcesso(5);

$db = (new Database())->getConnection();

// Filtro por ano (int)
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : intval(date('Y'));

// Paginação (opcional, exemplo sem paginação completa)
$stmt = $db->prepare("
    SELECT p.id,
           e.nome AS empresa,
           p.mes_referencia,
           p.ano_referencia,
           p.valor_pago,
           p.sla_atingido,
           p.multa_aplicada,
           p.status
    FROM pagamentos_empresas p
    JOIN contratos c ON p.contrato_id = c.id
    JOIN empresas e ON c.empresa_id = e.id
    WHERE p.ano_referencia = :ano
    ORDER BY p.mes_referencia DESC
");
$stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
$stmt->execute();
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Pagamentos a Empresas - SIGEPOLI</title>
  <link rel="stylesheet" href="/Context/CSS/styles.css">
</head>
<body>
  <h1>Gestão de Pagamentos a Empresas</h1>
  <form method="get" style="margin-bottom:1em;">
    <label for="ano">Ano:</label>
    <input type="number" id="ano" name="ano" value="<?=$ano?>" min="2000" max="2100">
    <button type="submit">Filtrar</button>
  </form>
  <a href="criar.php" class="btn btn-primary">Novo Pagamento</a>
  <table class="data-table">
    <thead>
      <tr>
        <th>ID</th><th>Empresa</th><th>Mês</th><th>Ano</th>
        <th>Valor Pago</th><th>SLA (%)</th><th>Multa</th><th>Status</th><th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($pagamentos)): ?>
        <tr><td colspan="9" style="text-align:center;">Nenhum pagamento encontrado</td></tr>
      <?php else: ?>
        <?php foreach ($pagamentos as $p): ?>
          <tr>
            <td><?=$p['id']?></td>
            <td><?=htmlspecialchars($p['empresa'])?></td>
            <td><?=$p['mes_referencia']?></td>
            <td><?=$p['ano_referencia']?></td>
            <td><?=number_format($p['valor_pago'],2,',','.')?> Kz</td>
            <td><?=$p['sla_atingido']?>%</td>
            <td><?=number_format($p['multa_aplicada'],2,',','.')?> Kz</td>
            <td><?=$p['status']?></td>
            <td>
              <a href="editar.php?id=<?=$p['id']?>" class="btn btn-sm btn-warning">Editar</a>
              <a href="excluir.php?id=<?=$p['id']?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Confirmar exclusão?')">Excluir</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
