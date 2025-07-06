<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(5);

$db=(new Database())->getConnection();

if(!isset($_GET['id'])) header("Location:index.php");
$id = intval($_GET['id']);

// buscar aula
$stmt=$db->prepare("SELECT * FROM aulas WHERE id=:id");
$stmt->execute([':id'=>$id]);
$a=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$a){ $_SESSION['mensagem']="Aula não encontrada"; $_SESSION['tipo_mensagem']="erro"; header("Location:index.php"); }

// listas
$disc = $db->query("SELECT id,nome FROM disciplinas WHERE ativo=1")->fetchAll();
$turmas = $db->query("SELECT id,nome FROM turmas WHERE ativo=1")->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
    extract($_POST);
    // RN01 novamente
    $stmt=$db->prepare("
      SELECT COUNT(*) FROM aulas
      WHERE professor_id=:p AND dia_semana=:dia
        AND (:i BETWEEN hora_inicio AND hora_fim OR :f BETWEEN hora_inicio AND hora_fim)
        AND id<>:id
    ");
    $stmt->execute([':p'=>$professor_id,':dia'=>$dia_semana,':i'=>$hora_inicio,':f'=>$hora_fim,':id'=>$id]);
    if($stmt->fetchColumn()>0){
      $_SESSION['mensagem']="Conflito de horário"; $_SESSION['tipo_mensagem']="erro";
    } else {
      $up = $db->prepare("
        UPDATE aulas SET disciplina_id=:d,turma_id=:t,professor_id=:p,
                        dia_semana=:dia,hora_inicio=:i,hora_fim=:f
        WHERE id=:id
      ");
      $up->execute([':d'=>$disciplina_id,':t'=>$turma_id,':p'=>$professor_id,
                    ':dia'=>$dia_semana,':i'=>$hora_inicio,':f'=>$hora_fim,':id'=>$id]);
      $_SESSION['mensagem']="Aula atualizada"; $_SESSION['tipo_mensagem']="sucesso";
      header("Location:index.php"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Editar Aula</title></head>
<body>
  <h1>Editar Aula #<?=$id?></h1>
  <?php if(isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?=$_SESSION['tipo_mensagem']?>"><?=$_SESSION['mensagem']?></div>
  <?php endif; ?>
  <form method="post">
    <label>Disciplina:</label>
    <select name="disciplina_id" required>
      <?php foreach($disc as $d): ?>
        <option value="<?=$d['id']?>" <?= $d['id']==$a['disciplina_id']?'selected':'' ?>>
          <?=$d['nome']?>
        </option>
      <?php endforeach; ?>
    </select>
    <label>Turma:</label>
    <select name="turma_id" required>
      <?php foreach($turmas as $t): ?>
        <option value="<?=$t['id']?>" <?= $t['id']==$a['turma_id']?'selected':'' ?>>
          <?=$t['nome']?>
        </option>
      <?php endforeach; ?>
    </select>
    <label>Professor ID:</label>
    <input type="number" name="professor_id" value="<?=$a['professor_id']?>" required>
    <label>Dia da Semana:</label>
    <select name="dia_semana" required>
      <?php foreach(['Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $dia): ?>
        <option <?= $dia==$a['dia_semana']?'selected':''?>><?=$dia?></option>
      <?php endforeach; ?>
    </select>
    <label>Hora Início:</label>
    <input type="time" name="hora_inicio" value="<?=$a['hora_inicio']?>" required>
    <label>Hora Fim:</label>
    <input type="time" name="hora_fim" value="<?=$a['hora_fim']?>" required>

    <button type="submit">Salvar</button>
    <a href="index.php">Cancelar</a>
  </form>
</body>
</html>
