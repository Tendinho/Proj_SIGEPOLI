<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso mínimo para cursos

$database = new Database();
$db = $database->getConnection();

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

$where = "WHERE c.ativo = 1";
if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = $_GET['busca'];
    $where .= " AND (c.nome LIKE '%$busca%' OR c.codigo LIKE '%$busca%' OR d.nome LIKE '%$busca%')";
}

// Contar total de registros
$query_count = "SELECT COUNT(*) as total 
                FROM cursos c
                LEFT JOIN departamentos d ON c.departamento_id = d.id
                $where";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute();
$total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar cursos
$query = "SELECT c.id, c.nome, c.codigo, c.duracao_anos, d.nome as departamento, 
                 p.funcionario_id, f.nome_completo as coordenador, c.ativo
          FROM cursos c
          LEFT JOIN departamentos d ON c.departamento_id = d.id
          LEFT JOIN professores p ON c.coordenador_id = p.id
          LEFT JOIN funcionarios f ON p.funcionario_id = f.id
          $where
          ORDER BY c.nome
          LIMIT $offset, $registros_por_pagina";
$stmt = $db->prepare($query);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos - SIGEPOLI</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/cursos.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="cursos-container">
        <div class="cursos-header">
            <h1 class="cursos-title">
                <i class="fas fa-book"></i> Gestão de Cursos
            </h1>
            <div class="cursos-actions">
                <a href="/PHP/index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Voltar ao Início
                </a>
                <a href="criar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Novo Curso
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
        
        <div class="cursos-toolbar">
            <form method="get" class="cursos-search">
                <input type="text" name="busca" placeholder="Pesquisar cursos..." value="<?= isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : '' ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <table class="cursos-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Código</th>
                    <th>Duração</th>
                    <th>Departamento</th>
                    <th>Coordenador</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nome']) ?></td>
                        <td><?= htmlspecialchars($row['codigo']) ?></td>
                        <td><?= $row['duracao_anos'] ?> ano(s)</td>
                        <td><?= htmlspecialchars($row['departamento']) ?></td>
                        <td><?= htmlspecialchars($row['coordenador']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $row['ativo'] ? 'ativo' : 'inativo' ?>">
                                <?= $row['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="cursos-actions-cell">
                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-edit" title="Editar">
                                <i class="fas fa-edit">Editar</i>
                            </a>
                            <a href="excluir.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este curso?')">
                                <i class="fas fa-trash">Excuir</i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: #6c757d;">
                            <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                            Nenhum curso encontrado
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_paginas > 1): ?>
            <div class="cursos-pagination">
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