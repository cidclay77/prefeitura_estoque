<?php
require_once 'includes/auth.php';
requireLogin();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado - Sistema de Estoque</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .access-denied-container {
            background: var(--white);
            padding: 60px 40px;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .access-denied-icon {
            width: 120px;
            height: 120px;
            background: #fef2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--danger-color);
            font-size: 48px;
        }
        
        .access-denied-container h1 {
            color: var(--gray-900);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .access-denied-container p {
            color: var(--gray-600);
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        
        .user-info {
            background: var(--gray-50);
            padding: 16px;
            border-radius: var(--border-radius);
            margin-bottom: 32px;
            border: 1px solid var(--gray-200);
        }
        
        .user-info h3 {
            color: var(--gray-800);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .user-role {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: var(--gray-200);
            color: var(--gray-700);
        }
        
        .actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="access-denied-icon">
            <i class="fas fa-ban"></i>
        </div>
        
        <h1>Acesso Negado</h1>
        <p>Você não possui permissão para acessar esta página. Entre em contato com o administrador do sistema se acredita que isso é um erro.</p>
        
        <div class="user-info">
            <h3>Informações do Usuário</h3>
            <p><strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></p>
            <span class="user-role"><?php echo getRoleName($_SESSION['user_role']); ?></span>
        </div>
        
        <div class="actions">
            <button class="btn btn-secondary" onclick="history.back()">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </button>
            <button class="btn btn-primary" onclick="window.location.href='index.php'">
                <i class="fas fa-home"></i>
                Ir para Dashboard
            </button>
        </div>
    </div>
</body>
</html>