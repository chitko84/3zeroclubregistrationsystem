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

// Get all members
$members_sql = "SELECT * FROM club_members WHERE club_id = ? ORDER BY 
               CASE member_type 
                   WHEN 'key_person' THEN 1 
                   WHEN 'deputy' THEN 2 
                   ELSE 3 
               END";
$members_stmt = $conn->prepare($members_sql);
$members_stmt->bind_param("i", $club_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();
$members = [];
while ($row = $members_result->fetch_assoc()) {
    $members[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($club['group_name']) ?> - Club Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="myclubs.php">My Clubs</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($club['group_name']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><?= htmlspecialchars($club['group_name']) ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Cluster Advisor:</strong> <?= htmlspecialchars($club['cluster_advisor']) ?></p>
                                <p><strong>Key Person:</strong> <?= htmlspecialchars($club['key_person_name']) ?> (<?= htmlspecialchars($club['key_person_student_id']) ?>)</p>
                                <p><strong>Deputy Key Person:</strong> <?= htmlspecialchars($club['deputy_key_person_name'] ?: 'Not specified') ?> <?= $club['deputy_key_person_student_id'] ? '(' . htmlspecialchars($club['deputy_key_person_student_id']) . ')' : '' ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Registration Date:</strong> <?= date('F j, Y', strtotime($club['date_of_registration'])) ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?= 
                                        $club['status'] == 'approved' ? 'success' : 
                                        ($club['status'] == 'pending' ? 'warning' : 'danger')
                                    ?>">
                                        <?= ucfirst($club['status']) ?>
                                    </span>
                                </p>
                                <p><strong>Your Role:</strong> 
                                    <span class="badge bg-info">
                                        <?= ucfirst(str_replace('_', ' ', $club['member_type'])) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Club Members</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Student ID</th>
                                        <th>Programme</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($member['full_name'] ?? 'Not provided') ?></td>
                                        <td><?= htmlspecialchars($member['student_id'] ?? 'Not provided') ?></td>
                                        <td><?= htmlspecialchars($member['programme'] ?? 'Not provided') ?></td>
                                        <td><?= htmlspecialchars($member['email'] ?? 'Not provided') ?></td>
                                        <td><?= htmlspecialchars($member['phone'] ?? 'Not provided') ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $member['member_type'] == 'key_person' ? 'primary' : 
                                                ($member['member_type'] == 'deputy' ? 'success' : 'warning')
                                            ?>">
                                                <?= ucfirst(str_replace('_', ' ', $member['member_type'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="myclubs.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to My Clubs
                            </a>
                            <?php if ($club['status'] == 'pending' || $club['status'] == 'rejected'): ?>
                            <a href="edit_club.php?id=<?= $club['id'] ?>" class="btn btn-outline-warning">
                                <i class="fas fa-edit me-2"></i>Edit Club
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>