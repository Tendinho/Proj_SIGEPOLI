<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_completo = $_POST['nome_completo'];
    $bi = $_POST['bi'];
    $data_nascimento = $_POST['data_nascimento'];
    $genero = $_POST['genero'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];
    $data_contratacao = $_POST['data_contratacao'];
    $titulacao = $_POST['titulacao'];
    $area_especializacao = $_POST['area_especializacao'];
    $salario = $_POST['salario'];
    
    try {
        $db->beginTransaction();
        
        // Primeiro criar o funcionário
        $query_funcionario = "INSERT INTO funcionarios 
                              SET nome_completo = :nome_completo, bi = :bi, data_nascimento = :data_nascimento,
                                  genero = :genero, telefone = :telefone, endereco = :endereco,
                                  data_contratacao = :data_contratacao, salario = :salario";
        
        $stmt_funcionario = $db->prepare($query_funcionario);
        $stmt_funcionario->bindParam(":nome_completo", $nome_completo);
        $stmt_funcionario->bindParam(":bi", $bi);
        $stmt_funcionario->bindParam(":data_nascimento", $data_nascimento);
        $stmt_funcionario->bindParam(":genero", $genero);
        $stmt_funcionario->bindParam(":telefone", $telefone);
        $stmt_funcionario->bindParam(":endereco", $endereco);
        $stmt_funcionario->bindParam(":data_contratacao", $data_contratacao);
        $stmt_funcionario->bindParam(":salario", $salario);
        
        $stmt_funcionario->execute();
        $funcionario_id = $db->lastInsertId();
        
        // Depois criar o professor
        $query_professor = "INSERT INTO professores 
                            SET funcionario_id = :funcionario_id, titulacao = :titulacao,
                                area_especializacao = :area_especializacao, data_contratacao = :data_contratacao";
        
        $stmt_professor = $db->prepare($query_professor);
        $stmt_professor->bindParam(":funcionario_id", $funcionario_id);
        $stmt_professor->bindParam(":titulacao", $titulacao);
        $stmt_professor->bindParam(":area_especializacao", $area_especializacao);
        $stmt_professor->bindParam(":data_contratacao", $data_contratacao);
        $stmt_professor->execute();
        
        $professor_id = $db->lastInsertId();
        
        $db->commit();
        
        registrarAuditoria('Criação', 'professores', $professor_id, null, json_encode($_POST));
        
        $_SESSION['mensagem'] = "Professor cadastrado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = "Erro ao cadastrar professor: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Professor - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-user-plus"></i> Cadastrar Novo Professor</h1>
        
        <form method="post" action="criar.php">
            <div class="form-group">
                <label for="nome_completo">Nome Completo:</label>
                <input type="text" id="nome_completo" name="nome_completo" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="bi">BI/Identificação:</label>
                    <input type="text" id="bi" name="bi" required>
                </div>
                
                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" required>
                </div>
                
                <div class="form-group">
                    <label for="genero">Gênero:</label>
                    <select id="genero" name="genero" required>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                        <option value="O">Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" required>
                </div>
                
                <div class="form-group">
                    <label for="data_contratacao">Data de Contratação:</label>
                    <input type="date" id="data_contratacao" name="data_contratacao" required>
                </div>
                
                <div class="form-group">
                    <label for="salario">Salário:</label>
                    <input type="number" id="salario" name="salario" step="0.01" min="0" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="titulacao">Titulação:</label>
                    <input type="text" id="titulacao" name="titulacao" required>
                </div>
                
                <div class="form-group">
                    <label for="area_especializacao">Área de Especialização:</label>
                    <input type="text" id="area_especializacao" name="area_especializacao" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <textarea id="endereco" name="endereco" rows="3" required></textarea>
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