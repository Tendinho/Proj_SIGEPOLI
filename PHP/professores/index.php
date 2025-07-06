<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

$where = "WHERE p.ativo = 1";
if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = $_GET['busca'];
    $where .= " AND (f.nome_completo LIKE '%$busca%' OR p.titulacao LIKE '%$busca%' OR f.bi LIKE '%$busca%')";
}

// Contar total de registros
$query_count = "SELECT COUNT(*) as total 
                FROM professores p
                JOIN funcionarios f ON p.funcionario_id = f.id
                $where";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute();
$total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar professores
$query = "SELECT p.id, f.nome_completo, f.bi, p.titulacao, p.area_especializacao, p.data_contratacao, p.ativo
          FROM professores p
          JOIN funcionarios f ON p.funcionario_id = f.id
          $where
          ORDER BY f.nome_completo
          LIMIT $offset, $registros_por_pagina";
$stmt = $db->prepare($query);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professores - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/professores.css">
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="professores-container">
        <div class="professores-header">
            <h1 class="professores-title">
                <i class="fas fa-chalkboard-teacher"></i> Gestão de Professores
            </h1>
            <div class="professores-actions">
                <a href="/PHP/index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Voltar ao Início
                </a>
                <a href="criar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Novo Professor
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
        
        <div class="professores-toolbar">
            <form method="get" class="professores-search">
                <input type="text" name="busca" placeholder="Pesquisar professores..." value="<?= isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : '' ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <table class="professores-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome Completo</th>
                    <th>BI</th>
                    <th>Titulação</th>
                    <th>Área</th>
                    <th>Data Contratação</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nome_completo']) ?></td>
                        <td><?= htmlspecialchars($row['bi']) ?></td>
                        <td><?= htmlspecialchars($row['titulacao']) ?></td>
                        <td><?= htmlspecialchars($row['area_especializacao']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['data_contratacao'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $row['ativo'] ? 'ativo' : 'inativo' ?>">
                                <?= $row['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="professores-actions-cell">
                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-edit" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="excluir.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este professor?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: #6c757d;">
                            <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                            Nenhum professor encontrado
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_paginas > 1): ?>
            <div class="professores-pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= $pagina - 1 ?><?= isset($_GET['busca']) ? '&busca=' . htmlspecialchars($_GET['busca']) : '' ?>" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?= $i ?><?= isset($_GET['busca']) ? '&busca=' . htmlspecialchars($_GET['busca']) : '' ?>" class="btn <?= $i == $pagina ? 'btn-primary' : 'btn-secondary' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?= $pagina + 1 ?><?= isset($_GET['busca']) ? '&busca=' . htmlspecialchars($_GET['busca']) : '' ?>" class="btn btn-secondary">
                        Próxima <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="/Context/JS/script.js"></script>
</body>
</html>