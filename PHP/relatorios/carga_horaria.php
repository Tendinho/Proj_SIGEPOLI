<?php
// Inicialização da sessão com configurações de segurança
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// Verificação de login e acesso (nível 7 = coordenador)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel_acesso'] ?? 0) < 7) {
    header("Location: /PHP/login.php");
    exit();
}

// Conexão com o banco de dados com tratamento de erros
try {
    $db = new PDO('mysql:host=localhost;dbname=sigepoli;charset=utf8mb4', 'admin', 'SenhaSegura123!');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Classe para gerenciar o relatório
class RelatorioCargaHoraria {
    private $db;
    private $filtros;
    
    public function __construct($db) {
        $this->db = $db;
        $this->filtros = [
            'curso_id' => isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : null,
            'professor_id' => isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : null,
            'semestre' => isset($_GET['semestre']) ? (int)$_GET['semestre'] : null,
            'ano_letivo' => isset($_GET['ano_letivo']) ? (int)$_GET['ano_letivo'] : date('Y'),
            'periodo' => $_GET['periodo'] ?? null
        ];
    }
    
    public function getFiltros() {
        return $this->filtros;
    }
    
    public function carregarCursos() {
        try {
            return $this->db->query("SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erro ao carregar cursos: " . $e->getMessage());
        }
    }
    
    public function carregarProfessores() {
        try {
            return $this->db->query("SELECT p.id, f.nome_completo 
                                   FROM professores p 
                                   JOIN funcionarios f ON p.funcionario_id = f.id 
                                   WHERE p.ativo = 1 
                                   ORDER BY f.nome_completo")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erro ao carregar professores: " . $e->getMessage());
        }
    }
    
    public function gerarRelatorio() {
        $where = "WHERE 1=1";
        $params = [];
        
        // Aplicar filtros
        if ($this->filtros['curso_id']) {
            $where .= " AND d.curso_id = :curso_id";
            $params[':curso_id'] = $this->filtros['curso_id'];
        }
        
        if ($this->filtros['professor_id']) {
            $where .= " AND a.professor_id = :professor_id";
            $params[':professor_id'] = $this->filtros['professor_id'];
        }
        
        if ($this->filtros['semestre']) {
            $where .= " AND d.semestre = :semestre";
            $params[':semestre'] = $this->filtros['semestre'];
        }
        
        if ($this->filtros['ano_letivo']) {
            $where .= " AND t.ano_letivo = :ano_letivo";
            $params[':ano_letivo'] = $this->filtros['ano_letivo'];
        }
        
        if ($this->filtros['periodo']) {
            $where .= " AND t.periodo = :periodo";
            $params[':periodo'] = $this->filtros['periodo'];
        }
        
        $sql = "SELECT 
                    c.id AS curso_id,
                    c.nome AS curso,
                    d.id AS disciplina_id,
                    d.nome AS disciplina,
                    d.semestre,
                    d.carga_horaria,
                    p.id AS professor_id,
                    f.nome_completo AS professor,
                    t.id AS turma_id,
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
        
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular totais
            $resultado = [
                'dados' => $dados,
                'total_geral_horas' => 0,
                'totais_por_professor' => [],
                'totais_por_curso' => [],
                'totais_por_disciplina' => []
            ];
            
            foreach ($dados as $linha) {
                $horas = (float)$linha['horas_lecionadas'];
                $resultado['total_geral_horas'] += $horas;
                
                // Totais por professor
                if (!isset($resultado['totais_por_professor'][$linha['professor_id']])) {
                    $resultado['totais_por_professor'][$linha['professor_id']] = [
                        'nome' => $linha['professor'],
                        'total' => 0
                    ];
                }
                $resultado['totais_por_professor'][$linha['professor_id']]['total'] += $horas;
                
                // Totais por curso
                if (!isset($resultado['totais_por_curso'][$linha['curso_id']])) {
                    $resultado['totais_por_curso'][$linha['curso_id']] = [
                        'nome' => $linha['curso'],
                        'total' => 0
                    ];
                }
                $resultado['totais_por_curso'][$linha['curso_id']]['total'] += $horas;
                
                // Totais por disciplina
                if (!isset($resultado['totais_por_disciplina'][$linha['disciplina_id']])) {
                    $resultado['totais_por_disciplina'][$linha['disciplina_id']] = [
                        'nome' => $linha['disciplina'],
                        'curso' => $linha['curso'],
                        'total' => 0,
                        'carga_horaria' => $linha['carga_horaria']
                    ];
                }
                $resultado['totais_por_disciplina'][$linha['disciplina_id']]['total'] += $horas;
            }
            
            return $resultado;
            
        } catch (PDOException $e) {
            throw new Exception("Erro ao gerar relatório: " . $e->getMessage());
        }
    }
}

// Instanciar e usar a classe
try {
    $relatorio = new RelatorioCargaHoraria($db);
    $filtros = $relatorio->getFiltros();
    $cursos = $relatorio->carregarCursos();
    $professores = $relatorio->carregarProfessores();
    $resultado = $relatorio->gerarRelatorio();
    
    $dados = $resultado['dados'];
    $total_geral_horas = $resultado['total_geral_horas'];
    $totais_por_professor = $resultado['totais_por_professor'];
    $totais_por_curso = $resultado['totais_por_curso'];
    $totais_por_disciplina = $resultado['totais_por_disciplina'];
    
} catch (Exception $e) {
    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Carga Horária - SIGEPOLI</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --border-color: #dee2e6;
            --text-color: #333;
            --text-light: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            color: var(--text-color);
            background-color: #f5f7fa;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .header h1 {
            margin: 0;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid transparent;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2185d0;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--text-color);
            border-color: var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: #e2e6ea;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .card-header {
            background-color: var(--secondary-color);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--dark-color);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .filter-section {
            margin-bottom: 30px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-light);
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .summary-card p {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th {
            background-color: var(--dark-color);
            color: white;
            text-align: left;
            padding: 12px 15px;
            position: sticky;
            top: 0;
        }
        
        .table td {
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table tr:nth-child(even) {
            background-color: var(--secondary-color);
        }
        
        .table tr:hover {
            background-color: #e9ecef;
        }
        
        .table .total-row {
            font-weight: bold;
            background-color: #e9ecef !important;
        }
        
        .table .subtotal-row {
            font-weight: 500;
            background-color: #f1f3f5 !important;
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
            font-weight: 500;
        }
        
        .bg-success { background-color: var(--success-color); }
        .bg-info { background-color: var(--info-color); }
        .bg-warning { background-color: var(--warning-color); }
        .bg-danger { background-color: var(--danger-color); }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        @media print {
            body {
                padding: 0;
                font-size: 12px;
                background: white;
            }
            
            .container {
                box-shadow: none;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .table th, .table td {
                padding: 6px 8px;
            }
            
            .header {
                margin-bottom: 15px;
                padding-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Relatório de Carga Horária
            </h1>
            <div class="btn-group no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 18H4C2.89543 18 2 17.1046 2 16V11C2 9.89543 2.89543 9 4 9H20C21.1046 9 22 9.89543 22 11V16C22 17.1046 21.1046 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Imprimir
                </button>
                <a href="/PHP/index.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger">
                <strong>Erro:</strong> <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <div class="filter-section no-print">
            <div class="card">
                <div class="card-header">
                    <h2>Filtros do Relatório</h2>
                </div>
                <div class="card-body">
                    <form method="get">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label for="curso_id">Curso</label>
                                <select id="curso_id" name="curso_id" class="form-control">
                                    <option value="">Todos os cursos</option>
                                    <?php foreach ($cursos as $curso): ?>
                                        <option value="<?= $curso['id'] ?>" <?= $filtros['curso_id'] == $curso['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($curso['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="professor_id">Professor</label>
                                <select id="professor_id" name="professor_id" class="form-control">
                                    <option value="">Todos os professores</option>
                                    <?php foreach ($professores as $professor): ?>
                                        <option value="<?= $professor['id'] ?>" <?= $filtros['professor_id'] == $professor['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($professor['nome_completo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="semestre">Semestre</label>
                                <select id="semestre" name="semestre" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="1" <?= $filtros['semestre'] == 1 ? 'selected' : '' ?>>1º Semestre</option>
                                    <option value="2" <?= $filtros['semestre'] == 2 ? 'selected' : '' ?>>2º Semestre</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="ano_letivo">Ano Letivo</label>
                                <input type="number" id="ano_letivo" name="ano_letivo" min="2000" max="2100" 
                                       value="<?= $filtros['ano_letivo'] ?>" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="periodo">Período</label>
                                <select id="periodo" name="periodo" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="Manhã" <?= $filtros['periodo'] == 'Manhã' ? 'selected' : '' ?>>Manhã</option>
                                    <option value="Tarde" <?= $filtros['periodo'] == 'Tarde' ? 'selected' : '' ?>>Tarde</option>
                                    <option value="Noite" <?= $filtros['periodo'] == 'Noite' ? 'selected' : '' ?>>Noite</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Aplicar Filtros
                            </button>
                            <a href="carga_horaria.php" class="btn btn-secondary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Limpar Filtros
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="summary-section">
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total de Cursos</h3>
                    <p><?= count($totais_por_curso) ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total de Professores</h3>
                    <p><?= count($totais_por_professor) ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total de Disciplinas</h3>
                    <p><?= count($totais_por_disciplina) ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total de Horas</h3>
                    <p><?= number_format($total_geral_horas, 2) ?>h</p>
                </div>
            </div>
        </div>

        <div class="results-section">
            <div class="card">
                <div class="card-header">
                    <h2>Detalhamento da Carga Horária</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($dados)): ?>
                        <div class="alert alert-secondary">
                            Nenhum dado encontrado com os filtros aplicados.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Curso</th>
                                        <th>Disciplina</th>
                                        <th class="text-center">Sem.</th>
                                        <th class="text-right">Carga Horária</th>
                                        <th>Professor</th>
                                        <th>Turma</th>
                                        <th>Período</th>
                                        <th class="text-center">Aulas</th>
                                        <th class="text-right">Horas Lec.</th>
                                        <th>Progresso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $curso_atual = null;
                                    $professor_atual = null;
                                    $disciplina_atual = null;
                                    
                                    foreach ($dados as $index => $linha): 
                                        $percentual = $linha['carga_horaria'] > 0 ? 
                                            ($linha['horas_lecionadas'] / $linha['carga_horaria']) * 100 : 0;
                                        
                                        // Verificar se mudou o curso
                                        if ($curso_atual !== null && $curso_atual != $linha['curso_id']) {
                                            echo $this->renderTotalRow('curso', $curso_atual, $totais_por_curso[$curso_atual]['nome'], $totais_por_curso[$curso_atual]['total']);
                                        }
                                        $curso_atual = $linha['curso_id'];
                                        
                                        // Verificar se mudou o professor dentro do mesmo curso
                                        if ($professor_atual !== null && $professor_atual != $linha['professor_id'] && 
                                            ($index > 0 && $dados[$index-1]['curso_id'] == $linha['curso_id'])) {
                                            echo $this->renderSubtotalRow('professor', $professor_atual, $totais_por_professor[$professor_atual]['nome'], $totais_por_professor[$professor_atual]['total']);
                                        }
                                        $professor_atual = $linha['professor_id'];
                                        
                                        // Verificar se mudou a disciplina dentro do mesmo curso/professor
                                        if ($disciplina_atual !== null && $disciplina_atual != $linha['disciplina_id'] && 
                                            ($index > 0 && $dados[$index-1]['professor_id'] == $linha['professor_id'] && 
                                             $dados[$index-1]['curso_id'] == $linha['curso_id'])) {
                                            echo $this->renderSubtotalRow('disciplina', $disciplina_atual, $totais_por_disciplina[$disciplina_atual]['nome'], $totais_por_disciplina[$disciplina_atual]['total'], $totais_por_disciplina[$disciplina_atual]['carga_horaria']);
                                        }
                                        $disciplina_atual = $linha['disciplina_id'];
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($linha['curso']) ?></td>
                                            <td><?= htmlspecialchars($linha['disciplina']) ?></td>
                                            <td class="text-center"><?= $linha['semestre'] ?></td>
                                            <td class="text-right"><?= number_format($linha['carga_horaria'], 2) ?>h</td>
                                            <td><?= htmlspecialchars($linha['professor']) ?></td>
                                            <td><?= htmlspecialchars($linha['turma']) ?></td>
                                            <td><?= htmlspecialchars($linha['periodo']) ?></td>
                                            <td class="text-center"><?= $linha['total_aulas'] ?></td>
                                            <td class="text-right"><?= number_format($linha['horas_lecionadas'], 2) ?>h</td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar <?= $this->getProgressBarClass($percentual) ?>" 
                                                         style="width: <?= min($percentual, 100) ?>%">
                                                        <?= number_format($percentual, 1) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Últimos totais -->
                                    <?php if (!empty($dados)): ?>
                                        <?= $this->renderTotalRow('curso', $curso_atual, $totais_por_curso[$curso_atual]['nome'], $totais_por_curso[$curso_atual]['total']) ?>
                                        <?= $this->renderSubtotalRow('professor', $professor_atual, $totais_por_professor[$professor_atual]['nome'], $totais_por_professor[$professor_atual]['total']) ?>
                                        <?= $this->renderSubtotalRow('disciplina', $disciplina_atual, $totais_por_disciplina[$disciplina_atual]['nome'], $totais_por_disciplina[$disciplina_atual]['total'], $totais_por_disciplina[$disciplina_atual]['carga_horaria']) ?>
                                        
                                        <tr class="total-row">
                                            <td colspan="3">Total Geral</td>
                                            <td class="text-right"><?= number_format(array_sum(array_column($totais_por_disciplina, 'carga_horaria')), 2) ?>h</td>
                                            <td colspan="4"></td>
                                            <td class="text-right"><?= number_format($total_geral_horas, 2) ?>h</td>
                                            <td></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para melhorar a experiência do usuário ao filtrar
        document.addEventListener('DOMContentLoaded', function() {
            // Focar no primeiro campo do formulário
            const firstFilter = document.querySelector('.form-control');
            if (firstFilter) firstFilter.focus();
            
            // Adicionar máscara para o ano letivo
            const anoLetivoInput = document.getElementById('ano_letivo');
            if (anoLetivoInput) {
                anoLetivoInput.addEventListener('change', function() {
                    if (this.value.length !== 4) {
                        this.value = new Date().getFullYear();
                    }
                });
            }
        });
    </script>
</body>
</html>