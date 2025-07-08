<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso necessário para gerenciar contratos

$database = new Database();
$db = $database->getConnection();

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Filtros
$filtros = [
    'empresa_id' => $_GET['empresa_id'] ?? null,
    'status' => $_GET['status'] ?? 'ativos',
    'busca' => $_GET['busca'] ?? ''
];

// Construir WHERE
$where = "WHERE 1=1";
$params = [];

if ($filtros['empresa_id']) {
    $where .= " AND c.empresa_id = :empresa_id";
    $params[':empresa_id'] = $filtros['empresa_id'];
}

if ($filtros['status'] === 'ativos') {
    $where .= " AND c.ativo = 1 AND c.data_fim >= CURDATE()";
} elseif ($filtros['status'] === 'inativos') {
    $where .= " AND c.ativo = 0";
} elseif ($filtros['status'] === 'vencidos') {
    $where .= " AND c.ativo = 1 AND c.data_fim < CURDATE()";
}

if (!empty($filtros['busca'])) {
    $where .= " AND (c.numero_contrato LIKE :busca OR e.nome LIKE :busca)";
    $params[':busca'] = "%{$filtros['busca']}%";
}

// Contar total de contratos
$sqlCount = "SELECT COUNT(*) FROM contratos c
             JOIN empresas e ON c.empresa_id = e.id
             $where";
$stmt = $db->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar contratos
$sql = "SELECT c.*, e.nome AS empresa_nome, 
               DATEDIFF(c.data_fim, CURDATE()) AS dias_restantes,
               (SELECT COUNT(*) FROM pagamentos_empresas WHERE contrato_id = c.id) AS total_pagamentos
        FROM contratos c
        JOIN empresas e ON c.empresa_id = e.id
        $where
        ORDER BY c.data_fim ASC
        LIMIT $offset, $por_pagina";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar empresas para filtro
$empresas = $db->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Contratos - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-ativo {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inativo {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-vencido {
            background-color: #fff3cd;
            color: #856404;
        }
        .dias-restantes {
            font-weight: bold;
        }
        .dias-positivo {
            color: #28a745;
        }
        .dias-negativo {
            color: #dc3545;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        
        <div class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span>Operacional</span>
                    <span>Contratos</span>
                </div>
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['nome_completo']) ?></span>
                    <img src="/Context/IMG/user-default.png" alt="User">
                </div>
                <a href="/PHP/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </header>
            
            <div class="content">
                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                        <?= $_SESSION['mensagem'] ?>
                        <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-contract"></i> Gestão de Contratos</h3>
                        <a href="/PHP/contratos/criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Contrato</a>
                    </div>
                    <div class="card-body">
                        <form method="get" class="filter-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="empresa_id">Empresa:</label>
                                    <select name="empresa_id" id="empresa_id">
                                        <option value="">Todas</option>
                                        <?php foreach ($empresas as $empresa): ?>
                                            <option value="<?= $empresa['id'] ?>" <?= $filtros['empresa_id'] == $empresa['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($empresa['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status:</label>
                                    <select name="status" id="status">
                                        <option value="ativos" <?= $filtros['status'] === 'ativos' ? 'selected' : '' ?>>Ativos</option>
                                        <option value="vencidos" <?= $filtros['status'] === 'vencidos' ? 'selected' : '' ?>>Vencidos</option>
                                        <option value="inativos" <?= $filtros['status'] === 'inativos' ? 'selected' : '' ?>>Inativos</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="busca">Pesquisar:</label>
                                    <input type="text" name="busca" placeholder="Nº contrato ou empresa..." value="<?= htmlspecialchars($filtros['busca']) ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                            <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                        </form>
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nº Contrato</th>
                                        <th>Empresa</th>
                                        <th>Serviço</th>
                                        <th>Início</th>
                                        <th>Término</th>
                                        <th>Dias Restantes</th>
                                        <th>Valor Mensal</th>
                                        <th>Pagamentos</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($contratos)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center">Nenhum contrato encontrado</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($contratos as $contrato): ?>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            $dias_class = '';
                                            
                                            if ($contrato['ativo'] == 0) {
                                                $status_class = 'status-inativo';
                                                $status_text = 'Inativo';
                                            } elseif ($contrato['dias_restantes'] < 0) {
                                                $status_class = 'status-vencido';
                                                $status_text = 'Vencido';
                                            } else {
                                                $status_class = 'status-ativo';
                                                $status_text = 'Ativo';
                                            }
                                            
                                            if ($contrato['dias_restantes'] < 0) {
                                                $dias_class = 'dias-negativo';
                                                $dias_text = abs($contrato['dias_restantes']) . ' dias atrás';
                                            } else {
                                                $dias_class = 'dias-positivo';
                                                $dias_text = $contrato['dias_restantes'] . ' dias';
                                            }
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($contrato['numero_contrato']) ?></td>
                                                <td><?= htmlspecialchars($contrato['empresa_nome']) ?></td>
                                                <td><?= htmlspecialchars($contrato['tipo_servico']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($contrato['data_inicio'])) ?></td>
                                                <td><?= date('d/m/Y', strtotime($contrato['data_fim'])) ?></td>
                                                <td class="dias-restantes <?= $dias_class ?>"><?= $dias_text ?></td>
                                                <td><?= number_format($contrato['valor_mensal'], 2, ',', '.') ?> Kz</td>
                                                <td><?= $contrato['total_pagamentos'] ?></td>
                                                <td><span class="status-badge <?= $status_class ?>"><?= $status_text ?></span></td>
                                                <td class="actions">
                                                    <a href="visualizar.php?id=<?= $contrato['id'] ?>" class="btn btn-sm btn-info" title="Visualizar">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar.php?id=<?= $contrato['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($contrato['ativo'] == 1): ?>
                                                        <a href="desativar.php?id=<?= $contrato['id'] ?>" class="btn btn-sm btn-danger" title="Desativar" onclick="return confirm('Tem certeza que deseja desativar este contrato?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="ativar.php?id=<?= $contrato['id'] ?>" class="btn btn-sm btn-success" title="Ativar" onclick="return confirm('Tem certeza que deseja ativar este contrato?')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
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
                                    <a href="?pagina=<?= $i ?>&empresa_id=<?= $filtros['empresa_id'] ?>&status=<?= $filtros['status'] ?>&busca=<?= urlencode($filtros['busca']) ?>" class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
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