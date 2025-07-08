<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(5);

$db = (new Database())->getConnection();

// Buscar listas
$disc = $db->query("SELECT id,nome FROM disciplinas WHERE ativo=1 ORDER BY nome")->fetchAll();
$turmas = $db->query("SELECT id,nome FROM turmas WHERE ativo=1 ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    extract($_POST);
    // RN01: evitar sobreposição
    $stmt = $db->prepare("
      SELECT COUNT(*) FROM aulas
      WHERE professor_id = :professor
        AND dia_semana   = :dia
        AND (:hora_inicio BETWEEN hora_inicio AND hora_fim
          OR  :hora_fim    BETWEEN hora_inicio AND hora_fim)
    ");
    $stmt->execute([
      ':professor'=>$professor_id,
      ':dia'=>$dia_semana,
      ':hora_inicio'=>$hora_inicio,
      ':hora_fim'=>$hora_fim
    ]);
    if ($stmt->fetchColumn()>0) {
      $_SESSION['mensagem']="Conflito de horário!"; $_SESSION['tipo_mensagem']="erro";
    } else {
      $ins = $db->prepare("
        INSERT INTO aulas (disciplina_id,turma_id,professor_id,
                           dia_semana,hora_inicio,hora_fim,ativo)
        VALUES (:d,:t,:p,:dia,:i,:f,1)
      ");
      $ins->execute([
        ':d'=>$disciplina_id,':t'=>$turma_id,':p'=>$professor_id,
        ':dia'=>$dia_semana,':i'=>$hora_inicio,':f'=>$hora_fim
      ]);
      $_SESSION['mensagem']="Aula cadastrada"; $_SESSION['tipo_mensagem']="sucesso";
      header("Location:index.php"); exit;
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Nova Aula</title></head>
<link rel="stylesheet" href="/Context/CSS/styles.css">
<body>
  <h1>Criar Aula</h1>
  <?php if(isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
      <?= $_SESSION['mensagem']; unset($_SESSION['mensagem'],$_SESSION['tipo_mensagem']);?>
    </div>
  <?php endif; ?>
  <form method="post">
    <label>Disciplina:</label>
    <select name="disciplina_id" required>
      <option value="">Selecione</option>
      <?php foreach($disc as $d): ?>
        <option value="<?=$d['id']?>"><?=$d['nome']?></option>
      <?php endforeach; ?>
    </select>

    <label>Turma:</label>
    <select name="turma_id" required>
      <option value="">Selecione</option>
      <?php foreach($turmas as $t): ?>
        <option value="<?=$t['id']?>"><?=$t['nome']?></option>
      <?php endforeach; ?>
    </select>

    <label>Professor ID:</label>
    <input type="number" name="professor_id" required>

    <label>Dia da Semana:</label>
    <select name="dia_semana" required>
      <?php foreach(['Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $dia): ?>
        <option><?=$dia?></option>
      <?php endforeach; ?>
    </select>

    <label>Hora Início:</label><input type="time" name="hora_inicio" required>
    <label>Hora Fim:</label><input type="time" name="hora_fim" required>

    <button type="submit">Salvar</button>
    <a href="index.php">Cancelar</a>
  </form>
</body>
</html>
