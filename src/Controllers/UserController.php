<?php
namespace Controllers;

class UserController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function register($userData) {
        try {
            // Verificar si el email ya existe
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$userData['email']]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Este email ya está registrado'];
            }

            // Hash de la contraseña
            $hashedPassword = md5($userData['password']); // Nota: MD5 se usa por compatibilidad con BD existente

            // Insertar nuevo usuario
            $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, 'user')");
            $stmt->execute([
                $userData['name'],
                $userData['email'],
                $hashedPassword
            ]);

            return ['success' => true, 'message' => 'Registro exitoso'];
        } catch (\Exception $e) {
            error_log("Error en registro: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar usuario'];
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
            $hashedPassword = md5($password); // Nota: MD5 se usa por compatibilidad con BD existente
            $stmt->execute([$email, $hashedPassword]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];

                return [
                    'success' => true,
                    'user_id' => $user['id'],
                    'user_name' => $user['name'],
                    'user_email' => $user['email'],
                    'user_type' => $user['user_type']
                ];
            }

            return ['success' => false, 'message' => 'Email o contraseña incorrecta'];
        } catch (\Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al iniciar sesión'];
        }
    }

    public function logout() {
        try {
            // Asegurarse de que la sesión está iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Limpiar todas las variables de sesión
            $_SESSION = array();
            
            // Destruir la sesión
            session_destroy();
            
            // Redireccionar al login
            header('location: ../auth/login.php');
            exit();
        } catch (\Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al cerrar sesión'];
        }
    }

    public function getUserById($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT id, name, email, user_type FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener usuario: " . $e->getMessage());
            return null;
        }
    }
} 