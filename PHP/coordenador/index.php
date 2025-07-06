<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(6); // Nível de acesso para coordenadores

$database = new Database();
$db = $database->getConnection();

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

// Buscar coordenadores
$query = "SELECT c.id, c.nome as curso, p.id as professor_id, 
                 f.nome_completo as coordenador, f.email, f.telefone
          FROM cursos c
          JOIN professores p ON c.coordenador_id = p.id
          JOIN funcionarios f ON p.funcionario_id = f.id
          WHERE c.ativo = 1
          ORDER BY c.nome
          LIMIT $offset, $registros_por_pagina";
$stmt = $db->prepare($query);
$stmt->execute();

// Contar total de registros
$query_count = "SELECT COUNT(*) as total 
                FROM cursos 
                WHERE ativo = 1 AND coordenador_id IS NOT NULL";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute();
$total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordenadores - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-user-tie"></i> Coordenadores de Curso</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                <?php echo $_SESSION['mensagem']; unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Coordenador</th>
                    <th>Contato</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['curso']; ?></td>
                        <td><?php echo $row['coordenador']; ?></td>
                        <td>
                            <?php echo $row['telefone']; ?><br>
                            <?php echo $row['email']; ?>
                        </td>
                        <td class="actions">
                            <a href="../professores/editar.php?id=<?php echo $row['professor_id']; ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i> Editar</a>
                            <a href="desvincular.php?curso_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Tem certeza que deseja desvincular este coordenador?')">
                                <i class="fas fa-unlink"></i> Desvincular
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if ($stmt->rowCount() == 0): ?>
                    <tr><td colspan="4">Nenhum coordenador designado</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=<?php echo $pagina - 1; ?>" class="btn">&laquo; Anterior</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?php echo $i; ?>" class="btn <?php echo $i == $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($pagina < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina + 1; ?>" class="btn">Próxima &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>