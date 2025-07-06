<?php
require_once __DIR__ . '/../../config.php';
verificarLogin();
verificarAcesso(6); // Acesso administrativo

$db = (new Database())->getConnection();

// Buscar departamentos ativos
$stmt = $db->prepare("SELECT id, nome FROM departamentos WHERE ativo=1 ORDER BY nome");
$stmt->execute();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $bi = $_POST['bi'];
    $data_nasc = $_POST['data_nascimento'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];
    $departamento_id = $_POST['departamento_id'];
    $tipo = 'colaborador';

    $stmt = $db->prepare("
        INSERT INTO funcionarios
        (nome_completo, bi, data_nascimento, telefone, endereco, tipo, departamento_id)
        VALUES
        (:nome,:bi,:data_nasc,:telefone,:endereco,:tipo,:dept)
    ");
    $stmt->execute([
        ':nome'=>$nome,':bi'=>$bi,':data_nasc'=>$data_nasc,
        ':telefone'=>$telefone,':endereco'=>$endereco,
        ':tipo'=>$tipo,':dept'=>$departamento_id
    ]);
    $_SESSION['mensagem']="Colaborador cadastrado!";
    header("Location: index.php"); exit();
}
?>
