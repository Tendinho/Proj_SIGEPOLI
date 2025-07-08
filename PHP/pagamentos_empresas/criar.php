<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão operacional

$database = new Database();
$db = $database->getConnection();

$erro = '';
$sucesso = '';

// Buscar empresas e contratos ativos
$empresas = $db->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $contrato_id = (int)$_POST['contrato_id'];
        $mes_referencia = (int)$_POST['mes_referencia'];
        $ano_referencia = (int)$_POST['ano_referencia'];
        $valor_pago = (float)str_replace(',', '.', $_POST['valor_pago']);
        $observacoes = $_POST['observacoes'] ?? null;

        // Validar dados
        if ($contrato_id <= 0 || $mes_referencia < 1 || $mes_referencia > 12 || $ano_referencia < 2000 || $valor_pago <= 0) {
            throw new Exception("Dados inválidos fornecidos");
        }

        // Verificar se já existe pagamento para este período
        $stmt = $db->prepare("SELECT id FROM pagamentos_empresas 
                             WHERE contrato_id = ? AND mes_referencia = ? AND ano_referencia = ?");
        $stmt->execute([$contrato_id, $mes_referencia, $ano_referencia]);
        
        if ($stmt->fetch()) {
            throw new Exception("Já existe um pagamento registrado para este contrato no mês/ano selecionado");
        }

        // Inserir novo pagamento
        $stmt = $db->prepare("INSERT INTO pagamentos_empresas 
                            (contrato_id, mes_referencia, ano_referencia, valor_pago, observacoes, status, data_pagamento)
                            VALUES (?, ?, ?, ?, ?, 'Pendente', NULL)");
        $stmt->execute([$contrato_id, $mes_referencia, $ano_referencia, $valor_pago, $observacoes]);

        registrarAuditoria('Criou pagamento', 'pagamentos_empresas', $db->lastInsertId(), "Valor: $valor_pago");

        $_SESSION['mensagem'] = "Pagamento cadastrado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Buscar contratos se empresa foi selecionada
$contratos = [];
if (isset($_GET['empresa_id']) && $_GET['empresa_id'] > 0) {
    $empresa_id = (int)$_GET['empresa_id'];
    $contratos = $db->prepare("SELECT id, numero_contrato FROM contratos 
                              WHERE empresa_id = ? AND ativo = 1 ORDER BY numero_contrato");
    $contratos->execute([$empresa_id]);
    $contratos = $contratos->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Novo Pagamento - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
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
            font-weight: 500;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        .error {
            color: #dc3545;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-money-bill-wave"></i> Novo Pagamento</h1>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="post" id="formPagamento">
                <div class="form-group">
                    <label for="empresa_id">Empresa:</label>
                    <select name="empresa_id" id="empresa_id" required>
                        <option value="">Selecione uma empresa</option>
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= isset($_GET['empresa_id']) && $_GET['empresa_id'] == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contrato_id">Contrato:</label>
                    <select name="contrato_id" id="contrato_id" required>
                        <option value="">Selecione um contrato</option>
                        <?php foreach ($contratos as $cont): ?>
                            <option value="<?= $cont['id'] ?>"><?= htmlspecialchars($cont['numero_contrato']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="mes_referencia">Mês Referência:</label>
                        <select name="mes_referencia" required>
                            <option value="">Selecione</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="ano_referencia">Ano Referência:</label>
                        <input type="number" name="ano_referencia" min="2000" max="2100" value="<?= date('Y') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="valor_pago">Valor (Kz):</label>
                    <input type="text" name="valor_pago" placeholder="0,00" required>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações:</label>
                    <textarea name="observacoes" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Pagamento</button>
            </form>
        </div>
    </div>

    <script>
        // Atualizar lista de contratos quando selecionar empresa
        document.getElementById('empresa_id').addEventListener('change', function() {
            const empresaId = this.value;
            const contratoSelect = document.getElementById('contrato_id');
            
            if (empresaId) {
                fetch(`../api/get_contratos.php?empresa_id=${empresaId}`)
                    .then(response => response.json())
                    .then(data => {
                        contratoSelect.innerHTML = '<option value="">Selecione um contrato</option>';
                        data.forEach(contrato => {
                            const option = document.createElement('option');
                            option.value = contrato.id;
                            option.textContent = contrato.numero_contrato;
                            contratoSelect.appendChild(option);
                        });
                    });
            } else {
                contratoSelect.innerHTML = '<option value="">Selecione um contrato</option>';
            }
        });

        // Formatar valor monetário
        document.querySelector('input[name="valor_pago"]').addEventListener('blur', function() {
            let value = this.value.replace(/[^\d,]/g, '').replace(',', '.');
            value = parseFloat(value) || 0;
            this.value = value.toFixed(2).replace('.', ',');
        });
    </script>
</body>
</html>