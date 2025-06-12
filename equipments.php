<?php
require_once 'config/database.php';
require_once 'models/Equipment.php';

$database = new Database();
$db = $database->getConnection();
$equipment = new Equipment($db);

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Processar ações
if ($_POST) {
    if ($action == 'add') {
        try {
            $equipment->name = $_POST['name'];
            $equipment->description = $_POST['description'];
            $equipment->category = $_POST['category'];
            $equipment->brand = $_POST['brand'];
            $equipment->model = $_POST['model'];
            $equipment->serial_number = $_POST['serial_number'];
            $equipment->quantity = $_POST['quantity'];
            $equipment->min_stock = $_POST['min_stock'];
            $equipment->unit_price = $_POST['unit_price'];
            $equipment->supplier = $_POST['supplier'];
            $equipment->status = $_POST['status'];
            
            if ($equipment->create()) {
                header("Location: equipments.php?success=1");
                exit;
            } else {
                $error = "Erro ao cadastrar equipamento";
            }
        } catch (Exception $e) {
            if ($e->getMessage() == 'DUPLICATE_FOUND') {
                $duplicate_info = $e->getPrevious();
                $duplicate_error = true;
                $duplicate_data = $duplicate_info;
            } else {
                $error = "Erro ao cadastrar equipamento: " . $e->getMessage();
            }
        }
    } elseif ($action == 'edit') {
        try {
            $equipment->id = $id;
            $equipment->name = $_POST['name'];
            $equipment->description = $_POST['description'];
            $equipment->category = $_POST['category'];
            $equipment->brand = $_POST['brand'];
            $equipment->model = $_POST['model'];
            $equipment->serial_number = $_POST['serial_number'];
            $equipment->quantity = $_POST['quantity'];
            $equipment->min_stock = $_POST['min_stock'];
            $equipment->unit_price = $_POST['unit_price'];
            $equipment->supplier = $_POST['supplier'];
            $equipment->status = $_POST['status'];
            
            if ($equipment->update()) {
                header("Location: equipments.php?success=2");
                exit;
            } else {
                $error = "Erro ao atualizar equipamento";
            }
        } catch (Exception $e) {
            if ($e->getMessage() == 'DUPLICATE_FOUND') {
                $duplicate_info = $e->getPrevious();
                $duplicate_error = true;
                $duplicate_data = $duplicate_info;
            } else {
                $error = "Erro ao atualizar equipamento: " . $e->getMessage();
            }
        }
    }
}

if ($action == 'delete' && $id) {
    $equipment->id = $id;
    if ($equipment->delete()) {
        header("Location: equipments.php?success=3");
        exit;
    } else {
        $error = "Erro ao excluir equipamento";
    }
}

// Buscar dados para edição
if ($action == 'edit' && $id) {
    $equipment->id = $id;
    $equipment->readOne();
}

// Listar equipamentos
$search = isset($_GET['search']) ? $_GET['search'] : '';
if ($search) {
    $stmt = $equipment->search($search);
} else {
    $stmt = $equipment->read();
}

$categories = $equipment->getCategories();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipamentos - Sistema de Estoque</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-city"></i>
            <h3>Prefeitura</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="equipments.php" class="nav-item active">
                <i class="fas fa-laptop"></i>
                Equipamentos
            </a>
            <a href="movements.php" class="nav-item">
                <i class="fas fa-exchange-alt"></i>
                Movimentações
            </a>
            <a href="reports.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                Relatórios
            </a>
            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                Configurações
            </a>
        </nav>
    </div>

    <div class="main-content">
        <?php if ($action == 'list'): ?>
            <div class="header">
                <h1>Equipamentos</h1>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar equipamentos..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="searchEquipments(this.value)">
                    </div>
                    <button class="btn btn-primary" onclick="window.location.href='equipments.php?action=add'">
                        <i class="fas fa-plus"></i>
                        Novo Equipamento
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    switch($_GET['success']) {
                        case 1: echo "Equipamento cadastrado com sucesso!"; break;
                        case 2: echo "Equipamento atualizado com sucesso!"; break;
                        case 3: echo "Equipamento excluído com sucesso!"; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Marca/Modelo</th>
                            <th>Quantidade</th>
                            <th>Estoque Mín.</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <div class="equipment-info">
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                        <small><?php echo htmlspecialchars($row['description']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['brand']); ?>
                                    <?php if ($row['model']): ?>
                                        <br><small><?php echo htmlspecialchars($row['model']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="quantity-badge <?php echo $row['quantity'] <= $row['min_stock'] ? 'low-stock' : ''; ?>">
                                        <?php echo $row['quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['min_stock']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='equipments.php?action=edit&id=<?php echo $row['id']; ?>'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success" onclick="window.location.href='movements.php?action=add&equipment_id=<?php echo $row['id']; ?>'">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="header">
                <h1><?php echo $action == 'add' ? 'Novo Equipamento' : 'Editar Equipamento'; ?></h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="window.location.href='equipments.php'">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </button>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($duplicate_error) && $duplicate_error): ?>
                <div class="alert alert-error">
                    <h4><i class="fas fa-exclamation-triangle"></i> Equipamento já existe no estoque!</h4>
                    <p><strong>Equipamento encontrado:</strong> <?php echo htmlspecialchars($duplicate_data['name']); ?></p>
                    <p><strong>Marca/Modelo:</strong> <?php echo htmlspecialchars($duplicate_data['brand']); ?> <?php echo htmlspecialchars($duplicate_data['model']); ?></p>
                    <?php if (!empty($duplicate_data['serial_number'])): ?>
                        <p><strong>Número de Série:</strong> <?php echo htmlspecialchars($duplicate_data['serial_number']); ?></p>
                    <?php endif; ?>
                    <p><strong>Quantidade atual:</strong> <?php echo $duplicate_data['quantity']; ?> unidades</p>
                    <hr style="margin: 15px 0; border: none; border-top: 1px solid #fecaca;">
                    <p><strong>Para adicionar mais unidades deste equipamento:</strong></p>
                    <button class="btn btn-primary" onclick="window.location.href='movements.php?action=add&equipment_id=<?php echo $duplicate_data['id']; ?>'">
                        <i class="fas fa-plus"></i>
                        Ir para Movimentações
                    </button>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" class="equipment-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Nome do Equipamento *</label>
                            <input type="text" id="name" name="name" required value="<?php echo isset($equipment->name) ? htmlspecialchars($equipment->name) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="category">Categoria *</label>
                            <select id="category" name="category" required>
                                <option value="">Selecione uma categoria</option>
                                <option value="Computador" <?php echo (isset($equipment->category) && $equipment->category == 'Computador') ? 'selected' : ''; ?>>Computador</option>
                                <option value="Monitor" <?php echo (isset($equipment->category) && $equipment->category == 'Monitor') ? 'selected' : ''; ?>>Monitor</option>
                                <option value="Impressora" <?php echo (isset($equipment->category) && $equipment->category == 'Impressora') ? 'selected' : ''; ?>>Impressora</option>
                                <option value="Mouse" <?php echo (isset($equipment->category) && $equipment->category == 'Mouse') ? 'selected' : ''; ?>>Mouse</option>
                                <option value="Teclado" <?php echo (isset($equipment->category) && $equipment->category == 'Teclado') ? 'selected' : ''; ?>>Teclado</option>
                                <option value="Cabo" <?php echo (isset($equipment->category) && $equipment->category == 'Cabo') ? 'selected' : ''; ?>>Cabo</option>
                                <option value="Roteador" <?php echo (isset($equipment->category) && $equipment->category == 'Roteador') ? 'selected' : ''; ?>>Roteador</option>
                                <option value="Switch" <?php echo (isset($equipment->category) && $equipment->category == 'Switch') ? 'selected' : ''; ?>>Switch</option>
                                <option value="Outros" <?php echo (isset($equipment->category) && $equipment->category == 'Outros') ? 'selected' : ''; ?>>Outros</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="brand">Marca</label>
                            <input type="text" id="brand" name="brand" value="<?php echo isset($equipment->brand) ? htmlspecialchars($equipment->brand) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="model">Modelo</label>
                            <input type="text" id="model" name="model" value="<?php echo isset($equipment->model) ? htmlspecialchars($equipment->model) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="serial_number">Número de Série</label>
                            <input type="text" id="serial_number" name="serial_number" value="<?php echo isset($equipment->serial_number) ? htmlspecialchars($equipment->serial_number) : ''; ?>">
                            <small class="form-help">Usado para verificar duplicatas</small>
                        </div>

                        <div class="form-group">
                            <label for="supplier">Fornecedor</label>
                            <input type="text" id="supplier" name="supplier" value="<?php echo isset($equipment->supplier) ? htmlspecialchars($equipment->supplier) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="quantity">Quantidade Inicial *</label>
                            <input type="number" id="quantity" name="quantity" required min="0" value="<?php echo isset($equipment->quantity) ? $equipment->quantity : '0'; ?>">
                            <small class="form-help warning">Para equipamentos existentes, use a página de Movimentações</small>
                        </div>

                        <div class="form-group">
                            <label for="min_stock">Estoque Mínimo *</label>
                            <input type="number" id="min_stock" name="min_stock" required min="0" value="<?php echo isset($equipment->min_stock) ? $equipment->min_stock : '1'; ?>">
                        </div>

                        <div class="form-group">
                            <label for="unit_price">Preço Unitário</label>
                            <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" value="<?php echo isset($equipment->unit_price) ? $equipment->unit_price : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="ativo" <?php echo (isset($equipment->status) && $equipment->status == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                                <option value="inativo" <?php echo (isset($equipment->status) && $equipment->status == 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                                <option value="manutencao" <?php echo (isset($equipment->status) && $equipment->status == 'manutencao') ? 'selected' : ''; ?>>Manutenção</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Descrição</label>
                        <textarea id="description" name="description" rows="3"><?php echo isset($equipment->description) ? htmlspecialchars($equipment->description) : ''; ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='equipments.php'">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo $action == 'add' ? 'Cadastrar' : 'Atualizar'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function confirmDelete(id) {
            if (confirm('Tem certeza que deseja excluir este equipamento?')) {
                window.location.href = 'equipments.php?action=delete&id=' + id;
            }
        }

        function searchEquipments(query) {
            if (query.length > 2 || query.length === 0) {
                window.location.href = 'equipments.php' + (query ? '?search=' + encodeURIComponent(query) : '');
            }
        }

        // Verificação em tempo real de duplicatas
        document.addEventListener('DOMContentLoaded', function() {
            const serialInput = document.getElementById('serial_number');
            const brandInput = document.getElementById('brand');
            const modelInput = document.getElementById('model');
            
            if (serialInput && brandInput && modelInput) {
                let checkTimeout;
                
                function checkDuplicates() {
                    clearTimeout(checkTimeout);
                    checkTimeout = setTimeout(() => {
                        const serial = serialInput.value.trim();
                        const brand = brandInput.value.trim();
                        const model = modelInput.value.trim();
                        
                        if ((serial && serial.length > 2) || (brand && model && brand.length > 1 && model.length > 1)) {
                            // Aqui você pode implementar uma verificação AJAX se desejar
                            // Por enquanto, a verificação acontece apenas no submit
                        }
                    }, 500);
                }
                
                serialInput.addEventListener('input', checkDuplicates);
                brandInput.addEventListener('input', checkDuplicates);
                modelInput.addEventListener('input', checkDuplicates);
            }
        });
    </script>
</body>
</html>