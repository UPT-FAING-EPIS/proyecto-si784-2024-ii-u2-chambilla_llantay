<?php
namespace Controllers;

class ProductController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getLatestProducts($limit = 6) {
        try {
            $query = "SELECT * FROM products LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener productos: " . $e->getMessage());
            return [];
        }
    }

    public function addToCart($userId, $productData) {
        try {
            // Verificar si el producto ya está en el carrito
            $stmt = $this->conn->prepare("SELECT * FROM cart WHERE user_id = ? AND name = ?");
            $stmt->execute([$userId, $productData['product_name']]);
            
            if($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'El producto ya está en el carrito'];
            }

            // Añadir al carrito
            $query = "INSERT INTO cart (user_id, name, price, quantity, image) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            
            $stmt->execute([
                $userId,
                $productData['product_name'],
                $productData['product_price'],
                $productData['product_quantity'],
                $productData['product_image']
            ]);
            
            return ['success' => true, 'message' => 'Producto añadido al carrito'];
        } catch (\Exception $e) {
            error_log("Error al añadir al carrito: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al añadir al carrito'];
        }
    }

    public function getAllProducts() {
        try {
            $query = "SELECT * FROM products";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener todos los productos: " . $e->getMessage());
            return [];
        }
    }

    public function getCartItems($userId) {
        try {
            $query = "SELECT * FROM cart WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener items del carrito: " . $e->getMessage());
            return [];
        }
    }

    public function updateCartQuantity($cartId, $quantity) {
        try {
            $query = "UPDATE cart SET quantity = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$quantity, $cartId]);
            return ['success' => true, 'message' => '¡Cantidad actualizada!'];
        } catch (\Exception $e) {
            error_log("Error al actualizar cantidad: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar cantidad'];
        }
    }

    public function deleteCartItem($cartId) {
        try {
            $query = "DELETE FROM cart WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$cartId]);
            return true;
        } catch (\Exception $e) {
            error_log("Error al eliminar item: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAllCartItems($userId) {
        try {
            $query = "DELETE FROM cart WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            return true;
        } catch (\Exception $e) {
            error_log("Error al eliminar todos los items: " . $e->getMessage());
            return false;
        }
    }
} 