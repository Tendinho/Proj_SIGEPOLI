<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão operacional

$database = new Database();
$db = $database->getConnection();

// Verificar se foi passado um contrato_id
$contrato_id = isset($_GET['contrato_id']) ? (int)$_GET['contrato_id'] : null;

// Paginação e filtros
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

$filtros = [
    'contrato_id' => $contrato_id,
    'status' => $_GET['status'] ?? '',
    'mes_referencia' => isset($_GET['mes_referencia']) ? (int)$_GET['mes_referencia'] : null,
    'ano_referencia' => isset($_GET['ano_referencia']) ? (int)$_GET['ano_referencia'] : null,
    'data_inicio' => $_GET['data_inicio'] ?? null,
    'data_fim' => $_GET['data_fim'] ?? null
];

// Construir WHERE
$where = "WHERE 1=1";
$params = [];

if ($filtros['contrato_id']) {
    $where .= " AND p.contrato_id = :contrato_id";
    $params[':contrato_id'] = $filtros['contrato_id'];
}

if (!empty($filtros['status'])) {
    $where .= " AND p.status = :status";
    $params[':status'] = $filtros['status'];
}

if ($filtros['mes_referencia']) {
    $where .= " AND p.mes_referencia = :mes_referencia";
    $params[':mes_referencia'] = $filtros['mes_referencia'];
}

if ($filtros['ano_referencia']) {
    $where .= " AND p.ano_referencia = :ano_referencia";
    $params[':ano_referencia'] = $filtros['ano_referencia'];
}

if ($filtros['data_inicio']) {
    $where .= " AND p.data_pagamento >= :data_inicio";
    $params[':data_inicio'] = $filtros['data_inicio'];
}

if ($filtros['data_fim']) {
    $where .= " AND p.data_pagamento <= :data_fim";
    $params[':data_fim'] = $filtros['data_fim'];
}

// Contar total
$sqlCount = "SELECT COUNT(*) FROM pagamentos_empresas p $where";
$stmt = $db->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar contratos para filtro
$contratos = $db->query("SELECT id, numero_contrato FROM contratos WHERE ativo = 1 ORDER BY numero_contrato")->fetchAll();

// Buscar pagamentos
$sql = "SELECT p.id, p.mes_referencia, p.ano_referencia, p.valor_pago, 
               p.data_pagamento, p.sla_atingido, p.multa_aplicada, p.status,
               c.numero_contrato, e.nome AS empresa,
               DATE_FORMAT(p.data_pagamento, '%d/%m/%Y') AS data_pagamento_formatada
        FROM pagamentos_empresas p
        JOIN contratos c ON p.contrato_id = c.id
        JOIN empresas e ON c.empresa_id = e.id
        $where
        ORDER BY p.ano_referencia DESC, p.mes_referencia DESC
        LIMIT $offset, $por_pagina";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar informações do contrato se estiver filtrado
$contrato_info = null;
if ($filtros['contrato_id']) {
    $stmt = $db->prepare("SELECT c.*, e.nome AS empresa FROM contratos c JOIN empresas e ON c.empresa_id = e.id WHERE c.id = ?");
    $stmt->execute([$filtros['contrato_id']]);
    $contrato_info = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Pagamentos - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="content-header">
        <h1>
            <i class="fas fa-money-bill-wave"></i> Gestão de Pagamentos
            <?php if ($contrato_info): ?>
                <small> - Contrato: <?= htmlspecialchars($contrato_info['numero_contrato']) ?> (<?= htmlspecialchars($contrato_info['empresa']) ?>)</small>
            <?php endif; ?>
        </h1>
        <div class="header-actions">
            <a href="/PHP/index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Menu Principal</a>
            <a href="registrar.php<?= $contrato_id ? '?contrato_id='.$contrato_id : '' ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Pagamento</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
        </div>
        <div class="card-body">
            <form method="get" class="filter-form">
                <?php if (!$contrato_id): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contrato_id">Contrato:</label>
                            <select name="contrato_id">
                                <option value="">Todos</option>
                                <?php foreach ($contratos as $contrato): ?>
                                    <option value="<?= $contrato['id'] ?>" <?= $filtros['contrato_id'] == $contrato['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($contrato['numero_contrato']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="contrato_id" value="<?= $contrato_id ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select name="status">
                            <option value="">Todos</option>
                            <option value="Pendente" <?= $filtros['status'] == 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="Pago" <?= $filtros['status'] == 'Pago' ? 'selected' : '' ?>>Pago</option>
                            <option value="Atrasado" <?= $filtros['status'] == 'Atrasado' ? 'selected' : '' ?>>Atrasado</option>
                            <option value="Cancelado" <?= $filtros['status'] == 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="mes_referencia">Mês:</label>
                        <select name="mes_referencia">
                            <option value="">Todos</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $filtros['mes_referencia'] == $i ? 'selected' : '' ?>>
                                    <?= DateTime::createFromFormat('!m', $i)->format('F') ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ano_referencia">Ano:</label>
                        <input type="number" name="ano_referencia" min="2000" max="2100" value="<?= $filtros['ano_referencia'] ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inicio">Data Pagamento Início:</label>
                        <input type="date" name="data_inicio" value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_fim">Data Pagamento Fim:</label>
                        <input type="date" name="data_fim" value="<?= htmlspecialchars($filtros['data_fim']) ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                <a href="index.php<?= $contrato_id ? '?contrato_id='.$contrato_id : '' ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Lista de Pagamentos</h3>
            <?php if ($contrato_info): ?>
                <div class="contract-info">
                    <p><strong>Empresa:</strong> <?= htmlspecialchars($contrato_info['empresa']) ?></p>
                    <p><strong>Valor Mensal:</strong> <?= number_format($contrato_info['valor_mensal'], 2, ',', '.') ?> Kz</p>
                    <p><strong>SLA Meta:</strong> <?= $contrato_info['sla_meta'] ?>%</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($pagamentos)): ?>
                <div class="alert alert-info">Nenhum pagamento encontrado com os filtros aplicados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php if (!$contrato_id): ?>
                                    <th>Contrato</th>
                                    <th>Empresa</th>
                                <?php endif; ?>
                                <th>Referência</th>
                                <th>Valor Pago</th>
                                <th>Data Pagamento</th>
                                <th>SLA Atingido</th>
                                <th>Multa</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagamentos as $pagamento): ?>
                                <tr>
                                    <?php if (!$contrato_id): ?>
                                        <td><?= htmlspecialchars($pagamento['numero_contrato']) ?></td>
                                        <td><?= htmlspecialchars($pagamento['empresa']) ?></td>
                                    <?php endif; ?>
                                    <td><?= DateTime::createFromFormat('!m', $pagamento['mes_referencia'])->format('m/Y') ?></td>
                                    <td><?= number_format($pagamento['valor_pago'], 2, ',', '.') ?> Kz</td>
                                    <td><?= $pagamento['data_pagamento'] ? $pagamento['data_pagamento_formatada'] : '--' ?></td>
                                    <td><?= $pagamento['sla_atingido'] ? $pagamento['sla_atingido'].'%' : '--' ?></td>
                                    <td><?= $pagamento['multa_aplicada'] > 0 ? number_format($pagamento['multa_aplicada'], 2, ',', '.').' Kz' : '--' ?></td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $pagamento['status'] == 'Pago' ? 'success' : 
                                            ($pagamento['status'] == 'Pendente' ? 'warning' : 
                                            ($pagamento['status'] == 'Atrasado' ? 'danger' : 'secondary')) ?>">
                                            <?= $pagamento['status'] ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="visualizar.php?id=<?= $pagamento['id'] ?>" class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?= $pagamento['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($pagamento['status'] == 'Pendente' || $pagamento['status'] == 'Atrasado'): ?>
                                            <a href="registrar_pagamento.php?id=<?= $pagamento['id'] ?>" class="btn btn-sm btn-primary" title="Registrar Pagamento">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($pagamento['status'] != 'Cancelado'): ?>
                                            <a href="cancelar.php?id=<?= $pagamento['id'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Tem certeza que deseja cancelar este pagamento?')" title="Cancelar">
                                                <i class="fas fa-ban"></i>
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
                            <a href="?pagina=<?= $i ?><?= $filtros['contrato_id'] ? '&contrato_id=' . $filtros['contrato_id'] : '' ?><?= $filtros['status'] ? '&status=' . urlencode($filtros['status']) : '' ?><?= $filtros['mes_referencia'] ? '&mes_referencia=' . $filtros['mes_referencia'] : '' ?><?= $filtros['ano_referencia'] ? '&ano_referencia=' . $filtros['ano_referencia'] : '' ?><?= $filtros['data_inicio'] ? '&data_inicio=' . $filtros['data_inicio'] : '' ?><?= $filtros['data_fim'] ? '&data_fim=' . $filtros['data_fim'] : '' ?>"
                               class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="/Context/JS/script.js"></script>
</body>
</html>