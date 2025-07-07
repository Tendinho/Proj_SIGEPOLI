<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão operacional

$database = new Database();
$db = $database->getConnection();

// Paginação e filtros
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'empresa_id' => isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : null,
    'status' => $_GET['status'] ?? 'ativos',
    'data_inicio' => $_GET['data_inicio'] ?? null,
    'data_fim' => $_GET['data_fim'] ?? null
];

// Construir WHERE
$where = "WHERE 1=1";
$params = [];

if (!empty($filtros['busca'])) {
    $where .= " AND (c.numero_contrato LIKE :busca OR e.nome LIKE :busca)";
    $params[':busca'] = "%{$filtros['busca']}%";
}

if ($filtros['empresa_id']) {
    $where .= " AND c.empresa_id = :empresa_id";
    $params[':empresa_id'] = $filtros['empresa_id'];
}

if ($filtros['status'] === 'ativos') {
    $where .= " AND c.ativo = 1 AND c.data_fim >= CURDATE()";
} elseif ($filtros['status'] === 'vencidos') {
    $where .= " AND c.data_fim < CURDATE()";
} elseif ($filtros['status'] === 'inativos') {
    $where .= " AND c.ativo = 0";
}

if ($filtros['data_inicio']) {
    $where .= " AND c.data_inicio >= :data_inicio";
    $params[':data_inicio'] = $filtros['data_inicio'];
}

if ($filtros['data_fim']) {
    $where .= " AND c.data_fim <= :data_fim";
    $params[':data_fim'] = $filtros['data_fim'];
}

// Contar total
$sqlCount = "SELECT COUNT(*) FROM contratos c JOIN empresas e ON c.empresa_id = e.id $where";
$stmt = $db->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar empresas para filtro
$empresas = $db->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar contratos
$sql = "SELECT c.id, c.numero_contrato, c.data_inicio, c.data_fim, 
               c.valor_mensal, c.sla_meta, c.ativo,
               e.nome AS empresa, e.tipo_servico,
               DATEDIFF(c.data_fim, CURDATE()) AS dias_restantes,
               (SELECT COUNT(*) FROM pagamentos_empresas p WHERE p.contrato_id = c.id) AS total_pagamentos
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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Contratos - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="content-header">
        <h1><i class="fas fa-file-contract"></i> Gestão de Contratos</h1>
        <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Contrato</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
        </div>
        <div class="card-body">
            <form method="get" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="busca">Pesquisar:</label>
                        <input type="text" name="busca" placeholder="Nº Contrato ou Empresa..." value="<?= htmlspecialchars($filtros['busca']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="empresa_id">Empresa:</label>
                        <select name="empresa_id">
                            <option value="">Todas</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $filtros['empresa_id'] == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select name="status">
                            <option value="ativos" <?= $filtros['status'] == 'ativos' ? 'selected' : '' ?>>Ativos</option>
                            <option value="vencidos" <?= $filtros['status'] == 'vencidos' ? 'selected' : '' ?>>Vencidos</option>
                            <option value="inativos" <?= $filtros['status'] == 'inativos' ? 'selected' : '' ?>>Inativos</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inicio">Data Início:</label>
                        <input type="date" name="data_inicio" value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_fim">Data Fim:</label>
                        <input type="date" name="data_fim" value="<?= htmlspecialchars($filtros['data_fim']) ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Lista de Contratos</h3>
        </div>
        <div class="card-body">
            <?php if (empty($contratos)): ?>
                <div class="alert alert-info">Nenhum contrato encontrado com os filtros aplicados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nº Contrato</th>
                                <th>Empresa</th>
                                <th>Tipo Serviço</th>
                                <th>Período</th>
                                <th>Valor Mensal</th>
                                <th>SLA Meta</th>
                                <th>Pagamentos</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contratos as $contrato): ?>
                                <tr>
                                    <td><?= htmlspecialchars($contrato['numero_contrato']) ?></td>
                                    <td><?= htmlspecialchars($contrato['empresa']) ?></td>
                                    <td><?= htmlspecialchars($contrato['tipo_servico']) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($contrato['data_inicio'])) ?> - 
                                        <?= date('d/m/Y', strtotime($contrato['data_fim'])) ?>
                                        <?php if ($contrato['dias_restantes'] < 30 && $contrato['dias_restantes'] > 0): ?>
                                            <span class="badge badge-warning"><?= $contrato['dias_restantes'] ?> dias</span>
                                        <?php elseif ($contrato['dias_restantes'] <= 0): ?>
                                            <span class="badge badge-danger">Vencido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($contrato['valor_mensal'], 2, ',', '.') ?> Kz</td>
                                    <td><?= $contrato['sla_meta'] ?>%</td>
                                    <td><?= $contrato['total_pagamentos'] ?></td>
                                    <td>
                                        <?php if ($contrato['ativo'] && $contrato['dias_restantes'] > 0): ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php elseif ($contrato['ativo'] && $contrato['dias_restantes'] <= 0): ?>
                                            <span class="badge badge-warning">Vencido</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="visualizar.php?id=<?= $contrato['id'] ?>" class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?= $contrato['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../pagamentos_empresas/index.php?contrato_id=<?= $contrato['id'] ?>" class="btn btn-sm btn-primary" title="Pagamentos">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                        <?php if ($contrato['ativo']): ?>
                                            <a href="excluir.php?id=<?= $contrato['id'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Tem certeza que deseja inativar este contrato?')" title="Inativar">
                                                <i class="fas fa-times-circle"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="ativar.php?id=<?= $contrato['id'] ?>" class="btn btn-sm btn-success" 
                                               onclick="return confirm('Tem certeza que deseja ativar este contrato?')" title="Ativar">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <a href="?pagina=<?= $i ?><?= !empty($filtros['busca']) ? '&busca=' . urlencode($filtros['busca']) : '' ?><?= $filtros['empresa_id'] ? '&empresa_id=' . $filtros['empresa_id'] : '' ?>&status=<?= $filtros['status'] ?><?= $filtros['data_inicio'] ? '&data_inicio=' . $filtros['data_inicio'] : '' ?><?= $filtros['data_fim'] ? '&data_fim=' . $filtros['data_fim'] : '' ?>"
                               class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>