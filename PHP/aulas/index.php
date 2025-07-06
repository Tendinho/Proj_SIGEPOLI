<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(5);

$db = (new Database())->getConnection();

// Paginação
$pagina = $_GET['pagina'] ?? 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Filtro opcional
$where = "WHERE a.ativo = 1";
if (!empty($_GET['busca'])) {
    $busca = "%{$_GET['busca']}%";
    $where .= " AND (d.nome LIKE :busca OR t.nome LIKE :busca)";
}

// Contar total
$sqlCount = "SELECT COUNT(*) FROM aulas a
    JOIN disciplinas d ON a.disciplina_id = d.id
    JOIN turmas t     ON a.turma_id      = t.id
    $where";
$stmt = $db->prepare($sqlCount);
if (isset($busca)) $stmt->bindParam(':busca', $busca);
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar registros
$sql = "SELECT a.id, d.nome AS disciplina, t.nome AS turma,
               a.dia_semana, a.hora_inicio, a.hora_fim
        FROM aulas a
        JOIN disciplinas d ON a.disciplina_id = d.id
        JOIN turmas t     ON a.turma_id      = t.id
        $where
        ORDER BY t.nome, a.dia_semana, a.hora_inicio
        LIMIT $offset, $por_pagina";
$stmt = $db->prepare($sql);
if (isset($busca)) $stmt->bindParam(':busca', $busca);
$stmt->execute();
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8"><title>Aulas - SIGEPOLI</title>
  <link rel="stylesheet" href="/Context/CSS/styles.css">
</head>
<body>
  <h1>Gestão de Aulas</h1>
  <a href="criar.php" class="btn btn-primary">Nova Aula</a>
  <form method="get" style="margin-top:1em;">
    <input type="text" name="busca" placeholder="Pesquisar..." value="<?=htmlspecialchars($_GET['busca']??'')?>">
    <button>Filtrar</button>
  </form>
  <table class="data-table">
    <thead>
      <tr><th>ID</th><th>Disciplina</th><th>Turma</th><th>Dia</th><th>Início</th><th>Fim</th><th>Ações</th></tr>
    </thead><tbody>
    <?php foreach($aulas as $a): ?>
      <tr>
        <td><?=$a['id']?></td>
        <td><?=htmlspecialchars($a['disciplina'])?></td>
        <td><?=htmlspecialchars($a['turma'])?></td>
        <td><?=$a['dia_semana']?></td>
        <td><?=$a['hora_inicio']?></td>
        <td><?=$a['hora_fim']?></td>
        <td>
          <a href="editar.php?id=<?=$a['id']?>" class="btn btn-sm btn-warning">Editar</a>
          <a href="excluir.php?id=<?=$a['id']?>" class="btn btn-sm btn-danger"
             onclick="return confirm('Excluir esta aula?')">Excluir</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if($total_paginas>1): ?>
    <div class="pagination">
      <?php for($i=1;$i<=$total_paginas;$i++): ?>
        <a href="?pagina=<?=$i?><?=isset($_GET['busca'])?"&busca=".urlencode($_GET['busca']):''?>"
           class="<?=$i==$pagina?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</body>
</html>
