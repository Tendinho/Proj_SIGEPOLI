<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão operacional

$database = new Database();
$db = $database->getConnection();

$erro = '';
$sucesso = '';

$pagamento_id = (int)$_GET['id'];
$pagamento = null;

// Buscar dados do pagamento
$stmt = $db->prepare("SELECT p.*, c.empresa_id 
                     FROM pagamentos_empresas p
                     JOIN contratos c ON p.contrato_id = c.id
                     WHERE p.id = ?");
$stmt->execute([$pagamento_id]);
$pagamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pagamento) {
    $_SESSION['mensagem'] = "Pagamento não encontrado";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

// Buscar empresas e contratos
$empresas = $db->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();
$contratos = $db->prepare("SELECT id, numero_contrato FROM contratos 
                          WHERE empresa_id = ? AND ativo = 1 ORDER BY numero_contrato");
$contratos->execute([$pagamento['empresa_id']]);
$contratos = $contratos->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mes_referencia = (int)$_POST['mes_referencia'];
        $ano_referencia = (int)$_POST['ano_referencia'];
        $valor_pago = (float)str_replace(',', '.', $_POST['valor_pago']);
        $observacoes = $_POST['observacoes'] ?? null;
        $status = $_POST['status'] ?? 'Pendente';
        $data_pagamento = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;

        // Validar dados
        if ($mes_referencia < 1 || $mes_referencia > 12 || $ano_referencia < 2000 || $valor_pago <= 0) {
            throw new Exception("Dados inválidos fornecidos");
        }

        // Verificar se já existe pagamento para este período (exceto o atual)
        $stmt = $db->prepare("SELECT id FROM pagamentos_empresas 
                             WHERE contrato_id = ? AND mes_referencia = ? AND ano_referencia = ? AND id != ?");
        $stmt->execute([$pagamento['contrato_id'], $mes_referencia, $ano_referencia, $pagamento_id]);
        
        if ($stmt->fetch()) {
            throw new Exception("Já existe outro pagamento registrado para este contrato no mês/ano selecionado");
        }

        // Atualizar pagamento
        $stmt = $db->prepare("UPDATE pagamentos_empresas 
                             SET mes_referencia = ?, ano_referencia = ?, valor_pago = ?, 
                                 observacoes = ?, status = ?, data_pagamento = ?
                             WHERE id = ?");
        $stmt->execute([
            $mes_referencia, $ano_referencia, $valor_pago, 
            $observacoes, $status, $data_pagamento,
            $pagamento_id
        ]);

        registrarAuditoria('Editou pagamento', 'pagamentos_empresas', $pagamento_id, "Novo valor: $valor_pago, Status: $status");

        $_SESSION['mensagem'] = "Pagamento atualizado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Pagamento - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        /* Estilos similares ao criar.php */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-money-bill-wave"></i> Editar Pagamento</h1>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="post">
                <div class="form-group">
                    <label>Empresa:</label>
                    <input type="text" value="<?= htmlspecialchars($empresas[array_search($pagamento['empresa_id'], array_column($empresas, 'id'))]['nome'] ?? '' )?>" readonly>
                </div>

                <div class="form-group">
                    <label>Contrato:</label>
                    <input type="text" value="<?= htmlspecialchars($contratos[array_search($pagamento['contrato_id'], array_column($contratos, 'id'))]['numero_contrato'] ?? '' )?>" readonly>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="mes_referencia">Mês Referência:</label>
                        <select name="mes_referencia" required>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $pagamento['mes_referencia'] == $i ? 'selected' : '' ?>>
                                    <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="ano_referencia">Ano Referência:</label>
                        <input type="number" name="ano_referencia" min="2000" max="2100" 
                               value="<?= $pagamento['ano_referencia'] ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="valor_pago">Valor (Kz):</label>
                    <input type="text" name="valor_pago" placeholder="0,00" 
                           value="<?= number_format($pagamento['valor_pago'], 2, ',', '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" required>
                        <option value="Pendente" <?= $pagamento['status'] == 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="Pago" <?= $pagamento['status'] == 'Pago' ? 'selected' : '' ?>>Pago</option>
                        <option value="Atrasado" <?= $pagamento['status'] == 'Atrasado' ? 'selected' : '' ?>>Atrasado</option>
                        <option value="Cancelado" <?= $pagamento['status'] == 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="data_pagamento">Data Pagamento:</label>
                    <input type="date" name="data_pagamento" 
                           value="<?= $pagamento['data_pagamento'] ? htmlspecialchars($pagamento['data_pagamento']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações:</label>
                    <textarea name="observacoes" rows="3"><?= htmlspecialchars($pagamento['observacoes'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Atualizar Pagamento</button>
            </form>
        </div>
    </div>

    <script>
        // Formatar valor monetário
        document.querySelector('input[name="valor_pago"]').addEventListener('blur', function() {
            let value = this.value.replace(/[^\d,]/g, '').replace(',', '.');
            value = parseFloat(value) || 0;
            this.value = value.toFixed(2).replace('.', ',');
        });
    </script>
</body>
</html>