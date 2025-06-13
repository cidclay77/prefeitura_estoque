<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>

<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-city"></i>
        <h3>Prefeitura</h3>
    </div>
    
    <div class="user-info">
        <div class="user-avatar">
            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
        </div>
        <div class="user-details">
            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
            <small><?php echo getRoleName($user['role']); ?></small>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        
        <a href="equipments.php" class="nav-item <?php echo $current_page == 'equipments.php' ? 'active' : ''; ?>">
            <i class="fas fa-laptop"></i>
            Equipamentos
        </a>
        
        <?php if (hasPermission('operador')): ?>
        <a href="movements.php" class="nav-item <?php echo $current_page == 'movements.php' ? 'active' : ''; ?>">
            <i class="fas fa-exchange-alt"></i>
            Movimentações
        </a>
        <?php endif; ?>
        
        <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            Relatórios
        </a>
        
        <?php if (hasPermission('administrador')): ?>
        <a href="users.php" class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            Usuários
        </a>
        
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            Configurações
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            Meu Perfil
        </a>
        <a href="logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            Sair
        </a>
    </div>
</div>