<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

$where = "WHERE 1=1";
if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = $_GET['busca'];
    $where .= " AND (nome LIKE '%$busca%' OR nif LIKE '%$busca%' OR tipo_servico LIKE '%$busca%')";
}

if (isset($_GET['tipo_servico']) && !empty($_GET['tipo_servico'])) {
    $tipo_servico = $_GET['tipo_servico'];
    $where .= " AND tipo_servico = '$tipo_servico'";
}

// Contar total de registros
$query_count = "SELECT COUNT(*) as total FROM empresas $where";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute();
$total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar empresas
$query = "SELECT * FROM empresas $where ORDER BY nome LIMIT $offset, $registros_por_pagina";
$stmt = $db->prepare($query);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-building"></i> Gestão de Empresas</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                <?php echo $_SESSION['mensagem']; unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="toolbar">
            <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nova Empresa</a>
            
            <form method="get" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="busca" placeholder="Pesquisar..." value="<?php echo isset($_GET['busca']) ? $_GET['busca'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <select name="tipo_servico">
                            <option value="">Todos os serviços</option>
                            <option value="Limpeza" <?php echo isset($_GET['tipo_servico']) && $_GET['tipo_servico'] == 'Limpeza' ? 'selected' : ''; ?>>Limpeza</option>
                            <option value="Segurança" <?php echo isset($_GET['tipo_servico']) && $_GET['tipo_servico'] == 'Segurança' ? 'selected' : ''; ?>>Segurança</option>
                            <option value="Cafetaria" <?php echo isset($_GET['tipo_servico']) && $_GET['tipo_servico'] == 'Cafetaria' ? 'selected' : ''; ?>>Cafetaria</option>
                        </select>
                    </div>
                    
                    <button type="submit"><i class="fas fa-search"></i> Filtrar</button>
                </div>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>NIF</th>
                    <th>Tipo de Serviço</th>
                    <th>Telefone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['nome']; ?></td>
                        <td><?php echo $row['nif']; ?></td>
                        <td><?php echo $row['tipo_servico']; ?></td>
                        <td><?php echo $row['telefone']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><span class="status <?php echo $row['ativo'] ? 'ativo' : 'inativo'; ?>"><?php echo $row['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
                        <td class="actions">
                            <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                            <a href="excluir.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Tem certeza que deseja excluir esta empresa?')"><i class="fas fa-trash"></i></a>
                            <a href="../relatorios/contratos.php?empresa_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Ver Contratos"><i class="fas fa-file-contract"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr><td colspan="8">Nenhuma empresa encontrada</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['busca']) ? '&busca=' . $_GET['busca'] : ''; ?><?php echo isset($_GET['tipo_servico']) ? '&tipo_servico=' . $_GET['tipo_servico'] : ''; ?>" class="btn">&laquo; Anterior</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?php echo $i; ?><?php echo isset($_GET['busca']) ? '&busca=' . $_GET['busca'] : ''; ?><?php echo isset($_GET['tipo_servico']) ? '&tipo_servico=' . $_GET['tipo_servico'] : ''; ?>" class="btn <?php echo $i == $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($pagina < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['busca']) ? '&busca=' . $_GET['busca'] : ''; ?><?php echo isset($_GET['tipo_servico']) ? '&tipo_servico=' . $_GET['tipo_servico'] : ''; ?>" class="btn">Próxima &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>