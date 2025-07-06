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

// Buscar empresa
$query = "SELECT * FROM empresas WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    $_SESSION['mensagem'] = "Empresa não encontrada!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $nif = $_POST['nif'];
    $tipo_servico = $_POST['tipo_servico'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    $responsavel = $_POST['responsavel'];
    $data_contratacao = $_POST['data_contratacao'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $dados_anteriores = json_encode($empresa);
    
    $query = "UPDATE empresas 
              SET nome = :nome, nif = :nif, tipo_servico = :tipo_servico,
                  telefone = :telefone, email = :email, endereco = :endereco,
                  responsavel = :responsavel, data_contratacao = :data_contratacao,
                  ativo = :ativo
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":nome", $nome);
    $stmt->bindParam(":nif", $nif);
    $stmt->bindParam(":tipo_servico", $tipo_servico);
    $stmt->bindParam(":telefone", $telefone);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":endereco", $endereco);
    $stmt->bindParam(":responsavel", $responsavel);
    $stmt->bindParam(":data_contratacao", $data_contratacao);
    $stmt->bindParam(":ativo", $ativo);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Atualização', 'empresas', $id, $dados_anteriores, json_encode($_POST));
        
        $_SESSION['mensagem'] = "Empresa atualizada com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar empresa!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empresa - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    
    
    <div class="content">
        <h1><i class="fas fa-building"></i> Editar Empresa</h1>
        
        <form method="post" action="editar.php?id=<?php echo $id; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome da Empresa:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo $empresa['nome']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="nif">NIF:</label>
                    <input type="text" id="nif" name="nif" value="<?php echo $empresa['nif']; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_servico">Tipo de Serviço:</label>
                    <select id="tipo_servico" name="tipo_servico" required>
                        <option value="Limpeza" <?php echo $empresa['tipo_servico'] == 'Limpeza' ? 'selected' : ''; ?>>Limpeza</option>
                        <option value="Segurança" <?php echo $empresa['tipo_servico'] == 'Segurança' ? 'selected' : ''; ?>>Segurança</option>
                        <option value="Cafetaria" <?php echo $empresa['tipo_servico'] == 'Cafetaria' ? 'selected' : ''; ?>>Cafetaria</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" value="<?php echo $empresa['telefone']; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo $empresa['email']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="responsavel">Responsável:</label>
                    <input type="text" id="responsavel" name="responsavel" value="<?php echo $empresa['responsavel']; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data_contratacao">Data de Contratação:</label>
                    <input type="date" id="data_contratacao" name="data_contratacao" value="<?php echo $empresa['data_contratacao']; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <textarea id="endereco" name="endereco" rows="3"><?php echo $empresa['endereco']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="ativo" <?php echo $empresa['ativo'] ? 'checked' : ''; ?>>
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