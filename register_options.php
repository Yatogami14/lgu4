<?php
session_start();
// Determine base path for assets
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Options - Health & Safety Inspection System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/register_option.css">
</head>
<body>

    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>

    <div class="main-content-wrapper">
        
        <!-- Left Side - Branding -->
        <div class="logo-left">
            <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Logo">
            <h1>HSI-QC Protektado</h1>
            <p class="tagline">Join the platform to ensure safer communities through intelligent inspection management.</p>
        </div>

        <!-- Right Side - Options -->
        <div class="options-container">
            
            <!-- Back Button -->
            <a href="<?php echo $base_path; ?>/main_login.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Login
            </a>

            <div class="options-header">
                <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo" class="mobile-logo">
                <h2>Create Account</h2>
                <p>Choose your account type to get started.</p>
            </div>

            <div class="options-grid">
                    
                <!-- Business Owner Option -->
                <a href="business/business_owner_register.php" class="option-card option-business">
                    <div class="option-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="option-content">
                        <h3 class="option-title">Business Owner</h3>
                        <p class="option-desc">
                            Register your establishment and manage inspections.
                        </p>
                    </div>
                </a>

                <!-- Community Member Option -->
                <a href="community/public_register.php" class="option-card option-community">
                    <div class="option-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="option-content">
                        <h3 class="option-title">Community Member</h3>
                        <p class="option-desc">
                            Report concerns and view safety ratings.
                        </p>
                    </div>
                </a>

            </div>

            <div class="login-section">
                <div class="divider">
                    <span>Already have an account?</span>
                </div>
                <a href="main_login.php" class="btn-outline">
                    Sign In
                </a>
            </div>
            
            <p class="footer">
                &copy; <?php echo date('Y'); ?> HSI-QC Protektado. All Rights Reserved.
            </p>
        </div>
    </div>

    <script>
        function createRipple(event) {
            const button = event.currentTarget;
            const circle = document.createElement("span");
            const diameter = Math.max(button.clientWidth, button.clientHeight);
            const radius = diameter / 2;

            circle.style.width = circle.style.height = `${diameter}px`;
            circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
            circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
            circle.classList.add("ripple");

            const ripple = button.getElementsByClassName("ripple")[0];
            if (ripple) ripple.remove();

            button.appendChild(circle);
        }
        document.querySelectorAll(".option-card, .btn-outline").forEach(btn => btn.addEventListener("click", createRipple));
    </script>
</body>
</html>