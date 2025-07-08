<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(3); // Nível de acesso para RH

$database = new Database();
$db = $database->getConnection();

$erro = '';
$sucesso = '';

$colaborador_id = (int)$_GET['id'];
$colaborador = null;
$usuario = null;

// Buscar dados do colaborador
$stmt = $db->prepare("SELECT f.*, u.username, u.email, u.cargo_id, u.departamento_id 
                     FROM funcionarios f
                     LEFT JOIN usuarios u ON f.usuario_id = u.id
                     WHERE f.id = ?");
$stmt->execute([$colaborador_id]);
$colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$colaborador) {
    $_SESSION['mensagem'] = "Colaborador não encontrado";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

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
        
        // Dados do usuário
        $username = trim($_POST['username'] ?? null);
        $email = trim($_POST['email'] ?? null);
        $password = trim($_POST['password'] ?? null);
        $cargo_id = isset($_POST['cargo_id']) ? (int)$_POST['cargo_id'] : null;
        $departamento_id = isset($_POST['departamento_id']) ? (int)$_POST['departamento_id'] : null;

        // Validar dados básicos
        if (empty($nome_completo) || empty($bi) || empty($data_contratacao)) {
            throw new Exception("Preencha todos os campos obrigatórios");
        }

        // Verificar se BI já existe (exceto para o próprio colaborador)
        $stmt = $db->prepare("SELECT id FROM funcionarios WHERE bi = ? AND id != ?");
        $stmt->execute([$bi, $colaborador_id]);
        if ($stmt->fetch()) {
            throw new Exception("Já existe um colaborador com este BI cadastrado");
        }

        // Iniciar transação
        $db->beginTransaction();

        // Atualizar funcionário
        $stmt = $db->prepare("UPDATE funcionarios 
                             SET nome_completo = ?, bi = ?, data_nascimento = ?, genero = ?,
                                 telefone = ?, endereco = ?, data_contratacao = ?, salario = ?
                             WHERE id = ?");
        $stmt->execute([
            $nome_completo, $bi, $data_nascimento, $genero,
            $telefone, $endereco, $data_contratacao, $salario,
            $colaborador_id
        ]);

        // Se já tem usuário, atualizar
        if ($colaborador['usuario_id']) {
            $updateFields = "username = :username, email = :email, cargo_id = :cargo_id, departamento_id = :departamento_id";
            $params = [
                ':username' => $username,
                ':email' => $email,
                ':cargo_id' => $cargo_id,
                ':departamento_id' => $departamento_id,
                ':id' => $colaborador['usuario_id']
            ];

            if (!empty($password)) {
                $updateFields .= ", password = :password";
                $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $stmt = $db->prepare("UPDATE usuarios SET $updateFields WHERE id = :id");
            $stmt->execute($params);
        }
        // Se não tem usuário mas foi fornecido username, criar
        elseif (!empty($username)) {
            if (empty($email) || empty($password) || $cargo_id <= 0) {
                throw new Exception("Para criar usuário, preencha todos os campos obrigatórios");
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

            // Atualizar funcionário com o novo usuario_id
            $stmt = $db->prepare("UPDATE funcionarios SET usuario_id = ? WHERE id = ?");
            $stmt->execute([$usuario_id, $colaborador_id]);
        }

        // Commit da transação
        $db->commit();

        registrarAuditoria('Editou colaborador', 'funcionarios', $colaborador_id, "Nome: $nome_completo");

        $_SESSION['mensagem'] = "Colaborador atualizado com sucesso!";
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
    <title>Editar Colaborador - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        /* Estilos similares ao criar.php */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Editar Colaborador</h1>
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
                        <input type="text" name="nome_completo" value="<?= htmlspecialchars($colaborador['nome_completo']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="bi">BI *</label>
                        <input type="text" name="bi" value="<?= htmlspecialchars($colaborador['bi']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" value="<?= htmlspecialchars($colaborador['data_nascimento']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="genero">Gênero</label>
                        <select name="genero">
                            <option value="M" <?= $colaborador['genero'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= $colaborador['genero'] == 'F' ? 'selected' : '' ?>>Feminino</option>
                            <option value="O" <?= $colaborador['genero'] == 'O' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="text" name="telefone" value="<?= htmlspecialchars($colaborador['telefone']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_contratacao">Data de Contratação *</label>
                        <input type="date" name="data_contratacao" value="<?= htmlspecialchars($colaborador['data_contratacao']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <textarea name="endereco" rows="2"><?= htmlspecialchars($colaborador['endereco']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="salario">Salário (Kz)</label>
                    <input type="text" name="salario" placeholder="0,00" value="<?= number_format($colaborador['salario'], 2, ',', '') ?>">
                </div>

                <div class="toggle-section">
                    <h3>Dados de Acesso</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($colaborador['username'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($colaborador['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Nova Senha (deixe em branco para manter atual)</label>
                            <input type="password" name="password">
                        </div>
                        <div class="form-group">
                            <label for="cargo_id">Cargo</label>
                            <select name="cargo_id">
                                <option value="">Selecione um cargo</option>
                                <?php foreach ($cargos as $cargo): ?>
                                    <option value="<?= $cargo['id'] ?>" <?= ($colaborador['cargo_id'] ?? 0) == $cargo['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cargo['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="departamento_id">Departamento</label>
                        <select name="departamento_id">
                            <option value="">Selecione um departamento</option>
                            <?php foreach ($departamentos as $depto): ?>
                                <option value="<?= $depto['id'] ?>" <?= ($colaborador['departamento_id'] ?? 0) == $depto['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($depto['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Atualizar Colaborador</button>
            </form>
        </div>
    </div>

    <script>
        // Formatar valor monetário
        document.querySelector('input[name="salario"]').addEventListener('blur', function() {
            let value = this.value.replace(/[^\d,]/g, '').replace(',', '.');
            value = parseFloat(value) || 0;
            this.value = value.toFixed(2).replace('.', ',');
        });
    </script>
</body>
</html>