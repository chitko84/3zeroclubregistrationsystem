<?php
include 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header('Location: login.php');
        exit();
    }

    $allowed_roles = ['admin', 'user'];
    if (!in_array($role, $allowed_roles, true)) {
        $_SESSION['error'] = "Invalid role selected.";
        header('Location: login.php');
        exit();
    }

    $query = "SELECT id, name, role, profile_pic, email, password, department, program_of_study
              FROM users
              WHERE email = ? AND role = ?
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['department'] = $user['department'];
        $_SESSION['program_of_study'] = $user['program_of_study'];

        header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
        exit();
    } else {
        $_SESSION['error'] = "Invalid email, password, or role. Please try again.";
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - 3ZERO Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary-blue: #1a5276;
        --light-blue: #e8f4fd;
        --dark-blue: #0e2a47;
        --accent-blue: #3498db;
        --white: #FFFFFF;
        --light-gray: #F9FBF8;
        --text-gray: #3E3E3E;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #e8f4fd 0%, #f0f8ff 100%);
        color: var(--text-gray);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Main content wrapper */
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding-top: 80px; /* Account for fixed header */
    }

    .auth-container {
        max-width: 500px;
        margin: 2rem auto;
        padding: 3rem 2.5rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(26, 82, 118, 0.12);
        width: 90%;
        flex: 1;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .logo {
        width: 80px;
        margin-bottom: 1.5rem;
    }

    .auth-title {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: var(--dark-blue);
        margin-bottom: 0.5rem;
        font-size: 1.8rem;
    }

    .auth-subtitle {
        color: var(--text-gray);
        opacity: 0.8;
        font-weight: 400;
        font-size: 1rem;
    }

    .form-label {
        font-weight: 500;
        color: var(--text-gray);
        margin-bottom: 0.5rem;
    }

    .form-control {
        padding: 0.85rem 1.25rem;
        border-radius: 12px;
        border: 1px solid #E0E0E0;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 0.25rem rgba(26, 82, 118, 0.18);
    }

    .input-group-text {
        background-color: var(--light-gray);
        cursor: pointer;
        border-radius: 0 12px 12px 0;
    }

    .btn-auth {
        width: 100%;
        padding: 1rem;
        border-radius: 12px;
        background-color: var(--primary-blue);
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-top: 1rem;
        font-size: 1.1rem;
    }

    .btn-auth:hover {
        background-color: var(--dark-blue);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(26, 82, 118, 0.3);
    }

    /* Role Selector Styles */
    .role-selector {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .role-option {
        flex: 1;
        position: relative;
    }
    
    .role-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .role-label {
        display: block;
        padding: 1.25rem 1rem;
        background-color: var(--light-blue);
        border: 2px solid #E0E0E0;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .role-option input[type="radio"]:checked + .role-label {
        border-color: var(--primary-blue);
        background-color: rgba(26, 82, 118, 0.1);
        font-weight: 500;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(26, 82, 118, 0.15);
    }
    
    .role-option input[type="radio"]:focus + .role-label {
        box-shadow: 0 0 0 0.25rem rgba(26, 82, 118, 0.25);
    }
    
    .role-icon {
        font-size: 1.75rem;
        margin-bottom: 0.75rem;
        color: var(--primary-blue);
    }

    @media (max-width: 576px) {
        .auth-container {
            padding: 2rem 1.5rem;
            margin: 1rem auto;
        }
        
        .role-selector {
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .main-content {
            padding-top: 70px; /* Adjust for smaller screens */
        }
    }

    .header-logo {
        height: 50px;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    
    .forgot-password {
        text-align: right;
        margin-top: 0.5rem;
    }
    
    .forgot-password a {
        color: var(--primary-blue);
        text-decoration: none;
        font-size: 0.9rem;
    }
    
    .forgot-password a:hover {
        text-decoration: underline;
    }
    </style>
</head>

<body>
    <?php include('header.php'); ?>
    
    <div class="main-content">
        <div class="container py-4">
            <div class="auth-container">
                <div class="auth-header">
                    <h2 class="auth-title">Welcome Back</h2>
                    <p class="auth-subtitle">Sign in to your 3ZERO Club account</p>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                <i class="bi bi-eye-slash-fill"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Login as</label>
                        <div class="role-selector">
                            <div class="role-option">
                                <input type="radio" id="role_admin" name="role" value="admin" required <?= (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'checked' : '' ?>>
                                <label for="role_admin" class="role-label">
                                    <i class="bi bi-shield-lock role-icon"></i>
                                    <div>Admin</div>
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" id="role_user" name="role" value="user" required <?= (isset($_POST['role']) && $_POST['role'] == 'user') ? 'checked' : '' ?>>
                                <label for="role_user" class="role-label">
                                    <i class="bi bi-person role-icon"></i>
                                    <div>Student</div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="forgot-password">
                        <a href="forgot_password.php">Forgot your password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-auth">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                    </button>
                </form>

                <div class="auth-footer text-center mt-4">
                    <p>Don't have an account? <a href="register.php" class="text-primary text-decoration-none fw-bold">Register here</a></p>
                </div>
            </div>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
            } else {
                password.type = 'password';
                icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
            }
        });
        
        // Auto-select role if previously selected
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_POST['role'])): ?>
                const selectedRole = "<?= htmlspecialchars($_POST['role']) ?>";
                if (selectedRole) {
                    document.getElementById(`role_${selectedRole}`).checked = true;
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>