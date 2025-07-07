<?php
require_once __DIR__ . '/config.php';
verificarLogin();
verificarAcesso(9); // Apenas administradores

$database = new Database();
$db = $database->getConnection();

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
    <title>Auditoria - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #ecf0f1;
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
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?= SISTEMA_NOME ?></h2>
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
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></span>
                    <img src="/Proj_SIGEPOLI/Context/IMG/user-default.png" alt="User">
                </div>
            </header>
            
            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clipboard-list"></i> Registros de Auditoria</h3>
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
                                                    <?php if (!empty($registro['detalhes'])): ?>
                                                        <button class="btn btn-sm btn-info" onclick="alert('<?= addslashes($registro['detalhes']) ?>')">
                                                            <i class="fas fa-info-circle"></i> Ver
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
    
    <script src="/Context/JS/script.js"></script>
</body>
</html>