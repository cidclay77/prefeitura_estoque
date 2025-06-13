<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'models/User.php';

// Verificar se está logado
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$success_message = '';
$error_message = '';

// Processar atualização do perfil
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_profile') {
            try {
                // Verificar se email já existe (excluindo o próprio usuário)
                if ($user->emailExists($_POST['email'], $_SESSION['user_id'])) {
                    $error_message = "Email já está em uso por outro usuário.";
                } else {
                    $user->id = $_SESSION['user_id'];
                    $user->email = $_POST['email'];
                    $user->full_name = $_POST['full_name'];
                    $user->role = $_SESSION['user_role']; // Manter role atual
                    $user->status = 'ativo'; // Manter status ativo
                    
                    if ($user->update()) {
                        // Atualizar sessão
                        $_SESSION['full_name'] = $user->full_name;
                        $_SESSION['email'] = $user->email;
                        
                        $success_message = "Perfil atualizado com sucesso!";
                    } else {
                        $error_message = "Erro ao atualizar perfil.";
                    }
                }
            } catch (Exception $e) {
                $error_message = "Erro ao atualizar perfil: " . $e->getMessage();
            }
        } elseif ($_POST['action'] == 'change_password') {
            try {
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($new_password !== $confirm_password) {
                    $error_message = "A confirmação da senha não confere.";
                } else {
                    // Verificar senha atual
                    $user->id = $_SESSION['user_id'];
                    $user->readOne();
                    
                    // Buscar senha atual do banco
                    $query = "SELECT password FROM users WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $_SESSION['user_id']);
                    $stmt->execute();
                    $current_hash = $stmt->fetchColumn();
                    
                    if (password_verify($current_password, $current_hash)) {
                        if ($user->updatePassword($new_password)) {
                            $success_message = "Senha alterada com sucesso!";
                        } else {
                            $error_message = "Erro ao alterar senha.";
                        }
                    } else {
                        $error_message = "Senha atual incorreta.";
                    }
                }
            } catch (Exception $e) {
                $error_message = "Erro ao alterar senha: " . $e->getMessage();
            }
        }
    }
}

// Buscar dados do usuário atual
$user->id = $_SESSION['user_id'];
$user->readOne();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Sistema de Estoque</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .profile-header {
            background: var(--white);
            padding: 32px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 36px;
            font-weight: 600;
        }
        
        .profile-info h2 {
            color: var(--gray-900);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .profile-info p {
            color: var(--gray-600);
            margin-bottom: 4px;
        }
        
        .profile-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 16px;
        }
        
        .profile-tab {
            padding: 12px 20px;
            background: var(--gray-100);
            border: none;
            border-radius: var(--border-radius);
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .profile-tab:hover {
            background: var(--gray-200);
            color: var(--gray-800);
        }
        
        .profile-tab.active {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .profile-section {
            display: none;
        }
        
        .profile-section.active {
            display: block;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .info-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--shadow-sm);
        }
        
        .info-card h4 {
            color: var(--gray-700);
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .info-card .value {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Meu Perfil</h1>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Dashboard
                </button>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user->full_name, 0, 2)); ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user->full_name); ?></h2>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user->email); ?></p>
                <p><i class="fas fa-user-tag"></i> <?php echo getRoleName($user->role); ?></p>
                <p><i class="fas fa-clock"></i> Último login: 
                    <?php 
                    if ($user->last_login) {
                        echo date('d/m/Y H:i', strtotime($user->last_login));
                    } else {
                        echo 'Nunca';
                    }
                    ?>
                </p>
            </div>
        </div>

        <div class="profile-tabs">
            <button class="profile-tab active" onclick="showSection('info')">
                <i class="fas fa-info-circle"></i>
                Informações
            </button>
            <button class="profile-tab" onclick="showSection('edit')">
                <i class="fas fa-edit"></i>
                Editar Perfil
            </button>
            <button class="profile-tab" onclick="showSection('password')">
                <i class="fas fa-lock"></i>
                Alterar Senha
            </button>
        </div>

        <!-- Seção: Informações -->
        <div id="info" class="profile-section active">
            <div class="info-grid">
                <div class="info-card">
                    <h4>Nome de Usuário</h4>
                    <div class="value"><?php echo htmlspecialchars($user->username); ?></div>
                </div>
                
                <div class="info-card">
                    <h4>Função no Sistema</h4>
                    <div class="value"><?php echo getRoleName($user->role); ?></div>
                </div>
                
                <div class="info-card">
                    <h4>Status da Conta</h4>
                    <div class="value">
                        <span class="status-badge status-<?php echo $user->status; ?>">
                            <?php echo ucfirst($user->status); ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-card">
                    <h4>Membro Desde</h4>
                    <div class="value"><?php echo date('d/m/Y', strtotime($user->created_at)); ?></div>
                </div>
            </div>

            <div class="form-container">
                <h3 style="margin-bottom: 20px;">Permissões do Usuário</h3>
                
                <div class="alert alert-info">
                    <h4><i class="fas fa-shield-alt"></i> Suas Permissões</h4>
                    
                    <?php if ($user->role == 'administrador'): ?>
                        <p><i class="fas fa-check text-success"></i> Acesso total ao sistema</p>
                        <p><i class="fas fa-check text-success"></i> Gerenciar usuários</p>
                        <p><i class="fas fa-check text-success"></i> Configurações do sistema</p>
                        <p><i class="fas fa-check text-success"></i> Gerenciar equipamentos</p>
                        <p><i class="fas fa-check text-success"></i> Realizar movimentações</p>
                        <p><i class="fas fa-check text-success"></i> Visualizar relatórios</p>
                    <?php elseif ($user->role == 'operador'): ?>
                        <p><i class="fas fa-times text-danger"></i> Gerenciar usuários</p>
                        <p><i class="fas fa-times text-danger"></i> Configurações do sistema</p>
                        <p><i class="fas fa-check text-success"></i> Gerenciar equipamentos</p>
                        <p><i class="fas fa-check text-success"></i> Realizar movimentações</p>
                        <p><i class="fas fa-check text-success"></i> Visualizar relatórios</p>
                    <?php else: ?>
                        <p><i class="fas fa-times text-danger"></i> Gerenciar usuários</p>
                        <p><i class="fas fa-times text-danger"></i> Configurações do sistema</p>
                        <p><i class="fas fa-times text-danger"></i> Gerenciar equipamentos</p>
                        <p><i class="fas fa-times text-danger"></i> Realizar movimentações</p>
                        <p><i class="fas fa-check text-success"></i> Visualizar relatórios</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Seção: Editar Perfil -->
        <div id="edit" class="profile-section">
            <div class="form-container">
                <h3 style="margin-bottom: 20px;">Editar Informações Pessoais</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Nome Completo *</label>
                            <input type="text" id="full_name" name="full_name" required 
                                   value="<?php echo htmlspecialchars($user->full_name); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($user->email); ?>">
                        </div>

                        <div class="form-group">
                            <label for="username_display">Nome de Usuário</label>
                            <input type="text" id="username_display" value="<?php echo htmlspecialchars($user->username); ?>" 
                                   disabled style="background: var(--gray-100);">
                            <small class="form-help">O nome de usuário não pode ser alterado</small>
                        </div>

                        <div class="form-group">
                            <label for="role_display">Função</label>
                            <input type="text" id="role_display" value="<?php echo getRoleName($user->role); ?>" 
                                   disabled style="background: var(--gray-100);">
                            <small class="form-help">Apenas administradores podem alterar funções</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Seção: Alterar Senha -->
        <div id="password" class="profile-section">
            <div class="form-container">
                <h3 style="margin-bottom: 20px;">Alterar Senha</h3>
                
                <form method="POST" id="password-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="current_password">Senha Atual *</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Nova Senha *</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                            <small class="form-help">Mínimo 6 caracteres</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmar Nova Senha *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <h4><i class="fas fa-exclamation-triangle"></i> Importante</h4>
                        <p>Após alterar sua senha, você precisará fazer login novamente em todas as sessões ativas.</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i>
                            Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function showSection(sectionId) {
            // Esconder todas as seções
            document.querySelectorAll('.profile-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remover classe active de todos os botões
            document.querySelectorAll('.profile-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar seção selecionada
            document.getElementById(sectionId).classList.add('active');
            
            // Adicionar classe active ao botão clicado
            event.target.classList.add('active');
        }

        // Validação de confirmação de senha
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showNotification('A confirmação da senha não confere.', 'error');
                return false;
            }
        });

        // Validação em tempo real da confirmação de senha
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('A confirmação da senha não confere');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>