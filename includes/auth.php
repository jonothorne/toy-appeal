<?php
require_once __DIR__ . '/db.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return getRow(
        "SELECT id, username, full_name, email FROM users WHERE id = ? AND is_active = 1",
        "i",
        [$_SESSION['user_id']]
    );
}

// Login user
function loginUser($username, $password) {
    $user = getRow(
        "SELECT id, username, password_hash, full_name, email FROM users WHERE username = ? AND is_active = 1",
        "s",
        [$username]
    );

    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];

    // Update last login
    updateQuery(
        "UPDATE users SET last_login = NOW() WHERE id = ?",
        "i",
        [$user['id']]
    );

    return true;
}

// Logout user
function logoutUser() {
    session_destroy();
    session_start();
}

// Require login (redirect if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

// Generate password hash
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Create new user
function createUser($username, $password, $fullName, $email) {
    $passwordHash = hashPassword($password);

    return insertQuery(
        "INSERT INTO users (username, password_hash, full_name, email) VALUES (?, ?, ?, ?)",
        "ssss",
        [$username, $passwordHash, $fullName, $email]
    );
}

// Update user
function updateUser($userId, $fullName, $email, $newPassword = null) {
    if ($newPassword) {
        $passwordHash = hashPassword($newPassword);
        return updateQuery(
            "UPDATE users SET full_name = ?, email = ?, password_hash = ? WHERE id = ?",
            "sssi",
            [$fullName, $email, $passwordHash, $userId]
        );
    } else {
        return updateQuery(
            "UPDATE users SET full_name = ?, email = ? WHERE id = ?",
            "ssi",
            [$fullName, $email, $userId]
        );
    }
}

// Delete user
function deleteUser($userId) {
    // Don't allow deleting the last admin user
    $userCount = getRow("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    if ($userCount['count'] <= 1) {
        return false;
    }

    return updateQuery(
        "UPDATE users SET is_active = 0 WHERE id = ?",
        "i",
        [$userId]
    );
}
