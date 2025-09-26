<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = '';

// Get user's email
$user_sql = "SELECT email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if ($user) {
    $user_email = $user['email'];
}

// Fetch user's clubs
$clubs = [];
$sql = "SELECT DISTINCT c.*, cm.member_type 
        FROM clubs c 
        JOIN club_members cm ON c.id = cm.club_id 
        WHERE cm.email = ? 
        ORDER BY c.group_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $clubs[] = $row;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_project'])) {
        // Add new project
        $club_id = $_POST['club_id'];
        $project_name = $_POST['project_name'];
        $description = $_POST['description'];
        $objectives = $_POST['objectives'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $budget = $_POST['budget'] ?: 0.00;
        $project_leader = $_POST['project_leader'];
        
        $insert_sql = "INSERT INTO projects (club_id, project_name, description, objectives, start_date, end_date, budget, project_leader, created_by) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssssdsi", $club_id, $project_name, $description, $objectives, $start_date, $end_date, $budget, $project_leader, $user_id);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "Project added successfully!";
        } else {
            $_SESSION['error'] = "Error adding project. Please try again.";
        }
    }
    
    if (isset($_POST['update_project'])) {
        // Update project
        $project_id = $_POST['project_id'];
        $project_name = $_POST['project_name'];
        $description = $_POST['description'];
        $objectives = $_POST['objectives'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $budget = $_POST['budget'] ?: 0.00;
        $project_leader = $_POST['project_leader'];
        $status = $_POST['status'];
        
        $update_sql = "UPDATE projects SET project_name=?, description=?, objectives=?, start_date=?, end_date=?, budget=?, project_leader=?, status=? WHERE id=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssssdssi", $project_name, $description, $objectives, $start_date, $end_date, $budget, $project_leader, $status, $project_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Project updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating project. Please try again.";
        }
    }
    
    if (isset($_POST['delete_project'])) {
        // Delete project
        $project_id = $_POST['project_id'];
        
        // Verify user has permission to delete this project
        $verify_sql = "SELECT p.id FROM projects p 
                       JOIN clubs c ON p.club_id = c.id 
                       JOIN club_members cm ON c.id = cm.club_id 
                       WHERE p.id = ? AND cm.email = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("is", $project_id, $user_email);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            $delete_sql = "DELETE FROM projects WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $project_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "Project deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting project. Please try again.";
            }
        } else {
            $_SESSION['error'] = "You don't have permission to delete this project.";
        }
    }
    
    // Redirect to avoid form resubmission
    header('Location: projects.php');
    exit();
}

// Fetch projects for the user's clubs
$projects = [];
if (!empty($clubs)) {
    $club_ids = array_column($clubs, 'id');
    $placeholders = str_repeat('?,', count($club_ids) - 1) . '?';
    
    $projects_sql = "SELECT p.*, c.group_name 
                     FROM projects p 
                     JOIN clubs c ON p.club_id = c.id 
                     WHERE p.club_id IN ($placeholders) 
                     ORDER BY p.status, p.created_at DESC";
    $projects_stmt = $conn->prepare($projects_sql);
    $projects_stmt->bind_param(str_repeat('i', count($club_ids)), ...$club_ids);
    $projects_stmt->execute();
    $projects_result = $projects_stmt->get_result();
    
    while ($row = $projects_result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Get project data for editing (if project_id is provided via GET for editing)
$edit_project_data = null;
if (isset($_GET['edit_project'])) {
    $project_id = $_GET['edit_project'];
    
    // Verify user has access to this project
    $verify_sql = "SELECT p.*, c.group_name 
                   FROM projects p 
                   JOIN clubs c ON p.club_id = c.id 
                   JOIN club_members cm ON c.id = cm.club_id 
                   WHERE p.id = ? AND cm.email = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("is", $project_id, $user_email);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $edit_project_data = $verify_result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - 3ZERO Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a5276;
            --primary-dark: #154360;
            --secondary: #28b463;
            --accent: #f39c12;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        .project-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .project-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .project-body {
            padding: 1.5rem;
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        .badge-planning { background: #6c757d; color: white; }
        .badge-in_progress { background: #17a2b8; color: white; }
        .badge-completed { background: var(--secondary); color: white; }
        .badge-on_hold { background: #ffc107; color: #212529; }
        .badge-cancelled { background: #dc3545; color: white; }
        .badge-not_started { background: #6c757d; color: white; }
        .badge-in_progress { background: #17a2b8; color: white; }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--gray-light);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .search-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .project-dates {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .status-dropdown {
            width: 150px;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <!-- Main Content Wrapper -->
    <div class="main-content" id="mainContent">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Projects</h1>
                <p class="text-muted">Manage and track your club projects</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                <i class="fas fa-plus me-2"></i>Add New Project
            </button>
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

        <!-- Search and Filter -->
        <div class="search-container">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search projects by name..." id="searchInput">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="planning">Planning</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="on_hold">On Hold</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="clubFilter">
                        <option value="">All Clubs</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['group_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Projects Grid -->
        <div class="row" id="projectsContainer">
            <?php if (empty($projects)): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <h3>No Projects Found</h3>
                        <p>You haven't created any projects yet. Start by adding your first project!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                    <div class="col-lg-6 col-xl-4 mb-4" data-status="<?= $project['status'] ?>" data-club="<?= $project['club_id'] ?>">
                        <div class="project-card">
                            <div class="project-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="h5 mb-2"><?= htmlspecialchars($project['project_name']) ?></h3>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="status-badge badge badge-<?= $project['status'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                                            </span>
                                            <small class="text-muted"><?= htmlspecialchars($project['group_name']) ?></small>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle dropdown-toggle" type="button" 
                                                id="dropdownMenuButton<?= $project['id'] ?>"
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?= $project['id'] ?>">
                                            <li>
                                                <a class="dropdown-item" href="projects.php?edit_project=<?= $project['id'] ?>">
                                                    <i class="fas fa-edit me-2"></i>Edit Project
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" 
                                                onclick="confirmDelete(<?= $project['id'] ?>, '<?= htmlspecialchars($project['project_name']) ?>')">
                                                    <i class="fas fa-trash me-2"></i>Delete Project
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="project-body">
                                <p class="text-muted"><?= htmlspecialchars(substr($project['description'], 0, 100)) ?>...</p>
                                
                                <div class="project-dates mb-3">
                                    <div><i class="fas fa-calendar-alt me-1"></i> Start: <?= date('M j, Y', strtotime($project['start_date'])) ?></div>
                                    <div><i class="fas fa-flag-checkered me-1"></i> End: <?= date('M j, Y', strtotime($project['end_date'])) ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Budget:</strong>
                                    <span class="text-muted">$<?= number_format($project['budget'], 2) ?></span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Project Leader:</strong>
                                    <span class="text-muted"><?= htmlspecialchars($project['project_leader']) ?></span>
                                </div>
                                
                                <div class="action-buttons">
                                    <a href="projects.php?edit_project=<?= $project['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <button class="btn btn-outline-danger btn-sm flex-fill" 
                                            onclick="confirmDelete(<?= $project['id'] ?>, '<?= htmlspecialchars($project['project_name']) ?>')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="projects.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProjectModalLabel">Add New Project</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="club_id" class="form-label">Club</label>
                                    <select class="form-select" id="club_id" name="club_id" required>
                                        <option value="">Select Club</option>
                                        <?php foreach ($clubs as $club): ?>
                                            <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['group_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_name" class="form-label">Project Name</label>
                                    <input type="text" class="form-control" id="project_name" name="project_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="objectives" class="form-label">Objectives</label>
                            <textarea class="form-control" id="objectives" name="objectives" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="budget" class="form-label">Budget ($)</label>
                                    <input type="number" class="form-control" id="budget" name="budget" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_leader" class="form-label">Project Leader</label>
                                    <input type="text" class="form-control" id="project_leader" name="project_leader">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_project" class="btn btn-primary">Add Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Project Modal -->
    <div class="modal fade <?= $edit_project_data ? 'show' : '' ?>" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true" <?= $edit_project_data ? 'style="display: block;"' : '' ?>>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="projects.php">
                    <input type="hidden" id="edit_project_id" name="project_id" value="<?= $edit_project_data['id'] ?? '' ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeEditModal()" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_club_id" class="form-label">Club</label>
                                    <select class="form-select" id="edit_club_id" name="club_id" required>
                                        <option value="">Select Club</option>
                                        <?php foreach ($clubs as $club): ?>
                                            <option value="<?= $club['id'] ?>" <?= ($edit_project_data['club_id'] ?? '') == $club['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($club['group_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_project_name" class="form-label">Project Name</label>
                                    <input type="text" class="form-control" id="edit_project_name" name="project_name" value="<?= htmlspecialchars($edit_project_data['project_name'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"><?= htmlspecialchars($edit_project_data['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_objectives" class="form-label">Objectives</label>
                            <textarea class="form-control" id="edit_objectives" name="objectives" rows="2"><?= htmlspecialchars($edit_project_data['objectives'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="edit_start_date" name="start_date" value="<?= $edit_project_data['start_date'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="edit_end_date" name="end_date" value="<?= $edit_project_data['end_date'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_budget" class="form-label">Budget ($)</label>
                                    <input type="number" class="form-control" id="edit_budget" name="budget" step="0.01" min="0" value="<?= $edit_project_data['budget'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_project_leader" class="form-label">Project Leader</label>
                                    <input type="text" class="form-control" id="edit_project_leader" name="project_leader" value="<?= htmlspecialchars($edit_project_data['project_leader'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="planning" <?= ($edit_project_data['status'] ?? '') == 'planning' ? 'selected' : '' ?>>Planning</option>
                                <option value="in_progress" <?= ($edit_project_data['status'] ?? '') == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= ($edit_project_data['status'] ?? '') == 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="on_hold" <?= ($edit_project_data['status'] ?? '') == 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                                <option value="cancelled" <?= ($edit_project_data['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" name="update_project" class="btn btn-primary">Update Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="projects.php">
                    <input type="hidden" id="delete_project_id" name="project_id">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="deleteConfirmModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the project "<span id="delete_project_name" class="fw-bold"></span>"?</p>
                        <p class="text-danger">This action cannot be undone. All associated milestones and tasks will also be deleted.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_project" class="btn btn-danger">Delete Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search and Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const clubFilter = document.getElementById('clubFilter');
            const projectCards = document.querySelectorAll('#projectsContainer .col-lg-6');
            
            function filterProjects() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                const clubValue = clubFilter.value;
                
                projectCards.forEach(card => {
                    const projectName = card.querySelector('h3').textContent.toLowerCase();
                    const projectStatus = card.getAttribute('data-status');
                    const projectClub = card.getAttribute('data-club');
                    
                    const matchesSearch = projectName.includes(searchTerm);
                    const matchesStatus = !statusValue || projectStatus === statusValue;
                    const matchesClub = !clubValue || projectClub === clubValue;
                    
                    if (matchesSearch && matchesStatus && matchesClub) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            
            searchInput.addEventListener('input', filterProjects);
            statusFilter.addEventListener('change', filterProjects);
            clubFilter.addEventListener('change', filterProjects);
            
            <?php if ($edit_project_data): ?>
                // Show edit modal if edit project data is loaded
                const editModal = new bootstrap.Modal(document.getElementById('editProjectModal'));
                editModal.show();
            <?php endif; ?>
        });
        
        function confirmDelete(projectId, projectName) {
            document.getElementById('delete_project_id').value = projectId;
            document.getElementById('delete_project_name').textContent = projectName;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            deleteModal.show();
        }
        
        function closeEditModal() {
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editProjectModal'));
            if (editModal) {
                editModal.hide();
            }
            // Remove the edit parameter from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        // Auto-close alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const editModal = document.getElementById('editProjectModal');
            
            if (editModal && event.target === editModal) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>