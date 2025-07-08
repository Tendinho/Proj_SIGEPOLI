<?php
require_once __DIR__ . '/../config.php';
verificarLogin();
verificarAcesso(6); // Nível de acesso para coordenadores

$database = new Database();
$db = $database->getConnection();

// Consulta cursos e coordenadores
$query = "SELECT c.id AS curso_id, c.nome AS curso, 
                 p.id AS prof_id, f.nome_completo AS coordenador
          FROM cursos c
          LEFT JOIN professores p ON c.coordenador_id = p.id
          LEFT JOIN funcionarios f ON p.funcionario_id = f.id
          WHERE c.ativo = 1
          ORDER BY c.nome";
$stmt = $db->prepare($query);
$stmt->execute();
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar professores disponíveis
$professores = $db->query("SELECT p.id, f.nome_completo 
                          FROM professores p
                          JOIN funcionarios f ON p.funcionario_id = f.id
                          WHERE p.ativo = 1
                          ORDER BY f.nome_completo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Coordenadores - <?= SISTEMA_NOME ?></title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">

    <style>
               .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-graduate"></i> Coordenadores de Curso</h1>
            <a href="/PHP/index.php" class="btn btn-back">Voltar ao Menu</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Coordenador</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cursos)): ?>
                    <tr><td colspan="3">Nenhum curso encontrado</td></tr>
                <?php else: ?>
                    <?php foreach ($cursos as $curso): ?>
                        <tr>
                            <td><?= htmlspecialchars($curso['curso']) ?></td>
                            <td><?= htmlspecialchars($curso['coordenador'] ?? 'Não designado') ?></td>
                            <td>
                                <button onclick="designarCoordenador(<?= $curso['curso_id'] ?>, '<?= htmlspecialchars($curso['curso']) ?>')" 
                                        class="btn btn-primary btn-sm">
                                    Designar
                                </button>
                                <?php if ($curso['coordenador']): ?>
                                    <a href="desvincular.php?curso_id=<?= $curso['curso_id'] ?>" 
                                       class="btn btn-secondary btn-sm" 
                                       onclick="return confirm('Remover coordenador?')">
                                        Remover
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Modal para designação -->
        <div id="modalDesignar" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; z-index:1000; box-shadow:0 0 10px rgba(0,0,0,0.3);">
            <h3>Designar Coordenador</h3>
            <form method="post" action="designar.php">
                <input type="hidden" name="curso_id" id="modalCursoId">
                <p id="modalCursoNome"></p>
                
                <div class="form-group">
                    <label for="professor_id">Professor:</label>
                    <select name="professor_id" id="professor_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($professores as $prof): ?>
                            <option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['nome_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Designar</button>
                <button type="button" onclick="document.getElementById('modalDesignar').style.display='none'" class="btn btn-secondary">Cancelar</button>
            </form>
        </div>

        <script>
            function designarCoordenador(cursoId, cursoNome) {
                document.getElementById('modalCursoId').value = cursoId;
                document.getElementById('modalCursoNome').textContent = 'Curso: ' + cursoNome;
                document.getElementById('modalDesignar').style.display = 'block';
            }
        </script>
    </div>
</body>
</html>