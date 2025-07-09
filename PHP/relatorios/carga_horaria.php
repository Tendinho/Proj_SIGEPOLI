<?php
// Inicialização da sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// Verificação de login e acesso (simplificada)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel_acesso'] ?? 0) < 7) {
    header("Location: /PHP/login.php");
    exit();
}

// Conexão com o banco de dados
try {
    $db = new PDO('mysql:host=localhost;dbname=sigepoli;charset=utf8mb4', 'admin', 'SenhaSegura123!');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Filtros
$filtros = [
    'curso_id' => isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : null,
    'professor_id' => isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : null,
    'semestre' => isset($_GET['semestre']) ? (int)$_GET['semestre'] : null,
    'ano_letivo' => isset($_GET['ano_letivo']) ? (int)$_GET['ano_letivo'] : date('Y'),
    'periodo' => $_GET['periodo'] ?? null
];

// Construir WHERE
$where = "WHERE 1=1";
$params = [];

if ($filtros['curso_id']) {
    $where .= " AND d.curso_id = :curso_id";
    $params[':curso_id'] = $filtros['curso_id'];
}

if ($filtros['professor_id']) {
    $where .= " AND a.professor_id = :professor_id";
    $params[':professor_id'] = $filtros['professor_id'];
}

if ($filtros['semestre']) {
    $where .= " AND d.semestre = :semestre";
    $params[':semestre'] = $filtros['semestre'];
}

if ($filtros['ano_letivo']) {
    $where .= " AND t.ano_letivo = :ano_letivo";
    $params[':ano_letivo'] = $filtros['ano_letivo'];
}

if ($filtros['periodo']) {
    $where .= " AND t.periodo = :periodo";
    $params[':periodo'] = $filtros['periodo'];
}

// Buscar cursos para filtro
try {
    $cursos = $db->query("SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cursos = [];
    $erro = "Erro ao carregar cursos: " . $e->getMessage();
}

// Buscar professores para filtro
try {
    $professores = $db->query("SELECT p.id, f.nome_completo 
                             FROM professores p 
                             JOIN funcionarios f ON p.funcionario_id = f.id 
                             WHERE p.ativo = 1 
                             ORDER BY f.nome_completo")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $professores = [];
    $erro = "Erro ao carregar professores: " . $e->getMessage();
}

// Buscar dados para o relatório
$dados = [];
$total_geral_horas = 0;
$totais_por_professor = [];
$totais_por_curso = [];

try {
    $sql = "SELECT 
                c.nome AS curso,
                d.nome AS disciplina,
                d.semestre,
                d.carga_horaria,
                p.id AS professor_id,
                f.nome_completo AS professor,
                t.nome AS turma,
                t.periodo,
                COUNT(a.id) AS total_aulas,
                SUM(TIME_TO_SEC(TIMEDIFF(a.hora_fim, a.hora_inicio))/3600) AS horas_lecionadas
            FROM aulas a
            JOIN disciplinas d ON a.disciplina_id = d.id
            JOIN cursos c ON d.curso_id = c.id
            JOIN professores p ON a.professor_id = p.id
            JOIN funcionarios f ON p.funcionario_id = f.id
            JOIN turmas t ON a.turma_id = t.id
            $where
            GROUP BY d.id, p.id, t.id
            ORDER BY c.nome, d.semestre, d.nome, f.nome_completo";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular totais
    foreach ($dados as $linha) {
        $horas = (float)$linha['horas_lecionadas'];
        $total_geral_horas += $horas;
        
        // Totais por professor
        if (!isset($totais_por_professor[$linha['professor_id']])) {
            $totais_por_professor[$linha['professor_id']] = [
                'nome' => $linha['professor'],
                'total' => 0
            ];
        }
        $totais_por_professor[$linha['professor_id']]['total'] += $horas;
        
        // Totais por curso
        if (!isset($totais_por_curso[$linha['curso']])) {
            $totais_por_curso[$linha['curso']] = 0;
        }
        $totais_por_curso[$linha['curso']] += $horas;
    }
} catch (PDOException $e) {
    $erro = "Erro ao gerar relatório: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Carga Horária - SIGEPOLI</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        h1 {
            color: #2c3e50;
            margin: 0;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h3 {
            margin: 0;
            color: #495057;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .filter-form {
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
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .report-summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .report-table th {
            background-color: #343a40;
            color: white;
            text-align: left;
        }
        
        .report-table td, .report-table th {
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        
        .report-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #e9ecef !important;
        }
        
        .bg-dark {
            background-color: #343a40 !important;
            color: white;
        }
        
        .text-white {
            color: white !important;
        }
        
        .progress {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
        }
        
        .bg-success {
            background-color: #28a745;
        }
        
        .bg-info {
            background-color: #17a2b8;
        }
        
        .bg-warning {
            background-color: #ffc107;
        }
        
        .bg-danger {
            background-color: #dc3545;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                padding: 0;
                font-size: 12px;
            }
            
            .report-table td, .report-table th {
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="content-header">
            <h1><i class="fas fa-clock"></i> Relatório de Carga Horária</h1>
            <div class="header-actions no-print">
                <a href="/PHP/index.php" class="btn btn-secondary">Menu Principal</a>
                <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
            </div>
        </div>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="card no-print">
            <div class="card-header">
                <h3>Filtros</h3>
            </div>
            <div class="card-body">
                <form method="get" class="filter-form">
                    <div class="form-row">
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
                            <label for="professor_id">Professor:</label>
                            <select name="professor_id">
                                <option value="">Todos</option>
                                <?php foreach ($professores as $professor): ?>
                                    <option value="<?= $professor['id'] ?>" <?= $filtros['professor_id'] == $professor['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($professor['nome_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="semestre">Semestre:</label>
                            <select name="semestre">
                                <option value="">Todos</option>
                                <option value="1" <?= $filtros['semestre'] == 1 ? 'selected' : '' ?>>1º Semestre</option>
                                <option value="2" <?= $filtros['semestre'] == 2 ? 'selected' : '' ?>>2º Semestre</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
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
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                    <a href="carga_horaria.php" class="btn btn-secondary">Limpar</a>
                </form>
            </div>
        </div>

        <div class="report-summary">
            <h3>Resumo do Relatório</h3>
            <p><strong>Total de Professores:</strong> <?= count($totais_por_professor) ?></p>
            <p><strong>Total de Cursos:</strong> <?= count($totais_por_curso) ?></p>
            <p><strong>Total de Horas Lecionadas:</strong> <?= number_format($total_geral_horas, 2) ?> horas</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Detalhes da Carga Horária</h3>
            </div>
            <div class="card-body">
                <?php if (empty($dados)): ?>
                    <div class="alert alert-info">Nenhum dado encontrado com os filtros aplicados.</div>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Disciplina</th>
                                <th>Semestre</th>
                                <th>Carga Horária</th>
                                <th>Professor</th>
                                <th>Turma</th>
                                <th>Período</th>
                                <th>Total Aulas</th>
                                <th>Horas Lecionadas</th>
                                <th>% Concluído</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $curso_atual = null;
                            $professor_atual = null;
                            foreach ($dados as $linha): 
                                $percentual = $linha['carga_horaria'] > 0 ? 
                                    ($linha['horas_lecionadas'] / $linha['carga_horaria']) * 100 : 0;
                                
                                // Adicionar linha de total por curso quando muda
                                if ($curso_atual !== null && $curso_atual != $linha['curso']) {
                                    echo '<tr class="total-row">
                                        <td colspan="3">Total '.htmlspecialchars($curso_atual).'</td>
                                        <td>'.array_sum(array_column(array_filter($dados, function($d) use ($curso_atual) {
                                            return $d['curso'] == $curso_atual;
                                        }), 'carga_horaria')).'</td>
                                        <td colspan="4"></td>
                                        <td>'.number_format($totais_por_curso[$curso_atual], 2).'</td>
                                        <td></td>
                                    </tr>';
                                }
                                $curso_atual = $linha['curso'];
                                
                                // Adicionar linha de total por professor quando muda
                                if ($professor_atual !== null && $professor_atual != $linha['professor_id']) {
                                    echo '<tr class="total-row">
                                        <td colspan="4"></td>
                                        <td>Total '.htmlspecialchars($totais_por_professor[$professor_atual]['nome']).'</td>
                                        <td colspan="3"></td>
                                        <td>'.number_format($totais_por_professor[$professor_atual]['total'], 2).'</td>
                                        <td></td>
                                    </tr>';
                                }
                                $professor_atual = $linha['professor_id'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($linha['curso']) ?></td>
                                    <td><?= htmlspecialchars($linha['disciplina']) ?></td>
                                    <td><?= $linha['semestre'] ?></td>
                                    <td><?= $linha['carga_horaria'] ?> horas</td>
                                    <td><?= htmlspecialchars($linha['professor']) ?></td>
                                    <td><?= htmlspecialchars($linha['turma']) ?></td>
                                    <td><?= htmlspecialchars($linha['periodo']) ?></td>
                                    <td><?= $linha['total_aulas'] ?></td>
                                    <td><?= number_format($linha['horas_lecionadas'], 2) ?> horas</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar <?= $percentual >= 100 ? 'bg-success' : ($percentual >= 75 ? 'bg-info' : ($percentual >= 50 ? 'bg-warning' : 'bg-danger')) ?>" 
                                                 style="width: <?= min($percentual, 100) ?>%;">
                                                <?= number_format($percentual, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Últimos totais -->
                            <?php if (!empty($dados)): ?>
                                <tr class="total-row">
                                    <td colspan="3">Total <?= htmlspecialchars($curso_atual) ?></td>
                                    <td><?= array_sum(array_column(array_filter($dados, function($d) use ($curso_atual) {
                                        return $d['curso'] == $curso_atual;
                                    }), 'carga_horaria')) ?></td>
                                    <td colspan="4"></td>
                                    <td><?= number_format($totais_por_curso[$curso_atual], 2) ?></td>
                                    <td></td>
                                </tr>
                                
                                <tr class="total-row">
                                    <td colspan="4"></td>
                                    <td>Total <?= htmlspecialchars($totais_por_professor[$professor_atual]['nome']) ?></td>
                                    <td colspan="3"></td>
                                    <td><?= number_format($totais_por_professor[$professor_atual]['total'], 2) ?></td>
                                    <td></td>
                                </tr>
                                
                                <tr class="total-row bg-dark text-white">
                                    <td colspan="7">Total Geral</td>
                                    <td><?= array_sum(array_column($dados, 'total_aulas')) ?></td>
                                    <td><?= number_format($total_geral_horas, 2) ?> horas</td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>