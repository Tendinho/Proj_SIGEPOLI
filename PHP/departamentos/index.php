<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para RH

$database = new Database();
$db = $database->getConnection();

// Consulta departamentos corrigida
$query = "SELECT d.*, 
                 (SELECT COUNT(*) FROM usuarios u WHERE u.departamento_id = d.id AND u.ativo = 1) AS total_colaboradores,
                 (SELECT f.nome_completo FROM funcionarios f 
                  JOIN usuarios u ON f.usuario_id = u.id 
                  WHERE u.id = d.chefe_id) AS chefe_nome
          FROM departamentos d
          ORDER BY d.nome";
$stmt = $db->prepare($query);
$stmt->execute();
$departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Departamentos - <?= SISTEMA_NOME ?></title>
    <style>
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            display: inline-block;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-back {
            background-color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-building"></i> Departamentos</h1>
            <a href="../index.php" class="btn btn-back">Voltar ao Menu</a>
        </div>

        <div class="card">
            <a href="criar.php" class="btn btn-primary">Novo Departamento</a>
            
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Orçamento</th>
                        <th>Colaboradores</th>
                        <th>Chefe</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($departamentos)): ?>
                        <tr><td colspan="5">Nenhum departamento cadastrado</td></tr>
                    <?php else: ?>
                        <?php foreach ($departamentos as $depto): ?>
                            <tr>
                                <td><?= htmlspecialchars($depto['nome']) ?></td>
                                <td><?= number_format($depto['orcamento_anual'], 2, ',', '.') ?> Kz</td>
                                <td><?= $depto['total_colaboradores'] ?></td>
                                <td><?= htmlspecialchars($depto['chefe_nome'] ?? '-') ?></td>
                                <td class="actions">
                                    <a href="editar.php?id=<?= $depto['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                                    <a href="designar_chefe.php?id=<?= $depto['id'] ?>" class="btn btn-secondary btn-sm">Designar Chefe</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>