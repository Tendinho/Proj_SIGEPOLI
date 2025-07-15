<?php
// Conexão com o banco de dados
require_once 'config.php';
require_once 'db.php';

// Verificar se usuário está logado
verificarLogin();

// Verificar nível de acesso (9 para administradores)
if ($_SESSION['nivel_acesso'] < 9) {
    $_SESSION['mensagem'] = "Acesso negado. Permissões insuficientes.";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: /PHP/index.php");
    exit();
}

// Filtros
$filtros = [
    'tipo_acao' => $_GET['tipo_acao'] ?? '',
    'tabela' => $_GET['tabela'] ?? '',
    'usuario' => $_GET['usuario'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? ''
];

// Construir a consulta com filtros
$where = [];
$params = [];

if (!empty($filtros['tipo_acao'])) {
    $where[] = "a.acao = :acao";
    $params[':acao'] = $filtros['tipo_acao'];
}

if (!empty($filtros['tabela'])) {
    $where[] = "a.tabela_afetada = :tabela";
    $params[':tabela'] = $filtros['tabela'];
}

if (!empty($filtros['usuario'])) {
    $where[] = "(u.username LIKE :usuario OR f.nome_completo LIKE :usuario)";
    $params[':usuario'] = '%' . $filtros['usuario'] . '%';
}

if (!empty($filtros['data_inicio'])) {
    $where[] = "a.data_hora >= :data_inicio";
    $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
}

if (!empty($filtros['data_fim'])) {
    $where[] = "a.data_hora <= :data_fim";
    $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

// Consulta principal
$sql = "SELECT a.*, u.username, f.nome_completo
        FROM auditoria a
        JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN funcionarios f ON u.id = f.usuario_id
        $whereClause
        ORDER BY a.data_hora DESC
        LIMIT $offset, $por_pagina";

$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total de registros (para paginação)
$sqlCount = "SELECT COUNT(*) FROM auditoria a
             JOIN usuarios u ON a.usuario_id = u.id
             LEFT JOIN funcionarios f ON u.id = f.usuario_id
             $whereClause";

$stmtCount = $db->prepare($sqlCount);

foreach ($params as $key => $value) {
    $stmtCount->bindValue($key, $value);
}

$stmtCount->execute();
$total = $stmtCount->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Obter tabelas distintas para filtro
$tabelas = $db->query("SELECT DISTINCT tabela_afetada FROM auditoria WHERE tabela_afetada IS NOT NULL ORDER BY tabela_afetada")
              ->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/auditoria.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SIGEPOLI</h2>
                <p><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></p>
                <p class="nivel-acesso">Nível: <?= $_SESSION['nivel_acesso'] ?? '0' ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    
                    <!-- Menu Acadêmico -->
                    <li class="menu-section"><i class="fas fa-graduation-cap"></i> Acadêmico</li>
                    <li><a href="/PHP/cursos/index.php"><i class="fas fa-book"></i> Cursos</a></li>
                    <li><a href="/PHP/turmas/index.php"><i class="fas fa-users-class"></i> Turmas</a></li>
                    <li><a href="/PHP/alunos/index.php"><i class="fas fa-users"></i> Alunos</a></li>
                    
                    <!-- Menu Pessoal -->
                    <li class="menu-section"><i class="fas fa-user-tie"></i> Pessoal</li>
                    <li><a href="/PHP/colaboradores/index.php"><i class="fas fa-users-cog"></i> Colaboradores</a></li>
                    
                    <!-- Configurações -->
                    <li class="menu-section"><i class="fas fa-cog"></i> Sistema</li>
                    <li><a href="/PHP/perfil.php"><i class="fas fa-user-cog"></i> Perfil</a></li>
                    <li><a href="/PHP/usuarios.php"><i class="fas fa-users-cog"></i> Usuários</a></li>
                    <li class="active"><a href="/PHP/auditoria.php"><i class="fas fa-clipboard-list"></i> Auditoria</a></li>
                    <li><a href="/PHP/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span>Dashboard</span>
                    <span>Auditoria</span>
                </div>
                
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></span>
                    <img src="/Context/IMG/user-default.png" alt="User">
                </div>
            </header>
            
            <div class="content">
                <div class="audit-container">
                    <div class="audit-header">
                        <h1 class="audit-title">
                            <i class="fas fa-clipboard-list"></i> Registros de Auditoria
                        </h1>
                        <div class="audit-actions">
                            <a href="/PHP/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>

                    <div class="audit-filters">
                        <h3 class="filter-title"><i class="fas fa-filter"></i> Filtros</h3>
                        <form method="get" action="auditoria.php">
                            <div class="filter-group">
                                <div class="filter-item">
                                    <label for="tipo_acao">Tipo de Ação</label>
                                    <select id="tipo_acao" name="tipo_acao">
                                        <option value="">Todos</option>
                                        <option value="INSERT" <?= $filtros['tipo_acao'] === 'INSERT' ? 'selected' : '' ?>>Criação</option>
                                        <option value="UPDATE" <?= $filtros['tipo_acao'] === 'UPDATE' ? 'selected' : '' ?>>Atualização</option>
                                        <option value="DELETE" <?= $filtros['tipo_acao'] === 'DELETE' ? 'selected' : '' ?>>Exclusão</option>
                                        <option value="LOGIN" <?= $filtros['tipo_acao'] === 'LOGIN' ? 'selected' : '' ?>>Login</option>
                                    </select>
                                </div>
                                
                                <div class="filter-item">
                                    <label for="tabela">Tabela Afetada</label>
                                    <select id="tabela" name="tabela">
                                        <option value="">Todas</option>
                                        <?php foreach ($tabelas as $tabela): ?>
                                            <option value="<?= htmlspecialchars($tabela) ?>" <?= $filtros['tabela'] === $tabela ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tabela) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-item">
                                    <label for="usuario">Usuário</label>
                                    <input type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($filtros['usuario']) ?>" placeholder="Nome ou usuário">
                                </div>
                                
                                <div class="filter-item">
                                    <label for="data_inicio">Data Início</label>
                                    <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
                                </div>
                                
                                <div class="filter-item">
                                    <label for="data_fim">Data Fim</label>
                                    <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($filtros['data_fim']) ?>">
                                </div>
                            </div>
                            <div style="margin-top: 15px; text-align: right;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <a href="auditoria.php" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Limpar
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="audit-table-container">
                        <table class="audit-table">
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
                                        <td colspan="6" style="text-align: center;">Nenhum registro encontrado</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($registros as $registro): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i:s', strtotime($registro['data_hora'])) ?></td>
                                            <td class="user-cell">
                                                <div class="user-avatar">
                                                    <img src="/Context/IMG/imhuman.png" alt="Avatar">
                                                </div>
                                                <div class="user-info">
                                                    <span class="username"><?= htmlspecialchars($registro['username']) ?></span>
                                                    <?php if (!empty($registro['nome_completo'])): ?>
                                                        <span class="user-fullname"><?= htmlspecialchars($registro['nome_completo']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $badgeClass = '';
                                                switch($registro['acao']) {
                                                    case 'INSERT': $badgeClass = 'badge-create'; break;
                                                    case 'UPDATE': $badgeClass = 'badge-update'; break;
                                                    case 'DELETE': $badgeClass = 'badge-delete'; break;
                                                    case 'LOGIN': $badgeClass = 'badge-login'; break;
                                                    default: $badgeClass = 'badge-other';
                                                }
                                                ?>
                                                <span class="action-badge <?= $badgeClass ?>">
                                                    <?= htmlspecialchars($registro['acao']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($registro['tabela_afetada'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($registro['registro_id'] ?? '-') ?></td>
                                            <td>
                                                <?php if (!empty($registro['dados_novos'])): ?>
                                                    <button class="btn-details" onclick="showAuditDetails(<?= htmlspecialchars(json_encode($registro['dados_novos']), ENT_QUOTES, 'UTF-8') ?>)">
                                                        <i class="fas fa-eye"></i> Ver
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
                        <div class="audit-pagination">
                            <?php if ($pagina > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>" class="pagination-link">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>" class="pagination-link">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-angle-double-left"></i></span>
                                <span class="pagination-link disabled"><i class="fas fa-angle-left"></i></span>
                            <?php endif; ?>

                            <?php
                            $inicio = max(1, $pagina - 2);
                            $fim = min($total_paginas, $pagina + 2);
                            
                            if ($inicio > 1) {
                                echo '<span class="pagination-link disabled">...</span>';
                            }
                            
                            for ($i = $inicio; $i <= $fim; $i++): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>" class="pagination-link <?= $i == $pagina ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
                            
                            if ($fim < $total_paginas) {
                                echo '<span class="pagination-link disabled">...</span>';
                            }
                            ?>

                            <?php if ($pagina < $total_paginas): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>" class="pagination-link">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) ?>" class="pagination-link">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-angle-right"></i></span>
                                <span class="pagination-link disabled"><i class="fas fa-angle-double-right"></i></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalhes -->
    <div class="audit-modal" id="auditModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Detalhes da Auditoria</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <pre class="json-data" id="auditDetailsContent"></pre>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Fechar</button>
            </div>
        </div>
    </div>

    <script src="/Context/JS/script.js"></script>
    <script>
        // Função para mostrar detalhes da auditoria
        function showAuditDetails(data) {
            try {
                // Tentar formatar como JSON se possível
                const jsonData = JSON.parse(data);
                document.getElementById('auditDetailsContent').textContent = 
                    JSON.stringify(jsonData, null, 2);
            } catch (e) {
                // Se não for JSON válido, mostrar o texto original
                document.getElementById('auditDetailsContent').textContent = data;
            }
            
            // Mostrar o modal
            document.getElementById('auditModal').classList.add('active');
        }

        // Fechar modal
        document.querySelectorAll('.modal-close').forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById('auditModal').classList.remove('active');
            });
        });

        // Fechar modal ao clicar fora do conteúdo
        document.getElementById('auditModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('auditModal')) {
                document.getElementById('auditModal').classList.remove('active');
            }
        });

        // Validar datas do filtro
        document.getElementById('data_fim').addEventListener('change', function() {
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = this.value;
            
            if (dataInicio && dataFim && dataFim < dataInicio) {
                alert('A data final não pode ser anterior à data inicial');
                this.value = '';
            }
        });
    </script>
</body>
</html>