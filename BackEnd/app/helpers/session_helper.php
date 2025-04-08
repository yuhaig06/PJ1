<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set flash message
function setFlash($name, $message, $class = 'alert alert-success') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'name' => $name,
        'message' => $message,
        'class' => $class
    ];
}

// Display flash messages
function flash($name = '') {
    if (isset($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $msg) {
            if ($msg['name'] === $name) {
                echo '<div class="' . $msg['class'] . '" id="msg-flash">' . $msg['message'] . '</div>';
                unset($msg);
            }
        }
        $_SESSION['flash_messages'] = array_filter($_SESSION['flash_messages']);
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Set user session data
function setUserSession($user) {
    $_SESSION['user_id'] = $user->id;
    $_SESSION['user_email'] = $user->email;
    $_SESSION['user_name'] = $user->name;
    $_SESSION['user_role'] = $user->role;
}

// Clear user session
function clearUserSession() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_role']);
}

// Check if user has specific role
function hasRole($role) {
    return getUserRole() === $role;
}

// Set session variable
function setSession($key, $value) {
    $_SESSION[$key] = $value;
}

// Get session variable
function getSession($key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

// Delete session variable
function deleteSession($key) {
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

// Clear all session data
function clearSession() {
    session_unset();
    session_destroy();
} 