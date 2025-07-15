<?php
// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'sigepoli';
$username = 'root';
$password = '2001';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("set names utf8mb4");
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Variáveis globais
$erro = '';
$sucesso = '';
$empresas = [];
$contratos = [];
$contratoInfo = null;

// Buscar todas as empresas ativas
$empresas = $db->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Processar seleção de empresa
if (isset($_GET['empresa_id']) && $_GET['empresa_id'] != '') {
    $empresa_id = (int)$_GET['empresa_id'];
    
    // Buscar contratos da empresa selecionada
    $stmt = $db->prepare("SELECT id, numero_contrato FROM contratos 
                         WHERE empresa_id = ? AND ativo = 1 ORDER BY numero_contrato");
    $stmt->execute([$empresa_id]);
    $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Processar seleção de contrato
if (isset($_GET['contrato_id']) && $_GET['contrato_id'] != '') {
    $contrato_id = (int)$_GET['contrato_id'];
    
    // Buscar informações completas do contrato
    $stmt = $db->prepare("SELECT 
                            c.id, 
                            c.numero_contrato, 
                            c.valor_mensal, 
                            c.data_inicio, 
                            c.data_fim,
                            c.sla_meta,
                            c.multa_sla,
                            e.nome AS empresa_nome,
                            e.tipo_servico
                          FROM contratos c
                          JOIN empresas e ON c.empresa_id = e.id
                          WHERE c.id = ?");
    $stmt->execute([$contrato_id]);
    $contratoInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Processar submissão do formulário de pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pagamento'])) {
    try {
        // Validar e sanitizar inputs
        $contrato_id = (int)$_POST['contrato_id'];
        $mes_referencia = (int)$_POST['mes_referencia'];
        $ano_referencia = (int)$_POST['ano_referencia'];
        $valor_pago = (float)str_replace(',', '.', $_POST['valor_pago']);
        $metodo_pagamento = $_POST['metodo_pagamento'];
        $sla_atingido = (int)$_POST['sla_atingido'];
        $observacoes = $_POST['observacoes'] ?? '';

        // Validações
        if ($mes_referencia < 1 || $mes_referencia > 12) {
            throw new Exception("Mês de referência inválido");
        }
        
        if ($ano_referencia < 2000 || $ano_referencia > 2100) {
            throw new Exception("Ano de referência inválido");
        }
        
        if ($valor_pago <= 0) {
            throw new Exception("Valor do pagamento deve ser positivo");
        }
        
        if (empty($metodo_pagamento)) {
            throw new Exception("Selecione um método de pagamento");
        }

        // Verificar se já existe pagamento para este período
        $stmt = $db->prepare("SELECT id FROM pagamentos_empresas 
                            WHERE contrato_id = ? AND mes_referencia = ? AND ano_referencia = ?");
        $stmt->execute([$contrato_id, $mes_referencia, $ano_referencia]);
        
        if ($stmt->fetch()) {
            throw new Exception("Já existe um pagamento para este contrato no período selecionado");
        }

        // Registrar o pagamento
        $stmt = $db->prepare("INSERT INTO pagamentos_empresas 
                            (contrato_id, mes_referencia, ano_referencia, valor_pago, 
                            metodo_pagamento, sla_atingido, observacoes, status, data_pagamento)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pago', NOW())");
        $stmt->execute([
            $contrato_id, 
            $mes_referencia, 
            $ano_referencia, 
            $valor_pago,
            $metodo_pagamento,
            $sla_atingido,
            $observacoes
        ]);

        $sucesso = "Pagamento registrado com sucesso!";
        
        // Limpar seleção após sucesso
        $contratoInfo = null;
        unset($_GET['contrato_id']);

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Função auxiliar para nome do mês
function getMonthName($monthNumber) {
    $months = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    return $months[$monthNumber] ?? '';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registrar Pagamento - SIGEPOLI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        h1 {
            margin: 0;
            color: #333;
        }
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-success {
            background-color: #28a745;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .contrato-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .contrato-info h3 {
            margin-top: 0;
            color: #3498db;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        .info-value {
            flex: 1;
        }
        small.text-muted {
            color: #6c757d;
            font-size: 0.875em;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-money-bill-wave"></i> Registrar Pagamento</h1>
            <a href="pagamentos.php" class="btn btn-secondary">Voltar</a>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <div class="card">
            <!-- Formulário de seleção de empresa e contrato -->
            <form method="get" action="">
                <div class="form-group">
                    <label for="empresa_id">Empresa:</label>
                    <select name="empresa_id" id="empresa_id" required>
                        <option value="">Selecione uma empresa</option>
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?= $emp['id'] ?>" 
                                <?= (isset($_GET['empresa_id']) && $_GET['empresa_id'] == $emp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!empty($contratos)): ?>
                    <div class="form-group">
                        <label for="contrato_id">Contrato:</label>
                        <select name="contrato_id" id="contrato_id" required>
                            <option value="">Selecione um contrato</option>
                            <?php foreach ($contratos as $cont): ?>
                                <option value="<?= $cont['id'] ?>" 
                                    <?= (isset($_GET['contrato_id']) && $_GET['contrato_id'] == $cont['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cont['numero_contrato']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Selecionar</button>
            </form>

            <!-- Formulário de pagamento (só aparece quando contrato é selecionado) -->
            <?php if ($contratoInfo): ?>
                <hr>
                <form method="post" id="formPagamento">
                    <div class="contrato-info">
                        <h3><i class="fas fa-file-contract"></i> Detalhes do Contrato</h3>
                        <div class="info-row">
                            <div class="info-label">Empresa:</div>
                            <div class="info-value"><?= htmlspecialchars($contratoInfo['empresa_nome']) ?> (<?= htmlspecialchars($contratoInfo['tipo_servico']) ?>)</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nº Contrato:</div>
                            <div class="info-value"><?= htmlspecialchars($contratoInfo['numero_contrato']) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Valor Mensal:</div>
                            <div class="info-value">Kz <?= number_format($contratoInfo['valor_mensal'], 2, ',', '.') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Vigência:</div>
                            <div class="info-value">
                                <?= date('d/m/Y', strtotime($contratoInfo['data_inicio'])) ?> 
                                até 
                                <?= date('d/m/Y', strtotime($contratoInfo['data_fim'])) ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Meta SLA:</div>
                            <div class="info-value"><?= $contratoInfo['sla_meta'] ?>%</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Multa por SLA:</div>
                            <div class="info-value">Kz <?= number_format($contratoInfo['multa_sla'], 2, ',', '.') ?></div>
                        </div>
                    </div>

                    <input type="hidden" name="contrato_id" value="<?= $contratoInfo['id'] ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="mes_referencia">Mês Referência:</label>
                            <select name="mes_referencia" required>
                                <option value="">Selecione</option>
                                <?php 
                                $currentMonth = date('n');
                                for ($i = 1; $i <= 12; $i++): 
                                    $selected = ($i == $currentMonth) ? 'selected' : '';
                                ?>
                                    <option value="<?= $i ?>" <?= $selected ?>>
                                        <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?> - <?= getMonthName($i) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="ano_referencia">Ano Referência:</label>
                            <input type="number" name="ano_referencia" min="2000" max="2100" 
                                   value="<?= date('Y') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="valor_pago">Valor Pago (Kz):</label>
                        <input type="text" name="valor_pago" placeholder="0,00" 
                               value="<?= number_format($contratoInfo['valor_mensal'], 2, ',', '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="metodo_pagamento">Método de Pagamento:</label>
                        <select name="metodo_pagamento" required>
                            <option value="">Selecione</option>
                            <option value="Transferência">Transferência Bancária</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="MBWay">MBWay</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sla_atingido">SLA Atingido (%):</label>
                        <input type="number" name="sla_atingido" min="0" max="100" value="100" required>
                        <small class="text-muted">Percentual de atendimento às metas contratadas</small>
                    </div>

                    <div class="form-group">
                        <label for="observacoes">Observações:</label>
                        <textarea name="observacoes" rows="3"></textarea>
                    </div>

                    <button type="submit" name="registrar_pagamento" class="btn btn-success">
                        <i class="fas fa-save"></i> Registrar Pagamento
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Formatar valor monetário
        document.querySelector('input[name="valor_pago"]')?.addEventListener('blur', function() {
            let value = this.value.replace(/[^\d,]/g, '').replace(',', '.');
            value = parseFloat(value) || 0;
            this.value = value.toFixed(2).replace('.', ',');
        });

        // Calcular multa se SLA for abaixo do mínimo
        document.querySelector('input[name="sla_atingido"]')?.addEventListener('change', function() {
            const slaAtingido = parseInt(this.value);
            const slaMeta = <?= $contratoInfo['sla_meta'] ?? 90 ?>;
            
            if (slaAtingido < slaMeta) {
                const multa = <?= $contratoInfo['multa_sla'] ?? 0 ?> * (slaMeta - slaAtingido) / 100;
                alert(`Atenção: SLA abaixo da meta (${slaMeta}%). Multa aplicada: Kz ${multa.toFixed(2).replace('.', ',')}`);
            }
        });

        // Submeter form quando selecionar empresa (para carregar contratos)
        document.getElementById('empresa_id')?.addEventListener('change', function() {
            if (this.value) {
                this.form.submit();
            }
        });
    </script>
</body>
</html>