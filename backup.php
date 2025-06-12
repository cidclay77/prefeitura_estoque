<?php
require_once 'config/database.php';

// Configurações de backup
set_time_limit(300); // 5 minutos
ini_set('memory_limit', '256M');

// Conectar ao banco
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Erro: Não foi possível conectar ao banco de dados.");
}

// Configurar headers para download
$filename = 'backup_prefeitura_estoque_' . date('Y-m-d_H-i-s') . '.sql';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Função para escapar valores SQL
function escapeValue($value) {
    if ($value === null) {
        return 'NULL';
    }
    return "'" . addslashes($value) . "'";
}

// Função para obter a estrutura de uma tabela
function getTableStructure($conn, $tableName) {
    $sql = "SHOW CREATE TABLE `$tableName`";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['Create Table'];
}

// Função para obter dados de uma tabela
function getTableData($conn, $tableName) {
    $sql = "SELECT * FROM `$tableName`";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Iniciar o backup
echo "-- =============================================\n";
echo "-- BACKUP DO SISTEMA DE ESTOQUE DA PREFEITURA\n";
echo "-- Data: " . date('Y-m-d H:i:s') . "\n";
echo "-- Gerado automaticamente pelo sistema\n";
echo "-- =============================================\n\n";

echo "SET FOREIGN_KEY_CHECKS = 0;\n";
echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
echo "SET AUTOCOMMIT = 0;\n";
echo "START TRANSACTION;\n";
echo "SET time_zone = \"+00:00\";\n\n";

echo "-- Criando database se não existir\n";
echo "CREATE DATABASE IF NOT EXISTS `prefeitura_estoque` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
echo "USE `prefeitura_estoque`;\n\n";

try {
    // Obter lista de tabelas
    $stmt = $conn->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "-- =============================================\n";
        echo "-- Estrutura da tabela: $table\n";
        echo "-- =============================================\n\n";
        
        // Remover tabela se existir
        echo "DROP TABLE IF EXISTS `$table`;\n";
        
        // Criar estrutura da tabela
        $createTable = getTableStructure($conn, $table);
        echo $createTable . ";\n\n";
        
        // Obter dados da tabela
        $data = getTableData($conn, $table);
        
        if (!empty($data)) {
            echo "-- =============================================\n";
            echo "-- Dados da tabela: $table\n";
            echo "-- =============================================\n\n";
            
            // Obter nomes das colunas
            $columns = array_keys($data[0]);
            $columnNames = '`' . implode('`, `', $columns) . '`';
            
            echo "INSERT INTO `$table` ($columnNames) VALUES\n";
            
            $values = [];
            foreach ($data as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    $rowValues[] = escapeValue($value);
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            echo implode(",\n", $values) . ";\n\n";
        } else {
            echo "-- Tabela $table está vazia\n\n";
        }
    }
    
    // Informações adicionais do backup
    echo "-- =============================================\n";
    echo "-- INFORMAÇÕES DO BACKUP\n";
    echo "-- =============================================\n\n";
    
    // Estatísticas das tabelas
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM `$table`");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "-- Tabela $table: $count registros\n";
    }
    
    echo "\n-- Backup concluído em: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Total de tabelas: " . count($tables) . "\n";
    
    // Calcular tamanho do banco
    $stmt = $conn->prepare("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB'
        FROM information_schema.tables 
        WHERE table_schema = 'prefeitura_estoque'
    ");
    $stmt->execute();
    $size = $stmt->fetchColumn();
    echo "-- Tamanho do banco: " . ($size ? $size . " MB" : "N/A") . "\n";
    
    echo "\nSET FOREIGN_KEY_CHECKS = 1;\n";
    echo "COMMIT;\n\n";
    
    echo "-- =============================================\n";
    echo "-- INSTRUÇÕES PARA RESTAURAÇÃO\n";
    echo "-- =============================================\n";
    echo "-- 1. Faça login no MySQL/phpMyAdmin\n";
    echo "-- 2. Execute este arquivo SQL completo\n";
    echo "-- 3. Verifique se todas as tabelas foram criadas\n";
    echo "-- 4. Teste o sistema para confirmar a restauração\n";
    echo "-- =============================================\n";
    
} catch (Exception $e) {
    echo "-- ERRO DURANTE O BACKUP: " . $e->getMessage() . "\n";
    echo "-- Data do erro: " . date('Y-m-d H:i:s') . "\n";
}

// Fechar conexão
$conn = null;
?>