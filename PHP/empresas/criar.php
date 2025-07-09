<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $nif = $_POST['nif'];
    $tipo_servico = $_POST['tipo_servico'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    $responsavel = $_POST['responsavel'];
    $data_contratacao = $_POST['data_contratacao'];
    
    $query = "INSERT INTO empresas 
              SET nome = :nome, nif = :nif, tipo_servico = :tipo_servico,
                  telefone = :telefone, email = :email, endereco = :endereco,
                  responsavel = :responsavel, data_contratacao = :data_contratacao";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":nome", $nome);
    $stmt->bindParam(":nif", $nif);
    $stmt->bindParam(":tipo_servico", $tipo_servico);
    $stmt->bindParam(":telefone", $telefone);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":endereco", $endereco);
    $stmt->bindParam(":responsavel", $responsavel);
    $stmt->bindParam(":data_contratacao", $data_contratacao);
    
    if ($stmt->execute()) {
        $empresa_id = $db->lastInsertId();
        registrarAuditoria('Criação', 'empresas', $empresa_id, null, json_encode($_POST));
        
        $_SESSION['mensagem'] = "Empresa cadastrada com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao cadastrar empresa!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Empresa - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
 
    <div class="content">
        <h1><i class="fas fa-building"></i> Cadastrar Nova Empresa</h1>
        
        <form method="post" action="criar.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome da Empresa:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="nif">NIF:</label>
                    <input type="text" id="nif" name="nif" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_servico">Tipo de Serviço:</label>
                    <select id="tipo_servico" name="tipo_servico" required>
                        <option value="">Selecione...</option>
                        <option value="Limpeza">Limpeza</option>
                        <option value="Segurança">Segurança</option>
                        <option value="Cafetaria">Cafetaria</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email">
                </div>
                
                <div class="form-group">
                    <label for="responsavel">Responsável:</label>
                    <input type="text" id="responsavel" name="responsavel" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data_contratacao">Data de Contratação:</label>
                    <input type="date" id="data_contratacao" name="data_contratacao" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <textarea id="endereco" name="endereco" rows="3"></textarea>
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