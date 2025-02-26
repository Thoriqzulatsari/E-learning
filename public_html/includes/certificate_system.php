<?php
// certificate_system.php - Class for handling certificate operations

require_once 'includes/config.php';

class Certificate {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo ?? new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getUserCertificates($userId) {
        $sql = "SELECT c.*, co.title AS course_title 
                FROM certificates c 
                LEFT JOIN courses co ON c.course_id = co.course_id 
                WHERE c.user_id = ? AND c.status = 'active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}