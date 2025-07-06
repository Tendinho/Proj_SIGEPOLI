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

// Buscar curso
$query = "SELECT * FROM cursos WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$curso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$curso) {
    $_SESSION['mensagem'] = "Curso não encontrado!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

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
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $dados_anteriores = json_encode($curso);
    
    $query = "UPDATE cursos 
              SET nome = :nome, codigo = :codigo, duracao_anos = :duracao_anos,
                  descricao = :descricao, departamento_id = :departamento_id,
                  coordenador_id = :coordenador_id, ativo = :ativo
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":nome", $nome);
    $stmt->bindParam(":codigo", $codigo);
    $stmt->bindParam(":duracao_anos", $duracao_anos);
    $stmt->bindParam(":descricao", $descricao);
    $stmt->bindParam(":departamento_id", $departamento_id);
    $stmt->bindParam(":coordenador_id", $coordenador_id);
    $stmt->bindParam(":ativo", $ativo);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Atualização', 'cursos', $id, $dados_anteriores, json_encode($_POST));
        
        $_SESSION['mensagem'] = "Curso atualizado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar curso!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Curso - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
        <link rel="stylesheet" href="/Context/CSS/cursos.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    
    <div class="content">
        <h1><i class="fas fa-book-edit"></i> Editar Curso</h1>
        
        <form method="post" action="editar.php?id=<?php echo $id; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome do Curso:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo $curso['nome']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="codigo">Código:</label>
                    <input type="text" id="codigo" name="codigo" value="<?php echo $curso['codigo']; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duracao_anos">Duração (anos):</label>
                    <input type="number" id="duracao_anos" name="duracao_anos" min="1" max="10" value="<?php echo $curso['duracao_anos']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="departamento_id">Departamento:</label>
                    <select id="departamento_id" name="departamento_id" required>
                        <option value="">Selecione...</option>
                        <?php 
                        $stmt_departamentos->execute();
                        while ($depto = $stmt_departamentos->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $depto['id']; ?>" <?php echo $depto['id'] == $curso['departamento_id'] ? 'selected' : ''; ?>>
                                <?php echo $depto['nome']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="coordenador_id">Coordenador:</label>
                    <select id="coordenador_id" name="coordenador_id" required>
                        <option value="">Selecione...</option>
                        <?php 
                        $stmt_professores->execute();
                        while ($prof = $stmt_professores->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $prof['id']; ?>" <?php echo $prof['id'] == $curso['coordenador_id'] ? 'selected' : ''; ?>>
                                <?php echo $prof['nome_completo']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="4"><?php echo $curso['descricao']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="ativo" <?php echo $curso['ativo'] ? 'checked' : ''; ?>>
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