<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(5);

if(!isset($_GET['id'])) header("Location:index.php");
$id=intval($_GET['id']);

$db=(new Database())->getConnection();
// exclusão lógica
$stmt=$db->prepare("UPDATE aulas SET ativo=0 WHERE id=:id");
$stmt->execute([':id'=>$id]);

registrarAuditoria('Exclusão','aulas',$id);
$_SESSION['mensagem']="Aula inativada"; $_SESSION['tipo_mensagem']="sucesso";
header("Location:index.php");
