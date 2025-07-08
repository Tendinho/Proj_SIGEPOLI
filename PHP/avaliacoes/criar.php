<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];

// Buscar professor logado
$query_professor = "SELECT p.id 
                    FROM professores p
                    JOIN funcionarios f ON p.funcionario_id = f.id
                    WHERE f.usuario_id = :usuario_id";
$stmt_professor = $db->prepare($query_professor);
$stmt_professor->bindParam(":usuario_id", $usuario_id);
$stmt_professor->execute();
$professor = $stmt_professor->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    $_SESSION['mensagem'] = "Professor não encontrado!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

// Buscar disciplinas que o professor leciona
$query_disciplinas = "SELECT DISTINCT d.id, d.nome 
                      FROM disciplinas d
                      JOIN aulas a ON d.id = a.disciplina_id
                      WHERE a.professor_id = :professor_id
                      AND d.ativo = 1
                      ORDER BY d.nome";
$stmt_disciplinas = $db->prepare($query_disciplinas);
$stmt_disciplinas->bindParam(":professor_id", $professor['id']);
$stmt_disciplinas->execute();
$disciplinas = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);

// Buscar turmas que o professor leciona
$query_turmas = "SELECT DISTINCT t.id, t.nome 
                 FROM turmas t
                 JOIN aulas a ON t.id = a.turma_id
                 WHERE a.professor_id = :professor_id
                 AND t.ativo = 1
                 ORDER BY t.nome";
$stmt_turmas = $db->prepare($query_turmas);
$stmt_turmas->bindParam(":professor_id", $professor['id']);
$stmt_turmas->execute();
$turmas = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validação dos dados
    $aluno_id = filter_input(INPUT_POST, 'aluno_id', FILTER_VALIDATE_INT);
    $disciplina_id = filter_input(INPUT_POST, 'disciplina_id', FILTER_VALIDATE_INT);
    $turma_id = filter_input(INPUT_POST, 'turma_id', FILTER_VALIDATE_INT);
    $tipo_avaliacao = filter_input(INPUT_POST, 'tipo_avaliacao', FILTER_SANITIZE_STRING);
    $nota = filter_input(INPUT_POST, 'nota', FILTER_VALIDATE_FLOAT);
    $data_avaliacao = filter_input(INPUT_POST, 'data_avaliacao', FILTER_SANITIZE_STRING);
    $observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING);
    
    // Verificar se todos os campos obrigatórios estão preenchidos
    if (!$aluno_id || !$disciplina_id || !$turma_id || !$tipo_avaliacao || $nota === false || !$data_avaliacao) {
        $_SESSION['mensagem'] = "Por favor, preencha todos os campos obrigatórios!";
        $_SESSION['tipo_mensagem'] = "erro";
    } 
    // RN03 - Notas devem estar entre 0-20
    elseif ($nota < 0 || $nota > 20) {
        $_SESSION['mensagem'] = "A nota deve estar entre 0 e 20!";
        $_SESSION['tipo_mensagem'] = "erro";
    } else {
        try {
            $query = "INSERT INTO avaliacoes 
                      SET aluno_id = :aluno_id, disciplina_id = :disciplina_id,
                          turma_id = :turma_id, tipo_avaliacao = :tipo_avaliacao,
                          nota = :nota, data_avaliacao = :data_avaliacao,
                          observacoes = :observacoes, professor_id = :professor_id";
            
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(":aluno_id", $aluno_id);
            $stmt->bindParam(":disciplina_id", $disciplina_id);
            $stmt->bindParam(":turma_id", $turma_id);
            $stmt->bindParam(":tipo_avaliacao", $tipo_avaliacao);
            $stmt->bindParam(":nota", $nota);
            $stmt->bindParam(":data_avaliacao", $data_avaliacao);
            $stmt->bindParam(":observacoes", $observacoes);
            $stmt->bindParam(":professor_id", $professor['id']);
            
            if ($stmt->execute()) {
                $avaliacao_id = $db->lastInsertId();
                registrarAuditoria('Criação de avaliação', 'avaliacoes', $avaliacao_id, json_encode($_POST));
                
                $_SESSION['mensagem'] = "Avaliação cadastrada com sucesso!";
                $_SESSION['tipo_mensagem'] = "sucesso";
                header("Location: index.php");
                exit();
            } else {
                throw new PDOException("Erro ao executar a query");
            }
        } catch (PDOException $e) {
            error_log("Erro ao cadastrar avaliação: " . $e->getMessage());
            $_SESSION['mensagem'] = "Erro ao cadastrar avaliação!";
            $_SESSION['tipo_mensagem'] = "erro";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Avaliação - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    
    <div class="content">
        <h1><i class="fas fa-clipboard-check"></i> Cadastrar Nova Avaliação</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['tipo_mensagem']); ?>">
                <?php echo htmlspecialchars($_SESSION['mensagem']); 
                unset($_SESSION['mensagem']); 
                unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="criar.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="disciplina_id">Disciplina:</label>
                    <select id="disciplina_id" name="disciplina_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($disciplinas as $disciplina): ?>
                            <option value="<?php echo htmlspecialchars($disciplina['id']); ?>" 
                                <?php echo (isset($_POST['disciplina_id']) && $_POST['disciplina_id'] == $disciplina['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($disciplina['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="turma_id">Turma:</label>
                    <select id="turma_id" name="turma_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($turmas as $turma): ?>
                            <option value="<?php echo htmlspecialchars($turma['id']); ?>"
                                <?php echo (isset($_POST['turma_id']) && $_POST['turma_id'] == $turma['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($turma['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="aluno_id">Aluno:</label>
                    <select id="aluno_id" name="aluno_id" required>
                        <option value="">Selecione a disciplina e turma primeiro</option>
                        <?php if (isset($_POST['aluno_id']) && isset($_POST['turma_id'])): ?>
                            <?php 
                            // Carregar aluno selecionado se houver POST
                            $query_aluno = "SELECT a.id, a.nome_completo 
                                          FROM alunos a
                                          JOIN matriculas m ON a.id = m.aluno_id
                                          WHERE a.id = :aluno_id
                                          AND m.turma_id = :turma_id
                                          AND a.ativo = 1
                                          AND m.status = 'Ativa'";
                            $stmt_aluno = $db->prepare($query_aluno);
                            $stmt_aluno->bindParam(":aluno_id", $_POST['aluno_id']);
                            $stmt_aluno->bindParam(":turma_id", $_POST['turma_id']);
                            $stmt_aluno->execute();
                            $aluno_selecionado = $stmt_aluno->fetch(PDO::FETCH_ASSOC);
                            if ($aluno_selecionado): ?>
                                <option value="<?php echo htmlspecialchars($aluno_selecionado['id']); ?>" selected>
                                    <?php echo htmlspecialchars($aluno_selecionado['nome_completo']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tipo_avaliacao">Tipo de Avaliação:</label>
                    <select id="tipo_avaliacao" name="tipo_avaliacao" required>
                        <option value="Teste" <?php echo (isset($_POST['tipo_avaliacao']) && $_POST['tipo_avaliacao'] == 'Teste') ? 'selected' : ''; ?>>Teste</option>
                        <option value="Exame" <?php echo (isset($_POST['tipo_avaliacao']) && $_POST['tipo_avaliacao'] == 'Exame') ? 'selected' : ''; ?>>Exame</option>
                        <option value="Trabalho" <?php echo (isset($_POST['tipo_avaliacao']) && $_POST['tipo_avaliacao'] == 'Trabalho') ? 'selected' : ''; ?>>Trabalho</option>
                        <option value="Projeto" <?php echo (isset($_POST['tipo_avaliacao']) && $_POST['tipo_avaliacao'] == 'Projeto') ? 'selected' : ''; ?>>Projeto</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nota">Nota (0-20):</label>
                    <input type="number" id="nota" name="nota" min="0" max="20" step="0.1" 
                           value="<?php echo isset($_POST['nota']) ? htmlspecialchars($_POST['nota']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="data_avaliacao">Data da Avaliação:</label>
                    <input type="date" id="data_avaliacao" name="data_avaliacao" 
                           value="<?php echo isset($_POST['data_avaliacao']) ? htmlspecialchars($_POST['data_avaliacao']) : date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações:</label>
                <textarea id="observacoes" name="observacoes" rows="3"><?php 
                    echo isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : ''; 
                ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                <a href="index.php" class="btn btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
    
    <script>
        $(document).ready(function() {
            // Carregar alunos dinamicamente baseado na turma selecionada
            $('#turma_id').change(function() {
                const turmaId = $(this).val();
                const alunoSelect = $('#aluno_id');
                
                if (turmaId) {
                    // Mostrar indicador de carregamento
                    alunoSelect.empty().append('<option value="">Carregando alunos...</option>');
                    
                    $.ajax({
                        url: '../api/alunos_matriculados.php',
                        method: 'GET',
                        data: {
                            turma_id: turmaId,
                            disciplina_id: $('#disciplina_id').val() // Envia também para compatibilidade
                        },
                        dataType: 'json',
                        success: function(data) {
                            alunoSelect.empty().append('<option value="">Selecione...</option>');
                            $.each(data, function(index, aluno) {
                                alunoSelect.append($('<option>', {
                                    value: aluno.id,
                                    text: aluno.nome_completo
                                }));
                            });
                            
                            // Selecionar aluno se veio do POST
                            <?php if (isset($_POST['aluno_id'])): ?>
                                alunoSelect.val(<?php echo json_encode($_POST['aluno_id']); ?>);
                            <?php endif; ?>
                        },
                        error: function(xhr, status, error) {
                            console.error('Erro ao carregar alunos:', error);
                            alunoSelect.empty().append('<option value="">Erro ao carregar alunos</option>');
                        }
                    });
                } else {
                    alunoSelect.empty().append('<option value="">Selecione a turma primeiro</option>');
                }
            });
            
            // Disparar o change se já houver turma selecionada
            if ($('#turma_id').val()) {
                $('#turma_id').trigger('change');
            }
        });
    </script>
</body>
</html>