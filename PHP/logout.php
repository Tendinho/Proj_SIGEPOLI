<?php
// Inclui primeiro o config.php que já inclui o db.php
require_once __DIR__ . '/config.php';

// Verifica se a sessão já está ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Registrar logout na auditoria
if (isset($_SESSION['usuario_id'])) {
    registrarAuditoria('Logout', 'sistema', null, 'Usuário saiu do sistema');
}

// Destruir a sessão
$_SESSION = array();

// Se deseja destruir o cookie de sessão também
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirecionar para a página de login
header("Location: login.php");
exit();
?>