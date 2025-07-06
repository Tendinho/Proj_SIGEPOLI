<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

// Buscar disciplinas ativas
$query_disciplinas = "SELECT id, nome FROM disciplinas WHERE ativo = 1 ORDER BY nome";
$stmt_disciplinas = $db->prepare($query_disciplinas);
$stmt_disciplinas->execute();

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
    $disciplinas = $_POST['disciplinas'] ?? [];
    
    try {
        $db->beginTransaction();
        
        // Criar funcionário
        $query_funcionario = "INSERT INTO funcionarios 
                             (nome_completo, bi, data_nascimento, genero, telefone, endereco, data_contratacao, salario)
                             VALUES 
                             (:nome_completo, :bi, :data_nascimento, :genero, :telefone, :endereco, :data_contratacao, :salario)";
        
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
        
        // Criar professor
        $query_professor = "INSERT INTO professores 
                           (funcionario_id, titulacao, area_especializacao, data_contratacao)
                           VALUES
                           (:funcionario_id, :titulacao, :area_especializacao, :data_contratacao)";
        
        $stmt_professor = $db->prepare($query_professor);
        $stmt_professor->bindParam(":funcionario_id", $funcionario_id);
        $stmt_professor->bindParam(":titulacao", $titulacao);
        $stmt_professor->bindParam(":area_especializacao", $area_especializacao);
        $stmt_professor->bindParam(":data_contratacao", $data_contratacao);
        $stmt_professor->execute();
        
        $professor_id = $db->lastInsertId();
        
        // Associar disciplinas ao professor
        if (!empty($disciplinas)) {
            $query_associar = "INSERT INTO professor_disciplinas (professor_id, disciplina_id) VALUES ";
            $values = [];
            $params = [];
            
            foreach ($disciplinas as $index => $disciplina_id) {
                $values[] = "(:professor_id, :disciplina_id_$index)";
                $params[":disciplina_id_$index"] = $disciplina_id;
            }
            
            $query_associar .= implode(", ", $values);
            $stmt_associar = $db->prepare($query_associar);
            $stmt_associar->bindParam(":professor_id", $professor_id);
            
            foreach ($params as $key => $val) {
                $stmt_associar->bindValue($key, $val);
            }
            
            $stmt_associar->execute();
        }
        
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
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/professores.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="professores-container">
        <div class="professores-header">
            <h1 class="professores-title">
                <i class="fas fa-user-plus"></i> Cadastrar Novo Professor
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
        
        <form method="post" class="professores-form">
            <div class="form-row">
                <div class="professores-form-group">
                    <label for="nome_completo">Nome Completo *</label>
                    <input type="text" id="nome_completo" name="nome_completo" required
                           value="<?= isset($_POST['nome_completo']) ? htmlspecialchars($_POST['nome_completo']) : '' ?>">
                </div>
                
                <div class="professores-form-group">
                    <label for="bi">BI/Identificação *</label>
                    <input type="text" id="bi" name="bi" required
                           value="<?= isset($_POST['bi']) ? htmlspecialchars($_POST['bi']) : '' ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="professores-form-group">
                    <label for="data_nascimento">Data de Nascimento *</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" required
                           value="<?= isset($_POST['data_nascimento']) ? htmlspecialchars($_POST['data_nascimento']) : '' ?>">
                </div>
                
                <div class="professores-form-group">
                    <label for="genero">Gênero *</label>
                    <select id="genero" name="genero" required>
                        <option value="M" <?= isset($_POST['genero']) && $_POST['genero'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= isset($_POST['genero']) && $_POST['genero'] == 'F' ? 'selected' : '' ?>>Feminino</option>
                        <option value="O" <?= isset($_POST['genero']) && $_POST['genero'] == 'O' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                
                <div class="professores-form-group">
                    <label for="telefone">Telefone *</label>
                    <input type="tel" id="telefone" name="telefone" required
                           value="<?= isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : '' ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="professores-form-group">
                    <label for="data_contratacao">Data de Contratação *</label>
                    <input type="date" id="data_contratacao" name="data_contratacao" required
                           value="<?= isset($_POST['data_contratacao']) ? htmlspecialchars($_POST['data_contratacao']) : '' ?>">
                </div>
                
                <div class="professores-form-group">
                    <label for="salario">Salário *</label>
                    <input type="number" id="salario" name="salario" step="0.01" min="0" required
                           value="<?= isset($_POST['salario']) ? htmlspecialchars($_POST['salario']) : '' ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="professores-form-group">
                    <label for="titulacao">Titulação *</label>
                    <input type="text" id="titulacao" name="titulacao" required
                           value="<?= isset($_POST['titulacao']) ? htmlspecialchars($_POST['titulacao']) : '' ?>">
                </div>
                
                <div class="professores-form-group">
                    <label for="area_especializacao">Área de Especialização *</label>
                    <input type="text" id="area_especializacao" name="area_especializacao" required
                           value="<?= isset($_POST['area_especializacao']) ? htmlspecialchars($_POST['area_especializacao']) : '' ?>">
                </div>
            </div>
            
            <div class="professores-form-group">
                <label for="endereco">Endereço *</label>
                <textarea id="endereco" name="endereco" rows="3" required><?= isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : '' ?></textarea>
            </div>
            
            <div class="professores-form-group">
                <label>Disciplinas que leciona:</label>
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
            
            <div class="professores-form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Professor
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