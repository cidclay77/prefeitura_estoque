<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'models/User.php';

// Verificar se é administrador
requireRole('administrador');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Processar ações
if ($_POST) {
    if ($action == 'add') {
        try {
            // Verificar se username já existe
            if ($user->usernameExists($_POST['username'])) {
                $error = "Nome de usuário já existe.";
            } elseif ($user->emailExists($_POST['email'])) {
                $error = "Email já está em uso.";
            } else {
                $user->username = $_POST['username'];
                $user->email = $_POST['email'];
                $user->password = $_POST['password'];
                $user->full_name = $_POST['full_name'];
                $user->role = $_POST['role'];
                $user->status = $_POST['status'];
                
                if ($user->create()) {
                    header("Location: users.php?success=1");
                    exit;
                } else {
                    $error = "Erro ao cadastrar usuário";
                }
            }
        } catch (Exception $e) {
            $error = "Erro ao cadastrar usuário: " . $e->getMessage();
        }
    } elseif ($action == 'edit') {
        try {
            // Verificar se username já existe (excluindo o próprio usuário)
            if ($user->usernameExists($_POST['username'], $id)) {
                $error = "Nome de usuário já existe.";
            } elseif ($user->emailExists($_POST['email'], $id)) {
                $error = "Email já está em uso.";
            } else {
                $user->id = $id;
                $user->username = $_POST['username'];
                $user->email = $_POST['email'];
                $user->full_name = $_POST['full_name'];
                $user->role = $_POST['role'];
                $user->status = $_POST['status'];
                
                if ($user->update()) {
                    // Se alterou a senha
                    if (!empty($_POST['password'])) {
                        $user->updatePassword($_POST['password']);
                    }
                    
                    header("Location: users.php?success=2");
                    exit;
                } else {
                    $error = "Erro ao atualizar usuário";
                }
            }
        } catch (Exception $e) {
            $error = "Erro ao atualizar usuário: " . $e->getMessage();
        }
    }
}

if ($action == 'delete' && $id) {
    // Não permitir deletar o próprio usuário
    if ($id == $_SESSION['user_id']) {
        $error = "Você não pode deletar seu próprio usuário.";
    } else {
        $user->id = $id;
        if ($user->delete()) {
            header("Location: users.php?success=3");
            exit;
        } else {
            $error = "Erro ao excluir usuário";
        }
    }
}

// Buscar dados para edição
if ($action == 'edit' && $id) {
    $user->id = $id;
    $user->readOne();
}

// Listar usuários
$stmt = $user->read();
$user_stats = $user->getUserStats();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Sistema de Estoque</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php if ($action == 'list'): ?>
            <div class="header">
                <h1>Gerenciar Usuários</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="window.location.href='users.php?action=add'">
                        <i class="fas fa-user-plus"></i>
                        Novo Usuário
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    switch($_GET['success']) {
                        case 1: echo "Usuário cadastrado com sucesso!"; break;
                        case 2: echo "Usuário atualizado com sucesso!"; break;
                        case 3: echo "Usuário excluído com sucesso!"; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Estatísticas dos usuários -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $user_stats['total_users']; ?></h3>
                        <p>Total de Usuários</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $user_stats['active_users']; ?></h3>
                        <p>Usuários Ativos</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-red">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $user_stats['admins']; ?></h3>
                        <p>Administradores</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $user_stats['operators']; ?></h3>
                        <p>Operadores</p>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Função</th>
                            <th>Status</th>
                            <th>Último Login</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <div class="equipment-info">
                                        <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                        <small>@<?php echo htmlspecialchars($row['username']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $row['role']; ?>">
                                        <?php echo getRoleName($row['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($row['last_login']) {
                                        echo date('d/m/Y H:i', strtotime($row['last_login']));
                                    } else {
                                        echo '<span class="text-gray">Nunca</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='users.php?action=edit&id=<?php echo $row['id']; ?>'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['full_name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="header">
                <h1><?php echo $action == 'add' ? 'Novo Usuário' : 'Editar Usuário'; ?></h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="window.location.href='users.php'">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </button>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" class="user-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Nome Completo *</label>
                            <input type="text" id="full_name" name="full_name" required 
                                   value="<?php echo isset($user->full_name) ? htmlspecialchars($user->full_name) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="username">Nome de Usuário *</label>
                            <input type="text" id="username" name="username" required 
                                   value="<?php echo isset($user->username) ? htmlspecialchars($user->username) : ''; ?>"
                                   pattern="[a-zA-Z0-9_]+" title="Apenas letras, números e underscore">
                            <small class="form-help">Apenas letras, números e underscore</small>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo isset($user->email) ? htmlspecialchars($user->email) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">Senha <?php echo $action == 'add' ? '*' : ''; ?></label>
                            <input type="password" id="password" name="password" 
                                   <?php echo $action == 'add' ? 'required' : ''; ?>
                                   minlength="6">
                            <small class="form-help">
                                <?php echo $action == 'add' ? 'Mínimo 6 caracteres' : 'Deixe em branco para manter a senha atual'; ?>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="role">Função *</label>
                            <select id="role" name="role" required>
                                <option value="">Selecione uma função</option>
                                <option value="visualizador" <?php echo (isset($user->role) && $user->role == 'visualizador') ? 'selected' : ''; ?>>
                                    Visualizador
                                </option>
                                <option value="operador" <?php echo (isset($user->role) && $user->role == 'operador') ? 'selected' : ''; ?>>
                                    Operador
                                </option>
                                <option value="administrador" <?php echo (isset($user->role) && $user->role == 'administrador') ? 'selected' : ''; ?>>
                                    Administrador
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="ativo" <?php echo (isset($user->status) && $user->status == 'ativo') ? 'selected' : ''; ?>>
                                    Ativo
                                </option>
                                <option value="inativo" <?php echo (isset($user->status) && $user->status == 'inativo') ? 'selected' : ''; ?>>
                                    Inativo
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h4><i class="fas fa-info-circle"></i> Níveis de Acesso</h4>
                        <p><strong>Visualizador:</strong> Pode apenas visualizar dados do sistema</p>
                        <p><strong>Operador:</strong> Pode gerenciar equipamentos e movimentações</p>
                        <p><strong>Administrador:</strong> Acesso total ao sistema, incluindo usuários e configurações</p>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='users.php'">
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
        function confirmDelete(id, name) {
            if (confirm(`Tem certeza que deseja excluir o usuário "${name}"?\n\nEsta ação não pode ser desfeita.`)) {
                window.location.href = 'users.php?action=delete&id=' + id;
            }
        }

        // Validação em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            
            if (usernameInput) {
                usernameInput.addEventListener('input', function() {
                    const value = this.value;
                    const pattern = /^[a-zA-Z0-9_]+$/;
                    
                    if (value && !pattern.test(value)) {
                        this.setCustomValidity('Apenas letras, números e underscore são permitidos');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>