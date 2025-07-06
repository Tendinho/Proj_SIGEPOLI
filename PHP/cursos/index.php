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
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-book"></i> Gestão de Cursos</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                <?php echo $_SESSION['mensagem']; unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="toolbar">
            <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Curso</a>
            
            <form method="get" class="search-form">
                <input type="text" name="busca" placeholder="Pesquisar..." value="<?php echo isset($_GET['busca']) ? $_GET['busca'] : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Código</th>
                    <th>Duração (anos)</th>
                    <th>Departamento</th>
                    <th>Coordenador</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['nome']; ?></td>
                        <td><?php echo $row['codigo']; ?></td>
                        <td><?php echo $row['duracao_anos']; ?></td>
                        <td><?php echo $row['departamento']; ?></td>
                        <td><?php echo $row['coordenador']; ?></td>
                        <td><span class="status <?php echo $row['ativo'] ? 'ativo' : 'inativo'; ?>"><?php echo $row['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
                        <td class="actions">
                            <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                            <a href="excluir.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Tem certeza que deseja excluir este curso?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr><td colspan="8">Nenhum curso encontrado</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['busca']) ? '&busca=' . $_GET['busca'] : ''; ?>" class="btn">&laquo; Anterior</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?php echo $i; ?><?php echo isset($_GET['busca']) ? '&busca=' . $_GET['busca'] : ''; ?>" class="btn <?php echo $i == $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($pagina < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['busca']) ? '&busca=' . $_GET['busca'] : ''; ?>" class="btn">Próxima &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>