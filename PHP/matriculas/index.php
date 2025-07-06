<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

$where = "WHERE 1=1";
if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = $_GET['busca'];
    $where .= " AND (a.nome_completo LIKE '%$busca%' OR t.nome LIKE '%$busca%' OR c.nome LIKE '%$busca%')";
}

if (isset($_GET['ano_letivo']) && !empty($_GET['ano_letivo'])) {
    $ano_letivo = $_GET['ano_letivo'];
    $where .= " AND m.ano_letivo = $ano_letivo";
}

if (isset($_GET['semestre']) && !empty($_GET['semestre'])) {
    $semestre = $_GET['semestre'];
    $where .= " AND m.semestre = $semestre";
}

// Contar total de registros
$query_count = "SELECT COUNT(*) as total 
                FROM matriculas m
                JOIN alunos a ON m.aluno_id = a.id
                JOIN turmas t ON m.turma_id = t.id
                JOIN cursos c ON t.curso_id = c.id
                $where";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute();
$total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar matrículas
$query = "SELECT m.id, a.nome_completo as aluno, t.nome as turma, c.nome as curso,
                 m.ano_letivo, m.semestre, m.data_matricula, m.propina_paga,
                 m.valor_propina, m.status
          FROM matriculas m
          JOIN alunos a ON m.aluno_id = a.id
          JOIN turmas t ON m.turma_id = t.id
          JOIN cursos c ON t.curso_id = c.id
          $where
          ORDER BY m.data_matricula DESC
          LIMIT $offset, $registros_por_pagina";
$stmt = $db->prepare($query);
$stmt->execute();

// Buscar anos letivos para filtro
$query_anos = "SELECT DISTINCT ano_letivo FROM matriculas ORDER BY ano_letivo DESC";
$stmt_anos = $db->prepare($query_anos);
$stmt_anos->execute();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrículas - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-clipboard-list"></i> Gestão de Matrículas</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                <?php echo $_SESSION['mensagem']; unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="toolbar">
            <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nova Matrícula</a>
            
            <form method="get" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="busca" placeholder="Pesquisar..." value="<?php echo isset($_GET['busca']) ? $_GET['busca'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <select name="ano_letivo">
                            <option value="">Todos os anos</option>
                            <?php while ($ano = $stmt_anos->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $ano['ano_letivo']; ?>" <?php echo isset($_GET['ano_letivo']) && $_GET['ano_letivo'] == $ano['ano_letivo'] ? 'selected' : ''; ?>>
                                    <?php echo $ano['ano_letivo']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="semestre">
                            <option value="">Todos semestres</option>
                            <option value="1" <?php echo isset($_GET['semestre']) && $_GET['semestre'] == 1 ? 'selected' : ''; ?>>1º Semestre</option>
                            <option value="2" <?php echo isset($_GET['semestre']) && $_GET['semestre'] == 2 ? 'selected' : ''; ?>>2º Semestre</option>
                        </select>
                    </div>
                    
                    <button type="submit"><i class="fas fa-search"></i> Filtrar</button>
                </div>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Aluno</th>
                    <th>Turma</th>
                    <th>Curso</th>
                    <th>Ano Letivo</th>
                    <th>Semestre</th>
                    <th>Data Matrícula</th>
                    <th>Propina</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['aluno']; ?></td>
                        <td><?php echo $row['turma']; ?></td>
                        <td><?php echo $row['curso']; ?></td>
                        <td><?php echo $row['ano_letivo']; ?></td>
                        <td><?php echo $row['semestre']; ?>º</td>
                        <td><?php echo date('d/m/Y', strtotime($row['data_matricula'])); ?></td>
                        <td>
                            <?php echo number_format($row['valor_propina'], 2, ',', '.'); ?> Kz
                            <span class="status <?php echo $row['propina_paga'] ? 'ativo' : 'inativo'; ?>">
                                <?php echo $row['propina_paga'] ? 'Paga' : 'Pendente'; ?>
                            </span>
                        </td>
                        <td><span class="status <?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                        <td class="actions">
                            <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                            <a href="excluir.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Tem certeza que deseja excluir esta matrícula?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr><td colspan="10">Nenhuma matrícula encontrada</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['busca']) ? '&busca=' . $_GET['busca'] : ''; ?><?php echo isset($_GET['ano_letivo']) ? '&ano_letivo=' . $_GET['ano_letivo'] : ''; ?><?php echo isset($_GET['semestre']) ? '&semestre=' . $_GET['semestre'] : ''; ?>" class="btn">&laquo; Anterior</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?php echo $i; ?><?php echo isset($_GET['busca']) ? '&busca=' . $_GET['busca'] : ''; ?><?php echo isset($_GET['ano_letivo']) ? '&ano_letivo=' . $_GET['ano_letivo'] : ''; ?><?php echo isset($_GET['semestre']) ? '&semestre=' . $_GET['semestre'] : ''; ?>" class="btn <?php echo $i == $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($pagina < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['busca']) ? '&busca=' . $_GET['busca'] : ''; ?><?php echo isset($_GET['ano_letivo']) ? '&ano_letivo=' . $_GET['ano_letivo'] : ''; ?><?php echo isset($_GET['semestre']) ? '&semestre=' . $_GET['semestre'] : ''; ?>" class="btn">Próxima &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>