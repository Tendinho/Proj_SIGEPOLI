<?php
// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'sigepoli';
$username = 'admin';
$password = 'SenhaSegura123!';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /PHP/login.php");
    exit();
}

// Verificar nível de acesso (9 para administradores)
if ($_SESSION['nivel_acesso'] < 9) {
    $_SESSION['mensagem'] = "Acesso negado. Permissões insuficientes.";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: /PHP/index.php");
    exit();
}

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

// Consulta
$sql = "SELECT a.*, u.username, f.nome_completo
        FROM auditoria a
        JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN funcionarios f ON u.id = f.usuario_id
        ORDER BY a.data_hora DESC
        LIMIT $offset, $por_pagina";
$stmt = $db->prepare($sql);
$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total
$sqlCount = "SELECT COUNT(*) FROM auditoria";
$total = $db->query($sqlCount)->fetchColumn();
$total_paginas = ceil($total / $por_pagina);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria - SIGEPOLI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
        }
        .sidebar-header {
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .sidebar-header p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            color: #bdc3c7;
        }
        .nivel-acesso {
            font-size: 0.8rem;
            color: #3498db;
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .top-bar {
            background-color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .breadcrumb {
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        .breadcrumb span {
            color: #3498db;
        }
        .breadcrumb span:after {
            content: '›';
            margin: 0 5px;
            color: #7f8c8d;
        }
        .breadcrumb span:last-child:after {
            content: '';
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .content {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card-header {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .card-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .data-table tr:hover {
            background-color: #f8f9fa;
        }
        .text-center {
            text-align: center;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #3498db;
        }
        .pagination a.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .btn-info {
            color: #fff;
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        small {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SIGEPOLI</h2>
                <p><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></p>
                <p class="nivel-acesso">Nível: <?= $_SESSION['nivel_acesso'] ?? '0' ?></p>
            </div>
        </div>
        
        <div class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span>Configurações</span>
                    <span>Auditoria</span>
                </div>
                <a href="/PHP/index.php" class="btn btn-secondary">← Voltar</a>
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></span>
                    <img src="/Context/IMG/user-default.png" alt="User">
                </div>
            </header>
            
            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Registros de Auditoria</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Usuário</th>
                                        <th>Ação</th>
                                        <th>Tabela</th>
                                        <th>Registro ID</th>
                                        <th>Detalhes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($registros)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Nenhum registro encontrado</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($registros as $registro): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i:s', strtotime($registro['data_hora'])) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($registro['username']) ?>
                                                    <?php if (!empty($registro['nome_completo'])): ?>
                                                        <br><small><?= htmlspecialchars($registro['nome_completo']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($registro['acao']) ?></td>
                                                <td><?= htmlspecialchars($registro['tabela_afetada'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($registro['registro_id'] ?? '-') ?></td>
                                                <td>
                                                    <?php if (!empty($registro['dados_novos'])): ?>
                                                        <button class="btn btn-sm btn-info" onclick="alert('<?= str_replace("'", "\\'", $registro['dados_novos']) ?>')">
                                                            👁️ Ver
                                                        </button>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($total_paginas > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <a href="?pagina=<?= $i ?>" class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>