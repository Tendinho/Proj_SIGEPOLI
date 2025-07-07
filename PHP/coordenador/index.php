<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(4); // Nível de acesso para coordenação

$database = new Database();
$db = $database->getConnection();

// Processar designação de novo coordenador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['curso_id'], $_POST['professor_id'])) {
    try {
        $stmt = $db->prepare("UPDATE cursos SET coordenador_id = :prof_id WHERE id = :curso_id");
        $stmt->bindParam(':prof_id', $_POST['professor_id']);
        $stmt->bindParam(':curso_id', $_POST['curso_id']);
        $stmt->execute();
        
        $_SESSION['mensagem'] = "Coordenador designado com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = "Erro ao designar coordenador: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
}

// Buscar cursos e seus coordenadores atuais
$cursos = $db->query("
    SELECT c.id AS curso_id, c.nome AS curso, c.coordenador_id,
           p.id AS prof_id, f.nome_completo AS coordenador
    FROM cursos c
    LEFT JOIN professores p ON c.coordenador_id = p.id
    LEFT JOIN funcionarios f ON p.funcionario_id = f.id
    WHERE c.ativo = 1
    ORDER BY c.nome
")->fetchAll(PDO::FETCH_ASSOC);

// Buscar professores disponíveis para designação
$professores = $db->query("
    SELECT p.id, f.nome_completo, d.nome AS departamento
    FROM professores p
    JOIN funcionarios f ON p.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE p.ativo = 1
    ORDER BY f.nome_completo
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Designação de Coordenadores - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="content-header">
        <h1><i class="fas fa-user-graduate"></i> Designação de Coordenadores</h1>
    </div>

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
            <?= $_SESSION['mensagem'] ?>
            <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Cursos e Seus Coordenadores</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Coordenador Atual</th>
                            <th>Departamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <td><?= htmlspecialchars($curso['curso']) ?></td>
                                <td>
                                    <?php if ($curso['coordenador']): ?>
                                        <?= htmlspecialchars($curso['coordenador']) ?>
                                    <?php else: ?>
                                        <span class="text-danger">Não designado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $depto = '';
                                    foreach ($professores as $prof) {
                                        if ($prof['id'] == $curso['coordenador_id']) {
                                            $depto = $prof['departamento'] ?? 'Não informado';
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($depto);
                                    ?>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" 
                                            data-target="#modalDesignar" data-curso-id="<?= $curso['curso_id'] ?>" 
                                            data-curso-nome="<?= htmlspecialchars($curso['curso']) ?>">
                                        <i class="fas fa-user-edit"></i> Designar
                                    </button>
                                    <?php if ($curso['coordenador_id']): ?>
                                        <a href="desvincular.php?curso_id=<?= $curso['curso_id'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Remover o coordenador deste curso?')">
                                            <i class="fas fa-user-minus"></i> Remover
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para designação -->
    <div class="modal" id="modalDesignar" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Designar Coordenador</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="curso_id" id="modalCursoId">
                        <p>Curso: <strong id="modalCursoNome"></strong></p>
                        
                        <div class="form-group">
                            <label for="professor_id">Selecione o Professor:</label>
                            <select class="form-control" name="professor_id" id="professor_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($professores as $prof): ?>
                                    <option value="<?= $prof['id'] ?>">
                                        <?= htmlspecialchars($prof['nome_completo']) ?> 
                                        (<?= htmlspecialchars($prof['departamento'] ?? 'Sem departamento') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Designar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/Context/JS/script.js"></script>
    <script>
        // Configurar modal quando aberto
        $('#modalDesignar').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var cursoId = button.data('curso-id');
            var cursoNome = button.data('curso-nome');
            
            var modal = $(this);
            modal.find('#modalCursoId').val(cursoId);
            modal.find('#modalCursoNome').text(cursoNome);
            modal.find('#professor_id').val('');
        });
    </script>
</body>
</html>