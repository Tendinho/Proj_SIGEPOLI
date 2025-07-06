<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Buscar aluno
$query = "SELECT * FROM alunos WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
    $_SESSION['mensagem'] = "Aluno não encontrado!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_completo = $_POST['nome_completo'];
    $bi = $_POST['bi'];
    $data_nascimento = $_POST['data_nascimento'];
    $genero = $_POST['genero'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];
    $email = $_POST['email'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $dados_anteriores = json_encode($aluno);
    
    $query = "UPDATE alunos 
              SET nome_completo = :nome_completo, bi = :bi, data_nascimento = :data_nascimento, 
                  genero = :genero, telefone = :telefone, endereco = :endereco, 
                  email = :email, ativo = :ativo 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":nome_completo", $nome_completo);
    $stmt->bindParam(":bi", $bi);
    $stmt->bindParam(":data_nascimento", $data_nascimento);
    $stmt->bindParam(":genero", $genero);
    $stmt->bindParam(":telefone", $telefone);
    $stmt->bindParam(":endereco", $endereco);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":ativo", $ativo);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Atualização', 'alunos', $id, $dados_anteriores, json_encode($_POST));
        
        $_SESSION['mensagem'] = "Aluno atualizado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar aluno!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aluno - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-user-edit"></i> Editar Aluno</h1>
        
        <form method="post" action="editar.php?id=<?php echo $id; ?>">
            <div class="form-group">
                <label for="nome_completo">Nome Completo:</label>
                <input type="text" id="nome_completo" name="nome_completo" value="<?php echo $aluno['nome_completo']; ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="bi">BI/Identificação:</label>
                    <input type="text" id="bi" name="bi" value="<?php echo $aluno['bi']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo $aluno['data_nascimento']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="genero">Gênero:</label>
                    <select id="genero" name="genero" required>
                        <option value="M" <?php echo $aluno['genero'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $aluno['genero'] == 'F' ? 'selected' : ''; ?>>Feminino</option>
                        <option value="O" <?php echo $aluno['genero'] == 'O' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" value="<?php echo $aluno['telefone']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo $aluno['email']; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <textarea id="endereco" name="endereco" rows="3" required><?php echo $aluno['endereco']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="ativo" <?php echo $aluno['ativo'] ? 'checked' : ''; ?>>
                    <span class="checkmark"></span>
                    Ativo
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                <a href="index.php" class="btn btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>