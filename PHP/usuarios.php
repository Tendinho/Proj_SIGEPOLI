<?php
require_once __DIR__ . '/config.php';
verificarLogin();
verificarAcesso(9); // Apenas administradores

$database = new Database();
$db = $database->getConnection();

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

// Filtros
$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'ativo' => isset($_GET['ativo']) ? (int)$_GET['ativo'] : 1
];

// Consulta
$where = "WHERE u.ativo = :ativo";
$params = [':ativo' => $filtros['ativo']];

if (!empty($filtros['busca'])) {
    $where .= " AND (u.username LIKE :busca OR f.nome_completo LIKE :busca)";
    $params[':busca'] = "%{$filtros['busca']}%";
}

// Contar total
$sqlCount = "SELECT COUNT(*) FROM usuarios u
             LEFT JOIN funcionarios f ON u.id = f.usuario_id
             $where";
$stmt = $db->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar usuários
$sql = "SELECT u.id, u.username, u.email, u.ativo, u.ultimo_login,
               f.nome_completo, f.telefone
        FROM usuarios u
        LEFT JOIN funcionarios f ON u.id = f.usuario_id
        $where
        ORDER BY u.username
        LIMIT $offset, $por_pagina";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Usuários - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Proj_SIGEPOLI/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Proj_SIGEPOLI/Context/fontawesome/css/all.min.css">
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
                    <span>Usuários</span>
                </div>
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></span>
                    <img src="/Proj_SIGEPOLI/Context/IMG/user-default.png" alt="User">
                </div>
            </header>
            
            <div class="content">
                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                        <?= $_SESSION['mensagem'] ?>
                        <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users-cog"></i> Gestão de Usuários</h3>
                        <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Usuário</a>
                    </div>
                    <div class="card-body">
                        <form method="get" class="filter-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="busca">Pesquisar:</label>
                                    <input type="text" name="busca" placeholder="Usuário ou nome..." value="<?= htmlspecialchars($filtros['busca']) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="ativo">Status:</label>
                                    <select name="ativo">
                                        <option value="1" <?= $filtros['ativo'] == 1 ? 'selected' : '' ?>>Ativos</option>
                                        <option value="0" <?= $filtros['ativo'] == 0 ? 'selected' : '' ?>>Inativos</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                            <a href="usuarios.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                        </form>
                        
                        <div class="table-responsive" style="margin-top: 20px;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Usuário</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Último Login</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($usuarios)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Nenhum usuário encontrado</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($usuario['nome_completo'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($usuario['username']) ?></td>
                                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                                <td><?= htmlspecialchars($usuario['telefone'] ?? '-') ?></td>
                                                <td>
                                                    <?= $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca' ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $usuario['ativo'] ? 'success' : 'danger' ?>">
                                                        <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                                                    </span>
                                                </td>
                                                <td class="actions">
                                                    <a href="editar.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                                        <?php if ($usuario['ativo']): ?>
                                                            <a href="desativar.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Desativar este usuário?')" title="Desativar">
                                                                <i class="fas fa-user-slash"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="ativar.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-success" 
                                                               onclick="return confirm('Ativar este usuário?')" title="Ativar">
                                                                <i class="fas fa-user-check"></i>
                                                            </a>
                                                        <?php endif; ?>
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
                                    <a href="?pagina=<?= $i ?>&busca=<?= urlencode($filtros['busca']) ?>&ativo=<?= $filtros['ativo'] ?>"
                                       class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
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