<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para coordenadores/professores

$database = new Database();
$db = $database->getConnection();

// Buscar todas as disciplinas com informações do curso
$query = "SELECT d.*, c.nome as nome_curso 
          FROM disciplinas d
          LEFT JOIN cursos c ON d.curso_id = c.id
          ORDER BY d.nome";
$disciplinas = $db->query($query)->fetchAll();

// Buscar cursos para filtro
$cursos = $db->query("SELECT id, nome FROM cursos ORDER BY nome")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Disciplinas - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-ativo {
            background-color: #28a745;
            color: white;
        }

        .badge-inativo {
            background-color: #dc3545;
            color: white;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #3498db;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-sm {
            padding: 3px 8px;
            font-size: 12px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-form .form-group {
            flex: 1;
        }

        .filter-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .filter-form select,
        .filter-form input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-book"></i> Disciplinas</h1>
            <a href="criar.php" class="btn btn-success">Nova Disciplina</a>
            <a href="/PHP/index.php" class="btn btn-secondary">Voltar</a>
        </div>

        <div class="card">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label for="curso">Filtrar por Curso</label>
                    <select name="curso" id="curso">
                        <option value="">Todos os Cursos</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?= $curso['id'] ?>" <?= isset($_GET['curso']) && $_GET['curso'] == $curso['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($curso['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">Todos</option>
                        <option value="1" <?= isset($_GET['status']) && $_GET['status'] == '1' ? 'selected' : '' ?>>Ativas</option>
                        <option value="0" <?= isset($_GET['status']) && $_GET['status'] == '0' ? 'selected' : '' ?>>Inativas</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semestre">Semestre</label>
                    <select name="semestre" id="semestre">
                        <option value="">Todos</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= isset($_GET['semestre']) && $_GET['semestre'] == $i ? 'selected' : '' ?>>
                                <?= $i ?>º Semestre
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="align-self: flex-end;">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="index.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Curso</th>
                        <th>Carga Horária</th>
                        <th>Semestre</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($disciplinas as $disciplina): ?>
                        <tr>
                            <td><?= htmlspecialchars($disciplina['codigo']) ?></td>
                            <td><?= htmlspecialchars($disciplina['nome']) ?></td>
                            <td><?= htmlspecialchars($disciplina['nome_curso'] ?? 'N/A') ?></td>
                            <td><?= $disciplina['carga_horaria'] ?> horas</td>
                            <td><?= $disciplina['semestre'] ?>º</td>
                            <td>
                                <span class="badge <?= $disciplina['ativo'] ? 'badge-ativo' : 'badge-inativo' ?>">
                                    <?= $disciplina['ativo'] ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </td>
                            <td>
                                <a href="editar.php?id=<?= $disciplina['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="excluir.php?id=<?= $disciplina['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir esta disciplina?');">
                                    <i class="fas fa-trash"></i> Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($disciplinas)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Nenhuma disciplina encontrada</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>