<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../db.php';
verificarLogin();
verificarAcesso(5);

$db=(new Database())->getConnection();
$contratos=$db->query("SELECT id,numero_contrato FROM contratos WHERE ativo=1")->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
    extract($_POST);
    // RN04: garantia válida?
    $stmt=$db->prepare("SELECT garantia_validade FROM contratos WHERE id=:cid");
    $stmt->execute([':cid'=>$contrato_id]);
    $v=$stmt->fetchColumn();
    if(strtotime($v) < time()){
      $erro="Garantia expirada.";
    }
    // Calcular multa RN05
    $multa = ($sla_atingido<90) ? ($valor_pago * 0.10) : 0;

    if(empty($erro)){
      $ins=$db->prepare("
        INSERT INTO pagamentos_empresas
        (contrato_id,mes_referencia,ano_referencia,valor_pago,sla_atingido,multa_aplicada,status)
        VALUES (:cid,:mes,:ano,:valor,:sla,:multa,:status)
      ");
      $ins->execute([
        ':cid'=>$contrato_id,':mes'=>$mes_referencia,':ano'=>$ano_referencia,
        ':valor'=>$valor_pago,':sla'=>$sla_atingido,':multa'=>$multa,
        ':status'=>'Pago'
      ]);
      $_SESSION['mensagem']="Pagamento registrado"; $_SESSION['tipo_mensagem']="sucesso";
      header("Location:index.php"); exit;
    } else {
      $_SESSION['mensagem']=$erro; $_SESSION['tipo_mensagem']="erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Novo Pagamento</title></head>
<body>
  <h1>Cadastrar Pagamento</h1>
  <?php if(isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?=$_SESSION['tipo_mensagem']?>">
      <?=$_SESSION['mensagem']?>
    </div>
  <?php endif; ?>
  <form method="post">
    <label>Contrato:</label>
    <select name="contrato_id" required>
      <option value="">Selecione</option>
      <?php foreach($contratos as $c): ?>
        <option value="<?=$c['id']?>"><?=$c['numero_contrato']?></option>
      <?php endforeach; ?>
    </select>
    <label>Mês:</label><input type="number" name="mes_referencia" min="1" max="12" required>
    <label>Ano:</label><input type="number" name="ano_referencia" value="<?=date('Y')?>" required>
    <label>Valor Pago:</label><input type="number" name="valor_pago" step="0.01" required>
    <label>SLA Atingido (%):</label><input type="number" name="sla_atingido" min="0" max="100" required>
    <button>Salvar</button><a href="index.php">Cancelar</a>
  </form>
</body>
</html>
