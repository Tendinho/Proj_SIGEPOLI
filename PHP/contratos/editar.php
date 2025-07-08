<?php
require_once __DIR__ . '/../../config.php';
verificarLogin();
verificarAcesso(5); // Nível de acesso necessário para editar contratos

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id']) ){
    $_SESSION['mensagem'] = "Contrato não especificado";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/pagamentos_empresas/contratos/index.php');
}

$contrato_id = (int)$_GET['id'];

// Buscar dados do contrato
$stmt = $db->prepare("SELECT * FROM contratos WHERE id = ?");
$stmt->execute([$contrato_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contrato) {
    $_SESSION['mensagem'] = "Contrato não encontrado";
    $_SESSION['tipo_mensagem'] = "erro";
    redirect('/PHP/pagamentos_empresas/contratos/index.php');
}

// Buscar empresas ativas
$empresas = $db->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Validar dados
        $required = ['numero_contrato', 'empresa_id', 'tipo_servico', 'data_inicio', 'data_fim', 'valor_mensal'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . str_replace('_', ' ', $field) . " é obrigatório");
            }
        }

        // Verificar se número de contrato já existe (excluindo o atual)
        $stmt = $db->prepare("SELECT COUNT(*) FROM contratos WHERE numero_contrato = ? AND id != ?");
        $stmt->execute([$_POST['numero_contrato'], $contrato_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Já existe outro contrato com este número");
        }

        // Atualizar contrato
        $stmt = $db->prepare("UPDATE contratos SET
            numero_contrato = :numero_contrato,
            empresa_id = :empresa_id,
            tipo_servico = :tipo_servico,
            data_inicio = :data_inicio,
            data_fim = :data_fim,
            valor_mensal = :valor_mensal,
            descricao = :descricao,
            garantia_info = :garantia_info,
            garantia_validade = :garantia_validade,
            sla_meta = :sla_meta,
            multa_sla = :multa_sla
            WHERE id = :id
        ");

        $params = [
            ':numero_contrato' => $_POST['numero_contrato'],
            ':empresa_id' => $_POST['empresa_id'],
            ':tipo_servico' => $_POST['tipo_servico'],
            ':data_inicio' => $_POST['data_inicio'],
            ':data_fim' => $_POST['data_fim'],
            ':valor_mensal' => str_replace(['.', ','], ['', '.'], $_POST['valor_mensal']),
            ':descricao' => $_POST['descricao'] ?? null,
            ':garantia_info' => $_POST['garantia_info'] ?? null,
            ':garantia_validade' => !empty($_POST['garantia_validade']) ? $_POST['garantia_validade'] : null,
            ':sla_meta' => $_POST['sla_meta'] ?? 90,
            ':multa_sla' => !empty($_POST['multa_sla']) ? str_replace(['.', ','], ['', '.'], $_POST['multa_sla']) : 0,
            ':id' => $contrato_id
        ];

        if (!$stmt->execute($params)) {
            throw new Exception("Erro ao atualizar contrato");
        }

        $db->commit();

        // Registrar auditoria
        registrarAuditoria("Atualizou o contrato: {$_POST['numero_contrato']}", 'contratos', $contrato_id);

        $_SESSION['mensagem'] = "Contrato atualizado com sucesso!";
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
    <title>Editar Contrato - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
    <style>
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
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span>Operacional</span>
                    <span><a href="/PHP/pagamentos_empresas/contratos/index.php">Contratos</a></span>
                    <span>Editar Contrato</span>
                </div>
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['nome_completo']) ?></span>
                    <img src="/Context/IMG/user-default.png" alt="User">
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
                    <div class="card-header">
                        <h3><i class="fas fa-file-contract"></i> Editar Contrato - <?= htmlspecialchars($contrato['numero_contrato']) ?></h3>
                        <a href="/PHP/pagamentos_empresas/contratos/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
                    </div>
                    <div class="card-body">
                        <form method="post" class="form-container">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="numero_contrato">Número do Contrato*</label>
                                    <input type="text" name="numero_contrato" id="numero_contrato" required 
                                           value="<?= htmlspecialchars($contrato['numero_contrato']) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="empresa_id">Empresa*</label>
                                    <select name="empresa_id" id="empresa_id" required>
                                        <option value="">Selecione uma empresa</option>
                                        <?php foreach ($empresas as $empresa): ?>
                                            <option value="<?= $empresa['id'] ?>" <?= ($contrato['empresa_id'] == $empresa['id'] || (isset($_POST['empresa_id']) && $_POST['empresa_id'] == $empresa['id'])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($empresa['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tipo_servico">Tipo de Serviço*</label>
                                    <select name="tipo_servico" id="tipo_servico" required>
                                        <option value="">Selecione o serviço</option>
                                        <option value="Limpeza" <?= ($contrato['tipo_servico'] == 'Limpeza' || (isset($_POST['tipo_servico']) && $_POST['tipo_servico'] == 'Limpeza')) ? 'selected' : '' ?>>Limpeza</option>
                                        <option value="Segurança" <?= ($contrato['tipo_servico'] == 'Segurança' || (isset($_POST['tipo_servico']) && $_POST['tipo_servico'] == 'Segurança')) ? 'selected' : '' ?>>Segurança</option>
                                        <option value="Cafetaria" <?= ($contrato['tipo_servico'] == 'Cafetaria' || (isset($_POST['tipo_servico']) && $_POST['tipo_servico'] == 'Cafetaria')) ? 'selected' : '' ?>>Cafetaria</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="valor_mensal">Valor Mensal (Kz)*</label>
                                    <input type="text" name="valor_mensal" id="valor_mensal" required class="money-mask"
                                           value="<?= number_format($contrato['valor_mensal'], 2, ',', '.') ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="data_inicio">Data de Início*</label>
                                    <input type="date" name="data_inicio" id="data_inicio" required
                                           value="<?= $contrato['data_inicio'] ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="data_fim">Data de Término*</label>
                                    <input type="date" name="data_fim" id="data_fim" required
                                           value="<?= $contrato['data_fim'] ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="sla_meta">Meta SLA (%)</label>
                                    <input type="number" name="sla_meta" id="sla_meta" min="0" max="100"
                                           value="<?= $contrato['sla_meta'] ?? 90 ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="multa_sla">Multa por não cumprimento SLA (Kz)</label>
                                    <input type="text" name="multa_sla" id="multa_sla" class="money-mask"
                                           value="<?= number_format($contrato['multa_sla'], 2, ',', '.') ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="garantia_info">Informações da Garantia</label>
                                    <input type="text" name="garantia_info" id="garantia_info"
                                           value="<?= htmlspecialchars($contrato['garantia_info']) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="garantia_validade">Validade da Garantia</label>
                                    <input type="date" name="garantia_validade" id="garantia_validade"
                                           value="<?= $contrato['garantia_validade'] ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="descricao">Descrição</label>
                                <textarea name="descricao" id="descricao" rows="3"><?= htmlspecialchars($contrato['descricao']) ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Atualizar Contrato</button>
                                <a href="/PHP/pagamentos_empresas/contratos/index.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/Context/JS/script.js"></script>
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
                this.value = '<?= $contrato['data_inicio'] ?>';
            }
        });

        document.getElementById('data_fim').addEventListener('change', function() {
            const inicio = document.getElementById('data_inicio');
            if (inicio.value && new Date(this.value) < new Date(inicio.value)) {
                alert('A data de término não pode ser anterior à data de início');
                this.value = '<?= $contrato['data_fim'] ?>';
            }
        });
    </script>
</body>
</html>