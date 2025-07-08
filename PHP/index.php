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
                <p><?php echo htmlspecialchars($_SESSION['nome_completo']); ?></p>
                <p class="nivel-acesso">Nível: <?php echo htmlspecialchars($_SESSION['nivel_acesso']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    
                    <!-- Menu Acadêmico -->
                    <li class="menu-section"><i class="fas fa-graduation-cap"></i> Acadêmico</li>
                    <li><a href="/PHP/cursos/index.php"><i class="fas fa-book"></i> Cursos</a></li>
                    <li><a href="/PHP/turmas/index.php"><i class="fas fa-users-class"></i> Turmas</a></li>
                    <li><a href="/PHP/alunos/index.php"><i class="fas fa-users"></i> Alunos</a></li>
                    <li><a href="/PHP/matriculas/index.php"><i class="fas fa-clipboard-list"></i> Matrículas</a></li>
                    <li><a href="/PHP/aulas/index.php"><i class="fas fa-chalkboard"></i> Aulas</a></li>
                    <li><a href="/PHP/avaliacoes/index.php"><i class="fas fa-clipboard-check"></i> Avaliações</a></li>
                    
                    <!-- Menu Pessoal -->
                    <li class="menu-section"><i class="fas fa-user-tie"></i> Pessoal</li>
                    <li><a href="/PHP/colaboradores/index.php"><i class="fas fa-users-cog"></i> Colaboradores</a></li>
                    <li><a href="/PHP/professores/index.php"><i class="fas fa-chalkboard-teacher"></i> Professores</a></li>
                    <li><a href="/PHP/departamentos/index.php"><i class="fas fa-building"></i> Departamentos</a></li>
                    <li><a href="/PHP/coordenador/index.php"><i class="fas fa-user-graduate"></i> Coordenadores</a></li>
                    
                    <!-- Menu Operacional -->
                    <li class="menu-section"><i class="fas fa-cogs"></i> Operacional</li>
                    <li><a href="/PHP/pagamentos_empresas/index.php"><i class="fas fa-industry"></i> Empresas</a></li>
                    <li><a href="/PHP/pagamentos_empresas/contratos.php"><i class="fas fa-file-contract"></i> Contratos</a></li>
                    <li><a href="/PHP/pagamentos_empresas/pagamentos.php"><i class="fas fa-money-bill-wave"></i> Pagamentos</a></li>
                    
                    <!-- Relatórios -->
                    <li class="menu-section"><i class="fas fa-chart-bar"></i> Relatórios</li>
                     <li><a href="/PHP/relatorios/index.php"><i class="fas fa-graduation-cap"></i> Home</a></li>
                    <li><a href="/PHP/relatorios/carga_horaria.php"><i class="fas fa-graduation-cap"></i> Carga Horária</a></li>
                    <li><a href="/PHP/relatorios/custo.php"><i class="fas fa-coins"></i> custos</a></li>
                    <li><a href="/PHP/relatorios/desempenho.php"><i class="fas fa-chart-line"></i> Desempenho</a></li>
                    
                    <!-- Configurações -->
                    <li class="menu-section"><i class="fas fa-cog"></i> Sistema</li>
                    <li><a href="/PHP/perfil.php"><i class="fas fa-user-cog"></i> Perfil</a></li>
                    <li><a href="/PHP/usuarios.php"><i class="fas fa-users-cog"></i> Usuários</a></li>
                    <li><a href="/PHP/auditoria.php"><i class="fas fa-clipboard-list"></i> Auditoria</a></li>
                    <li><a href="/PHP/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
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
                    <span><?php echo htmlspecialchars($_SESSION['nome_completo']); ?></span>
                    <img src="/Context/IMG/user-default.png" alt="User">
                </div>
            </header>
            
            <div class="content">
                <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome_completo']); ?></h1>
                <p>Sistema Integrado de Gestão Académica, Pessoal e Operacional</p>
                
                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['tipo_mensagem']); ?>">
                        <?php echo htmlspecialchars($_SESSION['mensagem']); unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
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
                            
                            echo "<h2>" . htmlspecialchars($row['total']) . "</h2>";
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
                            
                            echo "<h2>" . htmlspecialchars($row['total']) . "</h2>";
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
                            
                            echo "<h2>" . htmlspecialchars($row['total']) . "</h2>";
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
                            
                            echo "<h2>" . htmlspecialchars($row['total']) . "</h2>";
                            ?>
                            <a href="empresas/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Departamentos</h3>
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as total FROM departamentos";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo "<h2>" . htmlspecialchars($row['total']) . "</h2>";
                            ?>
                            <a href="departamentos/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Contratos Ativos</h3>
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as total FROM contratos WHERE ativo = 1";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo "<h2>" . htmlspecialchars($row['total']) . "</h2>";
                            ?>
                            <a href="contratos/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
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
                                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['acao']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['tabela_afetada']) . " (ID: " . htmlspecialchars($row['registro_id']) . ")</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Estatísticas Rápidas -->
                <div class="card full-width">
                    <div class="card-header">
                        <h3>Estatísticas Rápidas</h3>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <h4>Matrículas Este Mês</h4>
                                <?php
                                $query = "SELECT COUNT(*) as total FROM matriculas 
                                          WHERE MONTH(data_matricula) = MONTH(CURRENT_DATE()) 
                                          AND YEAR(data_matricula) = YEAR(CURRENT_DATE())";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo "<p>" . htmlspecialchars($row['total']) . "</p>";
                                ?>
                            </div>
                            
                            <div class="stat-item">
                                <h4>Pagamentos Pendentes</h4>
                                <?php
                                $query = "SELECT COUNT(*) as total FROM pagamentos_empresas 
                                          WHERE status = 'Pendente'";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo "<p>" . htmlspecialchars($row['total']) . "</p>";
                                ?>
                            </div>
                            
                            <div class="stat-item">
                                <h4>Aulas Hoje</h4>
                                <?php
                                $query = "SELECT COUNT(*) as total FROM aulas 
                                          WHERE dia_semana = UPPER(DAYNAME(CURRENT_DATE()))";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo "<p>" . htmlspecialchars($row['total']) . "</p>";
                                ?>
                            </div>
                            
                            <div class="stat-item">
                                <h4>Contratos Próximos do Vencimento</h4>
                                <?php
                                $query = "SELECT COUNT(*) as total FROM contratos 
                                          WHERE data_fim BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo "<p>" . htmlspecialchars($row['total']) . "</p>";
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/Context/JS/script.js"></script>
</body>
</html>