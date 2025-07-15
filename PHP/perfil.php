<?php
require_once __DIR__ . '/config.php';
verificarLogin();

$database = new Database();
$db = $database->getConnection();

// Buscar informações do usuário
$query = "SELECT u.*, u.email, f.nome_completo, f.telefone, 
                 c.nome AS cargo, d.nome AS departamento
          FROM usuarios u
          LEFT JOIN funcionarios f ON u.id = f.usuario_id
          LEFT JOIN cargos c ON u.cargo_id = c.id
          LEFT JOIN departamentos d ON u.departamento_id = d.id
          WHERE u.id = :usuario_id";
          
$stmt = $db->prepare($query);
$stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome_completo'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    try {
        $db->beginTransaction();
        
        // Atualizar informações básicas
        $query = "UPDATE funcionarios SET 
                  nome_completo = :nome, 
                  telefone = :telefone
                  WHERE usuario_id = :usuario_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
        $stmt->execute();
        
        // Atualizar email na tabela usuarios
        $query = "UPDATE usuarios SET email = :email WHERE id = :usuario_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
        $stmt->execute();
        
        // Atualizar senha se fornecida
        if (!empty($senha_atual)) {
            if (empty($nova_senha)) {
                throw new Exception("Nova senha não pode ser vazia");
            }
            
            if ($nova_senha !== $confirmar_senha) {
                throw new Exception("As senhas não coincidem");
            }
            
            // Verificar senha atual
            $query = "SELECT password FROM usuarios WHERE id = :usuario_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
            $stmt->execute();
            $senha_hash = $stmt->fetchColumn();
            
            if (!password_verify($senha_atual, $senha_hash)) {
                throw new Exception("Senha atual incorreta");
            }
            
            // Atualizar senha
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET password = :senha WHERE id = :usuario_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':senha', $nova_senha_hash);
            $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
            $stmt->execute();
        }
        
        $db->commit();
        
        $_SESSION['mensagem'] = "Perfil atualizado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        $_SESSION['nome_completo'] = $nome;
        
        header("Location: perfil.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = "Erro ao atualizar perfil: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/Context/CSS/perfil.css">

</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?= SISTEMA_NOME ?></h2>
                <p><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></p>
                <p class="nivel-acesso">Nível: <?= $_SESSION['nivel_acesso'] ?? '0' ?></p>
            </div>
            <a href="/PHP/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
        
        <div class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span>Configurações</span>
                    <span>Meu Perfil</span>
                </div>
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></span>
                    <img src="/Context/IMG/imhuman.png" alt="User">
                </div>
            </header>
            
            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Meu Perfil</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['mensagem'])): ?>
                            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                                <?= $_SESSION['mensagem'] ?>
                                <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" class="form-perfil">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nome_completo">Nome Completo</label>
                                    <input type="text" id="nome_completo" name="nome_completo" 
                                           value="<?= htmlspecialchars($usuario['nome_completo'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" 
                                           value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="telefone">Telefone</label>
                                    <input type="tel" id="telefone" name="telefone" 
                                           value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Cargo</label>
                                    <input type="text" value="<?= htmlspecialchars($usuario['cargo'] ?? '') ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4><i class="fas fa-lock"></i> Alterar Senha</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="senha_atual">Senha Atual</label>
                                        <input type="password" id="senha_atual" name="senha_atual">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="nova_senha">Nova Senha</label>
                                        <input type="password" id="nova_senha" name="nova_senha">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirmar_senha">Confirmar Nova Senha</label>
                                        <input type="password" id="confirmar_senha" name="confirmar_senha">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/Context/JS/script.js"></script>
</body>
</html>