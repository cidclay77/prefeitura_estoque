<?php
class Equipment {
    private $conn;
    private $table_name = "equipments";

    public $id;
    public $name;
    public $description;
    public $category;
    public $brand;
    public $model;
    public $serial_number;
    public $quantity;
    public $min_stock;
    public $unit_price;
    public $supplier;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->category = $row['category'];
            $this->brand = $row['brand'];
            $this->model = $row['model'];
            $this->serial_number = $row['serial_number'];
            $this->quantity = $row['quantity'];
            $this->min_stock = $row['min_stock'];
            $this->unit_price = $row['unit_price'];
            $this->supplier = $row['supplier'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    function checkDuplicate($serial_number = null, $brand = null, $model = null, $exclude_id = null) {
        $conditions = [];
        $params = [];
        
        // Verificar por número de série se fornecido
        if (!empty($serial_number)) {
            $conditions[] = "serial_number = ?";
            $params[] = $serial_number;
        }
        
        // Verificar por marca e modelo se fornecidos
        if (!empty($brand) && !empty($model)) {
            if (!empty($serial_number)) {
                $conditions[] = "OR (brand = ? AND model = ?)";
                $params[] = $brand;
                $params[] = $model;
            } else {
                $conditions[] = "brand = ? AND model = ?";
                $params[] = $brand;
                $params[] = $model;
            }
        }
        
        if (empty($conditions)) {
            return false;
        }
        
        $query = "SELECT id, name, brand, model, serial_number, quantity FROM " . $this->table_name . " WHERE ";
        $query .= implode(" ", $conditions);
        
        // Excluir o próprio registro se estiver editando
        if ($exclude_id) {
            $query .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function create() {
        // Verificar duplicatas antes de criar
        $duplicate = $this->checkDuplicate($this->serial_number, $this->brand, $this->model);
        if ($duplicate) {
            throw new Exception("DUPLICATE_FOUND", 0, $duplicate);
        }

        $query = "INSERT INTO " . $this->table_name . " 
                SET name=:name, description=:description, category=:category, 
                    brand=:brand, model=:model, serial_number=:serial_number,
                    quantity=:quantity, min_stock=:min_stock, unit_price=:unit_price,
                    supplier=:supplier, status=:status";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->brand = htmlspecialchars(strip_tags($this->brand));
        $this->model = htmlspecialchars(strip_tags($this->model));
        $this->serial_number = htmlspecialchars(strip_tags($this->serial_number));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->min_stock = htmlspecialchars(strip_tags($this->min_stock));
        $this->unit_price = htmlspecialchars(strip_tags($this->unit_price));
        $this->supplier = htmlspecialchars(strip_tags($this->supplier));
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":brand", $this->brand);
        $stmt->bindParam(":model", $this->model);
        $stmt->bindParam(":serial_number", $this->serial_number);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":min_stock", $this->min_stock);
        $stmt->bindParam(":unit_price", $this->unit_price);
        $stmt->bindParam(":supplier", $this->supplier);
        $stmt->bindParam(":status", $this->status);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    function update() {
        // Verificar duplicatas antes de atualizar (excluindo o próprio registro)
        $duplicate = $this->checkDuplicate($this->serial_number, $this->brand, $this->model, $this->id);
        if ($duplicate) {
            throw new Exception("DUPLICATE_FOUND", 0, $duplicate);
        }

        $query = "UPDATE " . $this->table_name . " 
                SET name=:name, description=:description, category=:category, 
                    brand=:brand, model=:model, serial_number=:serial_number,
                    quantity=:quantity, min_stock=:min_stock, unit_price=:unit_price,
                    supplier=:supplier, status=:status, updated_at=NOW()
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->brand = htmlspecialchars(strip_tags($this->brand));
        $this->model = htmlspecialchars(strip_tags($this->model));
        $this->serial_number = htmlspecialchars(strip_tags($this->serial_number));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->min_stock = htmlspecialchars(strip_tags($this->min_stock));
        $this->unit_price = htmlspecialchars(strip_tags($this->unit_price));
        $this->supplier = htmlspecialchars(strip_tags($this->supplier));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":brand", $this->brand);
        $stmt->bindParam(":model", $this->model);
        $stmt->bindParam(":serial_number", $this->serial_number);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":min_stock", $this->min_stock);
        $stmt->bindParam(":unit_price", $this->unit_price);
        $stmt->bindParam(":supplier", $this->supplier);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE name LIKE ? OR description LIKE ? OR category LIKE ? OR brand LIKE ?
                ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $keywords = "%{$keywords}%";
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->bindParam(4, $keywords);
        $stmt->execute();
        
        return $stmt;
    }

    function getLowStock() {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE quantity <= min_stock AND quantity > 0
                ORDER BY (quantity - min_stock) ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getStats() {
        $query = "SELECT 
                    COUNT(*) as total_equipment,
                    SUM(CASE WHEN quantity > min_stock THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN quantity <= min_stock AND quantity > 0 THEN 1 ELSE 0 END) as low_stock
                FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function updateQuantity($equipment_id, $new_quantity) {
        $query = "UPDATE " . $this->table_name . " 
                SET quantity = :quantity, updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quantity", $new_quantity);
        $stmt->bindParam(":id", $equipment_id);
        
        return $stmt->execute();
    }

    function getCategories() {
        $query = "SELECT DISTINCT category FROM " . $this->table_name . " ORDER BY category ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>