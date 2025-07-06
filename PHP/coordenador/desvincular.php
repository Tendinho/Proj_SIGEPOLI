<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(6);

if(!isset($_GET['curso_id'])) header("Location:index.php");
$id=intval($_GET['curso_id']);

$db=(new Database())->getConnection();
$db->prepare("UPDATE cursos SET coordenador_id=NULL WHERE id=:id")
   ->execute([':id'=>$id]);
registrarAuditoria('Desvinculação','cursos',$id);
$_SESSION['mensagem']="Coordenador desvinculado"; $_SESSION['tipo_mensagem']="sucesso";
header("Location:index.php");
