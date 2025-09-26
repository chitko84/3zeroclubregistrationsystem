<?php
include 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic email validation first
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header('Location: register.php'); 
        exit();
    }

    $email = $_POST['email'];

    // Check duplicate email
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "This email is already registered. Please log in.";
        header('Location: login.php'); 
        exit();
    }

    // Collect fields
    $name  = trim($_POST['name']);
    $date_of_birth = $_POST['date_of_birth'];
    $phone_number  = preg_replace('/\D+/', '', $_POST['phone_number']); // keep digits
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $department = $_POST['department'] ?? null;
    $program_of_study = $_POST['program_of_study'] ?? null;
    $intake = $_POST['intake'] ?? null;
    $country = $_POST['country'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $expected_graduation_year = !empty($_POST['expected_graduation_year']) 
        ? (int) $_POST['expected_graduation_year'] 
        : null;

    // Validation checks
    if (empty($name) || empty($date_of_birth) || empty($phone_number) || 
        empty($department) || empty($program_of_study) || empty($intake) || 
        empty($country) || empty($gender) || empty($expected_graduation_year)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header('Location: register.php'); 
        exit();
    }

    // Phone validation
    if (!preg_match('/^\d{5,}$/', $phone_number)) {
        $_SESSION['error'] = "Enter a valid phone number (at least 5 digits).";
        header('Location: register.php'); 
        exit();
    }

    // Password validation
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/', $password)) {
        $_SESSION['error'] = "Password must be 8+ chars with letters, numbers & a symbol.";
        header('Location: register.php'); 
        exit();
    }
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
        header('Location: register.php'); 
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Profile picture (optional)
    $profile_pic = 'default-profile.jpg';
    if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','webp'];
        $maxBytes = 2 * 1024 * 1024; // 2MB
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Only JPG, PNG or WEBP images allowed.";
            header('Location: register.php'); 
            exit();
        }
        if ($_FILES['profile_pic']['size'] > $maxBytes) {
            $_SESSION['error'] = "Image too large (max 2MB).";
            header('Location: register.php'); 
            exit();
        }

        $upload_dir = __DIR__ . '/user/uploads/';
        if (!is_dir($upload_dir)) { 
            mkdir($upload_dir, 0755, true); 
        }
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;

        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $filename)) {
            $_SESSION['error'] = "Failed to save the uploaded image.";
            header('Location: register.php'); 
            exit();
        }
        // Save relative path for DB
        $profile_pic = 'user/uploads/' . $filename;
    }

    $role = 'user';

    $sql = "INSERT INTO users 
        (name, date_of_birth, phone_number, email, password, role, profile_pic,
         department, program_of_study, intake, country, gender, expected_graduation_year)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header('Location: register.php'); 
        exit();
    }

    // 12 strings + 1 int (YEAR) => "ssssssssssssi"
    $stmt->bind_param(
        "ssssssssssssi",
        $name, $date_of_birth, $phone_number, $email, $hashed_password, $role, $profile_pic,
        $department, $program_of_study, $intake, $country, $gender, $expected_graduation_year
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! Please log in.";
        header('Location: login.php'); 
        exit();
    } else {
        $_SESSION['error'] = "Registration failed. Try again.";
        header('Location: register.php'); 
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - 3ZERO Club</title>
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
        padding-top: 80px; /* Added to prevent header overlap */
    }

    .auth-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 3rem 2.5rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(26, 82, 118, 0.12);
        width: 90%;
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

    .form-label.required::after {
        content: " *";
        color: #dc3545;
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

    .password-strength {
        height: 4px;
        background-color: #e0e0e0;
        border-radius: 2px;
        margin-top: 0.5rem;
        overflow: hidden;
    }

    .password-strength-bar {
        height: 100%;
        width: 0%;
        transition: width 0.3s ease, background-color 0.3s ease;
    }

    .section-title {
        color: var(--primary-blue);
        padding-bottom: 0.5rem;
        margin: 1.0rem 0 1rem 0;
        font-size: 0.5rem;
        font-weight: 600;
    }

    .form-section {
        background: #f8fafc;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        border-left: 4px solid var(--primary-blue);
    }

    /* Section title icons */
    .section-title i {
        font-size: 1rem;
    }

    @media (max-width: 576px) {
        .auth-container {
            padding: 2rem 1.5rem;
            margin: 1rem auto;
        }
        
        .section-title {
            font-size: 1rem;
        }
        
        body {
            padding-top: 70px;
        }
    }

    .header-logo {
        height: 50px;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    </style>
</head>

<body>
    <?php include('header.php'); ?>

    <div class="container py-4 flex-grow-1">
        <div class="auth-container">
            <div class="auth-header">
                <h2 class="auth-title">Join 3ZERO Club</h2>
                <p class="auth-subtitle">Create your personal account to start your journey towards a better world</p>
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

            <form action="register.php" method="POST" enctype="multipart/form-data">
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h4 class="section-title" style="font-size:1.7rem;">Personal Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label required">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label required">Date of Birth</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar"></i></span>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required value="<?= isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : '' ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone_number" class="form-label required">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" placeholder="01123456789" required value="<?= isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : '' ?>">
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label required">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="country" class="form-label required">Country</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-globe"></i></span>
                                <select class="form-control" id="country" name="country" required>
                                    <option value="">-- Select Country --</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label required">Gender</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-gender-ambiguous"></i></span>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Information Section -->
                <div class="form-section">
                    <h4 class="section-title" style="font-size:1.7rem;">Academic Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label required">Department</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                <select class="form-control" id="department" name="department" required>
                                    <option value="">-- Select Department --</option>
                                    <option value="School Of Business & Social Sciences" <?= (isset($_POST['department']) && $_POST['department'] == 'School Of Business & Social Sciences') ? 'selected' : '' ?>>School of Business & Social Sciences</option>
                                    <option value="School Of Education & Human Sciences" <?= (isset($_POST['department']) && $_POST['department'] == 'School Of Education & Human Sciences') ? 'selected' : '' ?>>School of Education & Human Sciences</option>
                                    <option value="School Of Computing and Informatics" <?= (isset($_POST['department']) && $_POST['department'] == 'School Of Computing and Informatics') ? 'selected' : '' ?>>School of Computing and Informatics</option>
                                    <option value="Centre for Foundation and General Studies" <?= (isset($_POST['department']) && $_POST['department'] == 'Centre for Foundation and General Studies') ? 'selected' : '' ?>>Centre for Foundation and General Studies</option>
                                    <option value="Language Center (LC)" <?= (isset($_POST['department']) && $_POST['department'] == 'Language Center (LC)') ? 'selected' : '' ?>>Language Center (LC)</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="program_of_study" class="form-label required">Program of Study</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-journal"></i></span>
                                <select class="form-control" id="program_of_study" name="program_of_study" required>
                                    <option value="">-- Select Program --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="intake" class="form-label required">Intake</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                <select class="form-control" id="intake" name="intake" required>
                                    <option value="">-- Select Intake --</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="expected_graduation_year" class="form-label required">Expected Graduation Year</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-mortarboard"></i></span>
                                <select class="form-control" id="expected_graduation_year" name="expected_graduation_year" required>
                                    <option value="">-- Select Year --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Security Section -->
                <div class="form-section">
                    <h4 class="section-title" style="font-size:1.7rem;">Account Security
                    </h4>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label required">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                                <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                    <i class="bi bi-eye-slash-fill"></i>
                                </span>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <small class="form-text text-muted">Minimum 8 characters with letters, numbers, and special characters</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label required">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                                <span class="input-group-text" id="toggleConfirmPassword" style="cursor: pointer;">
                                    <i class="bi bi-eye-slash-fill"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-auth">
                    <i class="bi bi-person-plus-fill me-2"></i> Create Account
                </button>
            </form>

            <div class="auth-footer text-center mt-4">
                <p>Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-bold">Sign in here</a></p>
                <small class="text-muted">After registration, you can create or join a club from your dashboard</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Programs by department
        const programsByDept = {
            "School Of Business & Social Sciences": [
                "Bachelor of Business Administration (Honours)",
                "Bachelor of Business Administration with Computer Science (Honours)",
                "Bachelor of Business Administration (Honours) (Marketing)",
                "Bachelor of Business Administration (Honours) (Human Resource Management)",
                "Bachelor of Economics (Honours)",
                "Bachelor of Social Development (Honours)",
                "Bachelor of Finance (Islamic Finance) (Honours)",
                "Bachelor of Politics and International Relations (Honours)",
                "Master of Business Management",
                "Master in Social Business",
                "Doctor of Philosophy (Business Management)"
            ],
            "School Of Education & Human Sciences": [
                "Bachelor of Elementary Education (Honours)",
                "Bachelor in Early Childhood Education (Honours)",
                "Bachelor of Media and Communication (Honours)",
                "Master of Education",
                "Doctor of Philosophy (Education)"
            ],
            "School Of Computing and Informatics": [
                "Bachelor in Computer Science (Honours)",
                "Bachelor in Data Science (Honours)"
            ],
            "Centre for Foundation and General Studies": [
                "Foundation in Computing",
                "Foundation in Arts"
            ],
            "Language Center (LC)": ["Language Studies"]
        };

        // Complete list of countries
        const countries = [
            "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", 
            "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan", "Bahamas", 
            "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", 
            "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", 
            "Bulgaria", "Burkina Faso", "Burundi", "Cabo Verde", "Cambodia", "Cameroon", 
            "Canada", "Central African Republic", "Chad", "Chile", "China", "Colombia", 
            "Comoros", "Congo (Congo-Brazzaville)", "Costa Rica", "Croatia", "Cuba", 
            "Cyprus", "Czechia (Czech Republic)", "Democratic Republic of the Congo", 
            "Denmark", "Djibouti", "Dominica", "Dominican Republic", "Ecuador", "Egypt", 
            "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Eswatini", 
            "Ethiopia", "Fiji", "Finland", "France", "Gabon", "Gambia", "Georgia", 
            "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", 
            "Guyana", "Haiti", "Honduras", "Hungary", "Iceland", "India", "Indonesia", 
            "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", 
            "Kazakhstan", "Kenya", "Kiribati", "Kuwait", "Kyrgyzstan", "Laos", "Latvia", 
            "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", 
            "Luxembourg", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", 
            "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", 
            "Moldova", "Monaco", "Mongolia", "Montenegro", "Morocco", "Mozambique", 
            "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "New Zealand", 
            "Nicaragua", "Niger", "Nigeria", "North Korea", "North Macedonia", "Norway", 
            "Oman", "Pakistan", "Palau", "Palestine", "Panama", "Papua New Guinea", 
            "Paraguay", "Peru", "Philippines", "Poland", "Portugal", "Qatar", "Romania", 
            "Russia", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", 
            "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", 
            "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", 
            "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", 
            "South Korea", "South Sudan", "Spain", "Sri Lanka", "Sudan", "Suriname", 
            "Sweden", "Switzerland", "Syria", "Tajikistan", "Tanzania", "Thailand", 
            "Timor-Leste", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", 
            "Turkmenistan", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", 
            "United Kingdom", "United States of America", "Uruguay", "Uzbekistan", 
            "Vanuatu", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe"
        ];

        // Initialize form elements
        document.addEventListener('DOMContentLoaded', function() {
            // Populate countries
            const countrySelect = document.getElementById('country');
            countries.sort().forEach(country => {
                let option = document.createElement('option');
                option.value = country;
                option.textContent = country;
                countrySelect.appendChild(option);
            });

            // Set previously selected country if exists
            <?php if (isset($_POST['country'])): ?>
                const selectedCountry = "<?= htmlspecialchars($_POST['country']) ?>";
                if (selectedCountry) {
                    countrySelect.value = selectedCountry;
                }
            <?php endif; ?>

            // Populate intake years
            const intakeSelect = document.getElementById('intake');
            const currentYear = new Date().getFullYear();
            for (let year = currentYear - 5; year <= currentYear + 2; year++) {
                ["March", "October"].forEach(month => {
                    let option = document.createElement('option');
                    option.value = `${month} ${year}`;
                    option.textContent = `${month} ${year}`;
                    intakeSelect.appendChild(option);
                });
            }

            // Set previously selected intake if exists
            <?php if (isset($_POST['intake'])): ?>
                const selectedIntake = "<?= htmlspecialchars($_POST['intake']) ?>";
                if (selectedIntake) {
                    intakeSelect.value = selectedIntake;
                }
            <?php endif; ?>

            // Populate graduation years
            const gradSelect = document.getElementById('expected_graduation_year');
            for (let year = currentYear; year <= currentYear + 6; year++) {
                let option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                gradSelect.appendChild(option);
            }

            // Set previously selected graduation year if exists
            <?php if (isset($_POST['expected_graduation_year'])): ?>
                const selectedGradYear = "<?= htmlspecialchars($_POST['expected_graduation_year']) ?>";
                if (selectedGradYear) {
                    gradSelect.value = selectedGradYear;
                }
            <?php endif; ?>

            // Department change handler
            const departmentSelect = document.getElementById('department');
            const programSelect = document.getElementById('program_of_study');
            
            function updatePrograms() {
                const dept = departmentSelect.value;
                programSelect.innerHTML = '<option value="">-- Select Program --</option>';
                
                if (programsByDept[dept]) {
                    programsByDept[dept].forEach(program => {
                        let option = document.createElement('option');
                        option.value = program;
                        option.textContent = program;
                        programSelect.appendChild(option);
                    });
                }
                
                // Set previously selected program if exists
                <?php if (isset($_POST['program_of_study'])): ?>
                    const selectedProgram = "<?= htmlspecialchars($_POST['program_of_study']) ?>";
                    if (selectedProgram && programsByDept[dept] && programsByDept[dept].includes(selectedProgram)) {
                        programSelect.value = selectedProgram;
                    }
                <?php endif; ?>
            }

            departmentSelect.addEventListener('change', updatePrograms);
            
            // Initialize programs on page load
            updatePrograms();
        });

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

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPassword = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (confirmPassword.type === 'password') {
                confirmPassword.type = 'text';
                icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
            } else {
                confirmPassword.type = 'password';
                icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
            }
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 25;
            if (/[A-Z]/.test(password)) strength += 15;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            strengthBar.style.width = Math.min(strength, 100) + '%';
            
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#fd7e14';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
            }
        });
    </script>
</body>
</html>