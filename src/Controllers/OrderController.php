<?php
namespace Controllers;

class OrderController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getOrders($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM orders WHERE user_id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener órdenes: " . $e->getMessage());
            return [];
        }
    }

    public function updatePaymentStatus($orderId, $status) {
        try {
            $stmt = $this->conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            return $stmt->execute([$status, $orderId]);
        } catch (\Exception $e) {
            error_log("Error al actualizar estado: " . $e->getMessage());
            return false;
        }
    }

    public function deleteOrder($orderId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM orders WHERE id = ?");
            return $stmt->execute([$orderId]);
        } catch (\Exception $e) {
            error_log("Error al eliminar orden: " . $e->getMessage());
            return false;
        }
    }

    public function getAllOrders() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM orders");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener todas las órdenes: " . $e->getMessage());
            return [];
        }
    }

    public function createOrder($userData, $userId) {
        try {
            // Obtener productos del carrito
            $cartItems = $this->getCartItems($userId);
            if(empty($cartItems)) {
                return ['success' => false, 'message' => 'Tu carrito está vacío'];
            }

            // Calcular total y preparar lista de productos
            $cartTotal = 0;
            $products = [];
            foreach($cartItems as $item) {
                $products[] = $item['name'] . ' (' . $item['quantity'] . ')';
                $cartTotal += ($item['price'] * $item['quantity']);
            }
            $totalProducts = implode(', ', $products);

            // Formatear dirección
            $address = 'flat no. ' . $userData['flat'] . ', ' . 
                      $userData['street'] . ', ' . 
                      $userData['city'] . ', ' . 
                      $userData['country'] . ' - ' . 
                      $userData['pin_code'];

            // Verificar si la orden ya existe
            $stmt = $this->conn->prepare("SELECT * FROM orders WHERE 
                name = ? AND number = ? AND email = ? AND 
                method = ? AND address = ? AND 
                total_products = ? AND total_price = ?");
            
            $stmt->execute([
                $userData['name'],
                $userData['number'],
                $userData['email'],
                $userData['method'],
                $address,
                $totalProducts,
                $cartTotal
            ]);

            if($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => '¡Pedido ya realizado!'];
            }

            // Insertar nueva orden
            $stmt = $this->conn->prepare("INSERT INTO orders 
                (user_id, name, number, email, method, address, 
                total_products, total_price, placed_on) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $userId,
                $userData['name'],
                $userData['number'],
                $userData['email'],
                $userData['method'],
                $address,
                $totalProducts,
                $cartTotal,
                date('d-M-Y')
            ]);

            // Limpiar carrito
            $this->clearCart($userId);

            return ['success' => true, 'message' => '¡Pedido realizado con éxito!'];
        } catch (\Exception $e) {
            error_log("Error al crear orden: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar el pedido'];
        }
    }

    private function getCartItems($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function clearCart($userId) {
        $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    public function getUserOrders($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM orders WHERE user_id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener órdenes del usuario: " . $e->getMessage());
            return [];
        }
    }
} 