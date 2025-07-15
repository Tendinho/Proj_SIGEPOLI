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

// Buscar avaliação
$query = "SELECT a.*, al.nome_completo as aluno_nome, 
                 d.nome as disciplina_nome, t.nome as turma_nome,
                 c.nome as curso_nome
          FROM avaliacoes a
          JOIN alunos al ON a.aluno_id = al.id
          JOIN disciplinas d ON a.disciplina_id = d.id
          JOIN turmas t ON a.turma_id = t.id
          JOIN cursos c ON t.curso_id = c.id
          WHERE a.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$avaliacao) {
    $_SESSION['mensagem'] = "Avaliação não encontrada!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

// Verificar se o professor pode editar esta avaliação
$usuario_id = $_SESSION['usuario_id'];
$query_verifica = "SELECT COUNT(*) as total 
                   FROM avaliacoes a
                   JOIN professores p ON a.professor_id = p.id
                   JOIN funcionarios f ON p.funcionario_id = f.id
                   WHERE a.id = :id AND f.usuario_id = :usuario_id";
$stmt_verifica = $db->prepare($query_verifica);
$stmt_verifica->bindParam(":id", $id);
$stmt_verifica->bindParam(":usuario_id", $usuario_id);
$stmt_verifica->execute();
$resultado = $stmt_verifica->fetch(PDO::FETCH_ASSOC);

if ($resultado['total'] == 0 && $_SESSION['nivel_acesso'] < 7) {
    $_SESSION['mensagem'] = "Você não tem permissão para editar esta avaliação!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo_avaliacao = filter_input(INPUT_POST, 'tipo_avaliacao', FILTER_SANITIZE_STRING);
    $nota = filter_input(INPUT_POST, 'nota', FILTER_VALIDATE_FLOAT);
    $data_avaliacao = filter_input(INPUT_POST, 'data_avaliacao', FILTER_SANITIZE_STRING);
    $observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING);
    
    // RN03 - Notas devem estar entre 0-20
    if ($nota < 0 || $nota > 20) {
        $_SESSION['mensagem'] = "A nota deve estar entre 0 e 20!";
        $_SESSION['tipo_mensagem'] = "erro";
    } else {
        $dados_anteriores = json_encode($avaliacao);
        
        $query = "UPDATE avaliacoes 
                  SET tipo_avaliacao = :tipo_avaliacao, nota = :nota,
                      data_avaliacao = :data_avaliacao, observacoes = :observacoes
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":tipo_avaliacao", $tipo_avaliacao);
        $stmt->bindParam(":nota", $nota);
        $stmt->bindParam(":data_avaliacao", $data_avaliacao);
        $stmt->bindParam(":observacoes", $observacoes);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            registrarAuditoria('ATUALIZACAO_AVALIACAO', 'avaliacoes', $id, $dados_anteriores, json_encode([
                'tipo_avaliacao' => $tipo_avaliacao,
                'nota' => $nota,
                'data_avaliacao' => $data_avaliacao,
                'observacoes' => $observacoes
            ]));
            
            $_SESSION['mensagem'] = "Avaliação atualizada com sucesso!";
            $_SESSION['tipo_mensagem'] = "sucesso";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['mensagem'] = "Erro ao atualizar avaliação!";
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
    <title>Editar Avaliação - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .form-container {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            min-height: 100px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .info-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="content-header">
            <h1><i class="fas fa-edit"></i> Editar Avaliação</h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['tipo_mensagem']); ?>">
                <?php echo htmlspecialchars($_SESSION['mensagem']); 
                unset($_SESSION['mensagem']); 
                unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" action="editar.php?id=<?php echo $id; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Aluno:</label>
                        <input type="text" value="<?php echo htmlspecialchars($avaliacao['aluno_nome']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Disciplina:</label>
                        <input type="text" value="<?php echo htmlspecialchars($avaliacao['disciplina_nome']); ?>" readonly>
                        <p class="info-text">Curso: <?php echo htmlspecialchars($avaliacao['curso_nome']); ?></p>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Turma:</label>
                        <input type="text" value="<?php echo htmlspecialchars($avaliacao['turma_nome']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_avaliacao">Tipo de Avaliação *</label>
                        <select id="tipo_avaliacao" name="tipo_avaliacao" required>
                            <option value="Teste" <?php echo $avaliacao['tipo_avaliacao'] == 'Teste' ? 'selected' : ''; ?>>Teste</option>
                            <option value="Exame" <?php echo $avaliacao['tipo_avaliacao'] == 'Exame' ? 'selected' : ''; ?>>Exame</option>
                            <option value="Trabalho" <?php echo $avaliacao['tipo_avaliacao'] == 'Trabalho' ? 'selected' : ''; ?>>Trabalho</option>
                            <option value="Projeto" <?php echo $avaliacao['tipo_avaliacao'] == 'Projeto' ? 'selected' : ''; ?>>Projeto</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nota">Nota (0-20) *</label>
                        <input type="number" id="nota" name="nota" min="0" max="20" step="0.1" 
                               value="<?php echo htmlspecialchars($avaliacao['nota']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_avaliacao">Data da Avaliação *</label>
                        <input type="date" id="data_avaliacao" name="data_avaliacao" 
                               value="<?php echo htmlspecialchars($avaliacao['data_avaliacao']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"><?php 
                        echo htmlspecialchars($avaliacao['observacoes']); 
                    ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>