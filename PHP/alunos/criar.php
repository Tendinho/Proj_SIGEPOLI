<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

verificarLogin();
verificarAcesso(2);

$database = new Database();
$db = $database->getConnection();

// Buscar cursos ativos
$query_cursos = "SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar e validar inputs
    $nome_completo = trim($_POST['nome_completo']);
    $bi = preg_replace('/[^0-9]/', '', $_POST['bi']);
    $data_nascimento = $_POST['data_nascimento'];
    $genero = $_POST['genero'];
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $endereco = trim($_POST['endereco']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $curso_id = !empty($_POST['curso_id']) ? $_POST['curso_id'] : null;
    
    // Validações
    $erros = [];
    
    if (empty($nome_completo)) {
        $erros[] = "O nome completo é obrigatório";
    }

    if (empty($bi)) {
        $erros[] = "O número do BI é obrigatório";
    } elseif (strlen($bi) > 20) {
        $erros[] = "O BI deve ter no máximo 20 caracteres";
    } elseif (verificarBIExistente($bi)) {
        $erros[] = "Este número de BI já está cadastrado em um aluno ativo";
    }

    if (!DateTime::createFromFormat('Y-m-d', $data_nascimento)) {
        $erros[] = "Data de nascimento inválida";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido";
    }

    // Se não houver erros, tenta inserir
    if (empty($erros)) {
        try {
            $query = "INSERT INTO alunos 
                     (nome_completo, bi, data_nascimento, genero, telefone, endereco, email, data_inscricao, curso_id) 
                     VALUES 
                     (:nome_completo, :bi, :data_nascimento, :genero, :telefone, :endereco, :email, CURDATE(), :curso_id)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(":nome_completo", $nome_completo);
            $stmt->bindParam(":bi", $bi);
            $stmt->bindParam(":data_nascimento", $data_nascimento);
            $stmt->bindParam(":genero", $genero);
            $stmt->bindParam(":telefone", $telefone);
            $stmt->bindParam(":endereco", $endereco);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":curso_id", $curso_id);

            if ($stmt->execute()) {
                $aluno_id = $db->lastInsertId();
                registrarAuditoria('Criação', 'alunos', $aluno_id, null, json_encode($_POST));
                
                $_SESSION['mensagem'] = "Aluno cadastrado com sucesso!";
                $_SESSION['tipo_mensagem'] = "sucesso";
                header("Location: index.php");
                exit();
            }
        } catch(PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $erros[] = strpos($e->getMessage(), 'email') !== false 
                    ? "Este e-mail já está cadastrado" 
                    : "Erro de duplicação de dados";
            } else {
                $erros[] = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }

    // Se chegou aqui, houve erros
    $_SESSION['mensagem'] = implode("<br>", $erros);
    $_SESSION['tipo_mensagem'] = "erro";
    $_SESSION['dados_formulario'] = $_POST;
    header("Location: criar.php");
    exit();
}

// Recupera dados do formulário se houver erro
$dadosFormulario = $_SESSION['dados_formulario'] ?? [];
unset($_SESSION['dados_formulario']);

// Função para verificar BI existente
function verificarBIExistente($bi) {
    global $db;
    $query = "SELECT COUNT(*) as total FROM alunos WHERE bi = :bi AND ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":bi", $bi);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] > 0;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Aluno - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/alunos.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="alunos-container">
        <div class="alunos-header">
            <h1><i class="fas fa-user-plus"></i> Cadastrar Novo Aluno</h1>
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

        <form method="post" class="alunos-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome_completo">Nome Completo *</label>
                    <input type="text" id="nome_completo" name="nome_completo" required
                           value="<?= isset($dadosFormulario['nome_completo']) ? htmlspecialchars($dadosFormulario['nome_completo']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="bi">Número do BI *</label>
                    <input type="text" id="bi" name="bi" required
                           value="<?= isset($dadosFormulario['bi']) ? htmlspecialchars($dadosFormulario['bi']) : '' ?>" 
                           maxlength="20">
                    <small>Máximo de 20 caracteres numéricos</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento *</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" required
                           value="<?= isset($dadosFormulario['data_nascimento']) ? htmlspecialchars($dadosFormulario['data_nascimento']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="genero">Gênero *</label>
                    <select id="genero" name="genero" required>
                        <option value="M" <?= isset($dadosFormulario['genero']) && $dadosFormulario['genero'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= isset($dadosFormulario['genero']) && $dadosFormulario['genero'] == 'F' ? 'selected' : '' ?>>Feminino</option>
                        <option value="O" <?= isset($dadosFormulario['genero']) && $dadosFormulario['genero'] == 'O' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone"
                           value="<?= isset($dadosFormulario['telefone']) ? htmlspecialchars($dadosFormulario['telefone']) : '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço</label>
                <textarea id="endereco" name="endereco"><?= isset($dadosFormulario['endereco']) ? htmlspecialchars($dadosFormulario['endereco']) : '' ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email"
                           value="<?= isset($dadosFormulario['email']) ? htmlspecialchars($dadosFormulario['email']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="curso_id">Curso</label>
                    <select id="curso_id" name="curso_id">
                        <option value="">Selecione um curso...</option>
                        <?php 
                        $stmt_cursos->execute();
                        while ($curso = $stmt_cursos->fetch(PDO::FETCH_ASSOC)): 
                            $selected = isset($dadosFormulario['curso_id']) && $dadosFormulario['curso_id'] == $curso['id'] ? 'selected' : '';
                        ?>
                            <option value="<?= $curso['id'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($curso['nome']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> Limpar
                </button>
            </div>
        </form>
    </div>
    
    <script src="/Context/JS/script.js"></script>
</body>
</html>