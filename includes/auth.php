<?php

// Start the session before reading or writing login, cart, or CSRF data.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Load the database connection and shared helper functions for every page.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
