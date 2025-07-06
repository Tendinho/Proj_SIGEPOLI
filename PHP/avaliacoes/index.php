<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para professores

$database = new Database();
$db = $database->getConnection();

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

$where = "WHERE 1=1";
$usuario_id = $_SESSION['usuario_id'];

// Se não for admin, filtrar apenas avaliações do professor logado
if ($_SESSION['nivel_acesso'] < 7) {
    $where .= " AND a.professor_id IN (SELECT p.id FROM professores p JOIN funcionarios f ON p.funcionario_id = f.id WHERE f.usuario_id = $usuario_id)";
}

if (isset($_GET['disciplina_id']) && !empty($_GET['disciplina_id'])) {
    $disciplina_id = $_GET['disciplina_id'];
    $where .= " AND a.disciplina_id = $disciplina_id";
}

if (isset($_GET['turma_id']) && !empty($_GET['turma_id'])) {
    $turma_id = $_GET['turma_id'];
    $where .= " AND a.turma_id = $turma_id";
}

if (isset($_GET['tipo_avaliacao']) && !empty($_GET['tipo_avaliacao'])) {
    $tipo_avaliacao = $_GET['tipo_avaliacao'];
    $where .= " AND a.tipo_avaliacao = '$tipo_avaliacao'";
}

// Buscar avaliações
$query = "SELECT a.id, al.nome_completo as aluno, d.nome as disciplina, 
                 t.nome as turma, a.tipo_avaliacao, a.nota, 
                 DATE_FORMAT(a.data_avaliacao, '%d/%m/%Y') as data_av,
                 p.funcionario_id, f.nome_completo as professor
          FROM avaliacoes a
          JOIN alunos al ON a.aluno_id = al.id
          JOIN disciplinas d ON a.disciplina_id = d.id
          JOIN turmas t ON a.turma_id = t.id
          JOIN professores p ON a.professor_id = p.id
          JOIN funcionarios f ON p.funcionario_id = f.id
          $where
          ORDER BY a.data_avaliacao DESC
          LIMIT $offset, $registros_por_pagina";
$stmt = $db->prepare($query);
$stmt->execute();

// Contar total de registros
$query_count = "SELECT COUNT(*) as total 
                FROM avaliacoes a
                $where";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute();
$total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar disciplinas para filtro
$query_disciplinas = "SELECT d.id, d.nome 
                      FROM disciplinas d
                      JOIN aulas au ON d.id = au.disciplina_id
                      JOIN professores p ON au.professor_id = p.id
                      JOIN funcionarios f ON p.funcionario_id = f.id
                      WHERE f.usuario_id = $usuario_id
                      ORDER BY d.nome";
$stmt_disciplinas = $db->prepare($query_disciplinas);
$stmt_disciplinas->execute();

// Buscar turmas para filtro
$query_turmas = "SELECT DISTINCT t.id, t.nome 
                 FROM turmas t
                 JOIN aulas a ON t.id = a.turma_id
                 JOIN professores p ON a.professor_id = p.id
                 JOIN funcionarios f ON p.funcionario_id = f.id
                 WHERE f.usuario_id = $usuario_id
                 ORDER BY t.nome";
$stmt_turmas = $db->prepare($query_turmas);
$stmt_turmas->execute();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-clipboard-check"></i> Gestão de Avaliações</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                <?php echo $_SESSION['mensagem']; unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="toolbar">
            <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nova Avaliação</a>
            
            <form method="get" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <select name="disciplina_id">
                            <option value="">Todas disciplinas</option>
                            <?php while ($disciplina = $stmt_disciplinas->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $disciplina['id']; ?>" <?php echo isset($_GET['disciplina_id']) && $_GET['disciplina_id'] == $disciplina['id'] ? 'selected' : ''; ?>>
                                    <?php echo $disciplina['nome']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="turma_id">
                            <option value="">Todas turmas</option>
                            <?php 
                            $stmt_turmas->execute();
                            while ($turma = $stmt_turmas->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $turma['id']; ?>" <?php echo isset($_GET['turma_id']) && $_GET['turma_id'] == $turma['id'] ? 'selected' : ''; ?>>
                                    <?php echo $turma['nome']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="tipo_avaliacao">
                            <option value="">Todos tipos</option>
                            <option value="Teste" <?php echo isset($_GET['tipo_avaliacao']) && $_GET['tipo_avaliacao'] == 'Teste' ? 'selected' : ''; ?>>Teste</option>
                            <option value="Exame" <?php echo isset($_GET['tipo_avaliacao']) && $_GET['tipo_avaliacao'] == 'Exame' ? 'selected' : ''; ?>>Exame</option>
                            <option value="Trabalho" <?php echo isset($_GET['tipo_avaliacao']) && $_GET['tipo_avaliacao'] == 'Trabalho' ? 'selected' : ''; ?>>Trabalho</option>
                            <option value="Projeto" <?php echo isset($_GET['tipo_avaliacao']) && $_GET['tipo_avaliacao'] == 'Projeto' ? 'selected' : ''; ?>>Projeto</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn"><i class="fas fa-filter"></i> Filtrar</button>
                </div>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Disciplina</th>
                    <th>Turma</th>
                    <th>Tipo</th>
                    <th>Nota</th>
                    <th>Data</th>
                    <th>Professor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['aluno']; ?></td>
                        <td><?php echo $row['disciplina']; ?></td>
                        <td><?php echo $row['turma']; ?></td>
                        <td><?php echo $row['tipo_avaliacao']; ?></td>
                        <td class="<?php echo $row['nota'] >= 10 ? 'text-success' : 'text-danger'; ?>">
                            <b><?php echo number_format($row['nota'], 1, ',', '.'); ?></b>
                        </td>
                        <td><?php echo $row['data_av']; ?></td>
                        <td><?php echo $row['professor']; ?></td>
                        <td class="actions">
                            <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                            <a href="excluir.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr><td colspan="8">Nenhuma avaliação encontrada</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['disciplina_id']) ? '&disciplina_id=' . $_GET['disciplina_id'] : ''; ?><?php echo isset($_GET['turma_id']) ? '&turma_id=' . $_GET['turma_id'] : ''; ?><?php echo isset($_GET['tipo_avaliacao']) ? '&tipo_avaliacao=' . $_GET['tipo_avaliacao'] : ''; ?>" class="btn">&laquo; Anterior</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?php echo $i; ?><?php echo isset($_GET['disciplina_id']) ? '&disciplina_id=' . $_GET['disciplina_id'] : ''; ?><?php echo isset($_GET['turma_id']) ? '&turma_id=' . $_GET['turma_id'] : ''; ?><?php echo isset($_GET['tipo_avaliacao']) ? '&tipo_avaliacao=' . $_GET['tipo_avaliacao'] : ''; ?>" class="btn <?php echo $i == $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($pagina < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['disciplina_id']) ? '&disciplina_id=' . $_GET['disciplina_id'] : ''; ?><?php echo isset($_GET['turma_id']) ? '&turma_id=' . $_GET['turma_id'] : ''; ?><?php echo isset($_GET['tipo_avaliacao']) ? '&tipo_avaliacao=' . $_GET['tipo_avaliacao'] : ''; ?>" class="btn">Próxima &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>