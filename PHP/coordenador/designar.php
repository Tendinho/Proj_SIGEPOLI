<?php
// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'sigepoli';
$username = 'admin';
$password = 'SenhaSegura123!';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Criar tabela de coordenadores se não existir
    $db->exec("CREATE TABLE IF NOT EXISTS coordenadores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        curso_id INT NOT NULL,
        professor_id INT NOT NULL,
        data_designacao DATE NOT NULL,
        FOREIGN KEY (curso_id) REFERENCES cursos(id),
        FOREIGN KEY (professor_id) REFERENCES professores(id),
        UNIQUE KEY (curso_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Criar tabela de auditoria com estrutura correta se não existir
    $db->exec("CREATE TABLE IF NOT EXISTS auditoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT,
        acao VARCHAR(50) NOT NULL,
        tabela_afetada VARCHAR(50) NOT NULL,
        registro_id INT,
        dados_anteriores TEXT,
        dados_novos TEXT,
        data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /PHP/login.php");
    exit();
}

// Verificar nível de acesso (7 para coordenadores)
if ($_SESSION['nivel_acesso'] < 7) {
    $_SESSION['mensagem'] = "Acesso negado. Permissões insuficientes.";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: /PHP/index.php");
    exit();
}

// Buscar cursos
$query_cursos = "SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute();

// Buscar professores
$query_professores = "SELECT p.id, f.nome_completo 
                      FROM professores p
                      JOIN funcionarios f ON p.funcionario_id = f.id
                      WHERE p.ativo = 1
                      ORDER BY f.nome_completo";
$stmt_professores = $db->prepare($query_professores);
$stmt_professores->execute();

// Processar designação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id = $_POST['curso_id'];
    $professor_id = $_POST['professor_id'];
    
    try {
        // Verificar se já existe um coordenador para o curso
        $query_verifica = "SELECT id FROM coordenadores WHERE curso_id = :curso_id";
        $stmt_verifica = $db->prepare($query_verifica);
        $stmt_verifica->bindParam(":curso_id", $curso_id);
        $stmt_verifica->execute();
        
        if ($stmt_verifica->rowCount() > 0) {
            // Atualizar coordenador existente
            $query = "UPDATE coordenadores SET professor_id = :professor_id WHERE curso_id = :curso_id";
        } else {
            // Inserir novo coordenador
            $query = "INSERT INTO coordenadores (curso_id, professor_id, data_designacao) 
                      VALUES (:curso_id, :professor_id, CURDATE())";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":curso_id", $curso_id);
        $stmt->bindParam(":professor_id", $professor_id);
        
        if ($stmt->execute()) {
            // Registrar ação na auditoria (usando a estrutura correta da tabela)
            $acao = "Designação de Coordenador";
            $detalhes = "Curso: $curso_id, Professor: $professor_id";
            $auditoria = $db->prepare("INSERT INTO auditoria 
                                     (usuario_id, acao, tabela_afetada, registro_id, dados_novos, data_hora) 
                                     VALUES (:usuario_id, :acao, 'coordenadores', :registro_id, :dados_novos, NOW())");
            $auditoria->bindParam(":usuario_id", $_SESSION['usuario_id']);
            $auditoria->bindParam(":acao", $acao);
            $auditoria->bindParam(":registro_id", $curso_id);
            $auditoria->bindParam(":dados_novos", $detalhes);
            $auditoria->execute();
            
            $_SESSION['mensagem'] = "Coordenador designado com sucesso!";
            $_SESSION['tipo_mensagem'] = "sucesso";
            header("Location: index.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['mensagem'] = "Erro ao designar coordenador: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: designar.php");
        exit();
    }
}

// Buscar coordenadores atuais
$query_coordenadores = "SELECT c.id, c.curso_id, c.professor_id, cr.nome as curso_nome, f.nome_completo as professor_nome
                        FROM coordenadores c
                        JOIN cursos cr ON c.curso_id = cr.id
                        JOIN professores p ON c.professor_id = p.id
                        JOIN funcionarios f ON p.funcionario_id = f.id
                        ORDER BY cr.nome";
$stmt_coordenadores = $db->prepare($query_coordenadores);
$stmt_coordenadores->execute();
$coordenadores = $stmt_coordenadores->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Designar Coordenador - SIGEPOLI</title>
    <style>
        .designar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .coordenadores-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f5f5f5;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        
        .alert-error {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 4px;
            text-decoration: none;
        }
        
        .btn-primary {
            color: #fff;
            background-color: #337ab7;
            border-color: #2e6da4;
        }
        
        .fas {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="designar-container">
        <h1><i class="fas">👔</i> Designar Coordenador de Curso</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] === 'sucesso' ? 'success' : 'error' ?>">
                <?= $_SESSION['mensagem'] ?>
                <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="curso_id">Curso</label>
                        <select id="curso_id" name="curso_id" required>
                            <option value="">Selecione um curso...</option>
                            <?php 
                            $stmt_cursos->execute();
                            while ($curso = $stmt_cursos->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                                <option value="<?= $curso['id'] ?>">
                                    <?= htmlspecialchars($curso['nome']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="professor_id">Professor</label>
                        <select id="professor_id" name="professor_id" required>
                            <option value="">Selecione um professor...</option>
                            <?php 
                            $stmt_professores->execute();
                            while ($professor = $stmt_professores->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                                <option value="<?= $professor['id'] ?>">
                                    <?= htmlspecialchars($professor['nome_completo']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas">💾</i> Designar Coordenador
                </button>
            </form>
        </div>
        
        <div class="coordenadores-list">
            <h2><i class="fas">📋</i> Coordenadores Atuais</h2>
            
            <?php if (count($coordenadores) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Coordenador</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coordenadores as $coordenador): ?>
                            <tr>
                                <td><?= htmlspecialchars($coordenador['curso_nome']) ?></td>
                                <td><?= htmlspecialchars($coordenador['professor_nome']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum coordenador designado no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>