<?php
require_once __DIR__ . '/config.php';

// Create database connection
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }

            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please contact the administrator.");
        }
    }

    return $conn;
}

// Execute a prepared statement
function executeQuery($sql, $types = "", $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $result = $stmt->execute();

    if (!$result) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }

    return $stmt;
}

// Get single row
function getRow($sql, $types = "", $params = []) {
    $stmt = executeQuery($sql, $types, $params);
    if (!$stmt) return null;

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row;
}

// Get all rows
function getRows($sql, $types = "", $params = []) {
    $stmt = executeQuery($sql, $types, $params);
    if (!$stmt) return [];

    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    return $rows;
}

// Insert and return last insert ID
function insertQuery($sql, $types = "", $params = []) {
    $stmt = executeQuery($sql, $types, $params);
    if (!$stmt) return false;

    $insertId = $stmt->insert_id;
    $stmt->close();

    return $insertId;
}

// Update/Delete query
function updateQuery($sql, $types = "", $params = []) {
    $stmt = executeQuery($sql, $types, $params);
    if (!$stmt) return false;

    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    return $affectedRows;
}

// Get setting value
function getSetting($key, $default = '') {
    $row = getRow("SELECT setting_value FROM settings WHERE setting_key = ?", "s", [$key]);
    return $row ? $row['setting_value'] : $default;
}

// Update setting value
function updateSetting($key, $value) {
    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?";
    return updateQuery($sql, "sss", [$key, $value, $value]);
}
