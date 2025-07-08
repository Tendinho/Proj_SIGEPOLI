<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para RH

$database = new Database();
$db = $database->getConnection();

$erro = '';
$sucesso = '';

// Buscar departamentos e cargos para os selects
$departamentos = $db->query("SELECT id, nome FROM departamentos ORDER BY nome")->fetchAll();
$cargos = $db->query("SELECT id, nome FROM cargos ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Dados do funcionário
        $nome_completo = trim($_POST['nome_completo']);
        $bi = trim($_POST['bi']);
        $data_nascimento = $_POST['data_nascimento'];
        $genero = $_POST['genero'];
        $telefone = trim($_POST['telefone']);
        $endereco = trim($_POST['endereco']);
        $data_contratacao = $_POST['data_contratacao'];
        $salario = (float)str_replace(',', '.', $_POST['salario']);
        
        // Dados do usuário (opcional)
        $criar_usuario = isset($_POST['criar_usuario']) ? true : false;
        $username = $criar_usuario ? trim($_POST['username']) : null;
        $email = $criar_usuario ? trim($_POST['email']) : null;
        $password = $criar_usuario ? trim($_POST['password']) : null;
        $cargo_id = $criar_usuario ? (int)$_POST['cargo_id'] : null;
        $departamento_id = $criar_usuario ? (int)$_POST['departamento_id'] : null;

        // Validar dados básicos
        if (empty($nome_completo) || empty($bi) || empty($data_contratacao)) {
            throw new Exception("Preencha todos os campos obrigatórios");
        }

        // Verificar se BI já existe
        $stmt = $db->prepare("SELECT id FROM funcionarios WHERE bi = ?");
        $stmt->execute([$bi]);
        if ($stmt->fetch()) {
            throw new Exception("Já existe um colaborador com este BI cadastrado");
        }

        // Iniciar transação
        $db->beginTransaction();

        // Criar usuário se necessário
        $usuario_id = null;
        if ($criar_usuario) {
            if (empty($username) || empty($email) || empty($password) || $cargo_id <= 0) {
                throw new Exception("Preencha todos os campos do usuário");
            }

            // Verificar se username ou email já existem
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                throw new Exception("Username ou email já estão em uso");
            }

            // Criar usuário
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (username, password, email, cargo_id, departamento_id) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $email, $cargo_id, $departamento_id]);
            $usuario_id = $db->lastInsertId();
        }

        // Inserir funcionário
        $stmt = $db->prepare("INSERT INTO funcionarios 
                             (usuario_id, nome_completo, bi, data_nascimento, genero, telefone, 
                              endereco, data_contratacao, salario)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $usuario_id, $nome_completo, $bi, $data_nascimento, $genero, $telefone,
            $endereco, $data_contratacao, $salario
        ]);
        $funcionario_id = $db->lastInsertId();

        // Commit da transação
        $db->commit();

        registrarAuditoria('Criou colaborador', 'funcionarios', $funcionario_id, "Nome: $nome_completo");

        $_SESSION['mensagem'] = "Colaborador cadastrado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Novo Colaborador - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .error {
            color: #dc3545;
            margin-top: 5px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .toggle-section {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Novo Colaborador</h1>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="post">
                <h3>Dados Pessoais</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome_completo">Nome Completo *</label>
                        <input type="text" name="nome_completo" required>
                    </div>
                    <div class="form-group">
                        <label for="bi">BI *</label>
                        <input type="text" name="bi" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento</label>
                        <input type="date" name="data_nascimento">
                    </div>
                    <div class="form-group">
                        <label for="genero">Gênero</label>
                        <select name="genero">
                            <option value="M">Masculino</option>
                            <option value="F">Feminino</option>
                            <option value="O">Outro</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="text" name="telefone">
                    </div>
                    <div class="form-group">
                        <label for="data_contratacao">Data de Contratação *</label>
                        <input type="date" name="data_contratacao" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <textarea name="endereco" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label for="salario">Salário (Kz)</label>
                    <input type="text" name="salario" placeholder="0,00">
                </div>

                <div class="toggle-section">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="criar_usuario" id="criarUsuarioCheckbox"> 
                            Criar usuário para este colaborador
                        </label>
                    </div>

                    <div id="usuarioFields" style="display: none;">
                        <h3>Dados de Acesso</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" name="username">
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" name="email">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Senha *</label>
                                <input type="password" name="password">
                            </div>
                            <div class="form-group">
                                <label for="cargo_id">Cargo *</label>
                                <select name="cargo_id">
                                    <option value="">Selecione um cargo</option>
                                    <?php foreach ($cargos as $cargo): ?>
                                        <option value="<?= $cargo['id'] ?>"><?= htmlspecialchars($cargo['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="departamento_id">Departamento</label>
                            <select name="departamento_id">
                                <option value="">Selecione um departamento</option>
                                <?php foreach ($departamentos as $depto): ?>
                                    <option value="<?= $depto['id'] ?>"><?= htmlspecialchars($depto['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Colaborador</button>
            </form>
        </div>
    </div>

    <script>
        // Mostrar/ocultar campos de usuário
        document.getElementById('criarUsuarioCheckbox').addEventListener('change', function() {
            document.getElementById('usuarioFields').style.display = this.checked ? 'block' : 'none';
            
            // Tornar campos obrigatórios
            const requiredFields = document.querySelectorAll('#usuarioFields input, #usuarioFields select');
            requiredFields.forEach(field => {
                field.required = this.checked;
            });
        });

        // Formatar valor monetário
        document.querySelector('input[name="salario"]').addEventListener('blur', function() {
            let value = this.value.replace(/[^\d,]/g, '').replace(',', '.');
            value = parseFloat(value) || 0;
            this.value = value.toFixed(2).replace('.', ',');
        });
    </script>
</body>
</html>