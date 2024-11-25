<?php
namespace Controllers;

class ContactController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function sendMessage($userData) {
        try {
            $name = $userData['name'];
            $email = $userData['email'];
            $number = $userData['number'];
            $msg = $userData['message'];
            $user_id = $userData['user_id'];

            // Verificar si el mensaje ya existe
            $stmt = $this->conn->prepare("SELECT * FROM message WHERE name = ? AND email = ? AND number = ? AND message = ?");
            $stmt->execute([$name, $email, $number, $msg]);

            if($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => '¡Mensaje ya enviado!'];
            }

            // Insertar nuevo mensaje
            $stmt = $this->conn->prepare("INSERT INTO message (user_id, name, email, number, message) VALUES (?, ?, ?, ?, ?)");
            
            if($stmt->execute([$user_id, $name, $email, $number, $msg])) {
                return ['success' => true, 'message' => '¡Mensaje enviado exitosamente!'];
            }

        } catch (\Exception $e) {
            error_log("Error al enviar mensaje: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar el mensaje'];
        }
    }
}