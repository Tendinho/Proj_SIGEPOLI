<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso para coordenadores/professores

$database = new Database();
$db = $database->getConnection();

// Verificar se ID foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem'] = "ID da disciplina inválido";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

try {
    // Verificar se disciplina existe
    $stmt = $db->prepare("SELECT nome, codigo FROM disciplinas WHERE id = ?");
    $stmt->execute([$id]);
    $disciplina = $stmt->fetch();

    if (!$disciplina) {
        $_SESSION['mensagem'] = "Disciplina não encontrada";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }

    // Verificar se disciplina está vinculada a aulas
    $stmt = $db->prepare("SELECT COUNT(*) FROM aulas WHERE disciplina_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['mensagem'] = "Não é possível excluir - disciplina está vinculada a aulas";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }

    // Verificar se disciplina está vinculada a avaliações
    $stmt = $db->prepare("SELECT COUNT(*) FROM avaliacoes WHERE disciplina_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['mensagem'] = "Não é possível excluir - disciplina está vinculada a avaliações";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }

    // Excluir disciplina
    $stmt = $db->prepare("DELETE FROM disciplinas WHERE id = ?");
    $stmt->execute([$id]);

    registrarAuditoria('Excluiu disciplina', 'disciplinas', $id, "Nome: {$disciplina['nome']}, Código: {$disciplina['codigo']}");

    $_SESSION['mensagem'] = "Disciplina excluída com sucesso!";
    $_SESSION['tipo_mensagem'] = "sucesso";

} catch (Exception $e) {
    $_SESSION['mensagem'] = "Erro ao excluir disciplina: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "erro";
}

header("Location: index.php");
exit();
?>