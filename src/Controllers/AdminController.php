<?php
namespace Controllers;

class AdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getDashboardData() {
        $data = [];
        
        // Obtener total pendientes
        $data['total_pendings'] = $this->getTotalPendings();
        $data['total_completed'] = $this->getTotalCompleted();
        $data['orders_count'] = $this->getOrdersCount();
        $data['products_count'] = $this->getProductsCount();
        $data['users_count'] = $this->getUsersCount();
        $data['admins_count'] = $this->getAdminsCount();
        $data['total_accounts'] = $this->getTotalAccounts();
        $data['messages_count'] = $this->getMessagesCount();

        return $data;
    }

    private function getTotalPendings() {
        $total = 0;
        $query = "SELECT total_price FROM `orders` WHERE payment_status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $total += $row['total_price'];
        }
        return $total;
    }

    private function getTotalCompleted() {
        $total = 0;
        $query = "SELECT total_price FROM `orders` WHERE payment_status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $total += $row['total_price'];
        }
        return $total;
    }

    private function getOrdersCount() {
        $query = "SELECT COUNT(*) as count FROM `orders`";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    private function getProductsCount() {
        $query = "SELECT COUNT(*) as count FROM `products`";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    private function getUsersCount() {
        $query = "SELECT COUNT(*) as count FROM `users` WHERE user_type = 'user'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    private function getAdminsCount() {
        $query = "SELECT COUNT(*) as count FROM `users` WHERE user_type = 'admin'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    private function getTotalAccounts() {
        $query = "SELECT COUNT(*) as count FROM `users`";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    private function getMessagesCount() {
        $query = "SELECT COUNT(*) as count FROM `message`";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    private function handleDatabaseError($e) {
        error_log("Error en la base de datos: " . $e->getMessage());
        throw new \Exception("Error al procesar la solicitud");
    }

    public function addProduct($postData, $files) {
        $name = $postData['name'];
        $price = $postData['price'];
        $image = $files['image']['name'];
        $image_size = $files['image']['size'];
        $image_tmp_name = $files['image']['tmp_name'];
        $image_folder = '../../uploaded_img/'.$image;

        // Verificar si el producto ya existe
        $stmt = $this->conn->prepare("SELECT name FROM `products` WHERE name = ?");
        $stmt->execute([$name]);
        
        if($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'El producto ya existe'];
        }

        if($image_size > 2000000) {
            return ['success' => false, 'message' => 'El tamaño de la imagen es demasiado grande'];
        }

        $stmt = $this->conn->prepare("INSERT INTO `products`(name, price, image) VALUES(?, ?, ?)");
        if($stmt->execute([$name, $price, $image])) {
            move_uploaded_file($image_tmp_name, $image_folder);
            return ['success' => true, 'message' => '¡Producto añadido exitosamente!'];
        }

        return ['success' => false, 'message' => 'Error al añadir el producto'];
    }

    public function deleteProduct($id) {
        // Obtener información de la imagen
        $stmt = $this->conn->prepare("SELECT image FROM `products` WHERE id = ?");
        $stmt->execute([$id]);
        $image_data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if($image_data) {
            unlink('../../uploaded_img/'.$image_data['image']);
        }
        
        $stmt = $this->conn->prepare("DELETE FROM `products` WHERE id = ?");
        if($stmt->execute([$id])) {
            return ['success' => true, 'message' => 'Producto eliminado'];
        }
        
        return ['success' => false, 'message' => 'Error al eliminar el producto'];
    }

    public function updateProduct($postData, $files) {
        $id = $postData['update_p_id'];
        $name = $postData['update_name'];
        $price = $postData['update_price'];
        
        $query = "UPDATE `products` SET name = ?, price = ? WHERE id = ?";
        $params = [$name, $price, $id];
        
        if(!empty($files['update_image']['name'])) {
            $image = $files['update_image']['name'];
            $image_size = $files['update_image']['size'];
            $image_tmp_name = $files['update_image']['tmp_name'];
            $image_folder = '../../uploaded_img/'.$image;
            
            if($image_size > 2000000) {
                return ['success' => false, 'message' => 'El tamaño de la imagen es demasiado grande'];
            }
            
            unlink('../../uploaded_img/'.$postData['update_old_image']);
            move_uploaded_file($image_tmp_name, $image_folder);
            
            $query = "UPDATE `products` SET name = ?, price = ?, image = ? WHERE id = ?";
            $params = [$name, $price, $image, $id];
        }
        
        $stmt = $this->conn->prepare($query);
        if($stmt->execute($params)) {
            return ['success' => true, 'message' => 'Producto actualizado exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al actualizar el producto'];
    }

    public function getAllProducts() {
        $stmt = $this->conn->query("SELECT * FROM `products`");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllOrders() {
        $stmt = $this->conn->prepare("SELECT * FROM `orders`");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateOrderStatus($orderId, $status) {
        $stmt = $this->conn->prepare("UPDATE `orders` SET payment_status = ? WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }

    public function deleteOrder($orderId) {
        $stmt = $this->conn->prepare("DELETE FROM `orders` WHERE id = ?");
        return $stmt->execute([$orderId]);
    }

    public function getAllUsers() {
        try {
            $select_users = mysqli_query($this->conn, "SELECT * FROM `users`");
            $users = [];
            while($row = mysqli_fetch_assoc($select_users)) {
                $users[] = $row;
            }
            return $users;
        } catch (\Exception $e) {
            $this->handleDatabaseError($e);
            return [];
        }
    }

    public function deleteUser($userId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM `users` WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e);
            return false;
        }
    }

    public function getAllMessages() {
        try {
            $query = "SELECT * FROM `message`";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e);
            return [];
        }
    }

    public function deleteMessage($messageId) {
        try {
            $query = "DELETE FROM `message` WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$messageId]);
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e);
            return false;
        }
    }
} 