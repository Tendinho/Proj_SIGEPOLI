<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

// Buscar alunos ativos
$query_alunos = "SELECT id, nome_completo FROM alunos WHERE ativo = 1 ORDER BY nome_completo";
$stmt_alunos = $db->prepare($query_alunos);
$stmt_alunos->execute();

// Buscar cursos ativos para filtro
$query_cursos = "SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute();

// Buscar turmas ativas (ou filtradas se curso foi selecionado)
$curso_filtro = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : null;
$query_turmas = "SELECT t.id, t.nome, c.nome as curso 
                 FROM turmas t
                 JOIN cursos c ON t.curso_id = c.id
                 WHERE t.ativo = 1" .
                 ($curso_filtro ? " AND t.curso_id = :curso_id" : "") . "
                 ORDER BY t.nome";
$stmt_turmas = $db->prepare($query_turmas);
if ($curso_filtro) {
    $stmt_turmas->bindParam(":curso_id", $curso_filtro);
}
$stmt_turmas->execute();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aluno_id = $_POST['aluno_id'];
    $turma_id = $_POST['turma_id'];
    $ano_letivo = $_POST['ano_letivo'];
    $semestre = $_POST['semestre'];
    $valor_propina = $_POST['valor_propina'];
    $propina_paga = isset($_POST['propina_paga']) ? 1 : 0;
    
    // Verificar se já existe matrícula para o mesmo aluno/turma/ano/semestre
    $query_verifica = "SELECT COUNT(*) as total 
                       FROM matriculas 
                       WHERE aluno_id = :aluno_id 
                       AND turma_id = :turma_id 
                       AND ano_letivo = :ano_letivo 
                       AND semestre = :semestre";
    
    $stmt_verifica = $db->prepare($query_verifica);
    $stmt_verifica->bindParam(":aluno_id", $aluno_id);
    $stmt_verifica->bindParam(":turma_id", $turma_id);
    $stmt_verifica->bindParam(":ano_letivo", $ano_letivo);
    $stmt_verifica->bindParam(":semestre", $semestre);
    $stmt_verifica->execute();
    
    $resultado = $stmt_verifica->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado['total'] > 0) {
        $_SESSION['mensagem'] = "Este aluno já está matriculado nesta turma para o mesmo ano letivo e semestre!";
        $_SESSION['tipo_mensagem'] = "erro";
    } else {
        // Verificar se há vagas na turma
        $query_vagas = "SELECT t.capacidade, COUNT(m.id) as matriculados
                        FROM turmas t
                        LEFT JOIN matriculas m ON t.id = m.turma_id AND m.ano_letivo = :ano_letivo AND m.semestre = :semestre
                        WHERE t.id = :turma_id";
        
        $stmt_vagas = $db->prepare($query_vagas);
        $stmt_vagas->bindParam(":turma_id", $turma_id);
        $stmt_vagas->bindParam(":ano_letivo", $ano_letivo);
        $stmt_vagas->bindParam(":semestre", $semestre);
        $stmt_vagas->execute();
        
        $vagas = $stmt_vagas->fetch(PDO::FETCH_ASSOC);
        
        if ($vagas['matriculados'] >= $vagas['capacidade']) {
            $_SESSION['mensagem'] = "Não há vagas disponíveis nesta turma!";
            $_SESSION['tipo_mensagem'] = "erro";
        } else {
            // RN02 - Só é permitida matrícula se houver vaga e propina paga
            if (!$propina_paga) {
                $_SESSION['mensagem'] = "A matrícula só pode ser realizada se a propina estiver paga!";
                $_SESSION['tipo_mensagem'] = "erro";
            } else {
                $query = "INSERT INTO matriculas 
                          SET aluno_id = :aluno_id, turma_id = :turma_id, ano_letivo = :ano_letivo,
                              semestre = :semestre, valor_propina = :valor_propina, 
                              propina_paga = :propina_paga, status = 'Ativa'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":aluno_id", $aluno_id);
                $stmt->bindParam(":turma_id", $turma_id);
                $stmt->bindParam(":ano_letivo", $ano_letivo);
                $stmt->bindParam(":semestre", $semestre);
                $stmt->bindParam(":valor_propina", $valor_propina);
                $stmt->bindParam(":propina_paga", $propina_paga);
                if ($stmt->execute()) {
                    $matricula_id = $db->lastInsertId();
                    registrarAuditoria('Criação', 'matriculas', $matricula_id, null, json_encode($_POST));
                    $_SESSION['mensagem'] = "Matrícula cadastrada com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                    header("Location: index.php");
                    exit();
                } else {
                    $_SESSION['mensagem'] = "Erro ao cadastrar matrícula!";
                    $_SESSION['tipo_mensagem'] = "erro";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Matrícula - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/matriculas.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Filtro de turmas por curso
            $('#filtro_curso').change(function() {
                const cursoId = $(this).val();
                if (cursoId) {
                    window.location.href = 'criar.php?curso_id=' + cursoId;
                } else {
                    window.location.href = 'criar.php';
                }
            });
        });
    </script>
</head>
<body>
    <div class="matriculas-container">
        <div class="matriculas-header">
            <h1 class="matriculas-title">
                <i class="fas fa-user-plus"></i> Nova Matrícula
            </h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] === 'sucesso' ? 'success' : ($_SESSION['tipo_mensagem'] === 'aviso' ? 'warning' : 'error') ?>">
                <i class="fas <?= $_SESSION['tipo_mensagem'] === 'sucesso' ? 'fa-check-circle' : ($_SESSION['tipo_mensagem'] === 'aviso' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle') ?>"></i>
                <?= $_SESSION['mensagem'] ?>
                <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="filtro-container">
            <label for="filtro_curso">Filtrar por Curso:</label>
            <select id="filtro_curso" name="filtro_curso">
                <option value="">Todos os Cursos</option>
                <?php 
                $stmt_cursos->execute();
                while ($curso = $stmt_cursos->fetch(PDO::FETCH_ASSOC)): ?>
                    <option value="<?= $curso['id'] ?>" <?= $curso_filtro == $curso['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($curso['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <form method="post" class="matriculas-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="aluno_id">Aluno *</label>
                    <select id="aluno_id" name="aluno_id" required>
                        <option value="">Selecione um aluno...</option>
                        <?php while ($aluno = $stmt_alunos->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $aluno['id'] ?>" <?= isset($_POST['aluno_id']) && $_POST['aluno_id'] == $aluno['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($aluno['nome_completo']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="turma_id">Turma *</label>
                    <select id="turma_id" name="turma_id" required>
                        <option value="">Selecione uma turma...</option>
                        <?php 
                        $stmt_turmas->execute();
                        while ($turma = $stmt_turmas->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $turma['id'] ?>" <?= isset($_POST['turma_id']) && $_POST['turma_id'] == $turma['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($turma['nome']) ?> (<?= htmlspecialchars($turma['curso']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="ano_letivo">Ano Letivo *</label>
                    <input type="number" id="ano_letivo" name="ano_letivo" min="2000" max="2100" 
                           value="<?= isset($_POST['ano_letivo']) ? $_POST['ano_letivo'] : date('Y') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="semestre">Semestre *</label>
                    <select id="semestre" name="semestre" required>
                        <option value="1" <?= isset($_POST['semestre']) && $_POST['semestre'] == 1 ? 'selected' : '' ?>>1º Semestre</option>
                        <option value="2" <?= isset($_POST['semestre']) && $_POST['semestre'] == 2 ? 'selected' : '' ?>>2º Semestre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="valor_propina">Valor da Propina (Kz) *</label>
                    <input type="number" id="valor_propina" name="valor_propina" step="0.01" min="0" 
                           value="<?= isset($_POST['valor_propina']) ? $_POST['valor_propina'] : '' ?>" required>
                </div>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="propina_paga" name="propina_paga" <?= isset($_POST['propina_paga']) ? 'checked' : '' ?>>
                <label for="propina_paga">Propina Paga</label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Matrícula
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