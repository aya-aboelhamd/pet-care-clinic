<?php

class ServiceZone {

    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }
    public function createZone($profile_id, $name, $lat, $lng, $radius){

        $sql = "INSERT INTO servicezone
                (profile_id, name, lat, lng, radius)
                VALUES
                (:profile_id, :name, :lat, :lng, :radius)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':profile_id' => $profile_id,
            ':name'       => $name,
            ':lat'        => $lat,
            ':lng'        => $lng,
            ':radius'     => $radius
        ]);
    }
    public function getZonesByProvider($profile_id){

        $sql = "SELECT * FROM servicezone
                WHERE profile_id = :profile_id";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':profile_id' => $profile_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function deleteZone($zone_id, $profile_id){

        $sql = "DELETE FROM servicezone
                WHERE zone_id = :zone_id
                AND profile_id = :profile_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':zone_id'    => $zone_id,
            ':profile_id' => $profile_id
        ]);
    }
        public function findWalkersNearMe($ownerLat, $ownerLng){

        $sql = "SELECT p.*, z.name,

                (
                    6371 * acos(
                        cos(radians(:olat))
                        * cos(radians(z.lat))
                        * cos(radians(z.lng) - radians(:olng))
                        + sin(radians(:olat))
                        * sin(radians(z.lat))
                    )
                ) AS distance

                FROM serviceproviderprofile p

                JOIN servicezone z
                ON p.id = z.profile_id

                HAVING distance <= z.radius

                ORDER BY distance ASC";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':olat' => $ownerLat,
            ':olng' => $ownerLng
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>