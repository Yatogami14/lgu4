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

    /**
     * Validates a complete business owner registration, including user, business, and document details.
     * This should be called from the business owner registration script.
     * The calling script can then use getErrors() to display feedback to the user.
     *
     * @param array $data The $_POST data from the form.
     * @param array $files The $_FILES data from the form.
     * @return bool True if validation passes, false otherwise.
     */
    public function validateBusinessOwnerRegistration(array $data, array $files) {
        // 1. Validate the common user registration fields
        $this->validateRegistration($data);

        // 2. Validate business-specific fields
        if (empty($data['business_name'])) {
            $this->errors['business_name'] = 'Business Name is required.';
        }
        if (empty($data['address'])) {
            $this->errors['address'] = 'Business Address is required.';
        }
        if (empty($data['business_type'])) {
            $this->errors['business_type'] = 'Business Type is required.';
        }
        if (empty($data['registration_number'])) {
            $this->errors['registration_number'] = 'Business Registration Number is required.';
        }

        // 3. Validate uploaded files
        $required_docs = [
            'business_permit' => 'Business Permit',
            'owner_id' => "Owner's ID"
        ];

        foreach ($required_docs as $key => $name) {
            if (empty($files[$key]) || $files[$key]['error'] !== UPLOAD_ERR_OK) {
                $this->errors[$key] = "A valid {$name} document is required.";
            } else {
                $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
                $file_size_limit = 5 * 1024 * 1024; // 5MB

                if (!in_array($files[$key]['type'], $allowed_types)) {
                    $this->errors[$key] = "Invalid file type for {$name}. Please upload a JPG, PNG, or PDF.";
                }
                if ($files[$key]['size'] > $file_size_limit) {
                    $this->errors[$key] = "The file for {$name} exceeds the 5MB size limit.";
                }
            }
        }

        return empty($this->errors);
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