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

// Buscar professor
$query = "SELECT p.*, f.* 
          FROM professores p
          JOIN funcionarios f ON p.funcionario_id = f.id
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$professor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    $_SESSION['mensagem'] = "Professor não encontrado!";
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
    $data_contratacao = $_POST['data_contratacao'];
    $titulacao = $_POST['titulacao'];
    $area_especializacao = $_POST['area_especializacao'];
    $salario = $_POST['salario'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $dados_anteriores = json_encode($professor);
    
    try {
        $db->beginTransaction();
        
        // Atualizar funcionário
        $query_funcionario = "UPDATE funcionarios 
                              SET nome_completo = :nome_completo, bi = :bi, data_nascimento = :data_nascimento,
                                  genero = :genero, telefone = :telefone, endereco = :endereco,
                                  data_contratacao = :data_contratacao, salario = :salario
                              WHERE id = :funcionario_id";
        
        $stmt_funcionario = $db->prepare($query_funcionario);
        $stmt_funcionario->bindParam(":nome_completo", $nome_completo);
        $stmt_funcionario->bindParam(":bi", $bi);
        $stmt_funcionario->bindParam(":data_nascimento", $data_nascimento);
        $stmt_funcionario->bindParam(":genero", $genero);
        $stmt_funcionario->bindParam(":telefone", $telefone);
        $stmt_funcionario->bindParam(":endereco", $endereco);
        $stmt_funcionario->bindParam(":data_contratacao", $data_contratacao);
        $stmt_funcionario->bindParam(":salario", $salario);
        $stmt_funcionario->bindParam(":funcionario_id", $professor['funcionario_id']);
        $stmt_funcionario->execute();
        
        // Atualizar professor
        $query_professor = "UPDATE professores 
                            SET titulacao = :titulacao, area_especializacao = :area_especializacao,
                                data_contratacao = :data_contratacao, ativo = :ativo
                            WHERE id = :id";
        
        $stmt_professor = $db->prepare($query_professor);
        $stmt_professor->bindParam(":titulacao", $titulacao);
        $stmt_professor->bindParam(":area_especializacao", $area_especializacao);
        $stmt_professor->bindParam(":data_contratacao", $data_contratacao);
        $stmt_professor->bindParam(":ativo", $ativo);
        $stmt_professor->bindParam(":id", $id);
        $stmt_professor->execute();
        
        $db->commit();
        
        registrarAuditoria('Atualização', 'professores', $id, $dados_anteriores, json_encode($_POST));
        
        $_SESSION['mensagem'] = "Professor atualizado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = "Erro ao atualizar professor: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Professor - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-user-edit"></i> Editar Professor</h1>
        
        <form method="post" action="editar.php?id=<?php echo $id; ?>">
            <div class="form-group">
                <label for="nome_completo">Nome Completo:</label>
                <input type="text" id="nome_completo" name="nome_completo" value="<?php echo $professor['nome_completo']; ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="bi">BI/Identificação:</label>
                    <input type="text" id="bi" name="bi" value="<?php echo $professor['bi']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo $professor['data_nascimento']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="genero">Gênero:</label>
                    <select id="genero" name="genero" required>
                        <option value="M" <?php echo $professor['genero'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $professor['genero'] == 'F' ? 'selected' : ''; ?>>Feminino</option>
                        <option value="O" <?php echo $professor['genero'] == 'O' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" value="<?php echo $professor['telefone']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="data_contratacao">Data de Contratação:</label>
                    <input type="date" id="data_contratacao" name="data_contratacao" value="<?php echo $professor['data_contratacao']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="salario">Salário:</label>
                    <input type="number" id="salario" name="salario" step="0.01" min="0" value="<?php echo $professor['salario']; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="titulacao">Titulação:</label>
                    <input type="text" id="titulacao" name="titulacao" value="<?php echo $professor['titulacao']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="area_especializacao">Área de Especialização:</label>
                    <input type="text" id="area_especializacao" name="area_especializacao" value="<?php echo $professor['area_especializacao']; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <textarea id="endereco" name="endereco" rows="3" required><?php echo $professor['endereco']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="ativo" <?php echo $professor['ativo'] ? 'checked' : ''; ?>>
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