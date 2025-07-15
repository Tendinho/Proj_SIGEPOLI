<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para coordenadores/professores

$database = new Database();
$db = $database->getConnection();

$erro = '';
$sucesso = '';

// Buscar cursos para o select
$cursos = $db->query("SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome']);
        $codigo = trim($_POST['codigo']);
        $carga_horaria = (int)$_POST['carga_horaria'];
        $curso_id = (int)$_POST['curso_id'];
        $semestre = (int)$_POST['semestre'];
        $descricao = trim($_POST['descricao']);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Validações
        if (empty($nome) || empty($codigo) || empty($carga_horaria) || $curso_id <= 0) {
            throw new Exception("Preencha todos os campos obrigatórios");
        }

        if ($carga_horaria <= 0) {
            throw new Exception("Carga horária deve ser maior que zero");
        }

        if ($semestre < 1 || $semestre > 8) {
            throw new Exception("Semestre inválido (deve ser entre 1 e 8)");
        }

        // Verificar se código já existe
        $stmt = $db->prepare("SELECT id FROM disciplinas WHERE codigo = ?");
        $stmt->execute([$codigo]);
        if ($stmt->fetch()) {
            throw new Exception("Já existe uma disciplina com este código");
        }

        // Inserir no banco
        $stmt = $db->prepare("INSERT INTO disciplinas 
                             (nome, codigo, carga_horaria, curso_id, semestre, descricao, ativo)
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $codigo, $carga_horaria, $curso_id, $semestre, $descricao, $ativo]);

        $disciplina_id = $db->lastInsertId();
        registrarAuditoria('Criou disciplina', 'disciplinas', $disciplina_id, "Nome: $nome, Código: $codigo");

        $_SESSION['mensagem'] = "Disciplina criada com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Nova Disciplina - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .required:after {
            content: " *";
            color: red;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-book"></i> Nova Disciplina</h1>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome" class="required">Nome da Disciplina</label>
                        <input type="text" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="codigo" class="required">Código</label>
                        <input type="text" name="codigo" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="carga_horaria" class="required">Carga Horária (horas)</label>
                        <input type="number" name="carga_horaria" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="curso_id" class="required">Curso</label>
                        <select name="curso_id" required>
                            <option value="">Selecione um curso</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="semestre" class="required">Semestre</label>
                        <select name="semestre" required>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>º Semestre</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ativo">Status</label>
                        <div class="checkbox-group">
                            <input type="checkbox" name="ativo" id="ativo" checked>
                            <label for="ativo" style="margin-bottom: 0;">Ativa</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea name="descricao" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Disciplina</button>
            </form>
        </div>
    </div>
</body>
</html>