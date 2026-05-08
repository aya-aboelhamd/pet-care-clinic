<?php

require_once '../models/Database.php';
require_once '../models/ServiceZone.php';

class ServiceZoneController {

    private $model;

    public function __construct() {
        $db = new Database();
        $this->model = new ServiceZone($db->getConnection());
    }

    public function save() {

        session_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_SESSION['profile_id'])) {
                die("Session error: profile_id missing");
            }

            $profile_id = $_SESSION['profile_id'];

            $name   = $_POST['zone_name'];
            $lat    = $_POST['lat'];
            $lng    = $_POST['lng'];
            $radius = $_POST['radius'];

            $result = $this->model->createZone(
                $profile_id,
                $name,
                $lat,
                $lng,
                $radius
            );

            if ($result) {
                header("Location: ../views/serviceProvider/myServiceZones.php?save=1");
                exit();
            } else {
                echo "Insert Failed";
            }
        }
    }

    public function delete() {

        session_start();

        if (!isset($_SESSION['profile_id'])) {
            die("Session error");
        }

        $profile_id = $_SESSION['profile_id'];
        $zone_id = $_GET['id'] ?? null;

        if ($zone_id && $this->model->deleteZone($zone_id, $profile_id)) {
            header("Location: ../views/serviceProvider/myServiceZones.php?deleted=1");
            exit();
        } else {
            echo "Delete Failed";
        }
    }
}

$controller = new ServiceZoneController();

if (isset($_GET['action'])) {

    if ($_GET['action'] == 'save') {
        $controller->save();
    }

    if ($_GET['action'] == 'delete') {
        $controller->delete();
    }
}
?>