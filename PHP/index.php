<?php
require_once 'config.php';
require_once 'db.php';
verificarLogin();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SIGEPOLI</h2>
                <p><?php echo $_SESSION['nome_completo']; ?></p>
                <p class="nivel-acesso">Nível: <?php echo $_SESSION['nivel_acesso']; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    
                    <!-- Menu Acadêmico -->
                    <li class="menu-section"><i class="fas fa-graduation-cap"></i> Acadêmico</li>
                    <li><a href="cursos/index.php"><i class="fas fa-book"></i> Cursos</a></li>
                    <li><a href="alunos/index.php"><i class="fas fa-users"></i> Alunos</a></li>
                    <li><a href="matriculas/index.php"><i class="fas fa-clipboard-list"></i> Matrículas</a></li>
                    
                    <!-- Menu Pessoal -->
                    <li class="menu-section"><i class="fas fa-user-tie"></i> Pessoal</li>
                    <li><a href="professores/index.php"><i class="fas fa-chalkboard-teacher"></i> Professores</a></li>
                    
                    <!-- Menu Operacional -->
                    <li class="menu-section"><i class="fas fa-cogs"></i> Operacional</li>
                    <li><a href="empresas/index.php"><i class="fas fa-building"></i> Empresas</a></li>
                    
                    <!-- Relatórios -->
                    <li class="menu-section"><i class="fas fa-chart-bar"></i> Relatórios</li>
                    <li><a href="relatorios/index.php"><i class="fas fa-file-alt"></i> Relatórios</a></li>
                    
                    <!-- Configurações -->
                    <li class="menu-section"><i class="fas fa-cog"></i> Sistema</li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span>Dashboard</span>
                </div>
                
                <div class="user-info">
                    <span><?php echo $_SESSION['nome_completo']; ?></span>
                    <img src="../Context/IMG/user-default.png" alt="User">
                </div>
            </header>
            
            <div class="content">
                <h1>Bem-vindo, <?php echo $_SESSION['nome_completo']; ?></h1>
                <p>Sistema Integrado de Gestão Académica, Pessoal e Operacional</p>
                
                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                        <?php echo $_SESSION['mensagem']; unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="dashboard-cards">
                    <!-- Cards de Resumo -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Alunos</h3>
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-body">
                            <?php
                            $database = new Database();
                            $db = $database->getConnection();
                            
                            $query = "SELECT COUNT(*) as total FROM alunos WHERE ativo = 1";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo "<h2>" . $row['total'] . "</h2>";
                            ?>
                            <a href="alunos/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Professores</h3>
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as total FROM professores WHERE ativo = 1";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo "<h2>" . $row['total'] . "</h2>";
                            ?>
                            <a href="professores/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Cursos</h3>
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as total FROM cursos WHERE ativo = 1";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo "<h2>" . $row['total'] . "</h2>";
                            ?>
                            <a href="cursos/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Empresas</h3>
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as total FROM empresas WHERE ativo = 1";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo "<h2>" . $row['total'] . "</h2>";
                            ?>
                            <a href="empresas/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
                        </div>
                    </div>
                </div>
                
                <!-- Últimas Atividades -->
                <div class="card full-width">
                    <div class="card-header">
                        <h3>Últimas Atividades</h3>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Usuário</th>
                                    <th>Ação</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT a.*, u.username 
                                          FROM auditoria a 
                                          JOIN usuarios u ON a.usuario_id = u.id 
                                          ORDER BY a.data_hora DESC 
                                          LIMIT 10";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($row['data_hora'])) . "</td>";
                                    echo "<td>" . $row['username'] . "</td>";
                                    echo "<td>" . $row['acao'] . "</td>";
                                    echo "<td>" . $row['tabela_afetada'] . " (ID: " . $row['registro_id'] . ")</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/Context/JS/script.js"></script>
</body>
</html>