<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para professores

$database = new Database();
$db = $database->getConnection();

// Filtros
$turma_id = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : null;
$disciplina_id = isset($_GET['disciplina_id']) ? (int)$_GET['disciplina_id'] : null;

// Buscar turmas e disciplinas
$turmas = $db->query("SELECT id, nome FROM turmas WHERE ativo = 1 ORDER BY nome")->fetchAll();
$disciplinas = $db->query("SELECT id, nome FROM disciplinas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Consulta avaliações
$query = "SELECT a.*, al.nome_completo AS aluno, d.nome AS disciplina, t.nome AS turma
          FROM avaliacoes a
          JOIN alunos al ON a.aluno_id = al.id
          JOIN disciplinas d ON a.disciplina_id = d.id
          JOIN turmas t ON a.turma_id = t.id
          WHERE 1=1";
$params = [];

if ($turma_id) {
    $query .= " AND a.turma_id = :turma_id";
    $params[':turma_id'] = $turma_id;
}

if ($disciplina_id) {
    $query .= " AND a.disciplina_id = :disciplina_id";
    $params[':disciplina_id'] = $disciplina_id;
}

$query .= " ORDER BY a.data_avaliacao DESC";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações - <?= SISTEMA_NOME ?></title>
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
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        select, input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
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
            <h1><i class="fas fa-clipboard-check"></i> Avaliações</h1>
            <div>
                <a href="criar.php" class="btn btn-add"><i class="fas fa-plus"></i> Adicionar Avaliação</a>
                <a href="/PHP/index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Voltar ao Menu</a>
            </div>
        </div>

        <div class="filter-form">
            <form method="get">
                <div class="form-row">
                    <div class="form-group">
                        <label for="turma_id">Turma:</label>
                        <select name="turma_id" id="turma_id">
                            <option value="">Todas as Turmas</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?= $turma['id'] ?>" <?= $turma_id == $turma['id'] ? 'selected' : '' ?>>
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
                                <option value="<?= $disciplina['id'] ?>" <?= $disciplina_id == $disciplina['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($disciplina['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-broom"></i> Limpar Filtros</a>
            </form>
        </div>

        <?php if (empty($avaliacoes)): ?>
            <div class="no-results">
                <p>Nenhuma avaliação encontrada com os critérios selecionados.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Disciplina</th>
                        <th>Turma</th>
                        <th>Tipo</th>
                        <th>Nota</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avaliacoes as $avaliacao): ?>
                        <tr>
                            <td><?= htmlspecialchars($avaliacao['aluno']) ?></td>
                            <td><?= htmlspecialchars($avaliacao['disciplina']) ?></td>
                            <td><?= htmlspecialchars($avaliacao['turma']) ?></td>
                            <td><?= htmlspecialchars($avaliacao['tipo_avaliacao']) ?></td>
                            <td>
                                <span class="badge <?= $avaliacao['nota'] >= 10 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= number_format($avaliacao['nota'], 2) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?></td>
                            <td class="action-buttons">
                                <a href="editar.php?id=<?= $avaliacao['id'] ?>" class="btn btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <a href="excluir.php?id=<?= $avaliacao['id'] ?>" class="btn btn-secondary" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta avaliação?');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        // Adiciona confirmação antes de excluir
        document.querySelectorAll('.btn-excluir').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirm('Tem certeza que deseja excluir esta avaliação?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>