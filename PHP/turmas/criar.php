<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para gestão acadêmica

$database = new Database();
$db = $database->getConnection();

// Buscar cursos para o formulário
$cursos = $db->query("SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = $_POST['nome'];
        $codigo = $_POST['codigo'];
        $curso_id = $_POST['curso_id'];
        $ano_letivo = $_POST['ano_letivo'];
        $ano_ingresso = $_POST['ano_ingresso'];
        $semestre = $_POST['semestre'];
        $capacidade = $_POST['capacidade'];
        $sala = $_POST['sala'];
        $periodo = $_POST['periodo'];

        // Validar dados
        if (empty($nome) || empty($codigo) || empty($curso_id) || empty($ano_letivo) || 
            empty($ano_ingresso) || empty($semestre) || empty($capacidade) || empty($periodo)) {
            throw new Exception("Todos os campos obrigatórios devem ser preenchidos");
        }

        // Verificar se o código já existe
        $stmt = $db->prepare("SELECT COUNT(*) FROM turmas WHERE codigo = ?");
        $stmt->execute([$codigo]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Já existe uma turma com este código");
        }

        // Inserir nova turma
        $stmt = $db->prepare("INSERT INTO turmas (nome, codigo, curso_id, ano_letivo, ano_ingresso, 
                             semestre, capacidade, sala, periodo, ativo) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$nome, $codigo, $curso_id, $ano_letivo, $ano_ingresso, $semestre, 
                       $capacidade, $sala, $periodo]);

        $turma_id = $db->lastInsertId();
        
        // Registrar ação na auditoria
        registrarAuditoria("CRIAR_TURMA", "turmas", $turma_id, "Nova turma criada: {$nome} ({$codigo})");

        $_SESSION['mensagem'] = "Turma criada com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        redirect('/PHP/turmas/index.php');
    } catch (Exception $e) {
        $_SESSION['mensagem'] = $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Criar Nova Turma - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="content-header">
        <h1><i class="fas fa-users-class"></i> Criar Nova Turma</h1>
        <div class="header-actions">
            <a href="/PHP/index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Menu Principal</a>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (isset($_SESSION['mensagem'])): ?>
                <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                    <?= $_SESSION['mensagem'] ?>
                    <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome da Turma *</label>
                        <input type="text" name="nome" id="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="codigo">Código da Turma *</label>
                        <input type="text" name="codigo" id="codigo" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="curso_id">Curso *</label>
                        <select name="curso_id" id="curso_id" required>
                            <option value="">Selecione um curso</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ano_letivo">Ano Letivo *</label>
                        <input type="number" name="ano_letivo" id="ano_letivo" min="2000" max="2100" 
                               value="<?= date('Y') ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ano_ingresso">Ano de Ingresso *</label>
                        <input type="number" name="ano_ingresso" id="ano_ingresso" min="2000" max="2100" 
                               value="<?= date('Y') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="semestre">Semestre *</label>
                        <select name="semestre" id="semestre" required>
                            <option value="1">1º Semestre</option>
                            <option value="2">2º Semestre</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="capacidade">Capacidade (alunos) *</label>
                        <input type="number" name="capacidade" id="capacidade" min="1" max="100" value="30" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sala">Sala</label>
                        <input type="text" name="sala" id="sala">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="periodo">Período *</label>
                        <select name="periodo" id="periodo" required>
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            </form>
        </div>
    </div>
    <script src="/Context/JS/script.js"></script>
</body>
</html>