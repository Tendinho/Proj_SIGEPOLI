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

// Buscar matrícula
$query = "SELECT * FROM matriculas WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$matricula = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$matricula) {
    $_SESSION['mensagem'] = "Matrícula não encontrada!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

// Buscar aluno da matrícula
$query_aluno = "SELECT id, nome_completo FROM alunos WHERE id = :aluno_id";
$stmt_aluno = $db->prepare($query_aluno);
$stmt_aluno->bindParam(":aluno_id", $matricula['aluno_id']);
$stmt_aluno->execute();
$aluno = $stmt_aluno->fetch(PDO::FETCH_ASSOC);

// Buscar turma da matrícula
$query_turma = "SELECT t.id, t.nome, c.nome as curso 
                FROM turmas t
                JOIN cursos c ON t.curso_id = c.id
                WHERE t.id = :turma_id";
$stmt_turma = $db->prepare($query_turma);
$stmt_turma->bindParam(":turma_id", $matricula['turma_id']);
$stmt_turma->execute();
$turma = $stmt_turma->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $valor_propina = $_POST['valor_propina'];
    $propina_paga = isset($_POST['propina_paga']) ? 1 : 0;
    $status = $_POST['status'];
    $data_pagamento = $propina_paga ? date('Y-m-d') : null;
    
    $dados_anteriores = json_encode($matricula);
    
    $query = "UPDATE matriculas 
              SET valor_propina = :valor_propina, propina_paga = :propina_paga,
                  data_pagamento = :data_pagamento, status = :status
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":valor_propina", $valor_propina);
    $stmt->bindParam(":propina_paga", $propina_paga);
    $stmt->bindParam(":data_pagamento", $data_pagamento);
    $stmt->bindParam(":status", $status);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        registrarAuditoria('Atualização', 'matriculas', $id, $dados_anteriores, json_encode($_POST));
        
        $_SESSION['mensagem'] = "Matrícula atualizada com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar matrícula!";
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Matrícula - SIGEPOLI</title>
    <link rel="stylesheet" href="../../Context/CSS/style.css">
    <link rel="stylesheet" href="../../Context/CSS/fontawesome/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-edit"></i> Editar Matrícula</h1>
        
        <form method="post" action="editar.php?id=<?php echo $id; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Aluno:</label>
                    <input type="text" value="<?php echo $aluno['nome_completo']; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Turma:</label>
                    <input type="text" value="<?php echo $turma['nome']; ?> (<?php echo $turma['curso']; ?>)" readonly>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Ano Letivo:</label>
                    <input type="text" value="<?php echo $matricula['ano_letivo']; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Semestre:</label>
                    <input type="text" value="<?php echo $matricula['semestre']; ?>º" readonly>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="valor_propina">Valor da Propina (Kz):</label>
                    <input type="number" id="valor_propina" name="valor_propina" step="0.01" min="0" value="<?php echo $matricula['valor_propina']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Ativa" <?php echo $matricula['status'] == 'Ativa' ? 'selected' : ''; ?>>Ativa</option>
                        <option value="Trancada" <?php echo $matricula['status'] == 'Trancada' ? 'selected' : ''; ?>>Trancada</option>
                        <option value="Cancelada" <?php echo $matricula['status'] == 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="propina_paga" <?php echo $matricula['propina_paga'] ? 'checked' : ''; ?>>
                    <span class="checkmark"></span>
                    Propina Paga
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                <a href="index.php" class="btn btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
    
    <script src="../../Context/JS/script.js"></script>
</body>
</html>