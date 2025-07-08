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
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 400px;
        }
        
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            text-align: center;
        }
        
        .login-box h1 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 28px;
        }
        
        .login-box h2 {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 25px;
            font-weight: normal;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .login-footer {
            margin-top: 20px;
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
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
            
            <div class="login-footer">
                <p>Usuário: admin | Senha: admin123</p>
            </div>
        </div>
    </div>
</body>
</html>