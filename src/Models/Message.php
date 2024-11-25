class Message {
    private $id;
    private $userId;
    private $name;
    private $email;
    private $number;
    private $message;

    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getNumber() { return $this->number; }
    public function getMessage() { return $this->message; }

    // Setters
    public function setUserId($userId) { $this->userId = $userId; }
    public function setName($name) { $this->name = $name; }
    public function setEmail($email) { $this->email = $email; }
    public function setNumber($number) { $this->number = $number; }
    public function setMessage($message) { $this->message = $message; }
} 