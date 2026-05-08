<?php
// models/Order.php

class Order {
    private $id;
    private $user_id;
    private $delivery_date;
    private $order_date;
    private $status;
    private $total_amount;
    private $batch_id;
    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->delivery_date = $data['delivery_date'] ?? null;
        $this->order_date = $data['order_date'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->total_amount = $data['total_amount'] ?? null;
        $this->batch_id = $data['batch_id'] ?? null;
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getDeliveryDate() {
        return $this->delivery_date;
    }

    public function getOrderDate() {
        return $this->order_date;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getTotalAmount() {
        return $this->total_amount;
    }

    public function getBatchId() {
        return $this->batch_id;
    }

    // Setters
    public function setStatus($status) {
        $this->status = $status;
    }

    public function setBatchId($batch_id) {
        $this->batch_id = $batch_id;
    }

    public function setDeliveryDate($delivery_date) {
        $this->delivery_date = $delivery_date;
    }
}