<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(7); // Nível de acesso maior para relatórios

$database = new Database();
$db = $database->getConnection();

// Filtros
$filtros = [
    'curso_id' => isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : null,
    'turma_id' => isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : null,
    'disciplina_id' => isset($_GET['disciplina_id']) ? (int)$_GET['disciplina_id'] : null,
    'semestre' => isset($_GET['semestre']) ? (int)$_GET['semestre'] : null,
    'ano_letivo' => isset($_GET['ano_letivo']) ? (int)$_GET['ano_letivo'] : date('Y'),
    'tipo_avaliacao' => $_GET['tipo_avaliacao'] ?? null
];

// Construir WHERE
$where = "WHERE a.nota IS NOT NULL";
$params = [];

if ($filtros['curso_id']) {
    $where .= " AND d.curso_id = :curso_id";
    $params[':curso_id'] = $filtros['curso_id'];
}

if ($filtros['turma_id']) {
    $where .= " AND a.turma_id = :turma_id";
    $params[':turma_id'] = $filtros['turma_id'];
}

if ($filtros['disciplina_id']) {
    $where .= " AND a.disciplina_id = :disciplina_id";
    $params[':disciplina_id'] = $filtros['disciplina_id'];
}

if ($filtros['semestre']) {
    $where .= " AND d.semestre = :semestre";
    $params[':semestre'] = $filtros['semestre'];
}

if ($filtros['ano_letivo']) {
    $where .= " AND t.ano_letivo = :ano_letivo";
    $params[':ano_letivo'] = $filtros['ano_letivo'];
}

if ($filtros['tipo_avaliacao']) {
    $where .= " AND a.tipo_avaliacao = :tipo_avaliacao";
    $params[':tipo_avaliacao'] = $filtros['tipo_avaliacao'];
}

// Buscar cursos para filtro
$cursos = $db->query("SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar todas turmas ativas para filtro
$turmas = $db->query("SELECT id, nome FROM turmas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar todas disciplinas ativas para filtro
$disciplinas = $db->query("SELECT id, nome FROM disciplinas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Consulta principal corrigida para o modo ONLY_FULL_GROUP_BY
$sql = "SELECT 
            c.nome AS curso,
            t.nome AS turma,
            d.nome AS disciplina,
            d.semestre,
            a.aluno_id,
            al.nome_completo AS aluno,
            AVG(a.nota) AS media,
            COUNT(a.id) AS total_avaliacoes,
            MAX(a.nota) AS melhor_nota,
            MIN(a.nota) AS pior_nota
        FROM avaliacoes a
        JOIN disciplinas d ON a.disciplina_id = d.id
        JOIN cursos c ON d.curso_id = c.id
        JOIN turmas t ON a.turma_id = t.id
        JOIN alunos al ON a.aluno_id = al.id
        $where
        GROUP BY a.aluno_id, a.disciplina_id, a.turma_id, c.nome, t.nome, d.nome, d.semestre, al.nome_completo
        ORDER BY c.nome, t.nome, d.nome, al.nome_completo";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular estatísticas gerais
$total_alunos = count($dados);
$media_geral = 0;
$melhor_media = 0;
$pior_media = 20;
$aprovados = 0;
$reprovados = 0;

$medias_por_disciplina = [];
$medias_por_turma = [];

if ($total_alunos > 0) {
    $soma_medias = 0;
    
    foreach ($dados as $linha) {
        $media = (float)$linha['media'];
        $soma_medias += $media;
        
        if ($media > $melhor_media) {
            $melhor_media = $media;
        }
        
        if ($media < $pior_media) {
            $pior_media = $media;
        }
        
        if ($media >= 10) {
            $aprovados++;
        } else {
            $reprovados++;
        }
        
        // Estatísticas por disciplina
        if (!isset($medias_por_disciplina[$linha['disciplina']])) {
            $medias_por_disciplina[$linha['disciplina']] = [
                'soma' => 0,
                'contagem' => 0,
                'melhor' => 0,
                'pior' => 20
            ];
        }
        $medias_por_disciplina[$linha['disciplina']]['soma'] += $media;
        $medias_por_disciplina[$linha['disciplina']]['contagem']++;
        
        if ($media > $medias_por_disciplina[$linha['disciplina']]['melhor']) {
            $medias_por_disciplina[$linha['disciplina']]['melhor'] = $media;
        }
        
        if ($media < $medias_por_disciplina[$linha['disciplina']]['pior']) {
            $medias_por_disciplina[$linha['disciplina']]['pior'] = $media;
        }
        
        // Estatísticas por turma
        if (!isset($medias_por_turma[$linha['turma']])) {
            $medias_por_turma[$linha['turma']] = [
                'soma' => 0,
                'contagem' => 0,
                'melhor' => 0,
                'pior' => 20
            ];
        }
        $medias_por_turma[$linha['turma']]['soma'] += $media;
        $medias_por_turma[$linha['turma']]['contagem']++;
        
        if ($media > $medias_por_turma[$linha['turma']]['melhor']) {
            $medias_por_turma[$linha['turma']]['melhor'] = $media;
        }
        
        if ($media < $medias_por_turma[$linha['turma']]['pior']) {
            $medias_por_turma[$linha['turma']]['pior'] = $media;
        }
    }
    
    $media_geral = $soma_medias / $total_alunos;
    
    // Calcular médias por disciplina
    foreach ($medias_por_disciplina as &$disciplina) {
        $disciplina['media'] = $disciplina['soma'] / $disciplina['contagem'];
    }
    
    // Calcular médias por turma
    foreach ($medias_por_turma as &$turma) {
        $turma['media'] = $turma['soma'] / $turma['contagem'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Desempenho Acadêmico - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-item {
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .summary-item h4 {
            margin-top: 0;
            color: #6c757d;
        }
        .summary-item p {
            font-size: 1.5em;
            margin-bottom: 0;
            font-weight: bold;
        }
        .chart-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .chart-box {
            width: 48%;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .report-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .report-table th {
            background-color: #343a40;
            color: white;
            padding: 10px;
            text-align: left;
        }
        .report-table td, .report-table th {
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        .report-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .total-row {
            font-weight: bold;
            background-color: #e9ecef !important;
        }
        .print-button {
            margin-bottom: 20px;
        }
        .approved {
            color: #28a745;
        }
        .failed {
            color: #dc3545;
        }
        .progress {
            height: 20px;
            margin-bottom: 10px;
        }
        .progress-bar {
            transition: width 0.6s ease;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
            margin-right: 15px;
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .badge-success {
            color: #fff;
            background-color: #28a745;
        }
        .badge-danger {
            color: #fff;
            background-color: #dc3545;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, 
                        border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn:hover {
            opacity: 0.8;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                background: white;
            }
            .card {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="content-header">
            <h1><i class="fas fa-chart-line"></i> Relatório de Desempenho Acadêmico</h1>
            <div class="header-actions no-print">
                <a href="/PHP/index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Menu Principal</a>
                <a href="javascript:window.print()" class="btn btn-primary print-button"><i class="fas fa-print"></i> Imprimir</a>
            </div>
        </div>

        <div class="card no-print">
            <div class="card-header">
                <h3><i class="fas fa-filter"></i> Filtros</h3>
            </div>
            <div class="card-body">
                <form method="get" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="curso_id">Curso:</label>
                            <select name="curso_id" id="curso_id">
                                <option value="">Todos</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?= $curso['id'] ?>" <?= $filtros['curso_id'] == $curso['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($curso['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="turma_id">Turma:</label>
                            <select name="turma_id" id="turma_id">
                                <option value="">Todas</option>
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
                                <option value="">Todas</option>
                                <?php foreach ($disciplinas as $disciplina): ?>
                                    <option value="<?= $disciplina['id'] ?>" <?= $filtros['disciplina_id'] == $disciplina['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($disciplina['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="semestre">Semestre:</label>
                            <select name="semestre">
                                <option value="">Todos</option>
                                <option value="1" <?= $filtros['semestre'] == 1 ? 'selected' : '' ?>>1º Semestre</option>
                                <option value="2" <?= $filtros['semestre'] == 2 ? 'selected' : '' ?>>2º Semestre</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="ano_letivo">Ano Letivo:</label>
                            <input type="number" name="ano_letivo" min="2000" max="2100" value="<?= $filtros['ano_letivo'] ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_avaliacao">Tipo de Avaliação:</label>
                            <select name="tipo_avaliacao">
                                <option value="">Todos</option>
                                <option value="Teste" <?= $filtros['tipo_avaliacao'] == 'Teste' ? 'selected' : '' ?>>Teste</option>
                                <option value="Exame" <?= $filtros['tipo_avaliacao'] == 'Exame' ? 'selected' : '' ?>>Exame</option>
                                <option value="Trabalho" <?= $filtros['tipo_avaliacao'] == 'Trabalho' ? 'selected' : '' ?>>Trabalho</option>
                                <option value="Projeto" <?= $filtros['tipo_avaliacao'] == 'Projeto' ? 'selected' : '' ?>>Projeto</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                    <a href="desempenho.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                </form>
            </div>
        </div>

        <div class="report-summary">
            <h3>Resumo do Relatório</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <h4>Total de Alunos</h4>
                    <p><?= $total_alunos ?></p>
                </div>
                <div class="summary-item">
                    <h4>Média Geral</h4>
                    <p><?= $total_alunos > 0 ? number_format($media_geral, 1) : '0.0' ?></p>
                </div>
                <div class="summary-item">
                    <h4>Melhor Média</h4>
                    <p class="approved"><?= $total_alunos > 0 ? number_format($melhor_media, 1) : '0.0' ?></p>
                </div>
                <div class="summary-item">
                    <h4>Pior Média</h4>
                    <p class="failed"><?= $total_alunos > 0 ? number_format($pior_media, 1) : '0.0' ?></p>
                </div>
            </div>
            
            <?php if ($total_alunos > 0): ?>
            <div class="progress-container">
                <h4>Taxa de Aprovação</h4>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?= ($aprovados / $total_alunos) * 100 ?>%" 
                         aria-valuenow="<?= $aprovados ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="<?= $total_alunos ?>">
                        <?= number_format(($aprovados / $total_alunos) * 100, 1) ?>%
                    </div>
                </div>
                <p><?= $aprovados ?> aprovados de <?= $total_alunos ?> alunos (<?= number_format(($aprovados / $total_alunos) * 100, 1) ?>%)</p>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($total_alunos > 0): ?>
        <div class="chart-container no-print">
            <div class="chart-box">
                <canvas id="chartPorDisciplina"></canvas>
            </div>
            <div class="chart-box">
                <canvas id="chartPorTurma"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-table"></i> Desempenho por Disciplina</h3>
            </div>
            <div class="card-body">
                <?php if (empty($medias_por_disciplina)): ?>
                    <div class="alert alert-info">Nenhum dado encontrado com os filtros aplicados.</div>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Disciplina</th>
                                <th>Média</th>
                                <th>Melhor Nota</th>
                                <th>Pior Nota</th>
                                <th>Progresso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medias_por_disciplina as $disciplina => $estatisticas): ?>
                                <tr>
                                    <td><?= htmlspecialchars($disciplina) ?></td>
                                    <td><?= number_format($estatisticas['media'], 1) ?></td>
                                    <td class="approved"><?= number_format($estatisticas['melhor'], 1) ?></td>
                                    <td class="failed"><?= number_format($estatisticas['pior'], 1) ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar <?= $estatisticas['media'] >= 10 ? 'bg-success' : 'bg-danger' ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= ($estatisticas['media'] / 20) * 100 ?>%" 
                                                 aria-valuenow="<?= $estatisticas['media'] ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="20">
                                                <?= number_format(($estatisticas['media'] / 20) * 100, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-table"></i> Desempenho Detalhado</h3>
            </div>
            <div class="card-body">
                <?php if (empty($dados)): ?>
                    <div class="alert alert-info">Nenhum dado encontrado com os filtros aplicados.</div>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Turma</th>
                                <th>Disciplina</th>
                                <th>Semestre</th>
                                <th>Aluno</th>
                                <th>Média</th>
                                <th>Total Avaliações</th>
                                <th>Melhor Nota</th>
                                <th>Pior Nota</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $curso_atual = null;
                            $turma_atual = null;
                            foreach ($dados as $linha): 
                                // Adicionar linha de total por curso quando muda
                                if ($curso_atual !== null && $curso_atual != $linha['curso']) {
                                    echo '<tr class="total-row">
                                        <td colspan="5">Total '.htmlspecialchars($curso_atual).'</td>
                                        <td>'.number_format(array_sum(array_column(array_filter($dados, function($d) use ($curso_atual) {
                                            return $d['curso'] == $curso_atual;
                                        }), 'media')) / count(array_filter($dados, function($d) use ($curso_atual) {
                                            return $d['curso'] == $curso_atual;
                                        })), 1).'</td>
                                        <td colspan="4"></td>
                                    </tr>';
                                }
                                $curso_atual = $linha['curso'];
                                
                                // Adicionar linha de total por turma quando muda
                                if ($turma_atual !== null && $turma_atual != $linha['turma']) {
                                    echo '<tr class="total-row">
                                        <td colspan="1"></td>
                                        <td colspan="4">Total '.htmlspecialchars($turma_atual).'</td>
                                        <td>'.number_format(array_sum(array_column(array_filter($dados, function($d) use ($turma_atual) {
                                            return $d['turma'] == $turma_atual;
                                        }), 'media')) / count(array_filter($dados, function($d) use ($turma_atual) {
                                            return $d['turma'] == $turma_atual;
                                        })), 1).'</td>
                                        <td colspan="4"></td>
                                    </tr>';
                                }
                                $turma_atual = $linha['turma'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($linha['curso']) ?></td>
                                    <td><?= htmlspecialchars($linha['turma']) ?></td>
                                    <td><?= htmlspecialchars($linha['disciplina']) ?></td>
                                    <td><?= $linha['semestre'] ?></td>
                                    <td><?= htmlspecialchars($linha['aluno']) ?></td>
                                    <td class="<?= $linha['media'] >= 10 ? 'approved' : 'failed' ?>"><?= number_format($linha['media'], 1) ?></td>
                                    <td><?= $linha['total_avaliacoes'] ?></td>
                                    <td class="approved"><?= number_format($linha['melhor_nota'], 1) ?></td>
                                    <td class="failed"><?= number_format($linha['pior_nota'], 1) ?></td>
                                    <td>
                                        <span class="badge <?= $linha['media'] >= 10 ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $linha['media'] >= 10 ? 'Aprovado' : 'Reprovado' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Últimos totais -->
                            <?php if (!empty($dados)): ?>
                                <tr class="total-row">
                                    <td colspan="5">Total <?= htmlspecialchars($curso_atual) ?></td>
                                    <td><?= number_format(array_sum(array_column(array_filter($dados, function($d) use ($curso_atual) {
                                        return $d['curso'] == $curso_atual;
                                    }), 'media')) / count(array_filter($dados, function($d) use ($curso_atual) {
                                        return $d['curso'] == $curso_atual;
                                    }), 1) )?></td>
                                    <td colspan="4"></td>
                                </tr>
                                
                                <tr class="total-row">
                                    <td colspan="1"></td>
                                    <td colspan="4">Total <?= htmlspecialchars($turma_atual) ?></td>
                                    <td><?= number_format(array_sum(array_column(array_filter($dados, function($d) use ($turma_atual) {
                                        return $d['turma'] == $turma_atual;
                                    }), 'media')) / count(array_filter($dados, function($d) use ($turma_atual) {
                                        return $d['turma'] == $turma_atual;
                                    }), 1) )?></td>
                                    <td colspan="4"></td>
                                </tr>
                                
                                <tr class="total-row bg-dark text-white">
                                    <td colspan="5">Total Geral</td>
                                    <td><?= number_format($media_geral, 1) ?></td>
                                    <td><?= array_sum(array_column($dados, 'total_avaliacoes')) ?></td>
                                    <td><?= number_format($melhor_media, 1) ?></td>
                                    <td><?= number_format($pior_media, 1) ?></td>
                                    <td><?= $aprovados ?>A / <?= $reprovados ?>R</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Atualizar turmas e disciplinas quando o curso for alterado
        document.getElementById('curso_id').addEventListener('change', function() {
            const cursoId = this.value;
            const turmaSelect = document.getElementById('turma_id');
            const disciplinaSelect = document.getElementById('disciplina_id');
            
            if (!cursoId) {
                // Se nenhum curso selecionado, mostrar todas as turmas e disciplinas
                fetch(`/PHP/api/getTurmas.php`)
                    .then(response => response.json())
                    .then(data => {
                        turmaSelect.innerHTML = '<option value="">Todas</option>';
                        data.forEach(turma => {
                            const option = document.createElement('option');
                            option.value = turma.id;
                            option.textContent = turma.nome;
                            turmaSelect.appendChild(option);
                        });
                        // Manter seleção se houver
                        if (<?= $filtros['turma_id'] ?? 'null' ?>) {
                            turmaSelect.value = <?= $filtros['turma_id'] ?? 'null' ?>;
                        }
                    });
                
                fetch(`/PHP/api/getDisciplinas.php`)
                    .then(response => response.json())
                    .then(data => {
                        disciplinaSelect.innerHTML = '<option value="">Todas</option>';
                        data.forEach(disciplina => {
                            const option = document.createElement('option');
                            option.value = disciplina.id;
                            option.textContent = disciplina.nome;
                            disciplinaSelect.appendChild(option);
                        });
                        // Manter seleção se houver
                        if (<?= $filtros['disciplina_id'] ?? 'null' ?>) {
                            disciplinaSelect.value = <?= $filtros['disciplina_id'] ?? 'null' ?>;
                        }
                    });
                return;
            }
            
            // Buscar turmas do curso selecionado
            fetch(`/PHP/api/getTurmasByCurso.php?curso_id=${cursoId}`)
                .then(response => response.json())
                .then(data => {
                    turmaSelect.innerHTML = '<option value="">Todas</option>';
                    data.forEach(turma => {
                        const option = document.createElement('option');
                        option.value = turma.id;
                        option.textContent = turma.nome;
                        turmaSelect.appendChild(option);
                    });
                    // Manter seleção se houver
                    if (<?= $filtros['turma_id'] ?? 'null' ?>) {
                        turmaSelect.value = <?= $filtros['turma_id'] ?? 'null' ?>;
                    }
                });
            
            // Buscar disciplinas do curso selecionado
            fetch(`/PHP/api/getDisciplinasByCurso.php?curso_id=${cursoId}`)
                .then(response => response.json())
                .then(data => {
                    disciplinaSelect.innerHTML = '<option value="">Todas</option>';
                    data.forEach(disciplina => {
                        const option = document.createElement('option');
                        option.value = disciplina.id;
                        option.textContent = disciplina.nome;
                        disciplinaSelect.appendChild(option);
                    });
                    // Manter seleção se houver
                    if (<?= $filtros['disciplina_id'] ?? 'null' ?>) {
                        disciplinaSelect.value = <?= $filtros['disciplina_id'] ?? 'null' ?>;
                    }
                });
        });

        <?php if (!empty($medias_por_disciplina)): ?>
        // Gráfico por disciplina
        const ctxDisciplina = document.getElementById('chartPorDisciplina').getContext('2d');
        new Chart(ctxDisciplina, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(function($d) { return "'".addslashes($d)."'"; }, array_keys($medias_por_disciplina)) )?>],
                datasets: [{
                    label: 'Média por Disciplina',
                    data: [<?= implode(',', array_column($medias_por_disciplina, 'media')) ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Médias por Disciplina',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Média: ${context.raw.toFixed(1)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 20,
                        ticks: {
                            callback: function(value) {
                                return value;
                            }
                        }
                    }
                }
            }
        });

        // Gráfico por turma
        const ctxTurma = document.getElementById('chartPorTurma').getContext('2d');
        new Chart(ctxTurma, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(function($d) { return "'".addslashes($d)."'"; }, array_keys($medias_por_turma))) ?>],
                datasets: [{
                    label: 'Média por Turma',
                    data: [<?= implode(',', array_column($medias_por_turma, 'media')) ?>],
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Médias por Turma',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Média: ${context.raw.toFixed(1)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 20,
                        ticks: {
                            callback: function(value) {
                                return value;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
    <script src="/Context/JS/script.js"></script>
</body>
</html>