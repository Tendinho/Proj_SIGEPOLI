<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

verificarLogin();
verificarAcesso(2); // Nível 2 para edição

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Buscar dados do aluno
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM alunos WHERE id = :id AND ativo = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    $_SESSION['mensagem'] = "Aluno não encontrado ou já excluído";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar e validar inputs (mesmo do criar.php)
    $nome_completo = trim($_POST['nome_completo']);
    $bi = preg_replace('/[^0-9]/', '', $_POST['bi']);
    $data_nascimento = $_POST['data_nascimento'];
    $genero = $_POST['genero'];
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $endereco = trim($_POST['endereco']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // Validações (mesmo do criar.php, mas com exclusão do ID atual)
    $erros = [];
    
    if (empty($nome_completo)) {
        $erros[] = "O nome completo é obrigatório";
    }

    if (empty($bi)) {
        $erros[] = "O número do BI é obrigatório";
    } elseif (strlen($bi) > 20) {
        $erros[] = "O BI deve ter no máximo 20 caracteres";
    } elseif (verificarBIExistente($bi, $id)) {
        $erros[] = "Este número de BI já está cadastrado em outro aluno ativo";
    }

    if (!DateTime::createFromFormat('Y-m-d', $data_nascimento)) {
        $erros[] = "Data de nascimento inválida";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido";
    }

    // Se não houver erros, tenta atualizar
    if (empty($erros)) {
        try {
            $query = "UPDATE alunos SET 
                     nome_completo = :nome_completo,
                     bi = :bi,
                     data_nascimento = :data_nascimento,
                     genero = :genero,
                     telefone = :telefone,
                     endereco = :endereco,
                     email = :email
                     WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(":nome_completo", $nome_completo);
            $stmt->bindParam(":bi", $bi);
            $stmt->bindParam(":data_nascimento", $data_nascimento);
            $stmt->bindParam(":genero", $genero);
            $stmt->bindParam(":telefone", $telefone);
            $stmt->bindParam(":endereco", $endereco);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                registrarAuditoria('Editar', 'alunos', $id);
                $_SESSION['mensagem'] = "Aluno atualizado com sucesso!";
                $_SESSION['tipo_mensagem'] = "sucesso";
                header("Location: index.php");
                exit();
            }
        } catch(PDOException $e) {
            $erros[] = "Erro no banco de dados: " . $e->getMessage();
        }
    }

    // Se chegou aqui, houve erros
    $_SESSION['mensagem'] = implode("<br>", $erros);
    $_SESSION['tipo_mensagem'] = "erro";
    $_SESSION['dados_formulario'] = $_POST;
    header("Location: editar.php?id=$id");
    exit();
}

// Se houver dados de formulário com erro, usa eles, senão usa os do banco
$dadosFormulario = isset($_SESSION['dados_formulario']) ? $_SESSION['dados_formulario'] : $aluno;
unset($_SESSION['dados_formulario']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aluno - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/alunos.css">
</head>
<body>
    <div class="alunos-container">
        <div class="alunos-header">
            <h1>Editar Aluno</h1>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                <?= $_SESSION['mensagem'] ?>
                <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="alunos-form">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="alunos-form-group">
                <label for="nome_completo">Nome Completo:*</label>
                <input type="text" id="nome_completo" name="nome_completo" 
                       value="<?= htmlspecialchars($dadosFormulario['nome_completo'] ?? '') ?>" required>
            </div>

            <div class="alunos-form-group">
                <label for="bi">Número do BI:*</label>
                <input type="text" id="bi" name="bi" 
                       value="<?= htmlspecialchars($dadosFormulario['bi'] ?? '') ?>" 
                       maxlength="20" required>
                <small>Máximo de 20 caracteres numéricos</small>
            </div>

            <div class="alunos-form-group">
                <label for="data_nascimento">Data de Nascimento:*</label>
                <input type="date" id="data_nascimento" name="data_nascimento" 
                       value="<?= htmlspecialchars($dadosFormulario['data_nascimento'] ?? '') ?>" required>
            </div>

            <div class="alunos-form-group">
                <label for="genero">Gênero:*</label>
                <select id="genero" name="genero" required>
                    <option value="M" <?= ($dadosFormulario['genero'] ?? '') == 'M' ? 'selected' : '' ?>>Masculino</option>
                    <option value="F" <?= ($dadosFormulario['genero'] ?? '') == 'F' ? 'selected' : '' ?>>Feminino</option>
                    <option value="O" <?= ($dadosFormulario['genero'] ?? '') == 'O' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>

            <div class="alunos-form-group">
                <label for="telefone">Telefone:</label>
                <input type="tel" id="telefone" name="telefone" 
                       value="<?= htmlspecialchars($dadosFormulario['telefone'] ?? '') ?>">
            </div>

            <div class="alunos-form-group">
                <label for="endereco">Endereço:</label>
                <textarea id="endereco" name="endereco"><?= htmlspecialchars($dadosFormulario['endereco'] ?? '') ?></textarea>
            </div>

            <div class="alunos-form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($dadosFormulario['email'] ?? '') ?>">
            </div>

            <div class="alunos-form-actions">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>