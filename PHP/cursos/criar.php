<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

// Buscar departamentos, professores e disciplinas
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

$query_disciplinas = "SELECT id, nome FROM disciplinas WHERE ativo = 1 AND curso_id IS NULL ORDER BY nome";
$stmt_disciplinas = $db->prepare($query_disciplinas);
$stmt_disciplinas->execute();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $codigo = $_POST['codigo'];
    $duracao_anos = $_POST['duracao_anos'];
    $descricao = $_POST['descricao'];
    $departamento_id = $_POST['departamento_id'];
    $coordenador_id = $_POST['coordenador_id'];
    $disciplinas = $_POST['disciplinas'] ?? [];
    
    try {
        $db->beginTransaction();
        
        // Criar curso
        $query = "INSERT INTO cursos 
                 (nome, codigo, duracao_anos, descricao, departamento_id, coordenador_id)
                 VALUES
                 (:nome, :codigo, :duracao_anos, :descricao, :departamento_id, :coordenador_id)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":codigo", $codigo);
        $stmt->bindParam(":duracao_anos", $duracao_anos);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":departamento_id", $departamento_id);
        $stmt->bindParam(":coordenador_id", $coordenador_id);
        $stmt->execute();
        
        $curso_id = $db->lastInsertId();
        
        // Associar disciplinas ao curso
        if (!empty($disciplinas)) {
            $query_associar = "UPDATE disciplinas SET curso_id = :curso_id WHERE id = :disciplina_id";
            $stmt_associar = $db->prepare($query_associar);
            $stmt_associar->bindParam(":curso_id", $curso_id);
            
            foreach ($disciplinas as $disciplina_id) {
                $stmt_associar->bindParam(":disciplina_id", $disciplina_id);
                $stmt_associar->execute();
            }
        }
        
        $db->commit();
        
        registrarAuditoria('Criação', 'cursos', $curso_id, null, json_encode($_POST));
        
        $_SESSION['mensagem'] = "Curso cadastrado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = "Erro ao cadastrar curso: " . $e->getMessage();
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
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/cursos.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="cursos-container">
        <div class="cursos-header">
            <h1 class="cursos-title">
                <i class="fas fa-book-medical"></i> Cadastrar Novo Curso
            </h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] === 'sucesso' ? 'success' : 'error' ?>">
                <i class="fas <?= $_SESSION['tipo_mensagem'] === 'sucesso' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= $_SESSION['mensagem'] ?>
                <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="cursos-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome do Curso *</label>
                    <input type="text" id="nome" name="nome" required
                           value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="codigo">Código *</label>
                    <input type="text" id="codigo" name="codigo" required
                           value="<?= isset($_POST['codigo']) ? htmlspecialchars($_POST['codigo']) : '' ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duracao_anos">Duração (anos) *</label>
                    <input type="number" id="duracao_anos" name="duracao_anos" min="1" max="10" required
                           value="<?= isset($_POST['duracao_anos']) ? htmlspecialchars($_POST['duracao_anos']) : '3' ?>">
                </div>
                
                <div class="form-group">
                    <label for="departamento_id">Departamento *</label>
                    <select id="departamento_id" name="departamento_id" required>
                        <option value="">Selecione...</option>
                        <?php 
                        $stmt_departamentos->execute();
                        while ($depto = $stmt_departamentos->fetch(PDO::FETCH_ASSOC)): 
                            $selected = isset($_POST['departamento_id']) && $_POST['departamento_id'] == $depto['id'] ? 'selected' : '';
                        ?>
                            <option value="<?= $depto['id'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($depto['nome']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="coordenador_id">Coordenador *</label>
                    <select id="coordenador_id" name="coordenador_id" required>
                        <option value="">Selecione...</option>
                        <?php 
                        $stmt_professores->execute();
                        while ($prof = $stmt_professores->fetch(PDO::FETCH_ASSOC)): 
                            $selected = isset($_POST['coordenador_id']) && $_POST['coordenador_id'] == $prof['id'] ? 'selected' : '';
                        ?>
                            <option value="<?= $prof['id'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($prof['nome_completo']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" rows="4"><?= isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : '' ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Disciplinas do Curso:</label>
                <div class="disciplinas-container">
                    <?php 
                    $stmt_disciplinas->execute();
                    while ($disciplina = $stmt_disciplinas->fetch(PDO::FETCH_ASSOC)): 
                        $checked = isset($_POST['disciplinas']) && in_array($disciplina['id'], $_POST['disciplinas']) ? 'checked' : '';
                    ?>
                        <div class="checkbox-group">
                            <input type="checkbox" id="disciplina_<?= $disciplina['id'] ?>" 
                                   name="disciplinas[]" value="<?= $disciplina['id'] ?>" <?= $checked ?>>
                            <label for="disciplina_<?= $disciplina['id'] ?>">
                                <?= htmlspecialchars($disciplina['nome']) ?>
                            </label>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Curso
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    
    <script src="/Context/JS/script.js"></script>
</body>
</html>