<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão operacional

$database = new Database();
$db = $database->getConnection();

$erro = '';
$sucesso = '';

// Verificar se a coluna comprovante_numero existe
$coluna_existe = $db->query("
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'pagamentos_empresas' 
    AND COLUMN_NAME = 'comprovante_numero'
")->fetchColumn();

// Gerar número de comprovante
$proximo_comprovante = 'PAG' . date('Ym') . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);

// Buscar contratos ativos com empresas
$contratos = $db->query("
    SELECT c.id, c.numero_contrato, e.nome AS empresa_nome
    FROM contratos c
    JOIN empresas e ON c.empresa_id = e.id
    WHERE c.ativo = 1
    ORDER BY e.nome, c.numero_contrato
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $contrato_id = (int)$_POST['contrato_id'];
        $valor_pago = (float)str_replace(',', '.', $_POST['valor_pago']);
        $data_pagamento = $_POST['data_pagamento'];
        $metodo_pagamento = $_POST['metodo_pagamento'];
        $observacoes = $_POST['observacoes'] ?? null;
        
        // Validar dados
        if ($contrato_id <= 0 || $valor_pago <= 0 || empty($data_pagamento)) {
            throw new Exception("Preencha todos os campos obrigatórios corretamente");
        }

        // Verificar se contrato existe e está ativo
        $stmt = $db->prepare("SELECT id FROM contratos WHERE id = ? AND ativo = 1");
        $stmt->execute([$contrato_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Contrato inválido ou inativo");
        }

        // Obter mês/ano atual para referência
        $mes_referencia = date('m');
        $ano_referencia = date('Y');

        // Verificar se já existe pagamento para este contrato no mês/ano
        $stmt = $db->prepare("SELECT id FROM pagamentos_empresas WHERE contrato_id = ? AND mes_referencia = ? AND ano_referencia = ?");
        $stmt->execute([$contrato_id, $mes_referencia, $ano_referencia]);
        if ($stmt->fetch()) {
            throw new Exception("Já existe um pagamento registrado para este contrato no mês corrente");
        }

        // Preparar query com ou sem comprovante_numero
        if ($coluna_existe) {
            $sql = "INSERT INTO pagamentos_empresas (
                contrato_id, mes_referencia, ano_referencia, valor_pago, 
                data_pagamento, metodo_pagamento, comprovante_numero, 
                observacoes, status, sla_atingido
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pago', 100)";
            $params = [
                $contrato_id, $mes_referencia, $ano_referencia, $valor_pago,
                $data_pagamento, $metodo_pagamento, $proximo_comprovante,
                $observacoes
            ];
        } else {
            $sql = "INSERT INTO pagamentos_empresas (
                contrato_id, mes_referencia, ano_referencia, valor_pago, 
                data_pagamento, metodo_pagamento, 
                observacoes, status, sla_atingido
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pago', 100)";
            $params = [
                $contrato_id, $mes_referencia, $ano_referencia, $valor_pago,
                $data_pagamento, $metodo_pagamento,
                $observacoes
            ];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        registrarAuditoria('Registrou pagamento', 'pagamentos_empresas', $db->lastInsertId(), "Valor: $valor_pago");

        $_SESSION['mensagem'] = "Pagamento registrado com sucesso!";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pagamento - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        /* Estilos mantidos do original */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-money-bill-wave"></i> Registrar Pagamento</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="post">
                <!-- Formulário mantido do original -->
                <!-- Removido o campo comprovante_numero se a coluna não existir -->
                <?php if ($coluna_existe): ?>
                    <div class="form-group">
                        <label for="comprovante_numero">Número Comprovante</label>
                        <input type="text" name="comprovante_numero" value="<?= $proximo_comprovante ?>" readonly>
                    </div>
                <?php endif; ?>
                
                <!-- Restante do formulário -->
            </form>
        </div>
    </div>

    <script>
        // Script de formatação mantido
    </script>
</body>
</html>