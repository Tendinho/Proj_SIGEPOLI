<?php
// Inicialização da sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações básicas
define('SISTEMA_NOME', 'SIGEPOLI');
define('BASE_URL', '');

// Funções essenciais
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

// Verificação de login
if (!isset($_SESSION['usuario_id'])) {
    redirect('/PHP/login.php');
}

// Verificação de acesso (nível 5 necessário)
if ($_SESSION['nivel_acesso'] < 5) {
    $_SESSION['mensagem'] = "Acesso negado. Permissões insuficientes.";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/index.php');
}

// Conexão com o banco de dados
try {
    $db = new PDO('mysql:host=localhost;dbname=sigepoli;charset=utf8', 'admin', 'SenhaSegura123!');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Buscar empresas ativas com seus tipos de serviço
$empresas = $db->query("SELECT id, nome, tipo_servico FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Validar dados
        $required = ['numero_contrato', 'empresa_id', 'data_inicio', 'data_fim', 'valor_mensal'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . str_replace('_', ' ', $field) . " é obrigatório");
            }
        }

        // Verificar se número de contrato já existe
        $stmt = $db->prepare("SELECT COUNT(*) FROM contratos WHERE numero_contrato = ?");
        $stmt->execute([$_POST['numero_contrato']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Já existe um contrato com este número");
        }

        // Inserir contrato (sem tipo_servico)
        $stmt = $db->prepare("INSERT INTO contratos (
            numero_contrato, empresa_id, 
            data_inicio, data_fim, valor_mensal, 
            descricao, garantia_info, garantia_validade, 
            sla_meta, multa_sla, ativo
        ) VALUES (
            :numero_contrato, :empresa_id, 
            :data_inicio, :data_fim, :valor_mensal, 
            :descricao, :garantia_info, :garantia_validade, 
            :sla_meta, :multa_sla, 1
        )");

        $params = [
            ':numero_contrato' => $_POST['numero_contrato'],
            ':empresa_id' => $_POST['empresa_id'],
            ':data_inicio' => $_POST['data_inicio'],
            ':data_fim' => $_POST['data_fim'],
            ':valor_mensal' => str_replace(['.', ','], ['', '.'], $_POST['valor_mensal']),
            ':descricao' => $_POST['descricao'] ?? null,
            ':garantia_info' => $_POST['garantia_info'] ?? null,
            ':garantia_validade' => !empty($_POST['garantia_validade']) ? $_POST['garantia_validade'] : null,
            ':sla_meta' => $_POST['sla_meta'] ?? 90,
            ':multa_sla' => !empty($_POST['multa_sla']) ? str_replace(['.', ','], ['', '.'], $_POST['multa_sla']) : 0
        ];

        if (!$stmt->execute($params)) {
            throw new Exception("Erro ao cadastrar contrato");
        }

        $contrato_id = $db->lastInsertId();
        $db->commit();

        $_SESSION['mensagem'] = "Contrato cadastrado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
        redirect('/PHP/pagamentos_empresas/contratos/index.php');

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Contrato - SIGEPOLI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            height: 100%;
            padding: 20px;
        }
        .sidebar-header {
            margin-bottom: 30px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .top-bar {
            background-color: white;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge-servico {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            margin-left: 8px;
        }
        .badge-limpeza {
            background-color: #17a2b8;
            color: white;
        }
        .badge-seguranca {
            background-color: #6c757d;
            color: white;
        }
        .badge-cafetaria {
            background-color: #fd7e14;
            color: white;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><?= SISTEMA_NOME ?></h2>
            <p><?= htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário') ?></p>
            <p>Nível: <?= $_SESSION['nivel_acesso'] ?? '0' ?></p>
        </div>
        <nav>
            <ul style="list-style: none; padding: 0;">
                <li><a href="/PHP/index.php" style="color: white; text-decoration: none;"><i class="fas fa-home"></i> Dashboard</a></li>
                <li style="margin-top: 20px; color: #aaa;"><i class="fas fa-cogs"></i> Operacional</li>
                <li><a href="/PHP/contratos/index.php" style="color: white; text-decoration: none;"><i class="fas fa-file-contract"></i> Contratos</a></li>
                <li><a href="/PHP/logout.php" style="color: white; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
            </ul>
        </nav>
    </div>
    
    <div class="main-content">
        <header class="top-bar">
            <div>
                <span>Operacional</span>
                <span> / </span>
                <span><a href="/PHP/contratos/index.php">Contratos</a></span>
                <span> / </span>
                <span>Novo Contrato</span>
            </div>
            <div>
                <span><?= htmlspecialchars($_SESSION['nome_completo']) ?></span>
                <img src="/Context/IMG/user-default.png" alt="User" style="width: 40px; border-radius: 50%; margin-left: 10px;">
            </div>
        </header>
        
        <div class="content">
            <?php if (isset($_SESSION['mensagem'])): ?>
                <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                    <?= $_SESSION['mensagem'] ?>
                    <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fas fa-file-contract"></i> Cadastrar Novo Contrato</h3>
                    <a href="/PHP/contratos/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
                </div>
                <div class="card-body">
                    <form method="post" class="form-container">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="numero_contrato">Número do Contrato*</label>
                                <input type="text" name="numero_contrato" id="numero_contrato" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="empresa_id">Empresa*</label>
                                <select name="empresa_id" id="empresa_id" required>
                                    <option value="">Selecione uma empresa</option>
                                    <?php foreach ($empresas as $empresa): 
                                        $badge_class = 'badge-' . strtolower($empresa['tipo_servico']);
                                    ?>
                                        <option value="<?= $empresa['id'] ?>" <?= isset($_POST['empresa_id']) && $_POST['empresa_id'] == $empresa['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($empresa['nome']) ?>
                                            <span class="badge-servico <?= $badge_class ?>">
                                                <?= $empresa['tipo_servico'] ?>
                                            </span>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="valor_mensal">Valor Mensal (Kz)*</label>
                                <input type="text" name="valor_mensal" id="valor_mensal" required class="money-mask">
                            </div>
                            
                            <div class="form-group">
                                <label for="sla_meta">Meta SLA (%)</label>
                                <input type="number" name="sla_meta" id="sla_meta" min="0" max="100" value="<?= $_POST['sla_meta'] ?? 90 ?>">
                            </div>
                        </div>
            
                        <div class="form-row">
                            <div class="form-group">
                                <label for="data_inicio">Data de Início*</label>
                                <input type="date" name="data_inicio" id="data_inicio" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="data_fim">Data de Término*</label>
                                <input type="date" name="data_fim" id="data_fim" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="multa_sla">Multa por não cumprimento SLA (Kz)</label>
                                <input type="text" name="multa_sla" id="multa_sla" class="money-mask" value="<?= $_POST['multa_sla'] ?? '0' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="garantia_validade">Validade da Garantia</label>
                                <input type="date" name="garantia_validade" id="garantia_validade" value="<?= $_POST['garantia_validade'] ?? '' ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="garantia_info">Informações da Garantia</label>
                            <input type="text" name="garantia_info" id="garantia_info" value="<?= $_POST['garantia_info'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea name="descricao" id="descricao" rows="3"><?= $_POST['descricao'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Contrato</button>
                            <a href="/PHP/contratos/index.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscara para valores monetários
        document.querySelectorAll('.money-mask').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = (value / 100).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                e.target.value = value;
            });
        });

        // Validação de datas
        document.getElementById('data_inicio').addEventListener('change', function() {
            const fim = document.getElementById('data_fim');
            if (fim.value && new Date(this.value) > new Date(fim.value)) {
                alert('A data de início não pode ser posterior à data de término');
                this.value = '';
            }
        });

        document.getElementById('data_fim').addEventListener('change', function() {
            const inicio = document.getElementById('data_inicio');
            if (inicio.value && new Date(this.value) < new Date(inicio.value)) {
                alert('A data de término não pode ser anterior à data de início');
                this.value = '';
            }
        });
    </script>
</body>
</html>