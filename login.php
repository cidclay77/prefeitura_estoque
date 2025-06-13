<?php
require_once 'config/database.php';
require_once 'models/User.php';

session_start();

// Se já estiver logado, redirecionar para dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        if ($user->login($username, $password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['full_name'] = $user->full_name;
            $_SESSION['email'] = $user->email;
            $_SESSION['user_role'] = $user->role;
            
            header("Location: index.php");
            exit;
        } else {
            $error = 'Usuário ou senha inválidos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Estoque</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: var(--white);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 10px 10px -5px rgb(0 0 0 / 0.04);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-header .logo {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: var(--white);
            font-size: 32px;
        }
        
        .login-header h1 {
            color: var(--gray-900);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: var(--gray-600);
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
            font-size: 14px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            z-index: 1;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(59 130 246 / 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 8px;
        }
        
        .login-btn:hover {
            background: var(--primary-dark);
        }
        
        .error-message {
            background: #fef2f2;
            color: var(--danger-color);
            padding: 12px;
            border-radius: var(--border-radius);
            border: 1px solid #fecaca;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        
        .demo-info {
            background: var(--gray-50);
            padding: 16px;
            border-radius: var(--border-radius);
            margin-top: 24px;
            border: 1px solid var(--gray-200);
        }
        
        .demo-info h4 {
            color: var(--gray-800);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .demo-info .demo-user {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-200);
            font-size: 12px;
        }
        
        .demo-info .demo-user:last-child {
            border-bottom: none;
        }
        
        .demo-info .role-badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }
        
        .role-admin { background: #fef2f2; color: #dc2626; }
        .role-operator { background: #eff6ff; color: #2563eb; }
        .role-viewer { background: #f0fdf4; color: #16a34a; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-city"></i>
            </div>
            <h1>Sistema de Estoque</h1>
            <p>Prefeitura Municipal</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Usuário ou Email</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="Digite seu usuário ou email">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required 
                           placeholder="Digite sua senha">
                </div>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Entrar
            </button>
        </form>

        <div class="demo-info">
            <h4><i class="fas fa-info-circle"></i> Usuários de Demonstração</h4>
            
            <div class="demo-user">
                <div>
                    <strong>admin</strong><br>
                    <small>admin@prefeitura.gov.br</small>
                </div>
                <div>
                    <span class="role-badge role-admin">Administrador</span><br>
                    <small>Senha: admin123</small>
                </div>
            </div>
            
            <div class="demo-user">
                <div>
                    <strong>operador</strong><br>
                    <small>operador@prefeitura.gov.br</small>
                </div>
                <div>
                    <span class="role-badge role-operator">Operador</span><br>
                    <small>Senha: op123</small>
                </div>
            </div>
            
            <div class="demo-user">
                <div>
                    <strong>visualizador</strong><br>
                    <small>viewer@prefeitura.gov.br</small>
                </div>
                <div>
                    <span class="role-badge role-viewer">Visualizador</span><br>
                    <small>Senha: view123</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus no campo de usuário
        document.getElementById('username').focus();
        
        // Permitir login com Enter
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });
    </script>
</body>
</html>