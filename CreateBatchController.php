<?php

require_once __DIR__ . '/BatchController.php';

$controller = new BatchController();

$success = $controller->createBatch();

if ($success) {
    header("Location: ../views/admin/viewBatches.php?success=1");
} else {
    header("Location: ../views/admin/viewBatches.php?success=0");
}

exit();