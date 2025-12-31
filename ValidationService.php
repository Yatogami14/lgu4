<?php

class ValidationService {
    private $errors = [];
    private $database;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function validateRegistration(array $data) {
        // Sanitize input first
        $name = sanitize_input($data['name'] ?? '');
        $email = sanitize_input($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';
        $account_type = sanitize_input($data['account_type'] ?? '');

        $this->validateName($name);
        $this->validateEmail($email);
        $this->validatePassword($password, $confirm_password);
        $this->validateTerms(isset($data['terms']));
        $this->validateAccountType($account_type);

        return empty($this->errors);
    }

    private function validateName(string $name) {
        if (empty($name)) {
            $this->errors['name'] = "Name is required.";
        }
    }

    private function validateEmail(string $email) {
        if (empty($this->errors['email'])) { // Avoid duplicate errors
            if (empty($email)) {
                $this->errors['email'] = "Email is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors['email'] = "Invalid email format.";
            } elseif ($this->database->exists('users', 'email = :email', ['email' => $email])) {
                $this->errors['email'] = "This email is already registered.";
            }
        }
    }

    private function validatePassword(string $password, string $confirm_password) {
        if (empty($password)) {
            $this->errors['password'] = "Password is required.";
        } else {
            $password_errors = [];
            if (strlen($password) < 8) {
                $password_errors[] = "be at least 8 characters long";
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $password_errors[] = "contain at least one uppercase letter";
            }
            if (!preg_match('/[a-z]/', $password)) {
                $password_errors[] = "contain at least one lowercase letter";
            }
            if (!preg_match('/[0-9]/', $password)) {
                $password_errors[] = "contain at least one number";
            }
            if (!preg_match('/[\W_]/', $password)) {
                $password_errors[] = "contain at least one special character (e.g., !@#$%^&*)";
            }

            if (!empty($password_errors)) {
                $this->errors['password'] = "Password must " . implode(', ', $password_errors) . ".";
            }
        }

        if ($password !== $confirm_password) {
            $this->errors['confirm_password'] = "Passwords do not match.";
        }
    }

    private function validateTerms(bool $agreed) {
        if (!$agreed) {
            $this->errors['terms'] = "You must agree to the Terms of Service and Privacy Policy.";
        }
    }

    private function validateAccountType(string $account_type) {
        if (empty($account_type)) {
            $this->errors['account_type'] = "Please select an account type.";
        } elseif (!in_array($account_type, ['community_user', 'business_owner'])) {
            $this->errors['account_type'] = "Invalid account type selected.";
        }
    }
}