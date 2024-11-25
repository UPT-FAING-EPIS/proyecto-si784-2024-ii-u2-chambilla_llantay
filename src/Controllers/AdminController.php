<?php
namespace Controllers;

require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Order.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Message.php';

use Models\Product;
use Models\Order;
use Models\User;
use Models\Message;

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
        $stmt = $this->conn->prepare("SELECT * FROM `orders` WHERE payment_status = 'pendiente'");
        $stmt->execute();
        $total = 0;
        foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $order = new Order();
            $order->setId($row['id']);
            $order->setTotalPrice($row['total_price']);
            $total += $order->getTotalPrice();
        }
        return $total;
    }

    private function getTotalCompleted() {
        $stmt = $this->conn->prepare("SELECT * FROM `orders` WHERE payment_status = 'completado'");
        $stmt->execute();
        $total = 0;
        foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $order = new Order();
            $order->setId($row['id']);
            $order->setTotalPrice($row['total_price']);
            $total += $order->getTotalPrice();
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
        try {
            $product = new Product();
            $product->setName($postData['name']);
            $product->setPrice($postData['price']);
            $product->setImage($files['image']['name']);

            // Verificar si el producto ya existe
            $stmt = $this->conn->prepare("SELECT name FROM `products` WHERE name = ?");
            $stmt->execute([$product->getName()]);
            
            if($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'El producto ya existe'];
            }

            if($files['image']['size'] > 2000000) {
                return ['success' => false, 'message' => 'El tamaño de la imagen es demasiado grande'];
            }

            $stmt = $this->conn->prepare("INSERT INTO `products`(name, price, image) VALUES(?, ?, ?)");
            if($stmt->execute([$product->getName(), $product->getPrice(), $product->getImage()])) {
                move_uploaded_file($files['image']['tmp_name'], '../../uploaded_img/'.$product->getImage());
                return ['success' => true, 'message' => '¡Producto añadido exitosamente!'];
            }
        } catch (\Exception $e) {
            $this->handleDatabaseError($e);
            return ['success' => false, 'message' => 'Error al añadir el producto'];
        }
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
        try {
            $product = new Product();
            $product->setId($postData['update_p_id']);
            $product->setName($postData['update_name']);
            $product->setPrice($postData['update_price']);
            
            $stmt = $this->conn->prepare("UPDATE `products` SET name = ?, price = ? WHERE id = ?");
            $params = [$product->getName(), $product->getPrice(), $product->getId()];
            
            if(!empty($files['update_image']['name'])) {
                $product->setImage($files['update_image']['name']);
                if($files['update_image']['size'] > 2000000) {
                    return ['success' => false, 'message' => 'El tamaño de la imagen es demasiado grande'];
                }
                
                unlink('../../uploaded_img/'.$postData['update_old_image']);
                move_uploaded_file($files['update_image']['tmp_name'], '../../uploaded_img/'.$product->getImage());
                
                $stmt = $this->conn->prepare("UPDATE `products` SET name = ?, price = ?, image = ? WHERE id = ?");
                $params = [$product->getName(), $product->getPrice(), $product->getImage(), $product->getId()];
            }
            
            if($stmt->execute($params)) {
                return ['success' => true, 'message' => 'Producto actualizado exitosamente'];
            }
            
            return ['success' => false, 'message' => 'Error al actualizar el producto'];
        } catch (\Exception $e) {
            $this->handleDatabaseError($e);
            return ['success' => false, 'message' => 'Error al actualizar el producto'];
        }
    }

    public function getAllProducts() {
        $stmt = $this->conn->query("SELECT * FROM `products`");
        $products = [];
        foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $product = new Product();
            $product->setId($row['id']);
            $product->setName($row['name']);
            $product->setPrice($row['price']);
            $product->setImage($row['image']);
            $products[] = $product;
        }
        return $products;
    }

    public function getAllOrders() {
        $stmt = $this->conn->prepare("SELECT * FROM `orders`");
        $stmt->execute();
        $orders = [];
        foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $order = new Order();
            $order->setId($row['id']);
            $order->setUserId($row['user_id']);
            $order->setPlacedOn($row['placed_on']);
            $order->setName($row['name']);
            $order->setNumber($row['number']);
            $order->setEmail($row['email']);
            $order->setAddress($row['address']);
            $order->setTotalProducts($row['total_products']);
            $order->setTotalPrice($row['total_price']);
            $order->setMethod($row['method']);
            $order->setPaymentStatus($row['payment_status']);
            $orders[] = $order;
        }
        return $orders;
    }

    public function updateOrderStatus($orderId, $status) {
        try {
            $order = new Order();
            $order->setId($orderId);
            $order->setPaymentStatus($status);
            
            $stmt = $this->conn->prepare("UPDATE `orders` SET payment_status = ? WHERE id = ?");
            return $stmt->execute([$order->getPaymentStatus(), $order->getId()]);
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e);
            return false;
        }
    }

    public function deleteOrder($orderId) {
        try {
            $order = new Order();
            $order->setId($orderId);
            
            $stmt = $this->conn->prepare("DELETE FROM `orders` WHERE id = ?");
            return $stmt->execute([$order->getId()]);
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e);
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM `users`");
            $stmt->execute();
            $users = [];
            
            foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $user = new User();
                $user->setId($row['id']);
                $user->setName($row['name']);
                $user->setEmail($row['email']);
                $user->setUserType($row['user_type']);
                $users[] = $user;
            }
            return $users;
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e);
            return [];
        }
    }

    public function deleteUser($userId) {
        try {
            $user = new User();
            $user->setId($userId);
            
            $stmt = $this->conn->prepare("DELETE FROM `users` WHERE id = ?");
            return $stmt->execute([$user->getId()]);
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e);
            return false;
        }
    }

    public function getAllMessages() {
        try {
            $stmt = $this->conn->query("SELECT * FROM `message`");
            $messages = [];
            foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $message = new Message();
                $message->setId($row['id']);
                $message->setUserId($row['user_id']);
                $message->setMessage($row['message']);
                $message->setName($row['name']);
                $message->setEmail($row['email']);
                $message->setNumber($row['number']);
                $messages[] = $message;
            }
            return $messages;
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