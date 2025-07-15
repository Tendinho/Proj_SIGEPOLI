<?php
// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'sigepoli';
$username = 'root';
$password = '2001';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Proj_SIGEPOLI/PHP/login.php");
    exit();
}

// Verificar se o usuário é professor
$usuario_id = $_SESSION['usuario_id'];
$query_professor = "SELECT p.id 
                    FROM professores p
                    JOIN funcionarios f ON p.funcionario_id = f.id
                    JOIN usuarios u ON f.usuario_id = u.id
                    WHERE u.id = :usuario_id
                    AND p.ativo = 1";
$stmt_professor = $db->prepare($query_professor);
$stmt_professor->bindParam(":usuario_id", $usuario_id);
$stmt_professor->execute();
$professor = $stmt_professor->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    $_SESSION['mensagem'] = "Acesso restrito a professores!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: /Proj_SIGEPOLI/PHP/index.php");
    exit();
}

// Filtros
$turma_id = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : null;
$disciplina_id = isset($_GET['disciplina_id']) ? (int)$_GET['disciplina_id'] : null;

// Buscar turmas do professor
$query_turmas = "SELECT DISTINCT t.id, t.nome, t.ano_letivo, t.semestre, c.nome as curso_nome
                 FROM turmas t
                 JOIN aulas a ON t.id = a.turma_id
                 JOIN cursos c ON t.curso_id = c.id
                 WHERE a.professor_id = :professor_id
                 AND t.ativo = 1
                 ORDER BY t.ano_letivo DESC, t.semestre DESC, c.nome, t.nome";
$stmt_turmas = $db->prepare($query_turmas);
$stmt_turmas->bindParam(":professor_id", $professor['id']);
$stmt_turmas->execute();
$turmas = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);

// Buscar disciplinas do professor
$query_disciplinas = "SELECT DISTINCT d.id, d.nome, c.nome as curso_nome
                      FROM disciplinas d
                      JOIN aulas a ON d.id = a.disciplina_id
                      JOIN cursos c ON d.curso_id = c.id
                      WHERE a.professor_id = :professor_id
                      AND d.ativo = 1
                      ORDER BY c.nome, d.nome";
$stmt_disciplinas = $db->prepare($query_disciplinas);
$stmt_disciplinas->bindParam(":professor_id", $professor['id']);
$stmt_disciplinas->execute();
$disciplinas = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);

// Consulta avaliações
$query = "SELECT a.*, al.nome_completo AS aluno, d.nome AS disciplina, t.nome AS turma
          FROM avaliacoes a
          JOIN alunos al ON a.aluno_id = al.id
          JOIN disciplinas d ON a.disciplina_id = d.id
          JOIN turmas t ON a.turma_id = t.id
          JOIN aulas au ON a.disciplina_id = au.disciplina_id AND a.turma_id = au.turma_id
          WHERE au.professor_id = :professor_id";
$params = [':professor_id' => $professor['id']];

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

// Calcular médias
$medias = [];
foreach ($avaliacoes as $avaliacao) {
    $key = $avaliacao['aluno_id'] . '_' . $avaliacao['disciplina_id'];
    
    if (!isset($medias[$key])) {
        $medias[$key] = [
            'aluno_id' => $avaliacao['aluno_id'],
            'aluno' => $avaliacao['aluno'],
            'disciplina_id' => $avaliacao['disciplina_id'],
            'disciplina' => $avaliacao['disciplina'],
            'notas' => [],
            'media' => 0,
            'quantidade' => 0
        ];
    }
    
    $medias[$key]['notas'][] = $avaliacao['nota'];
    $medias[$key]['quantidade']++;
    $medias[$key]['media'] = array_sum($medias[$key]['notas']) / count($medias[$key]['notas']);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações - SIGEPOLI</title>
    <link rel="stylesheet" href="/Proj_SIGEPOLI/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Proj_SIGEPOLI/Context/fontawesome/css/all.min.css">
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
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
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
        .tab-container {
            margin-bottom: 20px;
        }
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #ddd;
        }
        .tab-button {
            padding: 10px 20px;
            background: #f8f9fa;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 500;
        }
        .tab-button.active {
            border-bottom: 3px solid #3498db;
            background: white;
        }
        .tab-content {
            display: none;
            padding: 20px;
            background: white;
            border-radius: 0 0 5px 5px;
        }
        .tab-content.active {
            display: block;
        }
        .media-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .info-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
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
                                    (<?= htmlspecialchars($turma['curso_nome']) ?> - 
                                    <?= htmlspecialchars($turma['ano_letivo']) ?> - 
                                    <?= htmlspecialchars($turma['semestre']) ?>º Semestre)
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
                                    (<?= htmlspecialchars($disciplina['curso_nome']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-broom"></i> Limpar Filtros</a>
            </form>
        </div>

        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button active" data-tab="avaliacoes">Avaliações</button>
                <button class="tab-button" data-tab="medias">Médias</button>
            </div>
            
            <div id="avaliacoes" class="tab-content active">
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
                                        <span class="badge <?= $avaliacao['nota'] >= 10 ? 'badge-success' : ($avaliacao['nota'] >= 5 ? 'badge-warning' : 'badge-danger') ?>">
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
            
            <div id="medias" class="tab-content">
                <?php if (empty($medias)): ?>
                    <div class="no-results">
                        <p>Nenhuma média calculada com os critérios selecionados.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Disciplina</th>
                                <th>Média</th>
                                <th>Quantidade</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medias as $media): ?>
                                <tr>
                                    <td><?= htmlspecialchars($media['aluno']) ?></td>
                                    <td><?= htmlspecialchars($media['disciplina']) ?></td>
                                    <td>
                                        <span class="badge <?= $media['media'] >= 10 ? 'badge-success' : ($media['media'] >= 5 ? 'badge-warning' : 'badge-danger') ?>">
                                            <?= number_format($media['media'], 2) ?>
                                        </span>
                                    </td>
                                    <td><?= $media['quantidade'] ?></td>
                                    <td>
                                        <?php if ($media['media'] >= 10): ?>
                                            <span class="badge badge-success">Aprovado</span>
                                        <?php elseif ($media['media'] >= 5): ?>
                                            <span class="badge badge-warning">Recuperação</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Reprovado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="info-text">* Médias calculadas com base nas avaliações filtradas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Tabs functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons and contents
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Add active class to clicked button and corresponding content
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>