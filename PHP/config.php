<?php
// Primeiro incluímos o db.php para ter acesso à classe Database
require_once __DIR__ . '/db.php';

// Configurações do sistema
define('SISTEMA_NOME', 'SIGEPOLI');
define('SISTEMA_VERSAO', '1.0.0');
define('SISTEMA_CODIGO', '');

// Configurações de sessão - removemos session_start() daqui
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se o usuário está logado para páginas restritas
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

// Verificar nível de acesso
function verificarAcesso($nivel_requerido) {
    if ($_SESSION['nivel_acesso'] < $nivel_requerido) {
        $_SESSION['mensagem'] = "Acesso negado. Permissões insuficientes.";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../index.php");
        exit();
    }
}

// Registrar ações na auditoria
function registrarAuditoria($acao, $tabela_afetada = null, $registro_id = null, $detalhes = null) {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "INSERT INTO auditoria (usuario_id, acao, tabela_afetada, registro_id, detalhes, data_hora) 
              VALUES (:usuario_id, :acao, :tabela_afetada, :registro_id, :detalhes, NOW())";

    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(":usuario_id", $_SESSION['usuario_id']);
        $stmt->bindParam(":acao", $acao);
        $stmt->bindParam(":tabela_afetada", $tabela_afetada);
        $stmt->bindParam(":registro_id", $registro_id);
        $stmt->bindParam(":detalhes", $detalhes);
        return $stmt->execute();
    } catch(PDOException $exception) {
        error_log("Erro ao registrar auditoria: " . $exception->getMessage());
        return false;
    }
}
?>