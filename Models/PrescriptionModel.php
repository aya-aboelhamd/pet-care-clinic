<?php
class PrescriptionModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    
    public function checkAllergy($pet_id, $medication) {
        $stmt = $this->conn->prepare("SELECT allergies FROM pet WHERE id = ?");
        $stmt->execute([$pet_id]);
        $pet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pet && !empty($pet['allergies'])) {
            $allergies_array = array_map('trim', explode(',', strtolower($pet['allergies'])));
            if (in_array(strtolower($medication), $allergies_array)) {
                return true;
            }
        }
        return false; // Safe
    }

   
    public function checkInteraction($medications_array) {
        if (count($medications_array) < 2) return false;

        $in_clause = implode(',', array_fill(0, count($medications_array), '?'));
        
        
        $query = "SELECT * FROM drug_interactions 
                  WHERE drug_a IN ($in_clause) AND drug_b IN ($in_clause)";
        
        $stmt = $this->conn->prepare($query);
        
        $params = array_merge($medications_array, $medications_array);
        $stmt->execute($params);
        
        $interaction = $stmt->fetch(PDO::FETCH_ASSOC);
        return $interaction ? $interaction['warning_message'] : false;
    }

   
    public function savePrescription($vet_id, $pet_id, $diagnosis, $medications) {
        try {
            $this->conn->beginTransaction();

            // أ. إنشاء سجل طبي (Diagnosis)
            $stmt_record = $this->conn->prepare("INSERT INTO medicalrecord (pet_id, diagnosis, created_at) VALUES (?, ?, NOW())");
            $stmt_record->execute([$pet_id, $diagnosis]);
            $record_id = $this->conn->lastInsertId();

            // ب. إنشاء الروشتة وتوليد Unique ID
            $prescription_code = 'RX-' . strtoupper(uniqid());

            $stmt_presc = $this->conn->prepare("INSERT INTO prescription (prescription_code, record_id, veterinarian_id, medication_name, dosage, duration, instructions, issued_date) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
            
            // في حالة أكثر من دواء، بنحفظهم كـ String مفصولين، أو نعمل Loop (لو الداتا بيز بتاعتك بتسمح بدوا واحد في الريكورد هنحفظهم مجمعين مؤقتاً)
            $all_meds = implode(', ', $medications['names']);
            $all_dosages = implode(', ', $medications['dosages']);
            
            $stmt_presc->execute([$prescription_code, $record_id, $vet_id, $all_meds, $all_dosages, $medications['duration'], $medications['instructions']]);

            // ج. إرسال إشعار لصاحب الحيوان
            // هنجيب الـ user_id بتاع صاحب الحيوان الأول
            $stmt_owner = $this->conn->prepare("SELECT user_id FROM pet WHERE id = ?");
            $stmt_owner->execute([$pet_id]);
            $owner_id = $stmt_owner->fetchColumn();

            $stmt_notify = $this->conn->prepare("INSERT INTO notification (user_id, type, message, created_at, is_read) VALUES (?, 'Prescription', 'A new prescription has been issued for your pet.', NOW(), 0)");
            $stmt_notify->execute([$owner_id]);

            $this->conn->commit();
            return $prescription_code;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>