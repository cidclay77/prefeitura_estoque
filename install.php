<?php
require_once 'config/database.php';

// Criar database e tabelas
$database = new Database();
$conn = $database->createDatabase();

if ($conn) {
    try {
        // Criar tabela de usuários
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('administrador', 'operador', 'visualizador') DEFAULT 'visualizador',
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        echo "Tabela 'users' criada com sucesso.<br>";
        
        // Criar tabela de equipamentos
        $sql = "CREATE TABLE IF NOT EXISTS equipments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100) NOT NULL,
            brand VARCHAR(100),
            model VARCHAR(100),
            serial_number VARCHAR(100),
            quantity INT NOT NULL DEFAULT 0,
            min_stock INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2),
            supplier VARCHAR(255),
            status ENUM('ativo', 'inativo', 'manutencao') DEFAULT 'ativo',
            created_by INT,
            updated_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_status (status),
            INDEX idx_quantity (quantity),
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        echo "Tabela 'equipments' criada com sucesso.<br>";
        
        // Criar tabela de movimentações
        $sql = "CREATE TABLE IF NOT EXISTS movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            equipment_id INT NOT NULL,
            type ENUM('entrada', 'saida') NOT NULL,
            quantity INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_equipment_id (equipment_id),
            INDEX idx_type (type),
            INDEX idx_created_at (created_at),
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        echo "Tabela 'movements' criada com sucesso.<br>";
        
        // Criar tabela de logs de auditoria
        $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            table_name VARCHAR(50) NOT NULL,
            record_id INT,
            old_values JSON,
            new_values JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_table_name (table_name),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        echo "Tabela 'audit_logs' criada com sucesso.<br>";
        
        // Inserir usuários padrão
        insertDefaultUsers($conn);
        
        // Inserir dados de exemplo
        insertSampleData($conn);
        
        echo "<br><strong>Instalação concluída com sucesso!</strong><br>";
        echo "<a href='login.php' style='color: #1e40af; text-decoration: none; font-weight: bold;'>Ir para o Login</a>";
        
    } catch(PDOException $e) {
        echo "Erro ao criar tabelas: " . $e->getMessage();
    }
} else {
    echo "Erro ao conectar com o banco de dados.";
}

function insertDefaultUsers($conn) {
    try {
        // Verificar se já existem usuários
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "Usuários padrão já existem.<br>";
            return;
        }
        
        // Inserir usuários padrão
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@prefeitura.gov.br',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'full_name' => 'Administrador do Sistema',
                'role' => 'administrador'
            ],
            [
                'username' => 'operador',
                'email' => 'operador@prefeitura.gov.br',
                'password' => password_hash('op123', PASSWORD_DEFAULT),
                'full_name' => 'João Silva',
                'role' => 'operador'
            ],
            [
                'username' => 'visualizador',
                'email' => 'viewer@prefeitura.gov.br',
                'password' => password_hash('view123', PASSWORD_DEFAULT),
                'full_name' => 'Maria Santos',
                'role' => 'visualizador'
            ]
        ];
        
        $sql = "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        foreach ($users as $user) {
            $stmt->execute([
                $user['username'],
                $user['email'],
                $user['password'],
                $user['full_name'],
                $user['role']
            ]);
        }
        
        echo "Usuários padrão inseridos com sucesso.<br>";
        
    } catch(PDOException $e) {
        echo "Erro ao inserir usuários padrão: " . $e->getMessage();
    }
}

function insertSampleData($conn) {
    try {
        // Verificar se já existem dados
        $stmt = $conn->prepare("SELECT COUNT(*) FROM equipments");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "Dados de exemplo já existem.<br>";
            return;
        }
        
        // Buscar ID do usuário admin para usar como created_by
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin_id = $stmt->fetchColumn();
        
        // Inserir equipamentos de exemplo
        $equipments = [
            [
                'name' => 'Computador Dell OptiPlex',
                'description' => 'Computador desktop para uso administrativo',
                'category' => 'Computador',
                'brand' => 'Dell',
                'model' => 'OptiPlex 3080',
                'serial_number' => 'DL001',
                'quantity' => 15,
                'min_stock' => 3,
                'unit_price' => 2500.00,
                'supplier' => 'Dell Tecnologia',
                'created_by' => $admin_id
            ],
            [
                'name' => 'Monitor Samsung 24"',
                'description' => 'Monitor LED 24 polegadas Full HD',
                'category' => 'Monitor',
                'brand' => 'Samsung',
                'model' => 'F24T450FQR',
                'serial_number' => 'SM001',
                'quantity' => 8,
                'min_stock' => 5,
                'unit_price' => 800.00,
                'supplier' => 'Samsung Brasil',
                'created_by' => $admin_id
            ],
            [
                'name' => 'Impressora HP LaserJet',
                'description' => 'Impressora laser monocromática',
                'category' => 'Impressora',
                'brand' => 'HP',
                'model' => 'LaserJet Pro M404dn',
                'serial_number' => 'HP001',
                'quantity' => 2,
                'min_stock' => 1,
                'unit_price' => 1200.00,
                'supplier' => 'HP Brasil',
                'created_by' => $admin_id
            ],
            [
                'name' => 'Mouse Óptico Logitech',
                'description' => 'Mouse óptico com fio USB',
                'category' => 'Mouse',
                'brand' => 'Logitech',
                'model' => 'B100',
                'serial_number' => 'LG001',
                'quantity' => 25,
                'min_stock' => 10,
                'unit_price' => 35.00,
                'supplier' => 'Logitech Brasil',
                'created_by' => $admin_id
            ],
            [
                'name' => 'Teclado ABNT2 Multilaser',
                'description' => 'Teclado padrão ABNT2 com fio USB',
                'category' => 'Teclado',
                'brand' => 'Multilaser',
                'model' => 'TC007',
                'serial_number' => 'ML001',
                'quantity' => 20,
                'min_stock' => 8,
                'unit_price' => 45.00,
                'supplier' => 'Multilaser',
                'created_by' => $admin_id
            ],
            [
                'name' => 'Cabo HDMI 2 metros',
                'description' => 'Cabo HDMI 2.0 alta velocidade',
                'category' => 'Cabo',
                'brand' => 'Diversos',
                'model' => 'HDMI 2.0',
                'serial_number' => 'CB001',
                'quantity' => 1,
                'min_stock' => 5,
                'unit_price' => 25.00,
                'supplier' => 'Fornecedor Local',
                'created_by' => $admin_id
            ],
            [
                'name' => 'Roteador TP-Link',
                'description' => 'Roteador wireless dual band AC1200',
                'category' => 'Roteador',
                'brand' => 'TP-Link',
                'model' => 'Archer C6',
                'serial_number' => 'TP001',
                'quantity' => 0,
                'min_stock' => 2,
                'unit_price' => 180.00,
                'supplier' => 'TP-Link Brasil',
                'created_by' => $admin_id
            ]
        ];
        
        $sql = "INSERT INTO equipments (name, description, category, brand, model, serial_number, quantity, min_stock, unit_price, supplier, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        foreach ($equipments as $equipment) {
            $stmt->execute([
                $equipment['name'],
                $equipment['description'],
                $equipment['category'],
                $equipment['brand'],
                $equipment['model'],
                $equipment['serial_number'],
                $equipment['quantity'],
                $equipment['min_stock'],
                $equipment['unit_price'],
                $equipment['supplier'],
                $equipment['created_by']
            ]);
        }
        
        echo "Dados de exemplo inseridos com sucesso.<br>";
        
        // Inserir algumas movimentações de exemplo
        $movements = [
            [1, 'entrada', 20, 'Compra inicial', 'Administrador', 'Compra para montagem do laboratório', $admin_id],
            [2, 'entrada', 15, 'Compra inicial', 'Administrador', 'Monitores para as estações de trabalho', $admin_id],
            [1, 'saida', 5, 'Distribuição', 'João Silva', 'Distribuídos para o setor financeiro', $admin_id],
            [2, 'saida', 7, 'Distribuição', 'Maria Santos', 'Instalados nas salas de reunião', $admin_id]
        ];
        
        $sql = "INSERT INTO movements (equipment_id, type, quantity, reason, user_name, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        foreach ($movements as $movement) {
            $stmt->execute($movement);
        }
        
        echo "Movimentações de exemplo inseridas com sucesso.<br>";
        
    } catch(PDOException $e) {
        echo "Erro ao inserir dados de exemplo: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema de Estoque Prefeitura</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1e40af;
            text-align: center;
            margin-bottom: 30px;
        }
        .info {
            background: #e0f2fe;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #1e40af;
        }
        .result {
            background: #f0f8f0;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #059669;
            font-family: monospace;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #1e40af;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        a:hover {
            background: #1e3a8a;
        }
        .users-info {
            background: #fff7ed;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #ea580c;
        }
        .user-item {
            margin: 10px 0;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border: 1px solid #fed7aa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏛️ Sistema de Estoque - Prefeitura</h1>
        
        <div class="info">
            <h3>🚀 Instalação do Sistema com Autenticação</h3>
            <p>Este script irá criar automaticamente:</p>
            <ul>
                <li>Banco de dados <code>prefeitura_estoque</code></li>
                <li>Tabelas necessárias para o sistema (users, equipments, movements, audit_logs)</li>
                <li>Usuários padrão com diferentes níveis de acesso</li>
                <li>Dados de exemplo para demonstração</li>
            </ul>
            <p><strong>Nota:</strong> Certifique-se de que o MySQL está rodando e as credenciais estão corretas no arquivo <code>config/database.php</code></p>
        </div>
        
        <div class="result">
            <h3>📋 Resultado da Instalação:</h3>
            <?php // O resultado da instalação aparece aqui ?>
        </div>
        
        <div class="users-info">
            <h3>👥 Usuários Criados</h3>
            <p>Os seguintes usuários foram criados para demonstração:</p>
            
            <div class="user-item">
                <strong>Administrador</strong><br>
                <strong>Usuário:</strong> admin | <strong>Email:</strong> admin@prefeitura.gov.br | <strong>Senha:</strong> admin123<br>
                <em>Acesso total ao sistema</em>
            </div>
            
            <div class="user-item">
                <strong>Operador</strong><br>
                <strong>Usuário:</strong> operador | <strong>Email:</strong> operador@prefeitura.gov.br | <strong>Senha:</strong> op123<br>
                <em>Pode gerenciar equipamentos e movimentações</em>
            </div>
            
            <div class="user-item">
                <strong>Visualizador</strong><br>
                <strong>Usuário:</strong> visualizador | <strong>Email:</strong> viewer@prefeitura.gov.br | <strong>Senha:</strong> view123<br>
                <em>Apenas visualização de dados</em>
            </div>
        </div>
    </div>
</body>
</html>