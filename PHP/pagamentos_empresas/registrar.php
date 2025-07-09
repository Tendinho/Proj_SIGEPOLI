<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão operacional

$database = new Database();
$db = $database->getConnection();

// Inicializar variáveis
$erro = '';
$sucesso = '';
$dados_formulario = $_SESSION['dados_formulario'] ?? [];
unset($_SESSION['dados_formulario']);

// Verificar se a coluna comprovante_numero existe
$coluna_existe = false;
try {
    $coluna_existe = $db->query("
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'pagamentos_empresas' 
        AND COLUMN_NAME = 'comprovante_numero'
    ")->fetchColumn() > 0;
} catch (PDOException $e) {
    error_log("Erro ao verificar coluna comprovante_numero: " . $e->getMessage());
}

// Gerar número de comprovante
$proximo_comprovante = 'PAG' . date('Ym') . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);

// Buscar contratos ativos com empresas
$contratos = [];
try {
    $contratos = $db->query("
        SELECT c.id, c.numero_contrato, e.nome AS empresa_nome, e.tipo_servico
        FROM contratos c
        JOIN empresas e ON c.empresa_id = e.id
        WHERE c.ativo = 1
        ORDER BY e.nome, c.numero_contrato
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar contratos: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $contrato_id = (int)($_POST['contrato_id'] ?? 0);
        $valor_pago = (float)str_replace(['.', ','], ['', '.'], $_POST['valor_pago'] ?? '0');
        $data_pagamento = $_POST['data_pagamento'] ?? '';
        $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';
        $observacoes = $_POST['observacoes'] ?? null;
        $comprovante_numero = $proximo_comprovante;
        
        // Armazenar dados do formulário para caso de erro
        $_SESSION['dados_formulario'] = $_POST;

        // Validar dados
        if ($contrato_id <= 0) {
            throw new Exception("Selecione um contrato válido");
        }

        if ($valor_pago <= 0) {
            throw new Exception("O valor do pagamento deve ser maior que zero");
        }

        if (empty($data_pagamento)) {
            throw new Exception("Informe a data do pagamento");
        }

        if (empty($metodo_pagamento)) {
            throw new Exception("Selecione o método de pagamento");
        }

        // Verificar se contrato existe e está ativo
        $stmt = $db->prepare("SELECT id, valor_mensal FROM contratos WHERE id = ? AND ativo = 1");
        $stmt->execute([$contrato_id]);
        $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contrato) {
            throw new Exception("Contrato inválido ou inativo");
        }

        // Obter mês/ano atual para referência
        $mes_referencia = date('m');
        $ano_referencia = date('Y');

        // Verificar se já existe pagamento para este contrato no mês/ano
        $stmt = $db->prepare("
            SELECT id FROM pagamentos_empresas 
            WHERE contrato_id = ? AND mes_referencia = ? AND ano_referencia = ?
        ");
        $stmt->execute([$contrato_id, $mes_referencia, $ano_referencia]);
        
        if ($stmt->fetch()) {
            throw new Exception("Já existe um pagamento registrado para este contrato no mês corrente");
        }

        // Calcular SLA (100% se pagou valor completo)
        $sla_atingido = ($valor_pago >= $contrato['valor_mensal']) ? 100 : 90;

        // Preparar query com ou sem comprovante_numero
        if ($coluna_existe) {
            $sql = "INSERT INTO pagamentos_empresas (
                contrato_id, mes_referencia, ano_referencia, valor_pago, 
                data_pagamento, metodo_pagamento, comprovante_numero, 
                observacoes, status, sla_atingido
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pago', ?)";
            
            $params = [
                $contrato_id, $mes_referencia, $ano_referencia, $valor_pago,
                $data_pagamento, $metodo_pagamento, $comprovante_numero,
                $observacoes, $sla_atingido
            ];
        } else {
            $sql = "INSERT INTO pagamentos_empresas (
                contrato_id, mes_referencia, ano_referencia, valor_pago, 
                data_pagamento, metodo_pagamento, 
                observacoes, status, sla_atingido
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pago', ?)";
            
            $params = [
                $contrato_id, $mes_referencia, $ano_referencia, $valor_pago,
                $data_pagamento, $metodo_pagamento,
                $observacoes, $sla_atingido
            ];
        }

        $stmt = $db->prepare($sql);
        if (!$stmt->execute($params)) {
            throw new Exception("Erro ao registrar pagamento no banco de dados");
        }

        $pagamento_id = $db->lastInsertId();
        registrarAuditoria('Registro de pagamento', 'pagamentos_empresas', $pagamento_id, json_encode([
            'contrato_id' => $contrato_id,
            'valor_pago' => $valor_pago,
            'data_pagamento' => $data_pagamento
        ]));

        $_SESSION['mensagem'] = "Pagamento registrado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $erro = $e->getMessage();
        error_log("Erro ao registrar pagamento: " . $erro);
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pagamento - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-money-bill-wave"></i> Registrar Pagamento</h1>
            <a href="/PHP/pagamentos_empresas/pagamentos.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="contrato_id">Contrato *</label>
                        <select id="contrato_id" name="contrato_id" required>
                            <option value="">Selecione um contrato...</option>
                            <?php foreach ($contratos as $contrato): ?>
                                <option value="<?= $contrato['id'] ?>" 
                                    <?= (isset($dados_formulario['contrato_id']) && $dados_formulario['contrato_id'] == $contrato['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($contrato['empresa_nome']) ?> - 
                                    <?= htmlspecialchars($contrato['numero_contrato']) ?> 
                                    (<?= htmlspecialchars($contrato['tipo_servico']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="valor_pago">Valor Pago (MT) *</label>
                        <input type="text" id="valor_pago" name="valor_pago" required
                               value="<?= isset($dados_formulario['valor_pago']) ? htmlspecialchars($dados_formulario['valor_pago']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_pagamento">Data do Pagamento *</label>
                        <input type="date" id="data_pagamento" name="data_pagamento" required
                               value="<?= isset($dados_formulario['data_pagamento']) ? htmlspecialchars($dados_formulario['data_pagamento']) : date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="metodo_pagamento">Método de Pagamento *</label>
                        <select id="metodo_pagamento" name="metodo_pagamento" required>
                            <option value="">Selecione...</option>
                            <option value="Transferência" <?= (isset($dados_formulario['metodo_pagamento']) && $dados_formulario['metodo_pagamento'] == 'Transferência') ? 'selected' : '' ?>>Transferência Bancária</option>
                            <option value="Cheque" <?= (isset($dados_formulario['metodo_pagamento']) && $dados_formulario['metodo_pagamento'] == 'Cheque') ? 'selected' : '' ?>>Cheque</option>
                            <option value="Dinheiro" <?= (isset($dados_formulario['metodo_pagamento']) && $dados_formulario['metodo_pagamento'] == 'Dinheiro') ? 'selected' : '' ?>>Dinheiro</option>
                            <option value="MBWay" <?= (isset($dados_formulario['metodo_pagamento']) && $dados_formulario['metodo_pagamento'] == 'MBWay') ? 'selected' : '' ?>>MBWay</option>
                        </select>
                    </div>
                    
                    <?php if ($coluna_existe): ?>
                    <div class="form-group">
                        <label for="comprovante_numero">Nº Comprovante</label>
                        <input type="text" id="comprovante_numero" name="comprovante_numero" 
                               value="<?= $proximo_comprovante ?>" readonly>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes"><?= isset($dados_formulario['observacoes']) ? htmlspecialchars($dados_formulario['observacoes']) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Formatação do valor monetário
        document.getElementById('valor_pago').addEventListener('blur', function(e) {
            let value = this.value.replace(/[^\d,]/g, '');
            value = value.replace(',', '.');
            let num = parseFloat(value) || 0;
            this.value = num.toLocaleString('pt-PT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        });
    </script>
</body>
</html>