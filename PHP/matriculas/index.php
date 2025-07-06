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
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/matriculas.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="matriculas-container">
        <div class="matriculas-header">
            <h1 class="matriculas-title">
                <i class="fas fa-clipboard-list"></i> Gestão de Matrículas
            </h1>
            <div class="matriculas-actions">
                <a href="/PHP/index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Voltar ao Início
                </a>
                <a href="criar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nova Matrícula
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] === 'sucesso' ? 'success' : 'error' ?>">
                <i class="fas <?= $_SESSION['tipo_mensagem'] === 'sucesso' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= $_SESSION['mensagem'] ?>
                <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="matriculas-toolbar">
            <form method="get" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="busca" placeholder="Pesquisar aluno, turma ou curso" value="<?= isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <select name="ano_letivo">
                            <option value="">Todos os anos letivos</option>
                            <?php while ($ano = $stmt_anos->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= $ano['ano_letivo'] ?>" <?= isset($_GET['ano_letivo']) && $_GET['ano_letivo'] == $ano['ano_letivo'] ? 'selected' : '' ?>>
                                    <?= $ano['ano_letivo'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="semestre">
                            <option value="">Todos os semestres</option>
                            <option value="1" <?= isset($_GET['semestre']) && $_GET['semestre'] == 1 ? 'selected' : '' ?>>1º Semestre</option>
                            <option value="2" <?= isset($_GET['semestre']) && $_GET['semestre'] == 2 ? 'selected' : '' ?>>2º Semestre</option>
                        </select>
                    </div>
                    
                    <button type="submit">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
        
        <table class="matriculas-table">
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
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['aluno']) ?></td>
                        <td><?= htmlspecialchars($row['turma']) ?></td>
                        <td><?= htmlspecialchars($row['curso']) ?></td>
                        <td><?= $row['ano_letivo'] ?></td>
                        <td><?= $row['semestre'] ?>º</td>
                        <td><?= date('d/m/Y', strtotime($row['data_matricula'])) ?></td>
                        <td>
                            <span class="monetary-value"><?= number_format($row['valor_propina'], 2, ',', '.') ?> Kz</span>
                            <span class="status-badge status-<?= $row['propina_paga'] ? 'paga' : 'pendente' ?>">
                                <?= $row['propina_paga'] ? 'Paga' : 'Pendente' ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td class="matriculas-actions-cell">
                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-edit" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="excluir.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta matrícula?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 2rem; color: #6c757d;">
                            <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                            Nenhuma matrícula encontrada
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_paginas > 1): ?>
            <div class="matriculas-pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= $pagina - 1 ?><?= isset($_GET['busca']) ? '&busca=' . htmlspecialchars($_GET['busca']) : '' ?><?= isset($_GET['ano_letivo']) ? '&ano_letivo=' . htmlspecialchars($_GET['ano_letivo']) : '' ?><?= isset($_GET['semestre']) ? '&semestre=' . htmlspecialchars($_GET['semestre']) : '' ?>" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?= $i ?><?= isset($_GET['busca']) ? '&busca=' . htmlspecialchars($_GET['busca']) : '' ?><?= isset($_GET['ano_letivo']) ? '&ano_letivo=' . htmlspecialchars($_GET['ano_letivo']) : '' ?><?= isset($_GET['semestre']) ? '&semestre=' . htmlspecialchars($_GET['semestre']) : '' ?>" class="btn <?= $i == $pagina ? 'btn-primary' : 'btn-secondary' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?= $pagina + 1 ?><?= isset($_GET['busca']) ? '&busca=' . htmlspecialchars($_GET['busca']) : '' ?><?= isset($_GET['ano_letivo']) ? '&ano_letivo=' . htmlspecialchars($_GET['ano_letivo']) : '' ?><?= isset($_GET['semestre']) ? '&semestre=' . htmlspecialchars($_GET['semestre']) : '' ?>" class="btn btn-secondary">
                        Próxima <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="/Context/JS/script.js"></script>
</body>
</html>