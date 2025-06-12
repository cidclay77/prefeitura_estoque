<?php
require_once 'config/database.php';
require_once 'models/Equipment.php';
require_once 'models/Movement.php';

$database = new Database();
$db = $database->getConnection();

$equipment = new Equipment($db);
$movement = new Movement($db);

// Estatísticas para o dashboard
$stats = $equipment->getStats();
$lowStock = $equipment->getLowStock();
$recentMovements = $movement->getRecent(5);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Estoque - Prefeitura</title>
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
            <a href="index.php" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="equipments.php" class="nav-item">
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
        <div class="header">
            <h1>Dashboard - Controle de Estoque</h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='equipments.php?action=add'">
                    <i class="fas fa-plus"></i>
                    Novo Equipamento
                </button>
            </div>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-laptop"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_equipment']; ?></h3>
                    <p>Total de Equipamentos</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['available']; ?></h3>
                    <p>Disponíveis</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($lowStock); ?></h3>
                    <p>Estoque Baixo</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-red">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['out_of_stock']; ?></h3>
                    <p>Sem Estoque</p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Estoque Baixo</h3>
                    <i class="fas fa-exclamation-triangle text-orange"></i>
                </div>
                <div class="card-content">
                    <?php if (empty($lowStock)): ?>
                        <p class="no-data">Nenhum item com estoque baixo</p>
                    <?php else: ?>
                        <div class="low-stock-list">
                            <?php foreach ($lowStock as $item): ?>
                                <div class="stock-item">
                                    <div class="item-info">
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <span class="category"><?php echo htmlspecialchars($item['category']); ?></span>
                                    </div>
                                    <div class="stock-quantity">
                                        <span class="quantity"><?php echo $item['quantity']; ?></span>
                                        <span class="min-stock">Min: <?php echo $item['min_stock']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Movimentações Recentes</h3>
                    <i class="fas fa-history"></i>
                </div>
                <div class="card-content">
                    <?php if (empty($recentMovements)): ?>
                        <p class="no-data">Nenhuma movimentação recente</p>
                    <?php else: ?>
                        <div class="movements-list">
                            <?php foreach ($recentMovements as $mov): ?>
                                <div class="movement-item">
                                    <div class="movement-type <?php echo $mov['type']; ?>">
                                        <i class="fas <?php echo $mov['type'] == 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                                    </div>
                                    <div class="movement-info">
                                        <strong><?php echo htmlspecialchars($mov['equipment_name']); ?></strong>
                                        <span class="movement-details">
                                            <?php echo ucfirst($mov['type']); ?> de <?php echo $mov['quantity']; ?> unidades
                                        </span>
                                        <span class="movement-date"><?php echo date('d/m/Y H:i', strtotime($mov['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>