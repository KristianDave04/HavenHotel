<?php
// classes/User.php

class User {
    private $db;
    private $table = "users";
    
    // Define your team's custom admin provisioning secret key token
    private $master_admin_key = "SECURE_ADMIN_TOKEN_2026";

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Registers a new account, automatically evaluating the admin authorization key fields.
     */
    public function register($firstName, $lastName, $email, $phone, $password, $adminKey = '') {
        // Look up whether email already exists using standard safe prepared statements
        $check_stmt = $this->db->prepare("SELECT id FROM " . $this->table . " WHERE user_email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();
            return "This email address is already registered inside our system.";
        }
        $check_stmt->close();

        // Establish structural access authorization flags
        $role = "User";
        $tier = "Regular";

        if (!empty($adminKey)) {
            if ($adminKey === $this->master_admin_key) {
                $role = "Admin";
                $tier = "Platinum Staff";
            } else {
                return "Security Token Failure: Invalid Administrative Master Access Key.";
            }
        }

        // Encrypt the password string securely before final submission mapping
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Map column data definitions safely into the user database context grid
        $insert_stmt = $this->db->prepare("INSERT INTO " . $this->table . " (first_name, last_name, user_email, phone, password, role, membership_tier) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("sssssss", $firstName, $lastName, $email, $phone, $hashed_password, $role, $tier);

        if ($insert_stmt->execute()) {
            $insert_stmt->close();
            return true;
        } else {
            $error = $insert_stmt->error;
            $insert_stmt->close();
            return "Critical execution pipeline database write failure: " . $error;
        }
    }

    /**
     * Validates credentials, matches password hash arrays, and structures user metadata sessions.
     */
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, password, role, membership_tier FROM " . $this->table . " WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Core password evaluation logic loop
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['membership_tier'] = $user['membership_tier'];

                $stmt->close();
                return [
                    'status' => true,
                    'role' => $user['role']
                ];
            }
        }

        $stmt->close();
        return false;
    }
}