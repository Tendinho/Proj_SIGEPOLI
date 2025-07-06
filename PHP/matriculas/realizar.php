<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(5);

$db = (new Database())->getConnection();
$alunos = $db->query("SELECT id,nome_completo FROM alunos WHERE ativo=1")->fetchAll();
$turmas = $db->query("SELECT id,nome,capacidade FROM turmas WHERE ativo=1")->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $aluno = intval($_POST['aluno_id']);
    $turma = intval($_POST['turma_id']);
    $ano = intval($_POST['ano_letivo']);
    $sem = intval($_POST['semestre']);
    $valor = floatval($_POST['valor_propina']);
    $paga  = isset($_POST['propina_paga'])?1:0;

    // RN02: verificar duplicata
    $stmt=$db->prepare("
      SELECT COUNT(*) FROM matriculas
       WHERE aluno_id=:a AND turma_id=:t AND ano_letivo=:y AND semestre=:s
    ");
    $stmt->execute([':a'=>$aluno,':t'=>$turma,':y'=>$ano,':s'=>$sem]);
    if($stmt->fetchColumn()>0){
      $erro="Já matriculado neste ano/semestre";
    } else {
      // RN02: vagas
      $stmt=$db->prepare("
        SELECT t.capacidade,
          (SELECT COUNT(*) FROM matriculas m
            WHERE m.turma_id=:t AND m.ano_letivo=:y AND m.semestre=:s
          ) AS ocupadas
        FROM turmas t WHERE t.id=:t
      ");
      $stmt->execute([':t'=>$turma,':y'=>$ano,':s'=>$sem]);
      $v=$stmt->fetch(PDO::FETCH_ASSOC);
      if($v['ocupadas']>=$v['capacidade']){
        $erro="Turma sem vagas";
      }
    }

    // RN03: nota entra no módulo avaliações, aqui só propina
    if(empty($erro)){
      if(!$paga){
        $_SESSION['tipo_mensagem']="aviso";
        $_SESSION['mensagem']="Propina não paga, matrícula registrada como pendente.";
      } else {
        $_SESSION['tipo_mensagem']="sucesso";
        $_SESSION['mensagem']="Matrícula cadastrada com sucesso.";
      }
      $ins=$db->prepare("
        INSERT INTO matriculas
          (aluno_id,turma_id,ano_letivo,semestre,valor_propina,propina_paga,status)
        VALUES (:a,:t,:y,:s,:v,:p,'Ativa')
      ");
      $ins->execute([
        ':a'=>$aluno,':t'=>$turma,':y'=>$ano,
        ':s'=>$sem,':v'=>$valor,':p'=>$paga
      ]);
      registrarAuditoria('Criação','matriculas',$db->lastInsertId(),null);
      header("Location:index.php"); exit;
    } else {
      $_SESSION['mensagem']=$erro; $_SESSION['tipo_mensagem']="erro";
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Nova Matrícula</title></head>
<body>
  <h1>Realizar Matrícula</h1>
  <?php if(isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?=$_SESSION['tipo_mensagem']?>">
      <?= $_SESSION['mensagem']; unset($_SESSION['mensagem'],$_SESSION['tipo_mensagem']); ?>
    </div>
  <?php endif; ?>
  <form method="post">
    <label>Aluno:</label>
    <select name="aluno_id" required>
      <option value="">Selecione</option>
      <?php foreach($alunos as $a): ?>
        <option value="<?=$a['id']?>"><?=$a['nome_completo']?></option>
      <?php endforeach; ?>
    </select>

    <label>Turma:</label>
    <select name="turma_id" required>
      <option value="">Selecione</option>
      <?php foreach($turmas as $t): ?>
        <option value="<?=$t['id']?>">
          <?=$t['nome']?> (Capacidade: <?=$t['capacidade']?>)
        </option>
      <?php endforeach; ?>
    </select>

    <label>Ano Letivo:</label>
    <input type="number" name="ano_letivo" min="2000" max="2100" value="<?=date('Y')?>" required>

    <label>Semestre:</label>
    <select name="semestre" required>
      <option value="1">1º Semestre</option>
      <option value="2">2º Semestre</option>
    </select>

    <label>Valor Propina:</label>
    <input type="number" name="valor_propina" step="0.01" min="0" required>

    <label><input type="checkbox" name="propina_paga"> Propina Paga</label>

    <button type="submit">Salvar</button>
    <a href="index.php">Cancelar</a>
  </form>
</body>
</html>
