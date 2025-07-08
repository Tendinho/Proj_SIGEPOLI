<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para RH

$database = new Database();
$db = $database->getConnection();

// Consulta departamentos
$query = "SELECT d.*, COUNT(f.id) AS total_colaboradores, 
                 (SELECT nome_completo FROM funcionarios WHERE id = d.chefe_id) AS chefe_nome
          FROM departamentos d
          LEFT JOIN funcionarios f ON d.id = f.departamento_id AND f.ativo = 1
          GROUP BY d.id
          ORDER BY d.nome";
$stmt = $db->prepare($query);
$stmt->execute();
$departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departamentos - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-back {
            background-color: #7f8c8d;
        }
        .btn-back:hover {
            background-color: #6c757d;
        }
        .btn-add {
            background-color: #28a745;
        }
        .btn-add:hover {
            background-color: #218838;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .currency {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-building"></i> Departamentos</h1>
            <div>
                <a href="criar.php" class="btn btn-add"><i class="fas fa-plus"></i> Novo Departamento</a>
                <a href="../index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Voltar ao Menu</a>
            </div>
        </div>

        <?php if (empty($departamentos)): ?>
            <div class="card no-results">
                <p>Nenhum departamento cadastrado.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th class="currency">Orçamento Anual</th>
                            <th>Colaboradores</th>
                            <th>Chefe</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departamentos as $depto): ?>
                            <tr>
                                <td><?= htmlspecialchars($depto['nome']) ?></td>
                                <td class="currency"><?= number_format($depto['orcamento_anual'], 2, ',', '.') ?> Kz</td>
                                <td><?= $depto['total_colaboradores'] ?></td>
                                <td><?= htmlspecialchars($depto['chefe_nome'] ?? '-') ?></td>
                                <td class="actions">
                                    <a href="editar.php?id=<?= $depto['id'] ?>" class="btn btn-primary btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                                    <a href="designar_chefe.php?id=<?= $depto['id'] ?>" class="btn btn-secondary btn-sm" title="Designar Chefe"><i class="fas fa-user-tie"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>