<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(7);

$database = new Database();
$db = $database->getConnection();

$ano_letivo = isset($_GET['ano_letivo']) ? $_GET['ano_letivo'] : date('Y');
$semestre = isset($_GET['semestre']) ? $_GET['semestre'] : (date('m') > 6 ? 2 : 1);

// Buscar carga horária por professor
$query = "SELECT p.id, f.nome_completo as professor, c.nome as curso,
                 SUM(d.carga_horaria) as total_horas,
                 COUNT(DISTINCT d.id) as total_disciplinas
          FROM aulas a
          JOIN professores p ON a.professor_id = p.id
          JOIN funcionarios f ON p.funcionario_id = f.id
          JOIN disciplinas d ON a.disciplina_id = d.id
          JOIN cursos c ON d.curso_id = c.id
          JOIN turmas t ON a.turma_id = t.id
          WHERE t.ano_letivo = :ano_letivo
          AND t.semestre = :semestre
          GROUP BY p.id, f.nome_completo, c.nome
          ORDER BY f.nome_completo";
          
$stmt = $db->prepare($query);
$stmt->bindParam(":ano_letivo", $ano_letivo);
$stmt->bindParam(":semestre", $semestre);
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
    <title>Carga Horária - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-clock"></i> Relatório de Carga Horária</h1>
        
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
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="carga_horaria.php?export=pdf&ano_letivo=<?php echo $ano_letivo; ?>&semestre=<?php echo $semestre; ?>" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
            </div>
        </form>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Professor</th>
                    <th>Curso</th>
                    <th>Disciplinas</th>
                    <th>Carga Horária Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['professor']; ?></td>
                        <td><?php echo $row['curso']; ?></td>
                        <td><?php echo $row['total_disciplinas']; ?></td>
                        <td><?php echo $row['total_horas']; ?> horas</td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr><td colspan="4">Nenhum dado encontrado para os filtros selecionados</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>