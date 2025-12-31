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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/register_options.css">
    <style>
        .login-header h2 {
            color: #1a202c; /* A standard dark gray for titles */
        }
        .options-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 30px;
        }
        @media (min-width: 600px) {
            .options-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .option-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            border-color: #ca8a04; /* yellow-600 */
        }
        .option-icon {
            font-size: 2.5rem;
            color: #ca8a04; /* yellow-600 */
            margin-bottom: 15px;
            background: rgba(202, 138, 4, 0.1);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .option-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .option-desc {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
    <div class="bg-decoration bg-decoration-4"></div>

    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Watermark" class="watermark-logo">

    <a href="<?php echo $base_path; ?>/main_login.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Login
    </a>

    <div class="main-content-wrapper">
        <div class="login-container" style="max-width: 600px;">
            <div class="login-logo">
                <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection">
            </div>

            <div class="login-header">
                <h2>Create Account</h2>
                <p>Choose your account type to get started</p>
            </div>

            <div class="options-grid">
                <a href="business/business_owner_register.php" class="option-card">
                    <div class="option-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <span class="option-title">Business Owner</span>
                    <span class="option-desc">Register your establishment and manage inspections.</span>
                </a>

                <a href="community/public_register.php" class="option-card">
                    <div class="option-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="option-title">Community Member</span>
                    <span class="option-desc">Report concerns and view safety ratings.</span>
                </a>
            </div>

            <p class="register-link" style="margin-top: 30px;">
                Already have an account? <a href="main_login.php">Sign In</a>
            </p>

            <p class="footer">
                &copy; <?php echo date('Y'); ?> Health & Safety Inspection. All Rights Reserved.
            </p>
        </div>
    </div>

</body>
</html>