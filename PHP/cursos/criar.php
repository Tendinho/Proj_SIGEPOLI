<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

// Buscar departamentos e professores para selects
$query_departamentos = "SELECT id, nome FROM departamentos ORDER BY nome";
$stmt_departamentos = $db->prepare($query_departamentos);
$stmt_departamentos->execute();

$query_professores = "SELECT p.id, f.nome_completo 
                      FROM professores p
                      JOIN funcionarios f ON p.funcionario_id = f.id
                      WHERE p.ativo = 1
                      ORDER BY f.nome_completo";
$stmt_professores = $db->prepare($query_professores);
$stmt_professores->execute();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $codigo = $_POST['codigo'];
    $duracao_anos = $_POST['duracao_anos'];
    $descricao = $_POST['descricao'];
    $departamento_id = $_POST['departamento_id'];
    $coordenador_id = $_POST['coordenador_id'];
    
    $query = "INSERT INTO cursos 
              SET nome = :nome, codigo = :codigo, duracao_anos = :duracao_anos,
                  descricao = :descricao, departamento_id = :departamento_id,
                  coordenador_id = :coordenador_id";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":nome", $nome);
    $stmt->bindParam(":codigo", $codigo);
    $stmt->bindParam(":duracao_anos", $duracao_anos);
    $stmt->bindParam(":descricao", $descricao);
    $stmt->bindParam(":departamento_id", $departamento_id);
    $stmt->bindParam(":coordenador_id", $coordenador_id);
    
    if ($stmt->execute()) {
        $curso_id = $db->lastInsertId();
        registrarAuditoria('Criação', 'cursos', $curso_id, null, json_encode($_POST));
        
        $_SESSION['mensagem'] = "Curso cadastrado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao cadastrar curso!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Curso - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-book-medical"></i> Cadastrar Novo Curso</h1>
        
        <form method="post" action="criar.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome do Curso:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="codigo">Código:</label>
                    <input type="text" id="codigo" name="codigo" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duracao_anos">Duração (anos):</label>
                    <input type="number" id="duracao_anos" name="duracao_anos" min="1" max="10" required>
                </div>
                
                <div class="form-group">
                    <label for="departamento_id">Departamento:</label>
                    <select id="departamento_id" name="departamento_id" required>
                        <option value="">Selecione...</option>
                        <?php while ($depto = $stmt_departamentos->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $depto['id']; ?>"><?php echo $depto['nome']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="coordenador_id">Coordenador:</label>
                    <select id="coordenador_id" name="coordenador_id" required>
                        <option value="">Selecione...</option>
                        <?php while ($prof = $stmt_professores->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $prof['id']; ?>"><?php echo $prof['nome_completo']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="4"></textarea>
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