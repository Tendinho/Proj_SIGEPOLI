<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para RH

$database = new Database();
$db = $database->getConnection();

// Filtros
$departamento_id = isset($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : null;
$ativo = isset($_GET['ativo']) ? (int)$_GET['ativo'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Buscar departamentos
$departamentos = $db->query("SELECT id, nome FROM departamentos ORDER BY nome")->fetchAll();

// Consulta colaboradores
$query = "SELECT f.*, u.ativo, d.nome AS departamento, c.nome AS cargo,
                 DATE_FORMAT(f.data_contratacao, '%d/%m/%Y') AS data_contratacao_formatada
          FROM funcionarios f
          LEFT JOIN usuarios u ON f.usuario_id = u.id
          LEFT JOIN cargos c ON u.cargo_id = c.id
          LEFT JOIN departamentos d ON u.departamento_id = d.id
          WHERE u.ativo = :ativo";
$params = [':ativo' => $ativo];

if ($departamento_id) {
    $query .= " AND u.departamento_id = :departamento_id";
    $params[':departamento_id'] = $departamento_id;
}

if (!empty($search)) {
    $query .= " AND (f.nome_completo LIKE :search OR f.bi LIKE :search OR f.telefone LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY f.nome_completo";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colaboradores - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
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
            flex-wrap: wrap;
            gap: 15px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
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
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-back {
            background-color: #7f8c8d;
        }
        .btn-back:hover {
            background-color: #6c757d;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .filter-form {
            margin-bottom: 25px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #495057;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
        }
        input:focus, select:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .search-group {
            position: relative;
            flex: 2;
        }
        .search-group input {
            padding-left: 40px;
        }
        .search-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .no-results {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        .table-responsive {
            overflow-x: auto;
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            .form-group {
                min-width: 100%;
            }
            .actions {
                flex-direction: column;
                gap: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> Colaboradores</h1>
            <div>
                <a href="criar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Novo Colaborador
                </a>
                <a href="../index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                <?= htmlspecialchars($_SESSION['mensagem']) ?>
                <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="get" class="filter-form">
                <div class="form-row">
                    <div class="search-group">
                        <label for="search">Pesquisar:</label>
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Nome, BI ou telefone..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="departamento_id">Departamento:</label>
                        <select name="departamento_id">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $depto): ?>
                                <option value="<?= $depto['id'] ?>" <?= $departamento_id == $depto['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($depto['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ativo">Status:</label>
                        <select name="ativo">
                            <option value="1" <?= $ativo == 1 ? 'selected' : '' ?>>Ativos</option>
                            <option value="0" <?= $ativo == 0 ? 'selected' : '' ?>>Inativos</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpar Filtros
                    </a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>BI</th>
                            <th>Telefone</th>
                            <th>Departamento</th>
                            <th>Cargo</th>
                            <th>Contratação</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($colaboradores)): ?>
                            <tr>
                                <td colspan="8" class="no-results">
                                    <i class="fas fa-user-slash" style="font-size: 24px; margin-bottom: 10px;"></i>
                                    <p>Nenhum colaborador encontrado</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($colaboradores as $colab): ?>
                                <tr>
                                    <td><?= htmlspecialchars($colab['nome_completo']) ?></td>
                                    <td><?= htmlspecialchars($colab['bi']) ?></td>
                                    <td><?= htmlspecialchars($colab['telefone'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($colab['departamento'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($colab['cargo'] ?? '-') ?></td>
                                    <td><?= $colab['data_contratacao_formatada'] ?></td>
                                    <td>
                                        <span class="badge <?= $colab['ativo'] ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $colab['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="editar.php?id=<?= $colab['id'] ?>" class="btn btn-primary btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($colab['ativo']): ?>
                                            <a href="desativar.php?id=<?= $colab['id'] ?>" class="btn btn-secondary btn-sm" 
                                               onclick="return confirm('Tem certeza que deseja desativar este colaborador?')" title="Desativar">
                                                <i class="fas fa-toggle-off"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="ativar.php?id=<?= $colab['id'] ?>" class="btn btn-success btn-sm" 
                                               onclick="return confirm('Tem certeza que deseja ativar este colaborador?')" title="Ativar">
                                                <i class="fas fa-toggle-on"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="excluir.php?id=<?= $colab['id'] ?>" class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Tem certeza que deseja excluir este colaborador?')" title="Excluir">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
    // Confirmar antes de ações importantes
    document.querySelectorAll('a[onclick*="confirm"]').forEach(link => {
        // Extrai a mensagem de confirmação do atributo onclick
        const confirmMatch = link.getAttribute('onclick').match(/confirm\('([^']*)'/);
        const confirmMessage = confirmMatch ? confirmMatch[1] : 'Tem certeza que deseja realizar esta ação?';
        
        // Substitui o onclick por um event listener mais limpo
        link.removeAttribute('onclick');
        link.addEventListener('click', function(e) {
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>