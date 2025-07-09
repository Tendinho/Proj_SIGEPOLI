<?php
// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'sigepoli';
$username = 'admin';
$password = 'SenhaSegura123!';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /PHP/login.php");
    exit();
}

// Verificar nível de acesso (6 para coordenadores)
if ($_SESSION['nivel_acesso'] < 6) {
    $_SESSION['mensagem'] = "Acesso negado. Permissões insuficientes.";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: /PHP/index.php");
    exit();
}

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

// Processar desvinculação se necessário
if (isset($_GET['desvincular']) && isset($_GET['curso_id'])) {
    $curso_id = $_GET['curso_id'];
    
    try {
        $db->beginTransaction();
        
        // Remover coordenador do curso
        $stmt = $db->prepare("UPDATE cursos SET coordenador_id = NULL WHERE id = :curso_id");
        $stmt->bindParam(":curso_id", $curso_id);
        $stmt->execute();
        
        // Registrar na auditoria
        $auditoria = $db->prepare("INSERT INTO auditoria 
                                 (usuario_id, acao, tabela_afetada, registro_id, dados_novos, data_hora) 
                                 VALUES (:usuario_id, :acao, 'cursos', :registro_id, :dados_novos, NOW())");
        $acao = "Remoção de Coordenador";
        $detalhes = "Curso ID: $curso_id - Coordenador removido";
        $auditoria->bindParam(":usuario_id", $_SESSION['usuario_id']);
        $auditoria->bindParam(":acao", $acao);
        $auditoria->bindParam(":registro_id", $curso_id);
        $auditoria->bindParam(":dados_novos", $detalhes);
        $auditoria->execute();
        
        $db->commit();
        
        $_SESSION['mensagem'] = "Coordenador removido com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = "Erro ao remover coordenador: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordenadores - SIGEPOLI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-back {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        #modalDesignar {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            z-index: 1000;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            width: 400px;
            max-width: 90%;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👔 Coordenadores de Curso</h1>
            <a href="/PHP/index.php" class="btn btn-back">← Voltar ao Menu</a>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] === 'sucesso' ? 'success' : 'error' ?>">
                <?= $_SESSION['mensagem'] ?>
                <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
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
                                <td class="actions">
                                    <button onclick="designarCoordenador(<?= $curso['curso_id'] ?>, '<?= htmlspecialchars($curso['curso']) ?>')" 
                                            class="btn btn-primary btn-sm">
                                        Designar
                                    </button>
                                    <?php if ($curso['coordenador']): ?>
                                        <a href="?desvincular=1&curso_id=<?= $curso['curso_id'] ?>" 
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
        </div>

        <!-- Modal para designação -->
        <div id="modalDesignar">
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
                <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
            </form>
        </div>
        <div id="modalBackdrop" class="modal-backdrop" onclick="fecharModal()"></div>

        <script>
            function designarCoordenador(cursoId, cursoNome) {
                document.getElementById('modalCursoId').value = cursoId;
                document.getElementById('modalCursoNome').textContent = 'Curso: ' + cursoNome;
                document.getElementById('modalDesignar').style.display = 'block';
                document.getElementById('modalBackdrop').style.display = 'block';
            }

            function fecharModal() {
                document.getElementById('modalDesignar').style.display = 'none';
                document.getElementById('modalBackdrop').style.display = 'none';
            }
        </script>
    </div>
</body>
</html>