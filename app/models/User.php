<?php
// app/controllers/UserController.php

require_once __DIR__ . '/../models/User.php';

class UserController {
    private ?User $userModel = null;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}