<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $group_name = trim($_POST['group_name']);
    $cluster_advisor = trim($_POST['cluster_advisor']);
    $date_of_registration = $_POST['date_of_registration'];
    
    // Key Person details
    $key_person_name = trim($_POST['key_person_name']);
    $key_person_student_id = trim($_POST['key_person_student_id']);
    
    // Deputy Key Person details
    $deputy_key_person_name = trim($_POST['deputy_key_person_name']);
    $deputy_key_person_student_id = trim($_POST['deputy_key_person_student_id']);
    
    // Members array
    $members = [];
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_POST["member{$i}_name"])) {
            $members[] = [
                'full_name' => trim($_POST["member{$i}_name"]),
                'student_id' => trim($_POST["member{$i}_student_id"]),
                'programme' => trim($_POST["member{$i}_programme"]),
                'nationality' => trim($_POST["member{$i}_nationality"]),
                'phone' => trim($_POST["member{$i}_phone"]),
                'email' => trim($_POST["member{$i}_email"]),
                'school_centre' => trim($_POST["member{$i}_school_centre"]),
                'intake_month_year' => trim($_POST["member{$i}_intake"]),
                'expected_graduation_year' => (int)$_POST["member{$i}_graduation_year"],
                'current_semester' => trim($_POST["member{$i}_current_semester"])
            ];
        }
    }
    
    // Validation
    $errors = [];
    
    if (empty($group_name)) $errors[] = "Group Name is required";
    if (empty($cluster_advisor)) $errors[] = "Cluster Advisor is required";
    if (empty($date_of_registration)) $errors[] = "Date of Registration is required";
    if (empty($key_person_name)) $errors[] = "Key Person Name is required";
    if (empty($key_person_student_id)) $errors[] = "Key Person Student ID is required";
    if (empty($deputy_key_person_name)) $errors[] = "Deputy Key Person Name is required";
    if (empty($deputy_key_person_student_id)) $errors[] = "Deputy Key Person Student ID is required";
    
    // Check if at least one member is provided
    if (count($members) === 0) {
        $errors[] = "At least one member must be added";
    }
    
    // Email validation for all members
    foreach ($members as $index => $member) {
        if (!filter_var($member['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format for member " . ($index + 1);
        }
    }
    
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert club information
            $club_sql = "INSERT INTO clubs (group_name, cluster_advisor, key_person_name, key_person_student_id, 
                         deputy_key_person_name, deputy_key_person_student_id, date_of_registration) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($club_sql);
            $stmt->bind_param("sssssss", $group_name, $cluster_advisor, $key_person_name, $key_person_student_id,
                             $deputy_key_person_name, $deputy_key_person_student_id, $date_of_registration);
            $stmt->execute();
            $club_id = $conn->insert_id;
            
            // Insert key person as member
            $key_person_sql = "INSERT INTO club_members (club_id, full_name, student_id, programme, nationality, 
                              phone, email, school_centre, intake_month_year, expected_graduation_year, 
                              current_semester, member_type) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'key_person')";
            $stmt = $conn->prepare($key_person_sql);
            $stmt->bind_param("issssssssis", $club_id, $key_person_name, $key_person_student_id,
                             $_POST['key_person_programme'], $_POST['key_person_nationality'],
                             $_POST['key_person_phone'], $_POST['key_person_email'],
                             $_POST['key_person_school_centre'], $_POST['key_person_intake'],
                             $_POST['key_person_graduation_year'], $_POST['key_person_current_semester']);
            $stmt->execute();
            
            // Insert deputy key person as member
            $deputy_sql = "INSERT INTO club_members (club_id, full_name, student_id, programme, nationality, 
                          phone, email, school_centre, intake_month_year, expected_graduation_year, 
                          current_semester, member_type) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'deputy')";
            $stmt = $conn->prepare($deputy_sql);
            $stmt->bind_param("issssssssis", $club_id, $deputy_key_person_name, $deputy_key_person_student_id,
                             $_POST['deputy_programme'], $_POST['deputy_nationality'],
                             $_POST['deputy_phone'], $_POST['deputy_email'],
                             $_POST['deputy_school_centre'], $_POST['deputy_intake'],
                             $_POST['deputy_graduation_year'], $_POST['deputy_current_semester']);
            $stmt->execute();
            
            // Insert regular members
            $member_sql = "INSERT INTO club_members (club_id, full_name, student_id, programme, nationality, 
                          phone, email, school_centre, intake_month_year, expected_graduation_year, 
                          current_semester, member_type) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'regular')";
            $stmt = $conn->prepare($member_sql);
            
            foreach ($members as $member) {
                $stmt->bind_param("issssssssis", $club_id, $member['full_name'], $member['student_id'],
                                 $member['programme'], $member['nationality'], $member['phone'],
                                 $member['email'], $member['school_centre'], $member['intake_month_year'],
                                 $member['expected_graduation_year'], $member['current_semester']);
                $stmt->execute();
            }
            
            $conn->commit();
            $_SESSION['success'] = "Club registration submitted successfully!";
            header('Location: dashboard.php');
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3ZERO Club Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1a5276;
            --light-blue: #e8f4fd;
            --dark-blue: #0e2a47;
        }
        
        body {
            background: linear-gradient(135deg, #e8f4fd 0%, #f0f8ff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .registration-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(26, 82, 118, 0.15);
            overflow: hidden;
        }
        
        .registration-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .registration-header h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
        }
        
        .registration-header p {
            opacity: 0.9;
            margin: 0.5rem 0 0 0;
        }
        
        .form-section {
            padding: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .section-title {
            color: var(--primary-blue);
            border-bottom: 2px solid var(--light-blue);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .member-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .member-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .member-title {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .btn-primary {
            background: var(--primary-blue);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: var(--dark-blue);
            transform: translateY(-1px);
        }
        
        .form-label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e0;
        }
        
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(26, 82, 118, 0.25);
        }
        
        .required::after {
            content: " *";
            color: #e53e3e;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="registration-container">
        <div class="registration-header">
            <h1><i class="bi bi-people-fill me-2"></i>3ZERO Club Registration</h1>
            <p>Register your student club with all required member information</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form action="club_registration.php" method="POST">
            <!-- Club Information Section -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-info-circle me-2"></i>Club Information</h3>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="date_of_registration" class="form-label required">Date of Registration</label>
                        <input type="date" class="form-control" id="date_of_registration" name="date_of_registration" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="group_name" class="form-label required">Group Name</label>
                        <input type="text" class="form-control" id="group_name" name="group_name" 
                               placeholder="Enter group name" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="cluster_advisor" class="form-label required">Cluster Advisor</label>
                        <input type="text" class="form-control" id="cluster_advisor" name="cluster_advisor" 
                               placeholder="Enter cluster advisor name" required>
                    </div>
                </div>
            </div>
            
            <!-- Key Person Section -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-person-badge me-2"></i>Key Person</h3>
                <div class="member-card">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="key_person_name" class="form-label required">Full Name</label>
                            <input type="text" class="form-control" id="key_person_name" name="key_person_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="key_person_student_id" class="form-label required">Student ID</label>
                            <input type="text" class="form-control" id="key_person_student_id" name="key_person_student_id" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="key_person_programme" class="form-label required">Programme</label>
                            <input type="text" class="form-control" id="key_person_programme" name="key_person_programme" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="key_person_nationality" class="form-label required">Nationality</label>
                            <input type="text" class="form-control" id="key_person_nationality" name="key_person_nationality" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="key_person_phone" class="form-label required">Phone</label>
                            <input type="tel" class="form-control" id="key_person_phone" name="key_person_phone" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="key_person_email" class="form-label required">Email</label>
                            <input type="email" class="form-control" id="key_person_email" name="key_person_email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="key_person_school_centre" class="form-label required">School/Centre</label>
                            <input type="text" class="form-control" id="key_person_school_centre" name="key_person_school_centre" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="key_person_intake" class="form-label required">Intake (Month/Year)</label>
                            <input type="text" class="form-control" id="key_person_intake" name="key_person_intake" placeholder="e.g., March 2023" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="key_person_graduation_year" class="form-label required">Expected Year of Graduation</label>
                            <input type="number" class="form-control" id="key_person_graduation_year" name="key_person_graduation_year" min="2023" max="2030" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="key_person_current_semester" class="form-label required">Current Semester (Year/Sem)</label>
                            <input type="text" class="form-control" id="key_person_current_semester" name="key_person_current_semester" placeholder="e.g., Year 2 Sem 1" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Deputy Key Person Section -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-person-check me-2"></i>Deputy Key Person</h3>
                <div class="member-card">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="deputy_key_person_name" class="form-label required">Full Name</label>
                            <input type="text" class="form-control" id="deputy_key_person_name" name="deputy_key_person_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="deputy_key_person_student_id" class="form-label required">Student ID</label>
                            <input type="text" class="form-control" id="deputy_key_person_student_id" name="deputy_key_person_student_id" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="deputy_programme" class="form-label required">Programme</label>
                            <input type="text" class="form-control" id="deputy_programme" name="deputy_programme" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="deputy_nationality" class="form-label required">Nationality</label>
                            <input type="text" class="form-control" id="deputy_nationality" name="deputy_nationality" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="deputy_phone" class="form-label required">Phone</label>
                            <input type="tel" class="form-control" id="deputy_phone" name="deputy_phone" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="deputy_email" class="form-label required">Email</label>
                            <input type="email" class="form-control" id="deputy_email" name="deputy_email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="deputy_school_centre" class="form-label required">School/Centre</label>
                            <input type="text" class="form-control" id="deputy_school_centre" name="deputy_school_centre" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="deputy_intake" class="form-label required">Intake (Month/Year)</label>
                            <input type="text" class="form-control" id="deputy_intake" name="deputy_intake" placeholder="e.g., March 2023" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="deputy_graduation_year" class="form-label required">Expected Year of Graduation</label>
                            <input type="number" class="form-control" id="deputy_graduation_year" name="deputy_graduation_year" min="2023" max="2030" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="deputy_current_semester" class="form-label required">Current Semester (Year/Sem)</label>
                            <input type="text" class="form-control" id="deputy_current_semester" name="deputy_current_semester" placeholder="e.g., Year 2 Sem 1" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Members Section (1-5) -->
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-people me-2"></i>Club Members</h3>
                <p class="text-muted mb-3">Add at least one member (up to 5 members including key persons)</p>
                
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="member-card" id="member-<?= $i ?>">
                    <div class="member-header">
                        <span class="member-title">Member <?= $i ?></span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="member<?= $i ?>_name" class="form-label">Full Name & Student ID</label>
                            <input type="text" class="form-control" id="member<?= $i ?>_name" name="member<?= $i ?>_name" 
                                   placeholder="Full Name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="member<?= $i ?>_student_id" class="form-label" style="visibility: hidden;">Student ID</label>
                            <input type="text" class="form-control" id="member<?= $i ?>_student_id" name="member<?= $i ?>_student_id" 
                                   placeholder="Student ID">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="member<?= $i ?>_programme" class="form-label">Programme</label>
                            <input type="text" class="form-control" id="member<?= $i ?>_programme" name="member<?= $i ?>_programme">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="member<?= $i ?>_nationality" class="form-label">Nationality</label>
                            <input type="text" class="form-control" id="member<?= $i ?>_nationality" name="member<?= $i ?>_nationality">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="member<?= $i ?>_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="member<?= $i ?>_phone" name="member<?= $i ?>_phone">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="member<?= $i ?>_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="member<?= $i ?>_email" name="member<?= $i ?>_email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="member<?= $i ?>_school_centre" class="form-label">School/Centre</label>
                            <input type="text" class="form-control" id="member<?= $i ?>_school_centre" name="member<?= $i ?>_school_centre">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="member<?= $i ?>_intake" class="form-label">Intake (Month/Year)</label>
                            <input type="text" class="form-control" id="member<?= $i ?>_intake" name="member<?= $i ?>_intake" placeholder="e.g., March 2023">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="member<?= $i ?>_graduation_year" class="form-label">Expected Year of Graduation</label>
                            <input type="number" class="form-control" id="member<?= $i ?>_graduation_year" name="member<?= $i ?>_graduation_year" min="2023" max="2030">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="member<?= $i ?>_current_semester" class="form-label">Current Semester (Year/Sem)</label>
                            <input type="text" class="form-control" id="member<?= $i ?>_current_semester" name="member<?= $i ?>_current_semester" placeholder="e.g., Year 2 Sem 1">
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Submit Section -->
            <div class="form-section text-center">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-send-check me-2"></i>Submit Registration
                </button>
                <p class="text-muted mt-3">All fields marked with * are required</p>
            </div>
        </form>
    </div>

    <?php include('footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-fill current date
        document.addEventListener('DOMContentLoaded', function() {
            // Set today's date as default for registration date
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date_of_registration').value = today;
        });
    </script>
</body>
</html>