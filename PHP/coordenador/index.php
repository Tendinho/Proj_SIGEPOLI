<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(6);

$db=(new Database())->getConnection();
$stmt=$db->query("
  SELECT c.id AS curso_id, c.nome AS curso,
         p.id AS prof_id, f.nome_completo AS coordenador
  FROM cursos c
  JOIN professores p ON c.coordenador_id=p.id
  JOIN funcionarios f ON p.funcionario_id=f.id
  WHERE c.ativo=1
");
$lista=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Coordenadores</title></head>
<body>
  <h1>Coordenadores de Curso</h1>
  <table>
    <thead><tr><th>Curso</th><th>Coordenador</th><th>Ações</th></tr></thead>
    <tbody>
      <?php foreach($lista as $l): ?>
        <tr>
          <td><?=$l['curso']?></td>
          <td><?=$l['coordenador']?></td>
          <td>
            <a href="desvincular.php?curso_id=<?=$l['curso_id']?>"
               onclick="return confirm('Desvincular?')">Desvincular</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
