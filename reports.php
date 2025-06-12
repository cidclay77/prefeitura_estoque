<?php
require_once 'config/database.php';
require_once 'models/Equipment.php';
require_once 'models/Movement.php';

$database = new Database();
$db = $database->getConnection();
$equipment = new Equipment($db);
$movement = new Movement($db);

$report_type = isset($_GET['type']) ? $_GET['type'] : 'overview';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Função para gerar relatório de visão geral
function getOverviewReport($db) {
    $query = "SELECT 
                COUNT(*) as total_items,
                SUM(quantity) as total_quantity,
                SUM(quantity * unit_price) as total_value,
                SUM(CASE WHEN quantity <= min_stock THEN 1 ELSE 0 END) as low_stock_items,
                SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_items,
                COUNT(DISTINCT category) as total_categories
              FROM equipments";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Função para relatório por categoria
function getCategoryReport($db) {
    $query = "SELECT 
                category,
                COUNT(*) as total_items,
                SUM(quantity) as total_quantity,
                SUM(quantity * unit_price) as total_value,
                AVG(quantity) as avg_quantity,
                SUM(CASE WHEN quantity <= min_stock THEN 1 ELSE 0 END) as low_stock_items
              FROM equipments 
              GROUP BY category 
              ORDER BY total_value DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para relatório de movimentações
function getMovementReport($db, $start_date, $end_date, $category = '') {
    $where_clause = "WHERE DATE(m.created_at) BETWEEN :start_date AND :end_date";
    $params = [':start_date' => $start_date, ':end_date' => $end_date];
    
    if ($category) {
        $where_clause .= " AND e.category = :category";
        $params[':category'] = $category;
    }
    
    $query = "SELECT 
                m.type,
                COUNT(*) as total_movements,
                SUM(m.quantity) as total_quantity,
                SUM(m.quantity * e.unit_price) as total_value
              FROM movements m
              JOIN equipments e ON m.equipment_id = e.id
              $where_clause
              GROUP BY m.type";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para relatório detalhado de movimentações
function getDetailedMovementReport($db, $start_date, $end_date, $category = '') {
    $where_clause = "WHERE DATE(m.created_at) BETWEEN :start_date AND :end_date";
    $params = [':start_date' => $start_date, ':end_date' => $end_date];
    
    if ($category) {
        $where_clause .= " AND e.category = :category";
        $params[':category'] = $category;
    }
    
    $query = "SELECT 
                m.*,
                e.name as equipment_name,
                e.category,
                e.unit_price,
                (m.quantity * e.unit_price) as movement_value
              FROM movements m
              JOIN equipments e ON m.equipment_id = e.id
              $where_clause
              ORDER BY m.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para relatório de estoque baixo
function getLowStockReport($db) {
    $query = "SELECT 
                *,
                (min_stock - quantity) as deficit,
                (quantity * unit_price) as current_value
              FROM equipments 
              WHERE quantity <= min_stock 
              ORDER BY (quantity - min_stock) ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para relatório de equipamentos mais movimentados
function getMostMovedReport($db, $start_date, $end_date) {
    $query = "SELECT 
                e.name,
                e.category,
                e.quantity as current_stock,
                COUNT(m.id) as total_movements,
                SUM(CASE WHEN m.type = 'entrada' THEN m.quantity ELSE 0 END) as total_entries,
                SUM(CASE WHEN m.type = 'saida' THEN m.quantity ELSE 0 END) as total_exits,
                SUM(m.quantity * e.unit_price) as total_movement_value
              FROM equipments e
              LEFT JOIN movements m ON e.id = m.equipment_id
              WHERE DATE(m.created_at) BETWEEN :start_date AND :end_date
              GROUP BY e.id
              HAVING total_movements > 0
              ORDER BY total_movements DESC
              LIMIT 20";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar dados baseado no tipo de relatório
$overview_data = getOverviewReport($db);
$category_data = getCategoryReport($db);
$movement_data = getMovementReport($db, $start_date, $end_date, $category);
$detailed_movements = getDetailedMovementReport($db, $start_date, $end_date, $category);
$low_stock_data = getLowStockReport($db);
$most_moved_data = getMostMovedReport($db, $start_date, $end_date);

// Buscar categorias para filtro
$categories = $equipment->getCategories();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Sistema de Estoque</title>
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
            <a href="movements.php" class="nav-item">
                <i class="fas fa-exchange-alt"></i>
                Movimentações
            </a>
            <a href="reports.php" class="nav-item active">
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
            <h1>Relatórios do Sistema</h1>
            <div class="header-actions">
                <button class="btn btn-success" onclick="createBackup()" title="Fazer backup completo do banco de dados">
                    <i class="fas fa-database"></i>
                    Backup
                </button>
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
                <button class="btn btn-primary" onclick="exportReport()">
                    <i class="fas fa-download"></i>
                    Exportar
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="form-container" style="margin-bottom: 24px;">
            <form method="GET" class="report-filters">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="type">Tipo de Relatório</label>
                        <select id="type" name="type" onchange="this.form.submit()">
                            <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Visão Geral</option>
                            <option value="movements" <?php echo $report_type == 'movements' ? 'selected' : ''; ?>>Movimentações</option>
                            <option value="low_stock" <?php echo $report_type == 'low_stock' ? 'selected' : ''; ?>>Estoque Baixo</option>
                            <option value="category" <?php echo $report_type == 'category' ? 'selected' : ''; ?>>Por Categoria</option>
                            <option value="most_moved" <?php echo $report_type == 'most_moved' ? 'selected' : ''; ?>>Mais Movimentados</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Data Inicial</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>

                    <div class="form-group">
                        <label for="end_date">Data Final</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>

                    <div class="form-group">
                        <label for="category">Categoria</label>
                        <select id="category" name="category">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                        Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>

        <!-- Relatório de Visão Geral -->
        <?php if ($report_type == 'overview'): ?>
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($overview_data['total_items']); ?></h3>
                        <p>Tipos de Equipamentos</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($overview_data['total_quantity']); ?></h3>
                        <p>Quantidade Total</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>R$ <?php echo number_format($overview_data['total_value'], 2, ',', '.'); ?></h3>
                        <p>Valor Total do Estoque</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-red">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $overview_data['low_stock_items']; ?></h3>
                        <p>Itens com Estoque Baixo</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Resumo por Categoria</h3>
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="card-content">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th>Itens</th>
                                        <th>Quantidade</th>
                                        <th>Valor Total</th>
                                        <th>Estoque Baixo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_data as $cat): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cat['category']); ?></strong></td>
                                            <td><?php echo $cat['total_items']; ?></td>
                                            <td><?php echo $cat['total_quantity']; ?></td>
                                            <td>R$ <?php echo number_format($cat['total_value'], 2, ',', '.'); ?></td>
                                            <td>
                                                <?php if ($cat['low_stock_items'] > 0): ?>
                                                    <span class="quantity-badge low-stock"><?php echo $cat['low_stock_items']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-success">0</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Relatório de Movimentações -->
        <?php elseif ($report_type == 'movements'): ?>
            <div class="dashboard-stats">
                <?php 
                $total_entries = 0;
                $total_exits = 0;
                $total_value = 0;
                foreach ($movement_data as $mov) {
                    if ($mov['type'] == 'entrada') {
                        $total_entries = $mov['total_quantity'];
                    } else {
                        $total_exits = $mov['total_quantity'];
                    }
                    $total_value += $mov['total_value'];
                }
                ?>
                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_entries); ?></h3>
                        <p>Total de Entradas</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-red">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_exits); ?></h3>
                        <p>Total de Saídas</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_entries - $total_exits); ?></h3>
                        <p>Saldo Líquido</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>R$ <?php echo number_format($total_value, 2, ',', '.'); ?></h3>
                        <p>Valor Movimentado</p>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Equipamento</th>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Valor Unit.</th>
                            <th>Valor Total</th>
                            <th>Motivo</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailed_movements as $mov): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($mov['created_at'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($mov['equipment_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($mov['category']); ?></td>
                                <td>
                                    <span class="movement-type <?php echo $mov['type']; ?>">
                                        <i class="fas <?php echo $mov['type'] == 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                                        <?php echo ucfirst($mov['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo $mov['quantity']; ?></td>
                                <td>R$ <?php echo number_format($mov['unit_price'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($mov['movement_value'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($mov['reason']); ?></td>
                                <td><?php echo htmlspecialchars($mov['user_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <!-- Relatório de Estoque Baixo -->
        <?php elseif ($report_type == 'low_stock'): ?>
            <div class="alert alert-error" style="margin-bottom: 24px;">
                <h4><i class="fas fa-exclamation-triangle"></i> Atenção: Itens com Estoque Baixo</h4>
                <p>Os itens listados abaixo estão com estoque igual ou abaixo do mínimo recomendado.</p>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Equipamento</th>
                            <th>Categoria</th>
                            <th>Estoque Atual</th>
                            <th>Estoque Mínimo</th>
                            <th>Déficit</th>
                            <th>Valor Atual</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_data as $item): ?>
                            <tr>
                                <td>
                                    <div class="equipment-info">
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <small><?php echo htmlspecialchars($item['brand']); ?> <?php echo htmlspecialchars($item['model']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td>
                                    <span class="quantity-badge <?php echo $item['quantity'] == 0 ? 'low-stock' : ''; ?>">
                                        <?php echo $item['quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $item['min_stock']; ?></td>
                                <td>
                                    <span class="text-danger">
                                        <strong><?php echo $item['deficit']; ?></strong>
                                    </span>
                                </td>
                                <td>R$ <?php echo number_format($item['current_value'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($item['quantity'] == 0): ?>
                                        <span class="status-badge status-inativo">SEM ESTOQUE</span>
                                    <?php else: ?>
                                        <span class="status-badge status-manutencao">ESTOQUE BAIXO</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <!-- Relatório de Equipamentos Mais Movimentados -->
        <?php elseif ($report_type == 'most_moved'): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Equipamento</th>
                            <th>Categoria</th>
                            <th>Estoque Atual</th>
                            <th>Total Movimentações</th>
                            <th>Entradas</th>
                            <th>Saídas</th>
                            <th>Valor Movimentado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($most_moved_data as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td>
                                    <span class="quantity-badge">
                                        <?php echo $item['current_stock']; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo $item['total_movements']; ?></strong></td>
                                <td class="text-success"><?php echo $item['total_entries']; ?></td>
                                <td class="text-danger"><?php echo $item['total_exits']; ?></td>
                                <td>R$ <?php echo number_format($item['total_movement_value'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function exportReport() {
            const reportType = document.getElementById('type').value;
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const category = document.getElementById('category').value;
            
            let filename = `relatorio_${reportType}_${startDate}_${endDate}`;
            if (category) {
                filename += `_${category}`;
            }
            filename += '.csv';
            
            exportTableToCSV('.data-table', filename);
        }

        function createBackup() {
            if (confirm('Deseja fazer o backup completo do banco de dados?\n\nEste processo pode levar alguns minutos dependendo do tamanho dos dados.')) {
                showNotification('Iniciando backup do banco de dados...', 'info');
                
                // Criar um link temporário para download
                const link = document.createElement('a');
                link.href = 'backup.php';
                link.download = `backup_prefeitura_estoque_${new Date().toISOString().split('T')[0]}.sql`;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                setTimeout(() => {
                    showNotification('Backup concluído com sucesso!', 'success');
                }, 2000);
            }
        }

        // Auto-submit form when dates change
        document.getElementById('start_date').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('end_date').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('category').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>