<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(7);

$database = new Database();
$db = $database->getConnection();

$ano_letivo = isset($_GET['ano_letivo']) ? $_GET['ano_letivo'] : date('Y');
$semestre = isset($_GET['semestre']) ? $_GET['semestre'] : (date('m') > 6 ? 2 : 1);
$curso_id = isset($_GET['curso_id']) ? $_GET['curso_id'] : '';

// Buscar cursos para filtro
$query_cursos = "SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute();

// Buscar desempenho dos alunos
$query = "SELECT c.nome as curso, t.nome as turma, a.nome_completo as aluno,
                 AVG(av.nota) as media_geral,
                 SUM(CASE WHEN av.nota >= 10 THEN 1 ELSE 0 END) as aprovacoes,
                 SUM(CASE WHEN av.nota < 10 THEN 1 ELSE 0 END) as reprovacoes
          FROM avaliacoes av
          JOIN alunos a ON av.aluno_id = a.id
          JOIN turmas t ON av.turma_id = t.id
          JOIN cursos c ON t.curso_id = c.id
          WHERE t.ano_letivo = :ano_letivo
          AND t.semestre = :semestre
          " . (!empty($curso_id) ? "AND c.id = :curso_id" : "") . "
          GROUP BY c.nome, t.nome, a.nome_completo
          ORDER BY c.nome, t.nome, a.nome_completo";
          
$stmt = $db->prepare($query);
$stmt->bindParam(":ano_letivo", $ano_letivo);
$stmt->bindParam(":semestre", $semestre);
if (!empty($curso_id)) {
    $stmt->bindParam(":curso_id", $curso_id);
}
$stmt->execute();

// Buscar anos letivos para filtro
$query_anos = "SELECT DISTINCT ano_letivo FROM turmas ORDER BY ano_letivo DESC";
$stmt_anos = $db->prepare($query_anos);
$stmt_anos->execute();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desempenho - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-chart-line"></i> Relatório de Desempenho</h1>
        
        <form method="get" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="ano_letivo">Ano Letivo:</label>
                    <select id="ano_letivo" name="ano_letivo">
                        <?php while ($ano = $stmt_anos->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $ano['ano_letivo']; ?>" <?php echo $ano['ano_letivo'] == $ano_letivo ? 'selected' : ''; ?>>
                                <?php echo $ano['ano_letivo']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="semestre">Semestre:</label>
                    <select id="semestre" name="semestre">
                        <option value="1" <?php echo $semestre == 1 ? 'selected' : ''; ?>>1º Semestre</option>
                        <option value="2" <?php echo $semestre == 2 ? 'selected' : ''; ?>>2º Semestre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="curso_id">Curso:</label>
                    <select id="curso_id" name="curso_id">
                        <option value="">Todos</option>
                        <?php 
                        $stmt_cursos->execute();
                        while ($curso = $stmt_cursos->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $curso['id']; ?>" <?php echo $curso['id'] == $curso_id ? 'selected' : ''; ?>>
                                <?php echo $curso['nome']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="desempenho.php?export=pdf&ano_letivo=<?php echo $ano_letivo; ?>&semestre=<?php echo $semestre; ?>&curso_id=<?php echo $curso_id; ?>" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
            </div>
        </form>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Turma</th>
                    <th>Aluno</th>
                    <th>Média Geral</th>
                    <th>Aprovações</th>
                    <th>Reprovações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['curso']; ?></td>
                        <td><?php echo $row['turma']; ?></td>
                        <td><?php echo $row['aluno']; ?></td>
                        <td><?php echo number_format($row['media_geral'], 2, ',', '.'); ?></td>
                        <td><?php echo $row['aprovacoes']; ?></td>
                        <td><?php echo $row['reprovacoes']; ?></td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr><td colspan="6">Nenhum dado encontrado para os filtros selecionados</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>