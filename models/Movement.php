<?php
class Movement {
    private $conn;
    private $table_name = "movements";

    public $id;
    public $equipment_id;
    public $type;
    public $quantity;
    public $reason;
    public $user_name;
    public $notes;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET equipment_id=:equipment_id, type=:type, quantity=:quantity,
                    reason=:reason, user_name=:user_name, notes=:notes";

        $stmt = $this->conn->prepare($query);

        $this->equipment_id = htmlspecialchars(strip_tags($this->equipment_id));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->reason = htmlspecialchars(strip_tags($this->reason));
        $this->user_name = htmlspecialchars(strip_tags($this->user_name));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        $stmt->bindParam(":equipment_id", $this->equipment_id);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":reason", $this->reason);
        $stmt->bindParam(":user_name", $this->user_name);
        $stmt->bindParam(":notes", $this->notes);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    function read() {
        $query = "SELECT m.*, e.name as equipment_name, e.category 
                FROM " . $this->table_name . " m
                LEFT JOIN equipments e ON m.equipment_id = e.id
                ORDER BY m.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    function getRecent($limit = 10) {
        $query = "SELECT m.*, e.name as equipment_name, e.category 
                FROM " . $this->table_name . " m
                LEFT JOIN equipments e ON m.equipment_id = e.id
                ORDER BY m.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getByEquipment($equipment_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE equipment_id = :equipment_id
                ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":equipment_id", $equipment_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getByDateRange($start_date, $end_date) {
        $query = "SELECT m.*, e.name as equipment_name, e.category 
                FROM " . $this->table_name . " m
                LEFT JOIN equipments e ON m.equipment_id = e.id
                WHERE DATE(m.created_at) BETWEEN :start_date AND :end_date
                ORDER BY m.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function processMovement($equipment_id, $type, $quantity, $reason, $user_name, $notes = '') {
        try {
            $this->conn->beginTransaction();

            // Buscar quantidade atual do equipamento
            $query = "SELECT quantity FROM equipments WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $equipment_id);
            $stmt->execute();
            $current_quantity = $stmt->fetchColumn();

            if ($current_quantity === false) {
                throw new Exception("Equipamento não encontrado");
            }

            // Calcular nova quantidade
            if ($type == 'entrada') {
                $new_quantity = $current_quantity + $quantity;
            } else { // saída
                if ($current_quantity < $quantity) {
                    throw new Exception("Quantidade insuficiente em estoque");
                }
                $new_quantity = $current_quantity - $quantity;
            }

            // Atualizar quantidade do equipamento
            $query = "UPDATE equipments SET quantity = :quantity, updated_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":quantity", $new_quantity);
            $stmt->bindParam(":id", $equipment_id);
            $stmt->execute();

            // Registrar movimentação
            $this->equipment_id = $equipment_id;
            $this->type = $type;
            $this->quantity = $quantity;
            $this->reason = $reason;
            $this->user_name = $user_name;
            $this->notes = $notes;
            
            if (!$this->create()) {
                throw new Exception("Erro ao registrar movimentação");
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
?>