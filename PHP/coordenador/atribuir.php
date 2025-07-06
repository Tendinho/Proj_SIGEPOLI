<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(6);

$db=(new Database())->getConnection();
$cursos=$db->query("SELECT id,nome FROM cursos WHERE ativo=1 AND coordenador_id IS NULL")->fetchAll();
$profs = $db->query("
  SELECT p.id,f.nome_completo FROM professores p
  JOIN funcionarios f ON p.funcionario_id=f.id
  WHERE p.ativo=1
")->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $curso_id=intval($_POST['curso_id']);
  $prof_id =intval($_POST['professor_id']);
  $db->prepare("UPDATE cursos SET coordenador_id=:p WHERE id=:c")
     ->execute([':p'=>$prof_id,':c'=>$curso_id]);
  registrarAuditoria('Designação','cursos',$curso_id);
  $_SESSION['mensagem']="Coordenador atribuído"; $_SESSION['tipo_mensagem']="sucesso";
  header("Location:index.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Atribuir Coordenador</title></head>
<body>
  <h1>Atribuir Coordenador</h1>
  <form method="post">
    <label>Curso:</label>
    <select name="curso_id" required>
      <?php foreach($cursos as $c): ?>
        <option value="<?=$c['id']?>"><?=$c['nome']?></option>
      <?php endforeach;?>
    </select>
    <label>Professor:</label>
    <select name="professor_id" required>
      <?php foreach($profs as $p): ?>
        <option value="<?=$p['id']?>"><?=$p['nome_completo']?></option>
      <?php endforeach;?>
    </select>
    <button>Salvar</button><a href="index.php">Cancelar</a>
  </form>
</body>
</html>
