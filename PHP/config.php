<?php
// Configurações básicas
define('BASE_URL', '');
define('SISTEMA_NOME', 'SIGEPOLI');
define('SISTEMA_VERSAO', '1.0.0');

// Inclui o db.php primeiro
require_once __DIR__ . '/db.php';

// Controle de sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Funções de redirecionamento
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        redirect('/PHP/login.php');
    }
}

function verificarAcesso($nivel_requerido) {
    if ($_SESSION['nivel_acesso'] < $nivel_requerido) {
        $_SESSION['mensagem'] = "Acesso negado. Permissões insuficientes.";
        $_SESSION['tipo_mensagem'] = "erro";
        redirect('/PHP/index.php');
    }
}

// Função para verificar BI existente
function verificarBIExistente($bi, $excludeId = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM alunos WHERE bi = :bi AND ativo = 1";
    if ($excludeId) {
        $query .= " AND id != :id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":bi", $bi);
    if ($excludeId) {
        $stmt->bindParam(":id", $excludeId);
    }
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0;
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