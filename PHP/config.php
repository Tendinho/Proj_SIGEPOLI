<?php
// Configurações básicas
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '');
define('SISTEMA_NOME', 'SIGEPOLI');
define('SISTEMA_VERSAO', '1.0.0');

// Inclui o db.php primeiro
require_once __DIR__ . '/db.php';

// Controle de sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 dia
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// Funções de redirecionamento
function redirect($path) {
    if (headers_sent()) {
        echo '<script>window.location.href="' . BASE_URL . $path . '";</script>';
        exit();
    }
    header("Location: " . BASE_URL . $path);
    exit();
}

function verificarLogin() {
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('/PHP/login.php');
    }
}

function verificarAcesso($nivel_requerido) {
    if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] < $nivel_requerido) {
        $_SESSION['mensagem'] = "Acesso negado. Permissões insuficientes.";
        $_SESSION['tipo_mensagem'] = "erro";
        redirect('/PHP/index.php');
    }
}

// Função para verificar BI existente
function verificarBIExistente($bi, $excludeId = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT COUNT(*) FROM alunos WHERE bi = :bi AND ativo = 1";
        if ($excludeId) {
            $query .= " AND id != :id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":bi", $bi);
        if ($excludeId) {
            $stmt->bindParam(":id", $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    } catch(PDOException $e) {
        error_log("Erro ao verificar BI: " . $e->getMessage());
        return false;
    }
}

// Registrar ações na auditoria
function registrarAuditoria($acao, $tabela_afetada = null, $registro_id = null, $detalhes = null) {
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        return false;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        $query = "INSERT INTO auditoria (usuario_id, acao, tabela_afetada, registro_id, detalhes, data_hora, ip_address) 
                  VALUES (:usuario_id, :acao, :tabela_afetada, :registro_id, :detalhes, NOW(), :ip_address)";

        $stmt = $db->prepare($query);
        $stmt->bindValue(":usuario_id", $_SESSION['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(":acao", $acao, PDO::PARAM_STR);
        $stmt->bindValue(":tabela_afetada", $tabela_afetada, PDO::PARAM_STR);
        $stmt->bindValue(":registro_id", $registro_id, PDO::PARAM_INT);
        $stmt->bindValue(":detalhes", $detalhes, PDO::PARAM_STR);
        $stmt->bindValue(":ip_address", $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch(PDOException $exception) {
        error_log("Erro ao registrar auditoria: " . $exception->getMessage());
        return false;
    }
}
?>