<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(7);

$database = new Database();
$db = $database->getConnection();

$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');
$tipo_servico = isset($_GET['tipo_servico']) ? $_GET['tipo_servico'] : '';

// Buscar custos com empresas
$query = "SELECT e.nome, e.tipo_servico, c.numero_contrato, 
                 c.valor_mensal, 
                 SUM(IFNULL(p.valor_pago, 0)) as total_pago,
                 COUNT(p.id) as meses_pagos
          FROM empresas e
          JOIN contratos c ON e.id = c.empresa_id
          LEFT JOIN pagamentos_empresas p ON c.id = p.contrato_id AND p.ano_referencia = :ano
          WHERE c.ativo = 1
          " . (!empty($tipo_servico) ? "AND e.tipo_servico = :tipo_servico" : "") . "
          GROUP BY e.id, e.nome, e.tipo_servico, c.numero_contrato, c.valor_mensal
          ORDER BY e.tipo_servico, e.nome";
          
$stmt = $db->prepare($query);
$stmt->bindParam(":ano", $ano);
if (!empty($tipo_servico)) {
    $stmt->bindParam(":tipo_servico", $tipo_servico);
}
$stmt->execute();

// Buscar anos para filtro
$query_anos = "SELECT DISTINCT ano_referencia as ano FROM pagamentos_empresas ORDER BY ano DESC";
$stmt_anos = $db->prepare($query_anos);
$stmt_anos->execute();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custos - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-file-invoice-dollar"></i> Relatório de Custos</h1>
        
        <form method="get" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="ano">Ano:</label>
                    <select id="ano" name="ano">
                        <?php while ($ano_row = $stmt_anos->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $ano_row['ano']; ?>" <?php echo $ano_row['ano'] == $ano ? 'selected' : ''; ?>>
                                <?php echo $ano_row['ano']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tipo_servico">Tipo de Serviço:</label>
                    <select id="tipo_servico" name="tipo_servico">
                        <option value="">Todos</option>
                        <option value="Limpeza" <?php echo $tipo_servico == 'Limpeza' ? 'selected' : ''; ?>>Limpeza</option>
                        <option value="Segurança" <?php echo $tipo_servico == 'Segurança' ? 'selected' : ''; ?>>Segurança</option>
                        <option value="Cafetaria" <?php echo $tipo_servico == 'Cafetaria' ? 'selected' : ''; ?>>Cafetaria</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="custos.php?export=pdf&ano=<?php echo $ano; ?>&tipo_servico=<?php echo $tipo_servico; ?>" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
            </div>
        </form>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Tipo de Serviço</th>
                    <th>Contrato</th>
                    <th>Valor Mensal</th>
                    <th>Meses Pagos</th>
                    <th>Total Pago</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['nome']; ?></td>
                        <td><?php echo $row['tipo_servico']; ?></td>
                        <td><?php echo $row['numero_contrato']; ?></td>
                        <td><?php echo number_format($row['valor_mensal'], 2, ',', '.'); ?> Kz</td>
                        <td><?php echo $row['meses_pagos']; ?></td>
                        <td><?php echo number_format($row['total_pago'], 2, ',', '.'); ?> Kz</td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr><td colspan="6">Nenhum dado encontrado para os filtros selecionados</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>