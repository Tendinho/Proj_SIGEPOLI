<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5);

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];

// Buscar disciplinas que o professor leciona
$query_disciplinas = "SELECT d.id, d.nome 
                      FROM disciplinas d
                      JOIN aulas a ON d.id = a.disciplina_id
                      JOIN professores p ON a.professor_id = p.id
                      JOIN funcionarios f ON p.funcionario_id = f.id
                      WHERE f.usuario_id = $usuario_id
                      ORDER BY d.nome";
$stmt_disciplinas = $db->prepare($query_disciplinas);
$stmt_disciplinas->execute();

// Buscar turmas que o professor leciona
$query_turmas = "SELECT DISTINCT t.id, t.nome 
                 FROM turmas t
                 JOIN aulas a ON t.id = a.turma_id
                 JOIN professores p ON a.professor_id = p.id
                 JOIN funcionarios f ON p.funcionario_id = f.id
                 WHERE f.usuario_id = $usuario_id
                 ORDER BY t.nome";
$stmt_turmas = $db->prepare($query_turmas);
$stmt_turmas->execute();

// Buscar professor logado
$query_professor = "SELECT p.id 
                    FROM professores p
                    JOIN funcionarios f ON p.funcionario_id = f.id
                    WHERE f.usuario_id = $usuario_id";
$stmt_professor = $db->prepare($query_professor);
$stmt_professor->execute();
$professor = $stmt_professor->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aluno_id = $_POST['aluno_id'];
    $disciplina_id = $_POST['disciplina_id'];
    $turma_id = $_POST['turma_id'];
    $tipo_avaliacao = $_POST['tipo_avaliacao'];
    $nota = $_POST['nota'];
    $data_avaliacao = $_POST['data_avaliacao'];
    $observacoes = $_POST['observacoes'];
    $professor_id = $professor['id'];
    
    // RN03 - Notas devem estar entre 0-20
    if ($nota < 0 || $nota > 20) {
        $_SESSION['mensagem'] = "A nota deve estar entre 0 e 20!";
        $_SESSION['tipo_mensagem'] = "erro";
    } else {
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
        $stmt->bindParam(":professor_id", $professor_id);
        
        if ($stmt->execute()) {
            $avaliacao_id = $db->lastInsertId();
            registrarAuditoria('Criação', 'avaliacoes', $avaliacao_id, null, json_encode($_POST));
            
            $_SESSION['mensagem'] = "Avaliação cadastrada com sucesso!";
            $_SESSION['tipo_mensagem'] = "sucesso";
            header("Location: index.php");
            exit();
        } else {
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
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-clipboard-check"></i> Cadastrar Nova Avaliação</h1>
        
        <form method="post" action="criar.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="disciplina_id">Disciplina:</label>
                    <select id="disciplina_id" name="disciplina_id" required>
                        <option value="">Selecione...</option>
                        <?php 
                        $stmt_disciplinas->execute();
                        while ($disciplina = $stmt_disciplinas->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $disciplina['id']; ?>">
                                <?php echo $disciplina['nome']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="turma_id">Turma:</label>
                    <select id="turma_id" name="turma_id" required>
                        <option value="">Selecione...</option>
                        <?php 
                        $stmt_turmas->execute();
                        while ($turma = $stmt_turmas->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $turma['id']; ?>">
                                <?php echo $turma['nome']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="aluno_id">Aluno:</label>
                    <select id="aluno_id" name="aluno_id" required>
                        <option value="">Selecione a disciplina e turma primeiro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tipo_avaliacao">Tipo de Avaliação:</label>
                    <select id="tipo_avaliacao" name="tipo_avaliacao" required>
                        <option value="Teste">Teste</option>
                        <option value="Exame">Exame</option>
                        <option value="Trabalho">Trabalho</option>
                        <option value="Projeto">Projeto</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nota">Nota (0-20):</label>
                    <input type="number" id="nota" name="nota" min="0" max="20" step="0.1" required>
                </div>
                
                <div class="form-group">
                    <label for="data_avaliacao">Data da Avaliação:</label>
                    <input type="date" id="data_avaliacao" name="data_avaliacao" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações:</label>
                <textarea id="observacoes" name="observacoes" rows="3"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                <a href="index.php" class="btn btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
    <script>
        // Carregar alunos dinamicamente baseado na disciplina e turma selecionadas
        document.getElementById('disciplina_id').addEventListener('change', carregarAlunos);
        document.getElementById('turma_id').addEventListener('change', carregarAlunos);
        
        function carregarAlunos() {
            const disciplinaId = document.getElementById('disciplina_id').value;
            const turmaId = document.getElementById('turma_id').value;
            const alunoSelect = document.getElementById('aluno_id');
            
            if (disciplinaId && turmaId) {
                fetch(`../../api/alunos_matriculados.php?disciplina_id=${disciplinaId}&turma_id=${turmaId}`)
                    .then(response => response.json())
                    .then(data => {
                        alunoSelect.innerHTML = '<option value="">Selecione...</option>';
                        data.forEach(aluno => {
                            const option = document.createElement('option');
                            option.value = aluno.id;
                            option.textContent = aluno.nome_completo;
                            alunoSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Erro ao carregar alunos:', error));
            }
        }
    </script>
</body>
</html>