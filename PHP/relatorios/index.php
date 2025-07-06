<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(7); // Nível de acesso maior para relatórios

$database = new Database();
$db = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-chart-bar"></i> Relatórios</h1>
        
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <h3>Carga Horária</h3>
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-body">
                    <p>Relatório de carga horária de professores por curso/disciplina</p>
                    <a href="carga_horaria.php" class="btn btn-primary">Gerar Relatório</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Contratos e Custos</h3>
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="card-body">
                    <p>Relatório de contratos e custos com empresas terceirizadas</p>
                    <a href="custos.php" class="btn btn-primary">Gerar Relatório</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Desempenho Acadêmico</h3>
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-body">
                    <p>Relatório de desempenho dos alunos por curso/turma</p>
                    <a href="desempenho.php" class="btn btn-primary">Gerar Relatório</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>