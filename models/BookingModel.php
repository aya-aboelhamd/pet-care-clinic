<?php
class BookingModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getProvidersByRole($role_id) {
        $stmt = $this->db->prepare("SELECT id, name FROM users WHERE roleid = ? AND status = 'Active'");
        $stmt->execute([$role_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserPets($user_id) {
        $stmt = $this->db->prepare("SELECT id, name, breed, species AS type FROM pet WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createBooking($data) {
        $query = "INSERT INTO booking (user_id, pet_id, provider_id, service_type, status, start_time, notes) 
                  VALUES (?, ?, ?, ?, 'Pending', ?, ?)";
        $stmt = $this->db->prepare($query);
        $full_start_time = $data['date'] . ' ' . $data['time'] . ':00';
        return $stmt->execute([
            $data['user_id'], $data['pet_id'], $data['provider_id'], 
            $data['service'], $full_start_time, $data['notes']
        ]);
    }

    public function getUserBookings($user_id) {
        $query = "SELECT b.*, p.name as pet_name 
                  FROM booking b 
                  JOIN pet p ON b.pet_id = p.id 
                  WHERE b.user_id = ? 
                  ORDER BY b.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getProviderBookings($provider_id) {
        $query = "SELECT b.*, p.name as pet_name, u.name as owner_name, u.phone as owner_phone 
                  FROM booking b 
                  JOIN pet p ON b.pet_id = p.id 
                  JOIN users u ON b.user_id = u.id
                  WHERE b.provider_id = ? 
                  ORDER BY b.start_time ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$provider_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}