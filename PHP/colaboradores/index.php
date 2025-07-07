<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para gestão de pessoal

$database = new Database();
$db = $database->getConnection();

// Paginação e filtros
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'departamento_id' => isset($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : null,
    'ativo' => isset($_GET['ativo']) ? (int)$_GET['ativo'] : 1
];

// Construir WHERE
$where = "WHERE f.ativo = :ativo";
$params = [':ativo' => $filtros['ativo']];

if (!empty($filtros['busca'])) {
    $where .= " AND (f.nome_completo LIKE :busca OR f.email LIKE :busca OR f.telefone LIKE :busca)";
    $params[':busca'] = "%{$filtros['busca']}%";
}

if ($filtros['departamento_id']) {
    $where .= " AND f.departamento_id = :departamento_id";
    $params[':departamento_id'] = $filtros['departamento_id'];
}

// Contar total
$sqlCount = "SELECT COUNT(*) FROM funcionarios f $where";
$stmt = $db->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar departamentos para filtro
$departamentos = $db->query("SELECT id, nome FROM departamentos WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar colaboradores
$sql = "SELECT f.id, f.nome_completo, f.email, f.telefone, f.cargo,
               d.nome AS departamento, f.data_contratacao
        FROM funcionarios f
        LEFT JOIN departamentos d ON f.departamento_id = d.id
        $where
        ORDER BY f.nome_completo
        LIMIT $offset, $por_pagina";
$stmt = $db->prepare($sql);
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
    <title>Gestão de Colaboradores - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="content-header">
        <h1><i class="fas fa-user-tie"></i> Gestão de Colaboradores</h1>
        <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Colaborador</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
        </div>
        <div class="card-body">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label for="busca">Pesquisar:</label>
                    <input type="text" name="busca" placeholder="Nome, Email ou Telefone..." value="<?= htmlspecialchars($filtros['busca']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="departamento_id">Departamento:</label>
                    <select name="departamento_id">
                        <option value="">Todos</option>
                        <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?= $departamento['id'] ?>" <?= $filtros['departamento_id'] == $departamento['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($departamento['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ativo">Status:</label>
                    <select name="ativo">
                        <option value="1" <?= $filtros['ativo'] == 1 ? 'selected' : '' ?>>Ativos</option>
                        <option value="0" <?= $filtros['ativo'] == 0 ? 'selected' : '' ?>>Inativos</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Lista de Colaboradores</h3>
        </div>
        <div class="card-body">
            <?php if (empty($colaboradores)): ?>
                <div class="alert alert-info">Nenhum colaborador encontrado com os filtros aplicados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome Completo</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th>Contato</th>
                                <th>Data Contratação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($colaboradores as $colab): ?>
                                <tr>
                                    <td><?= $colab['id'] ?></td>
                                    <td><?= htmlspecialchars($colab['nome_completo']) ?></td>
                                    <td><?= htmlspecialchars($colab['departamento'] ?? 'Não atribuído') ?></td>
                                    <td><?= htmlspecialchars($colab['cargo']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($colab['email']) ?></div>
                                        <div><?= htmlspecialchars($colab['telefone']) ?></div>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($colab['data_contratacao'])) ?></td>
                                    <td class="actions">
                                        <a href="editar.php?id=<?= $colab['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($colab['ativo']): ?>
                                            <a href="excluir.php?id=<?= $colab['id'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Tem certeza que deseja inativar este colaborador?')" title="Inativar">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="ativar.php?id=<?= $colab['id'] ?>" class="btn btn-sm btn-success" 
                                               onclick="return confirm('Tem certeza que deseja ativar este colaborador?')" title="Ativar">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <a href="?pagina=<?= $i ?><?= !empty($filtros['busca']) ? '&busca=' . urlencode($filtros['busca']) : '' ?><?= $filtros['departamento_id'] ? '&departamento_id=' . $filtros['departamento_id'] : '' ?>&ativo=<?= $filtros['ativo'] ?>"
                               class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>