<?php
require_once __DIR__ . '/config.php';
verificarLogin();
verificarAcesso(9); // Apenas administradores

$database = new Database();
$db = $database->getConnection();

// Buscar cargos e departamentos para os selects
$cargos = $db->query("SELECT id, nome FROM cargos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$departamentos = $db->query("SELECT id, nome FROM departamentos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Validar dados
        $required = ['username', 'password', 'email', 'nome_completo', 'bi', 'cargo_id', 'departamento_id'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . str_replace('_', ' ', $field) . " é obrigatório");
            }
        }

        // Verificar se username ou email já existem
        $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ? OR email = ?");
        $stmt->execute([$_POST['username'], $_POST['email']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username ou email já estão em uso");
        }

        // Verificar BI existente
        if (verificarBIExistente($_POST['bi'])) {
            throw new Exception("O BI informado já está cadastrado");
        }

        // Criar usuário
        $stmt = $db->prepare("INSERT INTO usuarios (username, password, email, cargo_id, departamento_id, ativo) 
                             VALUES (?, ?, ?, ?, ?, 1)");
        $password_hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt->execute([
            $_POST['username'],
            $password_hashed,
            $_POST['email'],
            $_POST['cargo_id'],
            $_POST['departamento_id']
        ]);
        $usuario_id = $db->lastInsertId();

        // Criar funcionário associado
        $stmt = $db->prepare("INSERT INTO funcionarios (usuario_id, nome_completo, bi, data_nascimento, genero, telefone, endereco, data_contratacao) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $usuario_id,
            $_POST['nome_completo'],
            $_POST['bi'],
            $_POST['data_nascimento'] ?: null,
            $_POST['genero'] ?: null,
            $_POST['telefone'] ?: null,
            $_POST['endereco'] ?: null,
            $_POST['data_contratacao'] ?: date('Y-m-d')
        ]);

        $db->commit();
        
        // Registrar auditoria
        registrarAuditoria("Criou novo usuário: {$_POST['username']}", 'usuarios', $usuario_id);
        
        $_SESSION['mensagem'] = "Usuário criado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        redirect('/PHP/usuarios.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Criar Usuário - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?= SISTEMA_NOME ?></h2>
                <p><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></p>
                <p class="nivel-acesso">Nível: <?= $_SESSION['nivel_acesso'] ?? '0' ?></p>
            </div>
        </div>
        
        <div class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span>Configurações</span>
                    <span><a href="/PHP/usuarios.php">Usuários</a></span>
                    <span>Novo Usuário</span>
                </div>
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></span>
                    <img src="/Context/IMG/user-default.png" alt="User">
                </div>
            </header>
            
            <div class="content">
                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                        <?= $_SESSION['mensagem'] ?>
                        <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Criar Novo Usuário</h3>
                        <a href="/PHP/usuarios.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
                    </div>
                    <div class="card-body">
                        <form method="post" class="form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username">Username*</label>
                                    <input type="text" name="username" id="username" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Senha*</label>
                                    <input type="password" name="password" id="password" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email*</label>
                                    <input type="email" name="email" id="email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nome_completo">Nome Completo*</label>
                                    <input type="text" name="nome_completo" id="nome_completo" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bi">BI/Passaporte*</label>
                                    <input type="text" name="bi" id="bi" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="data_nascimento">Data Nascimento</label>
                                    <input type="date" name="data_nascimento" id="data_nascimento">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="genero">Gênero</label>
                                    <select name="genero" id="genero">
                                        <option value="">Selecione...</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Feminino</option>
                                        <option value="O">Outro</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="telefone">Telefone</label>
                                    <input type="text" name="telefone" id="telefone">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="cargo_id">Cargo*</label>
                                    <select name="cargo_id" id="cargo_id" required>
                                        <option value="">Selecione um cargo</option>
                                        <?php foreach ($cargos as $cargo): ?>
                                            <option value="<?= $cargo['id'] ?>"><?= htmlspecialchars($cargo['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="departamento_id">Departamento*</label>
                                    <select name="departamento_id" id="departamento_id" required>
                                        <option value="">Selecione um departamento</option>
                                        <?php foreach ($departamentos as $departamento): ?>
                                            <option value="<?= $departamento['id'] ?>"><?= htmlspecialchars($departamento['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="endereco">Endereço</label>
                                    <textarea name="endereco" id="endereco" rows="2"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="data_contratacao">Data Contratação</label>
                                    <input type="date" name="data_contratacao" id="data_contratacao" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                                <a href="/PHP/usuarios.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/Context/JS/script.js"></script>
    <script>
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
                value = (value[1] ? value[1] : '') + (value[2] ? '-' + value[2] : '') + (value[3] ? '-' + value[3] : '');
            }
            e.target.value = value;
        });
        
        // Máscara para BI (formato: 123456789LA123)
        document.getElementById('bi').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (value.length > 9) {
                value = value.substring(0, 9) + value.substring(9, 11).replace(/\d/g, '') + value.substring(11);
                if (value.length > 11) {
                    value = value.substring(0, 11) + value.substring(11, 14).replace(/[A-Z]/g, '');
                }
            }
            e.target.value = value;
        });
    </script>
</body>
</html>