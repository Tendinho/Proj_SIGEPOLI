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

// Buscar professor antes de excluir para auditoria
$query = "SELECT p.*, f.* 
          FROM professores p
          JOIN funcionarios f ON p.funcionario_id = f.id
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();
$professor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($professor) {
    // Verificar se o professor está vinculado a alguma aula
    $query_aulas = "SELECT COUNT(*) as total FROM aulas WHERE professor_id = :professor_id";
    $stmt_aulas = $db->prepare($query_aulas);
    $stmt_aulas->bindParam(":professor_id", $id);
    $stmt_aulas->execute();
    $aulas = $stmt_aulas->fetch(PDO::FETCH_ASSOC);
    
    if ($aulas['total'] > 0) {
        $_SESSION['mensagem'] = "Não é possível excluir o professor pois existem aulas vinculadas a ele!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
    
    // Verificar se o professor é coordenador de algum curso
    $query_cursos = "SELECT COUNT(*) as total FROM cursos WHERE coordenador_id = :professor_id";
    $stmt_cursos = $db->prepare($query_cursos);
    $stmt_cursos->bindParam(":professor_id", $id);
    $stmt_cursos->execute();
    $cursos = $stmt_cursos->fetch(PDO::FETCH_ASSOC);
    
    if ($cursos['total'] > 0) {
        $_SESSION['mensagem'] = "Não é possível excluir o professor pois ele é coordenador de curso(s)!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
    
    try {
        $db->beginTransaction();
        
        // Marcar professor como inativo
        $query_professor = "UPDATE professores SET ativo = 0 WHERE id = :id";
        $stmt_professor = $db->prepare($query_professor);
        $stmt_professor->bindParam(":id", $id);
        $stmt_professor->execute();
        
        // Marcar funcionário como inativo
        $query_funcionario = "UPDATE funcionarios SET ativo = 0 WHERE id = :funcionario_id";
        $stmt_funcionario = $db->prepare($query_funcionario);
        $stmt_funcionario->bindParam(":funcionario_id", $professor['funcionario_id']);
        $stmt_funcionario->execute();
        
        $db->commit();
        
        registrarAuditoria('Exclusão', 'professores', $id, json_encode($professor), null);
        
        $_SESSION['mensagem'] = "Professor marcado como inativo com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = "Erro ao marcar professor como inativo: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
} else {
    $_SESSION['mensagem'] = "Professor não encontrado!";
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>