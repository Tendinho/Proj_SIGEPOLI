<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(7); // Nível de acesso maior para relatórios

$database = new Database();
$db = $database->getConnection();

// Filtros
$filtros = [
    'empresa_id' => isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : null,
    'tipo_servico' => $_GET['tipo_servico'] ?? null,
    'ano_referencia' => isset($_GET['ano_referencia']) ? (int)$_GET['ano_referencia'] : date('Y'),
    'status_contrato' => $_GET['status_contrato'] ?? 'ativos'
];

// Construir WHERE
$where = "WHERE 1=1";
$params = [];

if ($filtros['empresa_id']) {
    $where .= " AND c.empresa_id = :empresa_id";
    $params[':empresa_id'] = $filtros['empresa_id'];
}

if ($filtros['tipo_servico']) {
    $where .= " AND e.tipo_servico = :tipo_servico";
    $params[':tipo_servico'] = $filtros['tipo_servico'];
}

if ($filtros['ano_referencia']) {
    $where .= " AND (YEAR(c.data_inicio) <= :ano AND YEAR(c.data_fim) >= :ano)";
    $params[':ano'] = $filtros['ano_referencia'];
}

if ($filtros['status_contrato'] === 'ativos') {
    $where .= " AND c.ativo = 1 AND c.data_fim >= CURDATE()";
} elseif ($filtros['status_contrato'] === 'vencidos') {
    $where .= " AND c.data_fim < CURDATE()";
} elseif ($filtros['status_contrato'] === 'inativos') {
    $where .= " AND c.ativo = 0";
}

// Buscar empresas para filtro
$empresas = $db->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar dados para o relatório
$sql = "SELECT 
            e.id AS empresa_id,
            e.nome AS empresa,
            e.tipo_servico,
            c.id AS contrato_id,
            c.numero_contrato,
            c.data_inicio,
            c.data_fim,
            c.valor_mensal,
            (SELECT SUM(valor_pago) FROM pagamentos_empresas p 
             WHERE p.contrato_id = c.id AND p.ano_referencia = :ano) AS total_pago,
            (SELECT COUNT(*) FROM pagamentos_empresas p 
             WHERE p.contrato_id = c.id AND p.ano_referencia = :ano) AS meses_pagos
        FROM contratos c
        JOIN empresas e ON c.empresa_id = e.id
        $where
        ORDER BY e.nome, c.data_inicio";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':ano', $filtros['ano_referencia']);
$stmt->execute();
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totais
$total_contratos = count($dados);
$total_valor_anual = 0;
$total_pago_anual = 0;
$totais_por_tipo = [
    'Limpeza' => ['valor' => 0, 'pago' => 0],
    'Segurança' => ['valor' => 0, 'pago'  => 0],
    'Cafetaria' => ['valor' => 0, 'pago' => 0]
];

foreach ($dados as $linha) {
    $valor_anual = $linha['valor_mensal'] * 12;
    $total_valor_anual += $valor_anual;
    $total_pago_anual += $linha['total_pago'] ?: 0;
    
    if (isset($totais_por_tipo[$linha['tipo_servico']])) {
        $totais_por_tipo[$linha['tipo_servico']]['valor'] += $valor_anual;
        $totais_por_tipo[$linha['tipo_servico']]['pago'] += $linha['total_pago'] ?: 0;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Custos - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/relatorios/custo.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li><a href="/PHP/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    
                    <!-- Menu Relatórios -->
                    <li class="menu-section"><i class="fas fa-chart-bar"></i> Relatórios</li>
                    <li><a href="/PHP/relatorios/index.php"><i class="fas fa-graduation-cap"></i> Home</a></li>
                    <li><a href="/PHP/relatorios/carga_horaria.php"><i class="fas fa-clock"></i> Carga Horária</a></li>
                    <li class="active"><a href="/PHP/relatorios/custo.php"><i class="fas fa-coins"></i> Custos</a></li>
                    <li><a href="/PHP/relatorios/desempenho.php"><i class="fas fa-chart-line"></i> Desempenho</a></li>
                    
                    <!-- Configurações -->
                    <li class="menu-section"><i class="fas fa-cog"></i> Sistema</li>
                    <li><a href="/PHP/perfil.php"><i class="fas fa-user-cog"></i> Perfil</a></li>
                    <?php if ($_SESSION['nivel_acesso'] >= 9): ?>
                        <li><a href="/PHP/usuarios.php"><i class="fas fa-users-cog"></i> Usuários</a></li>
                        <li><a href="/PHP/auditoria.php"><i class="fas fa-clipboard-list"></i> Auditoria</a></li>
                    <?php endif; ?>
                    <li><a href="/PHP/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span>Relatórios</span>
                    <span>Custos</span>
                </div>
                
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></span>
                    <img src="/Context/IMG/user-default.png" alt="User">
                </div>
            </header>
            
            <div class="content">
                <div class="cost-report-container">
                    <div class="report-header">
                        <h1 class="report-title">
                            <i class="fas fa-file-invoice-dollar"></i> Relatório de Custos com Empresas
                        </h1>
                        <div class="report-actions">
                            <a href="javascript:window.print()" class="btn btn-primary no-print">
                                <i class="fas fa-print"></i> Imprimir
                            </a>
                        </div>
                    </div>

                    <div class="report-filters no-print">
                        <h3 class="summary-title"><i class="fas fa-filter"></i> Filtros</h3>
                        <form method="get" class="filter-form">
                            <div class="filter-row">
                                <div class="filter-group">
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
                                
                                <div class="filter-group">
                                    <label for="tipo_servico">Tipo de Serviço:</label>
                                    <select name="tipo_servico" id="tipo_servico">
                                        <option value="">Todos</option>
                                        <option value="Limpeza" <?= $filtros['tipo_servico'] == 'Limpeza' ? 'selected' : '' ?>>Limpeza</option>
                                        <option value="Segurança" <?= $filtros['tipo_servico'] == 'Segurança' ? 'selected' : '' ?>>Segurança</option>
                                        <option value="Cafetaria" <?= $filtros['tipo_servico'] == 'Cafetaria' ? 'selected' : '' ?>>Cafetaria</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="ano_referencia">Ano:</label>
                                    <input type="number" name="ano_referencia" id="ano_referencia" min="2000" max="2100" 
                                           value="<?= $filtros['ano_referencia'] ?>">
                                </div>
                                
                                <div class="filter-group">
                                    <label for="status_contrato">Status Contrato:</label>
                                    <select name="status_contrato" id="status_contrato">
                                        <option value="ativos" <?= $filtros['status_contrato'] == 'ativos' ? 'selected' : '' ?>>Ativos</option>
                                        <option value="vencidos" <?= $filtros['status_contrato'] == 'vencidos' ? 'selected' : '' ?>>Vencidos</option>
                                        <option value="inativos" <?= $filtros['status_contrato'] == 'inativos' ? 'selected' : '' ?>>Inativos</option>
                                        <option value="todos" <?= $filtros['status_contrato'] == 'todos' ? 'selected' : '' ?>>Todos</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="filter-buttons">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Aplicar Filtros
                                </button>
                                <a href="custo.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpar
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="report-summary">
                        <h3 class="summary-title">
                            <i class="fas fa-chart-pie"></i> Resumo do Relatório - Ano <?= $filtros['ano_referencia'] ?>
                        </h3>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <h4>Total de Contratos</h4>
                                <p><?= $total_contratos ?></p>
                            </div>
                            <div class="summary-item">
                                <h4>Valor Anual Previsto</h4>
                                <p><?= number_format($total_valor_anual, 2, ',', '.') ?> Kz</p>
                            </div>
                            <div class="summary-item">
                                <h4>Total Pago</h4>
                                <p><?= number_format($total_pago_anual, 2, ',', '.') ?> Kz</p>
                            </div>
                            <div class="summary-item">
                                <h4>Saldo</h4>
                                <p class="<?= ($total_valor_anual - $total_pago_anual) < 0 ? 'negative-value' : 'positive-value' ?>">
                                    <?= number_format($total_valor_anual - $total_pago_anual, 2, ',', '.') ?> Kz
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="charts-container no-print">
                        <div class="chart-box">
                            <canvas id="chartPorTipo"></canvas>
                        </div>
                        <div class="chart-box">
                            <canvas id="chartPagamento"></canvas>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-table"></i> Detalhes dos Custos</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($dados)): ?>
                                <div class="alert alert-info">Nenhum dado encontrado com os filtros aplicados.</div>
                            <?php else: ?>
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Empresa</th>
                                            <th>Tipo Serviço</th>
                                            <th>Contrato</th>
                                            <th>Período</th>
                                            <th>Valor Mensal</th>
                                            <th>Valor Anual</th>
                                            <th>Meses Pagos</th>
                                            <th>Total Pago</th>
                                            <th>Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $empresa_atual = null;
                                        foreach ($dados as $linha): 
                                            $valor_anual = $linha['valor_mensal'] * 12;
                                            $total_pago = $linha['total_pago'] ?: 0;
                                            $saldo = $valor_anual - $total_pago;
                                            
                                            if ($empresa_atual !== null && $empresa_atual != $linha['empresa_id']) {
                                                // Calcular totais da empresa anterior
                                                $total_empresa_valor = array_sum(array_column(array_filter($dados, function($d) use ($empresa_atual) {
                                                    return $d['empresa_id'] == $empresa_atual;
                                                }), 'valor_mensal')) * 12;
                                                
                                                $total_empresa_pago = array_sum(array_column(array_filter($dados, function($d) use ($empresa_atual) {
                                                    return $d['empresa_id'] == $empresa_atual;
                                                }), 'total_pago'));
                                                
                                                echo '<tr class="total-row">
                                                    <td colspan="4">Total '.htmlspecialchars($empresa_atual).'</td>
                                                    <td>'.number_format(array_sum(array_column(array_filter($dados, function($d) use ($empresa_atual) {
                                                        return $d['empresa_id'] == $empresa_atual;
                                                    }), 'valor_mensal')), 2, ',', '.').'</td>
                                                    <td>'.number_format($total_empresa_valor, 2, ',', '.').'</td>
                                                    <td></td>
                                                    <td>'.number_format($total_empresa_pago, 2, ',', '.').'</td>
                                                    <td class="'.($total_empresa_valor - $total_empresa_pago < 0 ? 'negative-value' : 'positive-value').'">
                                                        '.number_format($total_empresa_valor - $total_empresa_pago, 2, ',', '.').'
                                                    </td>
                                                </tr>';
                                            }
                                            $empresa_atual = $linha['empresa_id'];
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($linha['empresa']) ?></td>
                                                <td><?= htmlspecialchars($linha['tipo_servico']) ?></td>
                                                <td><?= htmlspecialchars($linha['numero_contrato']) ?></td>
                                                <td>
                                                    <?= date('d/m/Y', strtotime($linha['data_inicio'])) ?> - 
                                                    <?= date('d/m/Y', strtotime($linha['data_fim'])) ?>
                                                </td>
                                                <td><?= number_format($linha['valor_mensal'], 2, ',', '.') ?> Kz</td>
                                                <td><?= number_format($valor_anual, 2, ',', '.') ?> Kz</td>
                                                <td><?= $linha['meses_pagos'] ?>/12</td>
                                                <td><?= number_format($total_pago, 2, ',', '.') ?> Kz</td>
                                                <td class="<?= $saldo < 0 ? 'negative-value' : 'positive-value' ?>">
                                                    <?= number_format($saldo, 2, ',', '.') ?> Kz
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <!-- Último total por empresa -->
                                        <?php if (!empty($dados)): ?>
                                            <?php
                                            $total_empresa_valor = array_sum(array_column(array_filter($dados, function($d) use ($empresa_atual) {
                                                return $d['empresa_id'] == $empresa_atual;
                                            }), 'valor_mensal')) * 12;
                                            
                                            $total_empresa_pago = array_sum(array_column(array_filter($dados, function($d) use ($empresa_atual) {
                                                return $d['empresa_id'] == $empresa_atual;
                                            }), 'total_pago'));
                                            ?>
                                            <tr class="total-row">
                                                <td colspan="4">Total <?= htmlspecialchars($empresa_atual) ?></td>
                                                <td><?= number_format(array_sum(array_column(array_filter($dados, function($d) use ($empresa_atual) {
                                                    return $d['empresa_id'] == $empresa_atual;
                                                }), 'valor_mensal')), 2, ',', '.') ?></td>
                                                <td><?= number_format($total_empresa_valor, 2, ',', '.') ?></td>
                                                <td></td>
                                                <td><?= number_format($total_empresa_pago, 2, ',', '.') ?></td>
                                                <td class="<?= ($total_empresa_valor - $total_empresa_pago) < 0 ? 'negative-value' : 'positive-value' ?>">
                                                    <?= number_format($total_empresa_valor - $total_empresa_pago, 2, ',', '.') ?>
                                                </td>
                                            </tr>
                                            
                                            <tr class="total-row" style="background-color: var(--secondary-color); color: white;">
                                                <td colspan="5">Total Geral</td>
                                                <td><?= number_format($total_valor_anual, 2, ',', '.') ?> Kz</td>
                                                <td></td>
                                                <td><?= number_format($total_pago_anual, 2, ',', '.') ?> Kz</td>
                                                <td class="<?= ($total_valor_anual - $total_pago_anual) < 0 ? 'negative-value' : 'positive-value' ?>">
                                                    <?= number_format($total_valor_anual - $total_pago_anual, 2, ',', '.') ?> Kz
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfico por tipo de serviço
        const ctxTipo = document.getElementById('chartPorTipo').getContext('2d');
        new Chart(ctxTipo, {
            type: 'pie',
            data: {
                labels: ['Limpeza', 'Segurança', 'Cafetaria'],
                datasets: [{
                    data: [
                        <?= $totais_por_tipo['Limpeza']['valor'] ?>,
                        <?= $totais_por_tipo['Segurança']['valor'] ?>,
                        <?= $totais_por_tipo['Cafetaria']['valor'] ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 206, 86, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribuição de Custos por Tipo de Serviço',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('pt-AO', { 
                                    style: 'currency', 
                                    currency: 'AOA' 
                                }).format(context.raw);
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de pagamentos
        const ctxPagamento = document.getElementById('chartPagamento').getContext('2d');
        new Chart(ctxPagamento, {
            type: 'bar',
            data: {
                labels: ['Previsto', 'Pago', 'Saldo'],
                datasets: [{
                    label: 'Valores em Kz',
                    data: [
                        <?= $total_valor_anual ?>,
                        <?= $total_pago_anual ?>,
                        <?= $total_valor_anual - $total_pago_anual ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        <?= ($total_valor_anual - $total_pago_anual) < 0 ? "'rgba(255, 99, 132, 0.7)'" : "'rgba(54, 162, 235, 0.7)'" ?>
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        <?= ($total_valor_anual - $total_pago_anual) < 0 ? "'rgba(255, 99, 132, 1)'" : "'rgba(54, 162, 235, 1)'" ?>
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Comparação entre Valores Previstos e Pagos',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('pt-AO', { 
                                    style: 'currency', 
                                    currency: 'AOA' 
                                }).format(context.raw);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('pt-AO', { 
                                    style: 'currency', 
                                    currency: 'AOA' 
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });

        // Validação de datas
        document.getElementById('ano_referencia').addEventListener('change', function() {
            if (this.value < 2000 || this.value > 2100) {
                alert('Por favor, insira um ano entre 2000 e 2100');
                this.value = new Date().getFullYear();
            }
        });
    </script>
    <script src="/Context/JS/script.js"></script>
</body>
</html>