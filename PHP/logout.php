<?php
require_once __DIR__ . '/config.php';

// Registrar logout
if (isset($_SESSION['usuario_id'])) {
    registrarAuditoria('Logout', 'sistema');
}

// Destruir sessão
session_unset();
session_destroy();

// Redirecionar usando a função do config.php
redirect('/PHP/login.php');
?>