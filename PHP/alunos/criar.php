<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

verificarLogin();
verificarAcesso(2);

$database = new Database();
$db = $database->getConnection();

// Buscar cursos ativos
$query_cursos = "SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute();
$cursos = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar e validar inputs
    $nome_completo = trim($_POST['nome_completo']);
    $bi = preg_replace('/[^0-9]/', '', $_POST['bi']);
    $data_nascimento = $_POST['data_nascimento'];
    $genero = $_POST['genero'];
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $endereco = trim($_POST['endereco']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $curso_id = !empty($_POST['curso_id']) ? $_POST['curso_id'] : null;
    $valor_propina = !empty($_POST['valor_propina']) ? (float)$_POST['valor_propina'] : 0;
    $propina_paga = isset($_POST['propina_paga']) ? 1 : 0;
    
    // Validações
    $erros = [];
    
    if (empty($nome_completo)) {
        $erros[] = "O nome completo é obrigatório";
    }

    if (empty($bi)) {
        $erros[] = "O número do BI é obrigatório";
    } elseif (strlen($bi) > 20) {
        $erros[] = "O BI deve ter no máximo 20 caracteres";
    } elseif (verificarBIExistente($bi)) {
        $erros[] = "Este número de BI já está cadastrado em um aluno ativo";
    }

    if (!DateTime::createFromFormat('Y-m-d', $data_nascimento)) {
        $erros[] = "Data de nascimento inválida";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido";
    }

    // Se curso for selecionado, validar propina
    if ($curso_id && $valor_propina <= 0) {
        $erros[] = "Valor da propina deve ser maior que zero quando curso é selecionado";
    }

    // Se não houver erros, tenta inserir
    if (empty($erros)) {
        try {
            // Inicia transação
            $db->beginTransaction();
            
            // Inserir aluno
            $query = "INSERT INTO alunos 
                     (nome_completo, bi, data_nascimento, genero, telefone, endereco, email, data_inscricao) 
                     VALUES 
                     (:nome_completo, :bi, :data_nascimento, :genero, :telefone, :endereco, :email, CURDATE())";

            $stmt = $db->prepare($query);
            $stmt->bindParam(":nome_completo", $nome_completo);
            $stmt->bindParam(":bi", $bi);
            $stmt->bindParam(":data_nascimento", $data_nascimento);
            $stmt->bindParam(":genero", $genero);
            $stmt->bindParam(":telefone", $telefone);
            $stmt->bindParam(":endereco", $endereco);
            $stmt->bindParam(":email", $email);

            if ($stmt->execute()) {
                $aluno_id = $db->lastInsertId();
                registrarAuditoria('Criação', 'alunos', $aluno_id, json_encode($_POST));
                
                $mensagem_sucesso = "Aluno cadastrado com sucesso!";
                
                // Se foi selecionado um curso, criar matrícula
                if ($curso_id) {
                    // Buscar turma padrão para o curso (primeira turma ativa)
                    $query_turma = "SELECT id, capacidade FROM turmas 
                                   WHERE curso_id = :curso_id AND ativo = 1 
                                   ORDER BY id LIMIT 1";
                    $stmt_turma = $db->prepare($query_turma);
                    $stmt_turma->bindParam(":curso_id", $curso_id);
                    $stmt_turma->execute();
                    $turma = $stmt_turma->fetch(PDO::FETCH_ASSOC);
                    
                    if ($turma) {
                        $turma_id = $turma['id'];
                        $ano_letivo = date('Y');
                        $semestre = (date('m') > 6) ? 2 : 1;
                        
                        // Verificar se há vagas na turma
                        $query_vagas = "SELECT COUNT(m.id) as matriculados
                                        FROM matriculas m
                                        WHERE m.turma_id = :turma_id 
                                        AND m.ano_letivo = :ano_letivo 
                                        AND m.semestre = :semestre";
                        
                        $stmt_vagas = $db->prepare($query_vagas);
                        $stmt_vagas->bindParam(":turma_id", $turma_id);
                        $stmt_vagas->bindParam(":ano_letivo", $ano_letivo);
                        $stmt_vagas->bindParam(":semestre", $semestre);
                        $stmt_vagas->execute();
                        $vagas = $stmt_vagas->fetch(PDO::FETCH_ASSOC);
                        
                        if ($vagas['matriculados'] >= $turma['capacidade']) {
                            $mensagem_sucesso .= " Mas não foi possível matricular na turma - capacidade esgotada.";
                        } else {
                            // Verificar se propina foi marcada como paga
                            if (!$propina_paga) {
                                $mensagem_sucesso .= " Mas matrícula pendente - propina não marcada como paga.";
                            }
                            
                            // Criar matrícula
                            $query_matricula = "INSERT INTO matriculas 
                                              (aluno_id, turma_id, data_matricula, ano_letivo, semestre, 
                                               valor_propina, propina_paga, status)
                                              VALUES 
                                              (:aluno_id, :turma_id, CURDATE(), :ano_letivo, :semestre, 
                                               :valor_propina, :propina_paga, 'Ativa')";
                            
                            $stmt_matricula = $db->prepare($query_matricula);
                            $stmt_matricula->bindParam(":aluno_id", $aluno_id);
                            $stmt_matricula->bindParam(":turma_id", $turma_id);
                            $stmt_matricula->bindParam(":ano_letivo", $ano_letivo);
                            $stmt_matricula->bindParam(":semestre", $semestre);
                            $stmt_matricula->bindParam(":valor_propina", $valor_propina);
                            $stmt_matricula->bindParam(":propina_paga", $propina_paga);
                            $stmt_matricula->execute();
                            
                            registrarAuditoria('Matrícula Automática', 'matriculas', $db->lastInsertId(), json_encode([
                                'aluno_id' => $aluno_id,
                                'turma_id' => $turma_id,
                                'curso_id' => $curso_id,
                                'valor_propina' => $valor_propina,
                                'propina_paga' => $propina_paga
                            ]));
                            
                            $mensagem_sucesso .= " Aluno matriculado no curso selecionado.";
                        }
                    }
                }
                
                $_SESSION['mensagem'] = $mensagem_sucesso;
                $_SESSION['tipo_mensagem'] = "sucesso";
                
                $db->commit();
                header("Location: index.php");
                exit();
            }
        } catch(PDOException $e) {
            $db->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $erros[] = strpos($e->getMessage(), 'email') !== false 
                    ? "Este e-mail já está cadastrado" 
                    : "Erro de duplicação de dados";
            } else {
                $erros[] = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }

    // Se chegou aqui, houve erros
    $_SESSION['mensagem'] = implode("<br>", $erros);
    $_SESSION['tipo_mensagem'] = "erro";
    $_SESSION['dados_formulario'] = $_POST;
    header("Location: criar.php");
    exit();
}

// Recupera dados do formulário se houver erro
$dadosFormulario = $_SESSION['dados_formulario'] ?? [];
unset($_SESSION['dados_formulario']);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Aluno - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/CSS/alunos.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
        .form-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .curso-fields {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="content-header">
            <h1><i class="fas fa-user-plus"></i> Cadastrar Novo Aluno</h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensagem'] === 'sucesso' ? 'success' : 'error'; ?>">
                <?php echo $_SESSION['mensagem']; 
                unset($_SESSION['mensagem']); 
                unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="post" action="criar.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome_completo">Nome Completo *</label>
                        <input type="text" id="nome_completo" name="nome_completo" required
                               value="<?php echo isset($dadosFormulario['nome_completo']) ? htmlspecialchars($dadosFormulario['nome_completo']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bi">Número do BI *</label>
                        <input type="text" id="bi" name="bi" required
                               value="<?php echo isset($dadosFormulario['bi']) ? htmlspecialchars($dadosFormulario['bi']) : ''; ?>" 
                               maxlength="20">
                        <small>Máximo de 20 caracteres numéricos</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento *</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" required
                               value="<?php echo isset($dadosFormulario['data_nascimento']) ? htmlspecialchars($dadosFormulario['data_nascimento']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="genero">Gênero *</label>
                        <select id="genero" name="genero" required>
                            <option value="M" <?php echo (isset($dadosFormulario['genero']) && $dadosFormulario['genero'] == 'M') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="F" <?php echo (isset($dadosFormulario['genero']) && $dadosFormulario['genero'] == 'F') ? 'selected' : ''; ?>>Feminino</option>
                            <option value="O" <?php echo (isset($dadosFormulario['genero']) && $dadosFormulario['genero'] == 'O') ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone"
                               value="<?php echo isset($dadosFormulario['telefone']) ? htmlspecialchars($dadosFormulario['telefone']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <textarea id="endereco" name="endereco" rows="3"><?php 
                        echo isset($dadosFormulario['endereco']) ? htmlspecialchars($dadosFormulario['endereco']) : ''; 
                    ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo isset($dadosFormulario['email']) ? htmlspecialchars($dadosFormulario['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="curso-fields">
                    <h3>Dados de Matrícula (Opcional)</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="curso_id">Curso</label>
                            <select id="curso_id" name="curso_id">
                                <option value="">Selecione um curso...</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo $curso['id']; ?>"
                                        <?php echo (isset($dadosFormulario['curso_id']) && $dadosFormulario['curso_id'] == $curso['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($curso['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="valor_propina">Valor da Propina (Kz)</label>
                            <input type="number" id="valor_propina" name="valor_propina" step="0.01" min="0"
                                   value="<?php echo isset($dadosFormulario['valor_propina']) ? htmlspecialchars($dadosFormulario['valor_propina']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="propina_paga" name="propina_paga" 
                            <?php echo (isset($dadosFormulario['propina_paga']) && $dadosFormulario['propina_paga']) ? 'checked' : ''; ?>>
                        <label for="propina_paga">Propina Paga</label>
                    </div>
                    
                    <small>Se selecionado um curso, o aluno será matriculado na primeira turma disponível</small>
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