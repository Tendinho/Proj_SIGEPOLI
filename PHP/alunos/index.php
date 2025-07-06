<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

verificarLogin();
verificarAcesso(2); // Nível 2 para visualização de alunos
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Alunos - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/alunos.css">
</head>
<body>
    <div class="alunos-container">
        <div class="alunos-header">
            <h1>Lista de Alunos</h1>
            <div class="alunos-actions">
                <a href="criar.php" class="btn btn-primary">Novo Aluno</a>
                <a href="/PHP/index.php" class="btn btn-secondary">Voltar ao Início</a>
            </div>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                <?= $_SESSION['mensagem'] ?>
                <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>

        <table class="alunos-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome Completo</th>
                    <th>BI</th>
                    <th>Data Nasc.</th>
                    <th>Gênero</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $database = new Database();
                $db = $database->getConnection();

                $query = "SELECT id, nome_completo, bi, data_nascimento, genero 
                          FROM alunos 
                          WHERE ativo = 1 
                          ORDER BY nome_completo";
                $stmt = $db->prepare($query);
                $stmt->execute();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nome_completo']) ?></td>
                        <td><?= htmlspecialchars($row['bi']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['data_nascimento'])) ?></td>
                        <td><?= $row['genero'] == 'M' ? 'Masculino' : ($row['genero'] == 'F' ? 'Feminino' : 'Outro') ?></td>
                        <td class="actions">
                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="excluir.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este aluno?')">Excluir</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>