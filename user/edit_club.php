<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: myclubs.php');
    exit();
}

$club_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get user email
$user_sql = "SELECT email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    header('Location: myclubs.php');
    exit();
}

// Get club details
$sql = "SELECT c.*, cm.member_type 
        FROM clubs c 
        JOIN club_members cm ON c.id = cm.club_id 
        WHERE c.id = ? AND cm.email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $club_id, $user['email']);
$stmt->execute();
$result = $stmt->get_result();
$club = $result->fetch_assoc();

if (!$club) {
    header('Location: myclubs.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic validation
    $errors = [];
    
    if (empty($_POST['group_name'])) {
        $errors[] = "Group name is required";
    }
    
    if (empty($_POST['cluster_advisor'])) {
        $errors[] = "Cluster advisor is required";
    }
    
    if (empty($_POST['key_person_name'])) {
        $errors[] = "Key person name is required";
    }
    
    if (empty($_POST['key_person_student_id'])) {
        $errors[] = "Key person student ID is required";
    }
    
    if (empty($errors)) {
        // Update club information
        $update_sql = "UPDATE clubs SET 
                      group_name = ?, 
                      cluster_advisor = ?, 
                      key_person_name = ?, 
                      key_person_student_id = ?,
                      deputy_key_person_name = ?,
                      deputy_key_person_student_id = ?,
                      status = 'pending' 
                      WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssi", 
            $_POST['group_name'],
            $_POST['cluster_advisor'],
            $_POST['key_person_name'],
            $_POST['key_person_student_id'],
            $_POST['deputy_key_person_name'],
            $_POST['deputy_key_person_student_id'],
            $club_id
        );
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Club updated successfully! Waiting for approval.";
            header('Location: myclubs.php');
            exit();
        } else {
            $errors[] = "Error updating club: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= htmlspecialchars($club['group_name']) ?> - 3ZERO Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="myclubs.php">My Clubs</a></li>
                <li class="breadcrumb-item"><a href="club_details.php?id=<?= $club_id ?>"><?= htmlspecialchars($club['group_name']) ?></a></li>
                <li class="breadcrumb-item active">Edit Club</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h3 class="mb-0">Edit Club: <?= htmlspecialchars($club['group_name']) ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="group_name" class="form-label">Group Name *</label>
                                <input type="text" class="form-control" id="group_name" name="group_name" 
                                       value="<?= htmlspecialchars($club['group_name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="cluster_advisor" class="form-label">Cluster Advisor *</label>
                                <input type="text" class="form-control" id="cluster_advisor" name="cluster_advisor" 
                                       value="<?= htmlspecialchars($club['cluster_advisor']) ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="key_person_name" class="form-label">Key Person Name *</label>
                                        <input type="text" class="form-control" id="key_person_name" name="key_person_name" 
                                               value="<?= htmlspecialchars($club['key_person_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="key_person_student_id" class="form-label">Key Person Student ID *</label>
                                        <input type="text" class="form-control" id="key_person_student_id" name="key_person_student_id" 
                                               value="<?= htmlspecialchars($club['key_person_student_id']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="deputy_key_person_name" class="form-label">Deputy Key Person Name</label>
                                        <input type="text" class="form-control" id="deputy_key_person_name" name="deputy_key_person_name" 
                                               value="<?= htmlspecialchars($club['deputy_key_person_name']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="deputy_key_person_student_id" class="form-label">Deputy Key Person Student ID</label>
                                        <input type="text" class="form-control" id="deputy_key_person_student_id" name="deputy_key_person_student_id" 
                                               value="<?= htmlspecialchars($club['deputy_key_person_student_id']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                After editing, your club status will be reset to "Pending" for approval.
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update Club</button>
                                <a href="club_details.php?id=<?= $club_id ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>