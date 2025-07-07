<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
verificarLogin();
verificarAcesso(2); // Nível de acesso para gestão administrativa

$database = new Database();
$db = $database->getConnection();

// Paginação e filtros
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'ativo' => isset($_GET['ativo']) ? (int)$_GET['ativo'] : 1
];

// Construir WHERE
$where = "WHERE d.ativo = :ativo";
$params = [':ativo' => $filtros['ativo']];

if (!empty($filtros['busca'])) {
    $where .= " AND (d.nome LIKE :busca OR d.sigla LIKE :busca OR f.nome_completo LIKE :busca)";
    $params[':busca'] = "%{$filtros['busca']}%";
}

// Contar total
$sqlCount = "SELECT COUNT(*) FROM departamentos d
    LEFT JOIN funcionarios f ON d.chefe_id = f.id
    $where";
$stmt = $db->prepare($sqlCount);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Buscar departamentos
$sql = "SELECT d.id, d.nome, d.sigla, d.orcamento_anual, 
               d.data_criacao, d.ativo,
               f.nome_completo AS chefe, f.id AS chefe_id,
               (SELECT COUNT(*) FROM funcionarios WHERE departamento_id = d.id AND ativo = 1) AS num_colaboradores
        FROM departamentos d
        LEFT JOIN funcionarios f ON d.chefe_id = f.id
        $where
        ORDER BY d.nome
        LIMIT $offset, $por_pagina";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar chefes disponíveis
$chefes = $db->query("
    SELECT id, nome_completo, cargo 
    FROM funcionarios 
    WHERE ativo = 1 AND nivel_acesso >= 3
    ORDER BY nome_completo
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Departamentos - SIGEPOLI</title>
    <link rel="stylesheet" href="/Context/CSS/styles.css">
    <link rel="stylesheet" href="/Context/fontawesome/css/all.min.css">
</head>
<body>
    <div class="content-header">
        <h1><i class="fas fa-building"></i> Gestão de Departamentos</h1>
        <a href="criar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Departamento</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
        </div>
        <div class="card-body">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label for="busca">Pesquisar:</label>
                    <input type="text" name="busca" placeholder="Nome, Sigla ou Chefe..." value="<?= htmlspecialchars($filtros['busca']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="ativo">Status:</label>
                    <select name="ativo">
                        <option value="1" <?= $filtros['ativo'] == 1 ? 'selected' : '' ?>>Ativos</option>
                        <option value="0" <?= $filtros['ativo'] == 0 ? 'selected' : '' ?>>Inativos</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Lista de Departamentos</h3>
        </div>
        <div class="card-body">
            <?php if (empty($departamentos)): ?>
                <div class="alert alert-info">Nenhum departamento encontrado com os filtros aplicados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Sigla</th>
                                <th>Chefe</th>
                                <th>Colaboradores</th>
                                <th>Orçamento</th>
                                <th>Criação</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departamentos as $depto): ?>
                                <tr>
                                    <td><?= $depto['id'] ?></td>
                                    <td><?= htmlspecialchars($depto['nome']) ?></td>
                                    <td><?= htmlspecialchars($depto['sigla']) ?></td>
                                    <td>
                                        <?php if ($depto['chefe']): ?>
                                            <?= htmlspecialchars($depto['chefe']) ?>
                                        <?php else: ?>
                                            <span class="text-danger">Não definido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $depto['num_colaboradores'] ?></td>
                                    <td><?= number_format($depto['orcamento_anual'], 2, ',', '.') ?> Kz</td>
                                    <td><?= date('d/m/Y', strtotime($depto['data_criacao'])) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $depto['ativo'] ? 'success' : 'danger' ?>">
                                            <?= $depto['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="editar.php?id=<?= $depto['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($depto['ativo']): ?>
                                            <a href="excluir.php?id=<?= $depto['id'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Tem certeza que deseja inativar este departamento?')" title="Inativar">
                                                <i class="fas fa-times-circle"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="ativar.php?id=<?= $depto['id'] ?>" class="btn btn-sm btn-success" 
                                               onclick="return confirm('Tem certeza que deseja ativar este departamento?')" title="Ativar">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" 
                                                data-target="#modalDesignarChefe" data-depto-id="<?= $depto['id'] ?>" 
                                                data-depto-nome="<?= htmlspecialchars($depto['nome']) ?>"
                                                title="Designar Chefe">
                                            <i class="fas fa-user-tie"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <a href="?pagina=<?= $i ?><?= !empty($filtros['busca']) ? '&busca=' . urlencode($filtros['busca']) : '' ?>&ativo=<?= $filtros['ativo'] ?>"
                               class="<?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para designação de chefe -->
    <div class="modal" id="modalDesignarChefe" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Designar Chefe de Departamento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="designar_chefe.php">
                    <div class="modal-body">
                        <input type="hidden" name="departamento_id" id="modalDeptoId">
                        <p>Departamento: <strong id="modalDeptoNome"></strong></p>
                        
                        <div class="form-group">
                            <label for="chefe_id">Selecione o Colaborador:</label>
                            <select class="form-control" name="chefe_id" id="chefe_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($chefes as $chefe): ?>
                                    <option value="<?= $chefe['id'] ?>">
                                        <?= htmlspecialchars($chefe['nome_completo']) ?> 
                                        (<?= htmlspecialchars($chefe['cargo']) ?>)
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
        $('#modalDesignarChefe').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var deptoId = button.data('depto-id');
            var deptoNome = button.data('depto-nome');
            
            var modal = $(this);
            modal.find('#modalDeptoId').val(deptoId);
            modal.find('#modalDeptoNome').text(deptoNome);
            modal.find('#chefe_id').val('');
        });
    </script>
</body>
</html>