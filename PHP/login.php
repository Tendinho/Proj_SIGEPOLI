<?php
require_once 'config.php';

// Se o usuário já estiver logado, redirecione para a página inicial
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Consulta para buscar o usuário
    $query = "SELECT u.id, u.username, u.password, u.email, c.nivel_acesso, f.nome_completo 
              FROM usuarios u 
              JOIN cargos c ON u.cargo_id = c.id 
              JOIN funcionarios f ON u.id = f.usuario_id 
              WHERE u.username = :username AND u.ativo = 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Comparação direta em texto puro
        if ($password === $row['password']) {
            // Definir variáveis de sessão
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['nivel_acesso'] = $row['nivel_acesso'];
            $_SESSION['nome_completo'] = $row['nome_completo'];

            // Atualizar último login
            $updateQuery = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(":id", $row['id'], PDO::PARAM_INT);
            $updateStmt->execute();

            // Redirecionar para a página inicial
            header("Location: index.php");
            exit();
        } else {
            $mensagem = "Senha incorreta!";
        }
    } else {
        $mensagem = "Usuário não encontrado ou inativo!";
    }
    
    // Se chegou aqui, o login falhou
    $mensagem = $mensagem ?: "Credenciais inválidas!";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/login.css">

</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <h1>SIGEPOLI</h1>
            <h2>Sistema Integrado de Gestão</h2>

            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label for="username">Usuário:</label>
                    <input type="text" id="username" name="username" required autofocus value="admin">
                </div>

                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required value="admin123">
                </div>

                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>
            

        </div>
    </div>
</body>
</html>