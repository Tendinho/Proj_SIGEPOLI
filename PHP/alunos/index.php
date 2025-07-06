<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso mínimo requerido
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    
    <div class="content">
        <h1><i class="fas fa-users"></i> Gestão de Alunos</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                <?php echo $_SESSION['mensagem']; unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="toolbar">
            <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Aluno</a>
            
            <form method="get" class="search-form">
                <input type="text" name="busca" placeholder="Pesquisar..." value="<?php echo isset($_GET['busca']) ? $_GET['busca'] : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome Completo</th>
                    <th>BI</th>
                    <th>Telefone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $database = new Database();
                $db = $database->getConnection();
                
                $pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 1;
                $registros_por_pagina = 10;
                $offset = ($pagina - 1) * $registros_por_pagina;
                
                $where = "WHERE 1=1";
                if (isset($_GET['busca']) && !empty($_GET['busca'])) {
                    $busca = $_GET['busca'];
                    $where .= " AND (nome_completo LIKE '%$busca%' OR bi LIKE '%$busca%' OR email LIKE '%$busca%')";
                }
                
                // Contar total de registros
                $query_count = "SELECT COUNT(*) as total FROM alunos $where";
                $stmt_count = $db->prepare($query_count);
                $stmt_count->execute();
                $total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
                $total_paginas = ceil($total_registros / $registros_por_pagina);
                
                // Buscar registros
                $query = "SELECT * FROM alunos $where ORDER BY nome_completo LIMIT $offset, $registros_por_pagina";
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['nome_completo'] . "</td>";
                    echo "<td>" . $row['bi'] . "</td>";
                    echo "<td>" . $row['telefone'] . "</td>";
                    echo "<td>" . $row['email'] . "</td>";
                    echo "<td><span class='status " . ($row['ativo'] ? 'ativo' : 'inativo') . "'>" . ($row['ativo'] ? 'Ativo' : 'Inativo') . "</span></td>";
                    echo "<td class='actions'>";
                    echo "<a href='editar.php?id=" . $row['id'] . "' class='btn btn-sm btn-edit'><i class='fas fa-edit'></i></a>";
                    echo "<a href='excluir.php?id=" . $row['id'] . "' class='btn btn-sm btn-delete' onclick='return confirm(\"Tem certeza que deseja excluir este aluno?\")'><i class='fas fa-trash'></i></a>";
                    echo "</td>";
                    echo "</tr>";
                }
                
                if ($stmt->rowCount() == 0) {
                    echo "<tr><td colspan='7'>Nenhum aluno encontrado</td></tr>";
                }
                ?>
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