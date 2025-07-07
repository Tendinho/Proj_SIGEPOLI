<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão operacional

$database = new Database();
$db = $database->getConnection();

// Filtros
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
$empresa_id = isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : null;
$contrato_id = isset($_GET['contrato_id']) ? (int)$_GET['contrato_id'] : null;
$status = $_GET['status'] ?? null;

// Construir WHERE
$where = "WHERE p.ano_referencia = :ano";
$params = [':ano' => $ano];

if ($mes) {
    $where .= " AND p.mes_referencia = :mes";
    $params[':mes'] = $mes;
}

if ($empresa_id) {
    $where .= " AND c.empresa_id = :empresa_id";
    $params[':empresa_id'] = $empresa_id;
}

if ($contrato_id) {
    $where .= " AND p.contrato_id = :contrato_id";
    $params[':contrato_id'] = $contrato_id;
}

if ($status) {
    $where .= " AND p.status = :status";
    $params[':status'] = $status;
}

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

// Contar total
$sqlCount = "SELECT COUNT(*) FROM pagamentos_empresas p
    JOIN contratos c ON p.contrato_id = c.id
    JOIN empresas e ON c.empresa_id = e.id
    $where";
$stmt = $db->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar empresas e contratos para filtro
$empresas = $db->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

if ($empresa_id) {
    $contratos = $db->prepare("SELECT id, numero_contrato FROM contratos WHERE empresa_id = ? AND ativo = 1 ORDER BY numero_contrato");
    $contratos->execute([$empresa_id]);
    $contratos = $contratos->fetchAll(PDO::FETCH_ASSOC);
} else {
    $contratos = [];
}

// Buscar pagamentos
$sql = "SELECT p.id, e.nome AS empresa, c.numero_contrato,
               p.mes_referencia, p.ano_referencia, p.valor_pago,
               p.sla_atingido, p.multa_aplicada, p.status,
               p.data_pagamento, p.metodo_pagamento,
               p.comprovante_path, p.observacoes,
               DATEDIFF(p.data_pagamento, CONCAT(p.ano_referencia, '-', LPAD(p.mes_referencia, 2, '0'), '-05')) AS dias_atraso
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

// Calcular totais
$sqlTotais = "SELECT SUM(p.valor_pago) AS total_pago, 
                     SUM(p.multa_aplicada) AS total_multa,
                     COUNT(*) AS total_registros
              FROM pagamentos_empresas p
              JOIN contratos c ON p.contrato_id = c.id
              $where";
$stmt = $db->prepare($sqlTotais);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totais = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Pagamentos a Empresas - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="content-header">
        <h1><i class="fas fa-money-bill-wave"></i> Pagamentos a Empresas</h1>
        <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Pagamento</a>
        <a href="relatorio.php?<?= http_build_query($_GET) ?>" class="btn btn-info" target="_blank">
            <i class="fas fa-file-pdf"></i> Gerar Relatório
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
        </div>
        <div class="card-body">
            <form method="get" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="ano">Ano:</label>
                        <input type="number" name="ano" min="2000" max="2100" value="<?= $ano ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="mes">Mês:</label>
                        <select name="mes">
                            <option value="">Todos</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $mes == $i ? 'selected' : '' ?>>
                                    <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?> - <?= nomeMes($i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="empresa_id">Empresa:</label>
                        <select name="empresa_id" id="empresa_id">
                            <option value="">Todas</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $empresa_id == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="contrato_id">Contrato:</label>
                        <select name="contrato_id" id="contrato_id" <?= empty($contratos) ? 'disabled' : '' ?>>
                            <option value="">Todos</option>
                            <?php foreach ($contratos as $cont): ?>
                                <option value="<?= $cont['id'] ?>" <?= $contrato_id == $cont['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cont['numero_contrato']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select name="status">
                            <option value="">Todos</option>
                            <option value="Pago" <?= $status == 'Pago' ? 'selected' : '' ?>>Pago</option>
                            <option value="Pendente" <?= $status == 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="Atrasado" <?= $status == 'Atrasado' ? 'selected' : '' ?>>Atrasado</option>
                            <option value="Cancelado" <?= $status == 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Pagamentos</h3>
            <div class="totais">
                <span><strong>Total Pago:</strong> <?= number_format($totais['total_pago'] ?? 0, 2, ',', '.') ?> Kz</span>
                <span><strong>Total Multas:</strong> <?= number_format($totais['total_multa'] ?? 0, 2, ',', '.') ?> Kz</span>
                <span><strong>Registros:</strong> <?= $totais['total_registros'] ?? 0 ?></span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($pagamentos)): ?>
                <div class="alert alert-info">Nenhum pagamento encontrado com os filtros aplicados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa</th>
                                <th>Contrato</th>
                                <th>Referência</th>
                                <th>Valor</th>
                                <th>SLA</th>
                                <th>Multa</th>
                                <th>Status</th>
                                <th>Pagamento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagamentos as $pag): ?>
                                <tr>
                                    <td><?= $pag['id'] ?></td>
                                    <td><?= htmlspecialchars($pag['empresa']) ?></td>
                                    <td><?= htmlspecialchars($pag['numero_contrato']) ?></td>
                                    <td><?= str_pad($pag['mes_referencia'], 2, '0', STR_PAD_LEFT) ?>/<?= $pag['ano_referencia'] ?></td>
                                    <td><?= number_format($pag['valor_pago'], 2, ',', '.') ?> Kz</td>
                                    <td>
                                        <span class="badge badge-<?= $pag['sla_atingido'] >= 90 ? 'success' : ($pag['sla_atingido'] >= 80 ? 'warning' : 'danger') ?>">
                                            <?= $pag['sla_atingido'] ?>%
                                        </span>
                                    </td>
                                    <td><?= $pag['multa_aplicada'] > 0 ? number_format($pag['multa_aplicada'], 2, ',', '.') . ' Kz' : '-' ?></td>
                                    <td>
                                        <span class="badge badge-<?= getStatusBadgeClass($pag['status']) ?>">
                                            <?= $pag['status'] ?>
                                            <?php if ($pag['status'] == 'Atrasado' && $pag['dias_atraso'] > 0): ?>
                                                (<?= $pag['dias_atraso'] ?> dias)
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($pag['data_pagamento']): ?>
                                            <?= date('d/m/Y', strtotime($pag['data_pagamento'])) ?><br>
                                            <?= $pag['metodo_pagamento'] ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="visualizar.php?id=<?= $pag['id'] ?>" class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?= $pag['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($pag['comprovante_path']): ?>
                                            <a href="<?= $pag['comprovante_path'] ?>" class="btn btn-sm btn-secondary" 
                                               target="_blank" title="Comprovante">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($pag['status'] == 'Pendente'): ?>
                                            <a href="registrar_pagamento.php?id=<?= $pag['id'] ?>" class="btn btn-sm btn-success" title="Registrar Pagamento">
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
                            <a href="?pagina=<?= $i ?>&ano=<?= $ano ?><?= $mes ? '&mes=' . $mes : '' ?><?= $empresa_id ? '&empresa_id=' . $empresa_id : '' ?><?= $contrato_id ? '&contrato_id=' . $contrato_id : '' ?>&status=<?= $status ?>" 
                               class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="/Context/JS/script.js"></script>
    <script>
        // Atualizar lista de contratos quando selecionar empresa
        document.getElementById('empresa_id').addEventListener('change', function() {
            const empresaId = this.value;
            const contratoSelect = document.getElementById('contrato_id');
            
            if (empresaId) {
                fetch(`../api/get_contratos.php?empresa_id=${empresaId}`)
                    .then(response => response.json())
                    .then(data => {
                        contratoSelect.innerHTML = '<option value="">Todos</option>';
                        data.forEach(contrato => {
                            const option = document.createElement('option');
                            option.value = contrato.id;
                            option.textContent = contrato.numero_contrato;
                            contratoSelect.appendChild(option);
                        });
                        contratoSelect.disabled = false;
                    });
            } else {
                contratoSelect.innerHTML = '<option value="">Todos</option>';
                contratoSelect.disabled = true;
            }
        });
    </script>
</body>
</html>
<?php
function nomeMes($mes) {
    $meses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    return $meses[$mes] ?? '';
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pago': return 'success';
        case 'Pendente': return 'warning';
        case 'Atrasado': return 'danger';
        case 'Cancelado': return 'secondary';
        default: return 'info';
    }
}