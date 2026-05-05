<?php
// models/VaccineModel.php

class VaccineModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function calculateNextDueDate($species, $vaccine_name, $last_date) {
        $next_date = "";

        if (empty($last_date)) {
            $next_date = date('Y-m-d', strtotime('+1 month'));
        } else {
            $last_date_time = strtotime($last_date);
            
            if ($species == 'Dog') {
                if ($vaccine_name == 'Rabies') {
                    $next_date = date('Y-m-d', strtotime('+3 years', $last_date_time));
                } else if ($vaccine_name == 'Bordetella') {
                    $next_date = date('Y-m-d', strtotime('+6 months', $last_date_time));
                } else {
                    $next_date = date('Y-m-d', strtotime('+1 year', $last_date_time));
                }
            } else if ($species == 'Cat') {
                if ($vaccine_name == 'FVRCP') {
                    $next_date = date('Y-m-d', strtotime('+3 years', $last_date_time));
                } else {
                    $next_date = date('Y-m-d', strtotime('+1 year', $last_date_time));
                }
            } else {
                $next_date = date('Y-m-d', strtotime('+1 year', $last_date_time));
            }
        }
        return $next_date;
    }

    public function saveSchedule($pet_id, $vaccine_name, $last_date, $next_due_date) {
        try {
            $this->conn->beginTransaction();

            $stmt_record = $this->conn->prepare("INSERT INTO medicalrecord (pet_id, diagnosis, created_at) VALUES (?, 'Vaccination Scheduling', NOW())");
            $stmt_record->execute([$pet_id]);
            $record_id = $this->conn->lastInsertId();

            $stmt_vaccine = $this->conn->prepare("INSERT INTO vaccination (record_id, vaccine_name, date_administered, next_due_date) VALUES (?, ?, ?, ?)");
            $stmt_vaccine->execute([$record_id, $vaccine_name, $last_date, $next_due_date]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>