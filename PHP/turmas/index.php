<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão acadêmica

$database = new Database();
$db = $database->getConnection();

// Paginação e filtros
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'curso_id' => isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : null,
    'ano_letivo' => isset($_GET['ano_letivo']) ? (int)$_GET['ano_letivo'] : date('Y'),
    'periodo' => $_GET['periodo'] ?? null,
    'ativo' => isset($_GET['ativo']) ? (int)$_GET['ativo'] : 1
];

// Construir WHERE
$where = "WHERE t.ativo = :ativo";
$params = [':ativo' => $filtros['ativo']];

if (!empty($filtros['busca'])) {
    $where .= " AND (t.nome LIKE :busca OR t.codigo LIKE :busca)";
    $params[':busca'] = "%{$filtros['busca']}%";
}

if ($filtros['curso_id']) {
    $where .= " AND t.curso_id = :curso_id";
    $params[':curso_id'] = $filtros['curso_id'];
}

if ($filtros['ano_letivo']) {
    $where .= " AND t.ano_letivo = :ano_letivo";
    $params[':ano_letivo'] = $filtros['ano_letivo'];
}

if ($filtros['periodo']) {
    $where .= " AND t.periodo = :periodo";
    $params[':periodo'] = $filtros['periodo'];
}

// Contar total
$sqlCount = "SELECT COUNT(*) FROM turmas t $where";
$stmt = $db->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar cursos para filtro
$cursos = $db->query("SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar turmas
$sql = "SELECT t.id, t.nome, t.codigo, t.ano_letivo, t.ano_ingresso, t.semestre, 
               t.capacidade, t.sala, t.periodo, t.ativo,
               c.nome AS curso, 
               (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.status = 'Ativa') AS alunos_matriculados,
               (SELECT COUNT(*) FROM turma_professores tp WHERE tp.turma_id = t.id) AS professores
        FROM turmas t
        LEFT JOIN cursos c ON t.curso_id = c.id
        $where
        ORDER BY t.ano_letivo DESC, t.semestre, t.nome
        LIMIT $offset, $por_pagina";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Turmas - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="content-header">
        <h1><i class="fas fa-users-class"></i> Gestão de Turmas</h1>
        <div class="header-actions">
            <a href="/PHP/index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Menu Principal</a>
            <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nova Turma</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
        </div>
        <div class="card-body">
            <form method="get" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="busca">Pesquisar:</label>
                        <input type="text" name="busca" placeholder="Nome ou Código da Turma..." value="<?= htmlspecialchars($filtros['busca']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="curso_id">Curso:</label>
                        <select name="curso_id">
                            <option value="">Todos</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?= $curso['id'] ?>" <?= $filtros['curso_id'] == $curso['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($curso['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ano_letivo">Ano Letivo:</label>
                        <input type="number" name="ano_letivo" min="2000" max="2100" value="<?= $filtros['ano_letivo'] ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="periodo">Período:</label>
                        <select name="periodo">
                            <option value="">Todos</option>
                            <option value="Manhã" <?= $filtros['periodo'] == 'Manhã' ? 'selected' : '' ?>>Manhã</option>
                            <option value="Tarde" <?= $filtros['periodo'] == 'Tarde' ? 'selected' : '' ?>>Tarde</option>
                            <option value="Noite" <?= $filtros['periodo'] == 'Noite' ? 'selected' : '' ?>>Noite</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ativo">Status:</label>
                        <select name="ativo">
                            <option value="1" <?= $filtros['ativo'] == 1 ? 'selected' : '' ?>>Ativas</option>
                            <option value="0" <?= $filtros['ativo'] == 0 ? 'selected' : '' ?>>Inativas</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Lista de Turmas</h3>
        </div>
        <div class="card-body">
            <?php if (empty($turmas)): ?>
                <div class="alert alert-info">Nenhuma turma encontrada com os filtros aplicados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Curso</th>
                                <th>Ano/Sem</th>
                                <th>Período</th>
                                <th>Sala</th>
                                <th>Alunos</th>
                                <th>Professores</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($turmas as $turma): ?>
                                <tr>
                                    <td><?= htmlspecialchars($turma['codigo']) ?></td>
                                    <td><?= htmlspecialchars($turma['nome']) ?></td>
                                    <td><?= htmlspecialchars($turma['curso'] ?? 'Não atribuído') ?></td>
                                    <td><?= $turma['ano_letivo'] ?>/<?= $turma['semestre'] ?></td>
                                    <td><?= htmlspecialchars($turma['periodo']) ?></td>
                                    <td><?= htmlspecialchars($turma['sala'] ?? '-') ?></td>
                                    <td><?= $turma['alunos_matriculados'] ?>/<?= $turma['capacidade'] ?></td>
                                    <td><?= $turma['professores'] ?></td>
                                    <td>
                                        <span class="badge badge-<?= $turma['ativo'] ? 'success' : 'danger' ?>">
                                            <?= $turma['ativo'] ? 'Ativa' : 'Inativa' ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="visualizar.php?id=<?= $turma['id'] ?>" class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?= $turma['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="disciplinas.php?id=<?= $turma['id'] ?>" class="btn btn-sm btn-secondary" title="Disciplinas">
                                            <i class="fas fa-book"></i>
                                        </a>
                                        <a href="professores.php?id=<?= $turma['id'] ?>" class="btn btn-sm btn-primary" title="Professores">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                        </a>
                                        <?php if ($turma['ativo']): ?>
                                            <a href="excluir.php?id=<?= $turma['id'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Tem certeza que deseja inativar esta turma?')" title="Inativar">
                                                <i class="fas fa-times-circle"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="ativar.php?id=<?= $turma['id'] ?>" class="btn btn-sm btn-success" 
                                               onclick="return confirm('Tem certeza que deseja ativar esta turma?')" title="Ativar">
                                                <i class="fas fa-check-circle"></i>
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
                            <a href="?pagina=<?= $i ?><?= !empty($filtros['busca']) ? '&busca=' . urlencode($filtros['busca']) : '' ?><?= $filtros['curso_id'] ? '&curso_id=' . $filtros['curso_id'] : '' ?>&ano_letivo=<?= $filtros['ano_letivo'] ?><?= $filtros['periodo'] ? '&periodo=' . urlencode($filtros['periodo']) : '' ?>&ativo=<?= $filtros['ativo'] ?>"
                               class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="/Context/JS/script.js"></script>
</body>
</html>