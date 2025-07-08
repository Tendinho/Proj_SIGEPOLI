<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão acadêmica

$database = new Database();
$db = $database->getConnection();

// Filtros e paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

$filtros = [
    'turma_id' => isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : null,
    'disciplina_id' => isset($_GET['disciplina_id']) ? (int)$_GET['disciplina_id'] : null
];

// Construir WHERE
$where = "WHERE a.ativo = 1";
$params = [];

if ($filtros['turma_id']) {
    $where .= " AND a.turma_id = :turma_id";
    $params[':turma_id'] = $filtros['turma_id'];
}

if ($filtros['disciplina_id']) {
    $where .= " AND a.disciplina_id = :disciplina_id";
    $params[':disciplina_id'] = $filtros['disciplina_id'];
}

// Contar total
$sqlCount = "SELECT COUNT(*) FROM aulas a
    JOIN disciplinas d ON a.disciplina_id = d.id
    JOIN turmas t ON a.turma_id = t.id
    $where";
$stmt = $db->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar turmas e disciplinas para filtros
$turmas = $db->query("SELECT id, nome FROM turmas WHERE ativo = 1 ORDER BY nome")->fetchAll();
$disciplinas = $db->query("SELECT id, nome FROM disciplinas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar aulas
$sql = "SELECT a.id, d.nome AS disciplina, t.nome AS turma,
               a.dia_semana, a.hora_inicio, a.hora_fim, p.nome_completo AS professor
        FROM aulas a
        JOIN disciplinas d ON a.disciplina_id = d.id
        JOIN turmas t ON a.turma_id = t.id
        JOIN professores pr ON a.professor_id = pr.id
        JOIN funcionarios p ON pr.funcionario_id = p.id
        $where
        ORDER BY t.nome, a.dia_semana, a.hora_inicio
        LIMIT $offset, $por_pagina";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Aulas - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        .data-table th {
            background-color: #343a40;
            color: white;
        }
        .data-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .actions {
            white-space: nowrap;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            gap: 5px;
        }
        .pagination a {
            padding: 5px 10px;
            border: 1px solid #dee2e6;
            text-decoration: none;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="content-header">
        <h1><i class="fas fa-chalkboard-teacher"></i> Gestão de Aulas</h1>
        <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nova Aula</a>
    </div>

    <a href="/PHP/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
        </div>
        <div class="card-body">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label for="turma_id">Turma:</label>
                    <select name="turma_id" id="turma_id">
                        <option value="">Todas as Turmas</option>
                        <?php foreach ($turmas as $turma): ?>
                            <option value="<?= $turma['id'] ?>" <?= $filtros['turma_id'] == $turma['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($turma['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="disciplina_id">Disciplina:</label>
                    <select name="disciplina_id" id="disciplina_id">
                        <option value="">Todas as Disciplinas</option>
                        <?php foreach ($disciplinas as $disciplina): ?>
                            <option value="<?= $disciplina['id'] ?>" <?= $filtros['disciplina_id'] == $disciplina['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($disciplina['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Lista de Aulas</h3>
        </div>
        <div class="card-body">
            <?php if (empty($aulas)): ?>
                <div class="alert alert-info">Nenhuma aula encontrada com os filtros aplicados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Disciplina</th>
                                <th>Turma</th>
                                <th>Professor</th>
                                <th>Dia</th>
                                <th>Horário</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aulas as $aula): ?>
                                <tr>
                                    <td><?= $aula['id'] ?></td>
                                    <td><?= htmlspecialchars($aula['disciplina']) ?></td>
                                    <td><?= htmlspecialchars($aula['turma']) ?></td>
                                    <td><?= htmlspecialchars($aula['professor']) ?></td>
                                    <td><?= traduzirDiaSemana($aula['dia_semana']) ?></td>
                                    <td><?= substr($aula['hora_inicio'], 0, 5) ?> - <?= substr($aula['hora_fim'], 0, 5) ?></td>
                                    <td class="actions">
                                        <a href="editar.php?id=<?= $aula['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="excluir.php?id=<?= $aula['id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Tem certeza que deseja excluir esta aula?')" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <a href="?pagina=<?= $i ?><?= $filtros['turma_id'] ? '&turma_id=' . $filtros['turma_id'] : '' ?><?= $filtros['disciplina_id'] ? '&disciplina_id=' . $filtros['disciplina_id'] : '' ?>"
                               class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
function traduzirDiaSemana($dia) {
    $dias = [
        'Monday' => 'Segunda-feira',
        'Tuesday' => 'Terça-feira',
        'Wednesday' => 'Quarta-feira',
        'Thursday' => 'Quinta-feira',
        'Friday' => 'Sexta-feira',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    return $dias[$dia] ?? $dia;
}
?>