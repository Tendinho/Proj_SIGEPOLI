<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

$where = "WHERE 1=1";
$params = [];

if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = '%'.$_GET['busca'].'%';
    $where .= " AND (nome LIKE :busca OR nif LIKE :busca OR tipo_servico LIKE :busca)";
    $params[':busca'] = $busca;
}

if (isset($_GET['tipo_servico']) && !empty($_GET['tipo_servico'])) {
    $where .= " AND tipo_servico = :tipo_servico";
    $params[':tipo_servico'] = $_GET['tipo_servico'];
}

// Contar total de registros
$query_count = "SELECT COUNT(*) as total FROM empresas $where";
$stmt_count = $db->prepare($query_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar empresas
$query = "SELECT * FROM empresas $where ORDER BY nome LIMIT :offset, :limit";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-back {
            background-color: #7f8c8d;
        }
        .btn-back:hover {
            background-color: #6c757d;
        }
        .btn-add {
            background-color: #28a745;
        }
        .btn-add:hover {
            background-color: #218838;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .search-form {
            margin-bottom: 20px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .status {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
        }
        .status.ativo {
            background-color: #d4edda;
            color: #155724;
        }
        .status.inativo {
            background-color: #f8d7da;
            color: #721c24;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .btn-edit {
            background-color: #17a2b8;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-info {
            background-color: #6c757d;
        }
        .pagination {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        .pagination .btn {
            min-width: 40px;
        }
        .pagination .btn.active {
            background-color: #007bff;
            font-weight: bold;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-building"></i> Empresas</h1>
            <div>
                <a href="criar.php" class="btn btn-add"><i class="fas fa-plus"></i> Nova Empresa</a>
                <a href="../index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Voltar ao Menu</a>
            </div>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                <?= htmlspecialchars($_SESSION['mensagem']) ?>
                <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="get" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="busca" placeholder="Pesquisar..." value="<?= isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <select name="tipo_servico">
                            <option value="">Todos os serviços</option>
                            <option value="Limpeza" <?= isset($_GET['tipo_servico']) && $_GET['tipo_servico'] == 'Limpeza' ? 'selected' : '' ?>>Limpeza</option>
                            <option value="Segurança" <?= isset($_GET['tipo_servico']) && $_GET['tipo_servico'] == 'Segurança' ? 'selected' : '' ?>>Segurança</option>
                            <option value="Cafetaria" <?= isset($_GET['tipo_servico']) && $_GET['tipo_servico'] == 'Cafetaria' ? 'selected' : '' ?>>Cafetaria</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                </div>
            </form>
        </div>

        <?php if (empty($empresas)): ?>
            <div class="card no-results">
                <p>Nenhuma empresa encontrada com os critérios selecionados.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <table>
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
                        <?php foreach ($empresas as $empresa): ?>
                            <tr>
                                <td><?= htmlspecialchars($empresa['id']) ?></td>
                                <td><?= htmlspecialchars($empresa['nome']) ?></td>
                                <td><?= htmlspecialchars($empresa['nif']) ?></td>
                                <td><?= htmlspecialchars($empresa['tipo_servico']) ?></td>
                                <td><?= htmlspecialchars($empresa['telefone']) ?></td>
                                <td><?= htmlspecialchars($empresa['email']) ?></td>
                                <td>
                                    <span class="status <?= $empresa['ativo'] ? 'ativo' : 'inativo' ?>">
                                        <?= $empresa['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="editar.php?id=<?= $empresa['id'] ?>" class="btn btn-sm btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                    <a href="excluir.php?id=<?= $empresa['id'] ?>" class="btn btn-sm btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta empresa?')"><i class="fas fa-trash"></i></a>
                                    <a href="../pagamentos_empresas/contratos.php?empresa_id=<?= $empresa['id'] ?>" class="btn btn-sm btn-info" title="Ver Contratos"><i class="fas fa-file-contract"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=<?= $pagina - 1 ?><?= isset($_GET['busca']) ? '&busca='.htmlspecialchars($_GET['busca']) : '' ?><?= isset($_GET['tipo_servico']) ? '&tipo_servico='.htmlspecialchars($_GET['tipo_servico']) : '' ?>" class="btn">&laquo; Anterior</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <a href="?pagina=<?= $i ?><?= isset($_GET['busca']) ? '&busca='.htmlspecialchars($_GET['busca']) : '' ?><?= isset($_GET['tipo_servico']) ? '&tipo_servico='.htmlspecialchars($_GET['tipo_servico']) : '' ?>" class="btn <?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?= $pagina + 1 ?><?= isset($_GET['busca']) ? '&busca='.htmlspecialchars($_GET['busca']) : '' ?><?= isset($_GET['tipo_servico']) ? '&tipo_servico='.htmlspecialchars($_GET['tipo_servico']) : '' ?>" class="btn">Próxima &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Adiciona confirmação antes de excluir
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirm('Tem certeza que deseja excluir esta empresa?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>