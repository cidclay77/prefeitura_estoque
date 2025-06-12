<?php
require_once 'config/database.php';

// Criar database e tabelas
$database = new Database();
$conn = $database->createDatabase();

if ($conn) {
    try {
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_status (status),
            INDEX idx_quantity (quantity)
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,
            INDEX idx_equipment_id (equipment_id),
            INDEX idx_type (type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        echo "Tabela 'movements' criada com sucesso.<br>";
        
        // Inserir dados de exemplo
        insertSampleData($conn);
        
        echo "<br><strong>Instalação concluída com sucesso!</strong><br>";
        echo "<a href='index.php' style='color: #1e40af; text-decoration: none; font-weight: bold;'>Ir para o Sistema</a>";
        
    } catch(PDOException $e) {
        echo "Erro ao criar tabelas: " . $e->getMessage();
    }
} else {
    echo "Erro ao conectar com o banco de dados.";
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
                'supplier' => 'Dell Tecnologia'
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
                'supplier' => 'Samsung Brasil'
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
                'supplier' => 'HP Brasil'
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
                'supplier' => 'Logitech Brasil'
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
                'supplier' => 'Multilaser'
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
                'supplier' => 'Fornecedor Local'
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
                'supplier' => 'TP-Link Brasil'
            ]
        ];
        
        $sql = "INSERT INTO equipments (name, description, category, brand, model, serial_number, quantity, min_stock, unit_price, supplier) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
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
                $equipment['supplier']
            ]);
        }
        
        echo "Dados de exemplo inseridos com sucesso.<br>";
        
        // Inserir algumas movimentações de exemplo
        $movements = [
            [1, 'entrada', 20, 'Compra inicial', 'Administrador', 'Compra para montagem do laboratório'],
            [2, 'entrada', 15, 'Compra inicial', 'Administrador', 'Monitores para as estações de trabalho'],
            [1, 'saida', 5, 'Distribuição', 'João Silva', 'Distribuídos para o setor financeiro'],
            [2, 'saida', 7, 'Distribuição', 'Maria Santos', 'Instalados nas salas de reunião']
        ];
        
        $sql = "INSERT INTO movements (equipment_id, type, quantity, reason, user_name, notes) VALUES (?, ?, ?, ?, ?, ?)";
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🏛️ Sistema de Estoque - Prefeitura</h1>
        
        <div class="info">
            <h3>🚀 Instalação do Sistema</h3>
            <p>Este script irá criar automaticamente:</p>
            <ul>
                <li>Banco de dados <code>prefeitura_estoque</code></li>
                <li>Tabelas necessárias para o sistema</li>
                <li>Dados de exemplo para demonstração</li>
            </ul>
            <p><strong>Nota:</strong> Certifique-se de que o MySQL está rodando e as credenciais estão corretas no arquivo <code>config/database.php</code></p>
        </div>
        
        <div class="result">
            <h3>📋 Resultado da Instalação:</h3>