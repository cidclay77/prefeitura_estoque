<?php
require_once 'config/database.php';
require_once 'models/Equipment.php';
require_once 'models/Movement.php';

$database = new Database();
$db = $database->getConnection();
$equipment = new Equipment($db);
$movement = new Movement($db);

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$equipment_id = isset($_GET['equipment_id']) ? $_GET['equipment_id'] : null;

// Processar movimentação
if ($_POST && $action == 'add') {
    try {
        $movement->processMovement(
            $_POST['equipment_id'],
            $_POST['type'],
            $_POST['quantity'],
            $_POST['reason'],
            $_POST['user_name'],
            $_POST['notes']
        );
        header("Location: movements.php?success=1");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Listar movimentações
$stmt = $movement->read();

// Buscar equipamentos para o formulário
$equipments_stmt = $equipment->read();
$equipments = $equipments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Se um equipamento específico foi selecionado
$selected_equipment = null;
if ($equipment_id) {
    $equipment->id = $equipment_id;
    if ($equipment->readOne()) {
        $selected_equipment = $equipment;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentações - Sistema de Estoque</title>
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
            <a href="equipments.php" class="nav-item">
                <i class="fas fa-laptop"></i>
                Equipamentos
            </a>
            <a href="movements.php" class="nav-item active">
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
                <h1>Movimentações de Estoque</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="window.location.href='movements.php?action=add'">
                        <i class="fas fa-plus"></i>
                        Nova Movimentação
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Movimentação registrada com sucesso!
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Equipamento</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Motivo</th>
                            <th>Usuário</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="equipment-info">
                                        <strong><?php echo htmlspecialchars($row['equipment_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($row['category']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="movement-type <?php echo $row['type']; ?>">
                                        <i class="fas <?php echo $row['type'] == 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                                        <?php echo ucfirst($row['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['notes']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="header">
                <h1>Nova Movimentação</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="window.location.href='movements.php'">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </button>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" class="movement-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="equipment_id">Equipamento *</label>
                            <select id="equipment_id" name="equipment_id" required onchange="updateEquipmentInfo(this.value)">
                                <option value="">Selecione um equipamento</option>
                                <?php foreach ($equipments as $eq): ?>
                                    <option value="<?php echo $eq['id']; ?>" 
                                            data-quantity="<?php echo $eq['quantity']; ?>"
                                            <?php echo ($equipment_id == $eq['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($eq['name']); ?> - <?php echo htmlspecialchars($eq['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="type">Tipo de Movimentação *</label>
                            <select id="type" name="type" required onchange="updateMaxQuantity()">
                                <option value="">Selecione o tipo</option>
                                <option value="entrada">Entrada</option>
                                <option value="saida">Saída</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="quantity">Quantidade *</label>
                            <input type="number" id="quantity" name="quantity" required min="1">
                            <small id="quantity-info" class="form-help"></small>
                        </div>

                        <div class="form-group">
                            <label for="reason">Motivo *</label>
                            <select id="reason" name="reason" required>
                                <option value="">Selecione o motivo</option>
                                <option value="Compra">Compra</option>
                                <option value="Doação">Doação</option>
                                <option value="Devolução">Devolução</option>
                                <option value="Distribuição">Distribuição</option>
                                <option value="Transferência">Transferência</option>
                                <option value="Descarte">Descarte</option>
                                <option value="Manutenção">Manutenção</option>
                                <option value="Perda">Perda</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="user_name">Usuário Responsável *</label>
                            <input type="text" id="user_name" name="user_name" required placeholder="Nome do usuário">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="notes">Observações</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Observações adicionais sobre a movimentação"></textarea>
                    </div>

                    <div id="equipment-info" class="equipment-summary" style="display: none;">
                        <h4>Informações do Equipamento</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Estoque Atual:</span>
                                <span id="current-stock" class="info-value">0</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Estoque Mínimo:</span>
                                <span id="min-stock" class="info-value">0</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Após Movimentação:</span>
                                <span id="new-stock" class="info-value">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='movements.php'">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i>
                            Registrar Movimentação
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        let equipmentData = {};
        
        // Carregar dados dos equipamentos
        <?php foreach ($equipments as $eq): ?>
            equipmentData[<?php echo $eq['id']; ?>] = {
                quantity: <?php echo $eq['quantity']; ?>,
                min_stock: <?php echo $eq['min_stock']; ?>,
                name: "<?php echo htmlspecialchars($eq['name']); ?>"
            };
        <?php endforeach; ?>

        function updateEquipmentInfo(equipmentId) {
            const infoDiv = document.getElementById('equipment-info');
            const currentStock = document.getElementById('current-stock');
            const minStock = document.getElementById('min-stock');
            
            if (equipmentId && equipmentData[equipmentId]) {
                const data = equipmentData[equipmentId];
                currentStock.textContent = data.quantity;
                minStock.textContent = data.min_stock;
                infoDiv.style.display = 'block';
                updateMaxQuantity();
            } else {
                infoDiv.style.display = 'none';
            }
        }

        function updateMaxQuantity() {
            const equipmentId = document.getElementById('equipment_id').value;
            const type = document.getElementById('type').value;
            const quantityInput = document.getElementById('quantity');
            const quantityInfo = document.getElementById('quantity-info');
            
            if (equipmentId && equipmentData[equipmentId] && type) {
                const data = equipmentData[equipmentId];
                
                if (type === 'saida') {
                    quantityInput.max = data.quantity;
                    quantityInfo.textContent = `Máximo: ${data.quantity} unidades disponíveis`;
                    quantityInfo.className = 'form-help';
                } else {
                    quantityInput.removeAttribute('max');
                    quantityInfo.textContent = '';
                }
                
                calculateNewStock();
            }
        }

        function calculateNewStock() {
            const equipmentId = document.getElementById('equipment_id').value;
            const type = document.getElementById('type').value;
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const newStockSpan = document.getElementById('new-stock');
            
            if (equipmentId && equipmentData[equipmentId] && type && quantity) {
                const currentStock = equipmentData[equipmentId].quantity;
                let newStock;
                
                if (type === 'entrada') {
                    newStock = currentStock + quantity;
                } else {
                    newStock = currentStock - quantity;
                }
                
                newStockSpan.textContent = newStock;
                
                // Adicionar classe de alerta se necessário
                const minStock = equipmentData[equipmentId].min_stock;
                if (newStock <= minStock) {
                    newStockSpan.className = 'info-value warning';
                } else {
                    newStockSpan.className = 'info-value';
                }
            }
        }

        // Event listeners
        document.getElementById('quantity').addEventListener('input', calculateNewStock);
        
        // Inicializar se um equipamento já estiver selecionado
        window.addEventListener('load', function() {
            const equipmentId = document.getElementById('equipment_id').value;
            if (equipmentId) {
                updateEquipmentInfo(equipmentId);
            }
        });
    </script>
</body>
</html>