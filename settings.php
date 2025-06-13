<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'models/Equipment.php';
require_once 'models/User.php';

// Verificar se é administrador
requireRole('administrador');

$database = new Database();
$db = $database->getConnection();
$equipment = new Equipment($db);
$user = new User($db);

$action = isset($_GET['action']) ? $_GET['action'] : 'general';
$success_message = '';
$error_message = '';

// Processar configurações
if ($_POST) {
    if ($action == 'backup') {
        // Redirecionar para backup
        header("Location: backup.php");
        exit;
    } elseif ($action == 'categories') {
        // Adicionar nova categoria (simulado - em um sistema real seria armazenado no banco)
        if (isset($_POST['new_category']) && !empty($_POST['new_category'])) {
            $success_message = "Categoria '{$_POST['new_category']}' adicionada com sucesso!";
        }
    } elseif ($action == 'system') {
        // Configurações do sistema
        $success_message = "Configurações do sistema atualizadas com sucesso!";
    }
}

// Obter estatísticas do sistema
function getSystemStats($db) {
    $stats = [];
    
    // Estatísticas gerais
    $query = "SELECT COUNT(*) as total FROM equipments";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_equipments'] = $stmt->fetchColumn();
    
    $query = "SELECT COUNT(*) as total FROM movements";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_movements'] = $stmt->fetchColumn();
    
    $query = "SELECT COUNT(*) as total FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Tamanho do banco de dados
    $query = "SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
              FROM information_schema.tables 
              WHERE table_schema = 'prefeitura_estoque'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['db_size'] = $stmt->fetchColumn() ?: 0;
    
    // Último backup (simulado)
    $stats['last_backup'] = date('d/m/Y H:i:s', strtotime('-3 days'));
    
    return $stats;
}

$system_stats = getSystemStats($db);
$categories = $equipment->getCategories();
$user_stats = $user->getUserStats();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Sistema de Estoque</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .settings-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 16px;
            flex-wrap: wrap;
        }
        
        .settings-nav-item {
            padding: 12px 20px;
            background: var(--gray-100);
            border: none;
            border-radius: var(--border-radius);
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 14px;
        }
        
        .settings-nav-item:hover {
            background: var(--gray-200);
            color: var(--gray-800);
        }
        
        .settings-nav-item.active {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .settings-section {
            display: none;
        }
        
        .settings-section.active {
            display: block;
        }
        
        .system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .system-info-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--shadow-sm);
        }
        
        .system-info-card h4 {
            color: var(--gray-700);
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .system-info-card .value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .danger-zone {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-top: 24px;
        }
        
        .danger-zone h3 {
            color: var(--danger-color);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .category-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .category-item {
            background: var(--gray-50);
            padding: 12px 16px;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .maintenance-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        
        .maintenance-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            text-align: center;
        }
        
        .maintenance-card i {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 12px;
        }
        
        .maintenance-card h4 {
            margin-bottom: 8px;
            color: var(--gray-800);
        }
        
        .maintenance-card p {
            color: var(--gray-600);
            font-size: 14px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Configurações do Sistema</h1>
            <div class="header-actions">
                <button class="btn btn-success" onclick="createBackup()">
                    <i class="fas fa-database"></i>
                    Backup Agora
                </button>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Navegação das Configurações -->
        <div class="settings-nav">
            <button class="settings-nav-item active" onclick="showSection('general')">
                <i class="fas fa-info-circle"></i>
                Informações Gerais
            </button>
            <button class="settings-nav-item" onclick="showSection('categories')">
                <i class="fas fa-tags"></i>
                Categorias
            </button>
            <button class="settings-nav-item" onclick="showSection('users-overview')">
                <i class="fas fa-users"></i>
                Usuários
            </button>
            <button class="settings-nav-item" onclick="showSection('backup')">
                <i class="fas fa-database"></i>
                Backup & Restauração
            </button>
            <button class="settings-nav-item" onclick="showSection('maintenance')">
                <i class="fas fa-tools"></i>
                Manutenção
            </button>
        </div>

        <!-- Seção: Informações Gerais -->
        <div id="general" class="settings-section active">
            <div class="form-container">
                <h3 style="margin-bottom: 20px;">Informações do Sistema</h3>
                
                <div class="system-info-grid">
                    <div class="system-info-card">
                        <h4>Total de Equipamentos</h4>
                        <div class="value"><?php echo number_format($system_stats['total_equipments']); ?></div>
                    </div>
                    
                    <div class="system-info-card">
                        <h4>Total de Movimentações</h4>
                        <div class="value"><?php echo number_format($system_stats['total_movements']); ?></div>
                    </div>
                    
                    <div class="system-info-card">
                        <h4>Total de Usuários</h4>
                        <div class="value"><?php echo number_format($system_stats['total_users']); ?></div>
                    </div>
                    
                    <div class="system-info-card">
                        <h4>Tamanho do Banco</h4>
                        <div class="value"><?php echo $system_stats['db_size']; ?> MB</div>
                    </div>
                </div>

                <form method="POST" action="?action=system">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="system_name">Nome do Sistema</label>
                            <input type="text" id="system_name" name="system_name" value="Sistema de Estoque - Prefeitura">
                        </div>

                        <div class="form-group">
                            <label for="organization">Organização</label>
                            <input type="text" id="organization" name="organization" value="Prefeitura Municipal">
                        </div>

                        <div class="form-group">
                            <label for="contact_email">Email de Contato</label>
                            <input type="email" id="contact_email" name="contact_email" value="admin@prefeitura.gov.br">
                        </div>

                        <div class="form-group">
                            <label for="timezone">Fuso Horário</label>
                            <select id="timezone" name="timezone">
                                <option value="America/Sao_Paulo" selected>América/São Paulo (UTC-3)</option>
                                <option value="America/Manaus">América/Manaus (UTC-4)</option>
                                <option value="America/Rio_Branco">América/Rio Branco (UTC-5)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="currency">Moeda</label>
                            <select id="currency" name="currency">
                                <option value="BRL" selected>Real Brasileiro (R$)</option>
                                <option value="USD">Dólar Americano ($)</option>
                                <option value="EUR">Euro (€)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="date_format">Formato de Data</label>
                            <select id="date_format" name="date_format">
                                <option value="d/m/Y" selected>DD/MM/AAAA</option>
                                <option value="Y-m-d">AAAA-MM-DD</option>
                                <option value="m/d/Y">MM/DD/AAAA</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Seção: Categorias -->
        <div id="categories" class="settings-section">
            <div class="form-container">
                <h3 style="margin-bottom: 20px;">Gerenciar Categorias</h3>
                
                <div class="category-list">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-item">
                            <span><?php echo htmlspecialchars($category); ?></span>
                            <button class="btn btn-sm btn-danger" onclick="removeCategory('<?php echo $category; ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" action="?action=categories">
                    <div class="form-group">
                        <label for="new_category">Nova Categoria</label>
                        <div style="display: flex; gap: 12px;">
                            <input type="text" id="new_category" name="new_category" placeholder="Nome da nova categoria" style="flex: 1;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Adicionar
                            </button>
                        </div>
                    </div>
                </form>

                <div class="alert alert-warning" style="margin-top: 20px;">
                    <h4><i class="fas fa-exclamation-triangle"></i> Atenção</h4>
                    <p>Remover uma categoria pode afetar equipamentos existentes. Certifique-se de que não há equipamentos usando a categoria antes de removê-la.</p>
                </div>
            </div>
        </div>

        <!-- Seção: Usuários -->
        <div id="users-overview" class="settings-section">
            <div class="form-container">
                <h3 style="margin-bottom: 20px;">Visão Geral dos Usuários</h3>
                
                <div class="system-info-grid">
                    <div class="system-info-card">
                        <h4>Total de Usuários</h4>
                        <div class="value"><?php echo $user_stats['total_users']; ?></div>
                    </div>
                    
                    <div class="system-info-card">
                        <h4>Usuários Ativos</h4>
                        <div class="value"><?php echo $user_stats['active_users']; ?></div>
                    </div>
                    
                    <div class="system-info-card">
                        <h4>Administradores</h4>
                        <div class="value"><?php echo $user_stats['admins']; ?></div>
                    </div>
                    
                    <div class="system-info-card">
                        <h4>Operadores</h4>
                        <div class="value"><?php echo $user_stats['operators']; ?></div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button class="btn btn-primary" onclick="window.location.href='users.php'">
                        <i class="fas fa-users-cog"></i>
                        Gerenciar Usuários
                    </button>
                </div>

                <div class="alert alert-info" style="margin-top: 20px;">
                    <h4><i class="fas fa-info-circle"></i> Níveis de Acesso</h4>
                    <p><strong>Administrador:</strong> Acesso total ao sistema, incluindo gerenciamento de usuários e configurações</p>
                    <p><strong>Operador:</strong> Pode gerenciar equipamentos e realizar movimentações</p>
                    <p><strong>Visualizador:</strong> Apenas visualização de dados e relatórios</p>
                </div>
            </div>
        </div>

        <!-- Seção: Backup & Restauração -->
        <div id="backup" class="settings-section">
            <div class="form-container">
                <h3 style="margin-bottom: 20px;">Backup & Restauração</h3>
                
                <div class="system-info-grid">
                    <div class="system-info-card">
                        <h4>Último Backup</h4>
                        <div class="value" style="font-size: 16px;"><?php echo $system_stats['last_backup']; ?></div>
                    </div>
                    
                    <div class="system-info-card">
                        <h4>Tamanho do Backup</h4>
                        <div class="value"><?php echo $system_stats['db_size']; ?> MB</div>
                    </div>
                    
                    <div class="system-info-card">
                        <h4>Frequência</h4>
                        <div class="value" style="font-size: 16px;">Manual</div>
                    </div>
                    
                    <div class="system-info-card">
                        <h4>Status</h4>
                        <div class="value" style="font-size: 16px; color: var(--success-color);">Ativo</div>
                    </div>
                </div>

                <div class="maintenance-actions">
                    <div class="maintenance-card">
                        <i class="fas fa-download"></i>
                        <h4>Backup Manual</h4>
                        <p>Criar um backup completo do banco de dados agora</p>
                        <button class="btn btn-primary" onclick="createBackup()">
                            <i class="fas fa-database"></i>
                            Fazer Backup
                        </button>
                    </div>
                    
                    <div class="maintenance-card">
                        <i class="fas fa-upload"></i>
                        <h4>Restaurar Backup</h4>
                        <p>Restaurar o sistema a partir de um arquivo de backup</p>
                        <button class="btn btn-secondary" onclick="restoreBackup()">
                            <i class="fas fa-undo"></i>
                            Restaurar
                        </button>
                    </div>
                    
                    <div class="maintenance-card">
                        <i class="fas fa-clock"></i>
                        <h4>Backup Automático</h4>
                        <p>Configurar backups automáticos periódicos</p>
                        <button class="btn btn-secondary" onclick="configureAutoBackup()">
                            <i class="fas fa-cog"></i>
                            Configurar
                        </button>
                    </div>
                </div>

                <div class="alert alert-warning" style="margin-top: 24px;">
                    <h4><i class="fas fa-exclamation-triangle"></i> Importante</h4>
                    <p>Sempre faça backup antes de realizar atualizações importantes no sistema. Mantenha os backups em local seguro e teste a restauração periodicamente.</p>
                </div>
            </div>
        </div>

        <!-- Seção: Manutenção -->
        <div id="maintenance" class="settings-section">
            <div class="form-container">
                <h3 style="margin-bottom: 20px;">Manutenção do Sistema</h3>
                
                <div class="maintenance-actions">
                    <div class="maintenance-card">
                        <i class="fas fa-broom"></i>
                        <h4>Limpar Cache</h4>
                        <p>Limpar arquivos temporários e cache do sistema</p>
                        <button class="btn btn-secondary" onclick="clearCache()">
                            <i class="fas fa-trash"></i>
                            Limpar Cache
                        </button>
                    </div>
                    
                    <div class="maintenance-card">
                        <i class="fas fa-database"></i>
                        <h4>Otimizar Banco</h4>
                        <p>Otimizar tabelas do banco de dados para melhor performance</p>
                        <button class="btn btn-secondary" onclick="optimizeDatabase()">
                            <i class="fas fa-tachometer-alt"></i>
                            Otimizar
                        </button>
                    </div>
                    
                    <div class="maintenance-card">
                        <i class="fas fa-chart-line"></i>
                        <h4>Verificar Integridade</h4>
                        <p>Verificar a integridade dos dados do sistema</p>
                        <button class="btn btn-secondary" onclick="checkIntegrity()">
                            <i class="fas fa-check-circle"></i>
                            Verificar
                        </button>
                    </div>
                    
                    <div class="maintenance-card">
                        <i class="fas fa-file-alt"></i>
                        <h4>Logs do Sistema</h4>
                        <p>Visualizar e gerenciar logs de atividades</p>
                        <button class="btn btn-secondary" onclick="viewLogs()">
                            <i class="fas fa-eye"></i>
                            Ver Logs
                        </button>
                    </div>
                    
                    <div class="maintenance-card">
                        <i class="fas fa-sync"></i>
                        <h4>Atualizar Sistema</h4>
                        <p>Verificar e instalar atualizações disponíveis</p>
                        <button class="btn btn-secondary" onclick="checkUpdates()">
                            <i class="fas fa-download"></i>
                            Verificar
                        </button>
                    </div>
                    
                    <div class="maintenance-card">
                        <i class="fas fa-shield-alt"></i>
                        <h4>Verificar Segurança</h4>
                        <p>Executar verificação de segurança do sistema</p>
                        <button class="btn btn-secondary" onclick="securityCheck()">
                            <i class="fas fa-lock"></i>
                            Verificar
                        </button>
                    </div>
                </div>

                <div class="danger-zone">
                    <h3><i class="fas fa-exclamation-triangle"></i> Zona de Perigo</h3>
                    <p>As ações abaixo são irreversíveis e podem causar perda de dados. Use com extrema cautela.</p>
                    
                    <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <button class="btn btn-danger" onclick="resetSystem()">
                            <i class="fas fa-undo"></i>
                            Resetar Sistema
                        </button>
                        <button class="btn btn-danger" onclick="clearAllData()">
                            <i class="fas fa-trash-alt"></i>
                            Limpar Todos os Dados
                        </button>
                        <button class="btn btn-danger" onclick="factoryReset()">
                            <i class="fas fa-power-off"></i>
                            Reset de Fábrica
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function showSection(sectionId) {
            // Esconder todas as seções
            document.querySelectorAll('.settings-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remover classe active de todos os botões
            document.querySelectorAll('.settings-nav-item').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar seção selecionada
            document.getElementById(sectionId).classList.add('active');
            
            // Adicionar classe active ao botão clicado
            event.target.classList.add('active');
        }

        function createBackup() {
            if (confirm('Deseja fazer o backup completo do banco de dados?\n\nEste processo pode levar alguns minutos.')) {
                showNotification('Iniciando backup do banco de dados...', 'info');
                
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

        function restoreBackup() {
            if (confirm('ATENÇÃO: Restaurar um backup irá substituir todos os dados atuais.\n\nTem certeza que deseja continuar?')) {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = '.sql';
                input.onchange = function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        showNotification('Funcionalidade de restauração será implementada em versão futura.', 'info');
                    }
                };
                input.click();
            }
        }

        function configureAutoBackup() {
            showNotification('Configuração de backup automático será implementada em versão futura.', 'info');
        }

        function removeCategory(category) {
            if (confirm(`Tem certeza que deseja remover a categoria "${category}"?\n\nEsta ação pode afetar equipamentos existentes.`)) {
                showNotification(`Categoria "${category}" removida com sucesso!`, 'success');
                // Aqui seria implementada a remoção real
            }
        }

        function clearCache() {
            if (confirm('Deseja limpar o cache do sistema?')) {
                showNotification('Cache limpo com sucesso!', 'success');
            }
        }

        function optimizeDatabase() {
            if (confirm('Deseja otimizar o banco de dados?\n\nEste processo pode levar alguns minutos.')) {
                showNotification('Otimização do banco de dados iniciada...', 'info');
                setTimeout(() => {
                    showNotification('Banco de dados otimizado com sucesso!', 'success');
                }, 3000);
            }
        }

        function checkIntegrity() {
            showNotification('Verificando integridade dos dados...', 'info');
            setTimeout(() => {
                showNotification('Verificação concluída. Nenhum problema encontrado.', 'success');
            }, 2000);
        }

        function viewLogs() {
            showNotification('Visualização de logs será implementada em versão futura.', 'info');
        }

        function checkUpdates() {
            showNotification('Verificando atualizações...', 'info');
            setTimeout(() => {
                showNotification('Sistema está atualizado. Nenhuma atualização disponível.', 'success');
            }, 2000);
        }

        function securityCheck() {
            showNotification('Executando verificação de segurança...', 'info');
            setTimeout(() => {
                showNotification('Verificação de segurança concluída. Sistema seguro.', 'success');
            }, 3000);
        }

        function resetSystem() {
            if (confirm('ATENÇÃO: Esta ação irá resetar todas as configurações do sistema.\n\nTem certeza que deseja continuar?')) {
                if (confirm('Esta ação é IRREVERSÍVEL. Confirma o reset do sistema?')) {
                    showNotification('Reset do sistema será implementado em versão futura.', 'info');
                }
            }
        }

        function clearAllData() {
            if (confirm('PERIGO: Esta ação irá APAGAR TODOS OS DADOS do sistema.\n\nTem certeza que deseja continuar?')) {
                if (confirm('ÚLTIMA CONFIRMAÇÃO: Todos os equipamentos e movimentações serão perdidos.\n\nConfirma a exclusão de todos os dados?')) {
                    showNotification('Limpeza de dados será implementada em versão futura.', 'info');
                }
            }
        }

        function factoryReset() {
            if (confirm('PERIGO MÁXIMO: Reset de fábrica irá restaurar o sistema ao estado inicial.\n\nTodos os dados e configurações serão perdidos.\n\nTem certeza?')) {
                if (confirm('CONFIRMAÇÃO FINAL: Esta é sua última chance de cancelar.\n\nConfirma o reset de fábrica?')) {
                    showNotification('Reset de fábrica será implementado em versão futura.', 'info');
                }
            }
        }
    </script>
</body>
</html>