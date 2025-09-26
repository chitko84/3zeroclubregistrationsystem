<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch user's clubs from the database
$user_id = $_SESSION['user_id'];
$clubs = [];

// First, get the user's email from the users table
$user_sql = "SELECT email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if ($user) {
    $user_email = $user['email'];
    
    // Query to get clubs where the user is a member (matching by email in club_members)
    $sql = "SELECT DISTINCT c.*, cm.member_type 
            FROM clubs c 
            JOIN club_members cm ON c.id = cm.club_id 
            WHERE cm.email = ? 
            ORDER BY c.date_of_registration DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $clubs[] = $row;
    }

    // Get member counts for each club
    foreach ($clubs as &$club) {
        $count_sql = "SELECT COUNT(*) as member_count FROM club_members WHERE club_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $club['id']);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $club['member_count'] = $count_result->fetch_assoc()['member_count'];
    }
}

// Handle club deletion if requested
if (isset($_GET['delete_club'])) {
    $club_id = $_GET['delete_club'];
    
    // Verify user owns this club before deletion (check if user's email exists in club_members for this club)
    $verify_sql = "SELECT cm.id FROM club_members cm 
                   WHERE cm.club_id = ? AND cm.email = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("is", $club_id, $user_email);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        // Delete club members first (foreign key constraint)
        $delete_members_sql = "DELETE FROM club_members WHERE club_id = ?";
        $delete_members_stmt = $conn->prepare($delete_members_sql);
        $delete_members_stmt->bind_param("i", $club_id);
        $delete_members_stmt->execute();
        
        // Then delete the club
        $delete_club_sql = "DELETE FROM clubs WHERE id = ?";
        $delete_club_stmt = $conn->prepare($delete_club_sql);
        $delete_club_stmt->bind_param("i", $club_id);
        
        if ($delete_club_stmt->execute()) {
            $_SESSION['success'] = "Club deleted successfully!";
            header('Location: myclubs.php');
            exit();
        } else {
            $_SESSION['error'] = "Error deleting club. Please try again.";
        }
    } else {
        $_SESSION['error'] = "You don't have permission to delete this club.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Clubs - 3ZERO Club</title>
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

        .club-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
            transition: var(--transition);
        }

        .club-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .club-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
        }

        .club-body {
            padding: 1.5rem;
        }

        .club-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .stat-item {
            text-align: center;
            padding: 10px;
            background: var(--light);
            border-radius: var(--border-radius);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .member-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-right: 5px;
        }

        .badge-primary { background: var(--primary); color: white; }
        .badge-success { background: var(--secondary); color: white; }
        .badge-warning { background: var(--accent); color: white; }
        .badge-secondary { background: var(--gray); color: white; }
        .badge-danger { background: #dc3545; color: white; }

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

        .club-role {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        .search-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 10px;
        }

        /* Ensure dropdowns work properly */
        .dropdown-menu {
            display: none;
        }
        
        .dropdown-menu.show {
            display: block;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="main-content" id="mainContent">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">My Clubs</h1>
                <p class="text-muted">Manage and view all your registered clubs</p>
            </div>
            <a href="club_registration.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Register New Club
            </a>
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
                        <input type="text" class="form-control" placeholder="Search clubs by name..." id="searchInput">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sortFilter">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="name">Name A-Z</option>
                        <option value="status">Status</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Clubs Grid -->
        <div class="row" id="clubsContainer">
            <?php if (empty($clubs)): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No Clubs Found</h3>
                        <p>You haven't registered any clubs yet. Start by creating your first club!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($clubs as $club): ?>
                    <div class="col-lg-6 col-xl-4 mb-4" data-status="<?= $club['status'] ?>">
                        <div class="club-card">
                            <div class="club-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="h5 mb-2"><?= htmlspecialchars($club['group_name']) ?></h3>
                                        <div class="club-role">
                                            <span class="badge <?= 
                                                $club['member_type'] == 'key_person' ? 'badge-primary' : 
                                                ($club['member_type'] == 'deputy' ? 'badge-success' : 'badge-warning')
                                            ?>">
                                                <?= ucfirst(str_replace('_', ' ', $club['member_type'])) ?>
                                            </span>
                                            <span class="status-badge badge <?= 
                                                $club['status'] == 'approved' ? 'badge-success' : 
                                                ($club['status'] == 'pending' ? 'badge-warning' : 'badge-danger')
                                            ?>">
                                                <?= ucfirst($club['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <!-- Fixed dropdown button -->
                                        <button class="btn btn-sm btn-light rounded-circle dropdown-toggle" type="button" 
                                                id="dropdownMenuButton<?= $club['id'] ?>"
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        
                                        <!-- Fixed dropdown menu -->
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?= $club['id'] ?>">
                                            <li>
                                                <a class="dropdown-item" href="club_details.php?id=<?= $club['id'] ?>">
                                                    <i class="fas fa-eye me-2"></i>View Details
                                                </a>
                                            </li>
                                            <?php if ($club['status'] == 'pending' || $club['status'] == 'rejected'): ?>
                                            <li>
                                                <a class="dropdown-item" href="edit_club.php?id=<?= $club['id'] ?>">
                                                    <i class="fas fa-edit me-2"></i>Edit Club
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <!-- Delete option - this should always show up -->
                                                <a class="dropdown-item text-danger" href="#" 
                                                onclick="confirmDelete(<?= $club['id'] ?>, '<?= htmlspecialchars($club['group_name']) ?>')">
                                                    <i class="fas fa-trash me-2"></i>Delete Club
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="club-body">
                                <div class="club-stats">
                                    <div class="stat-item">
                                        <div class="stat-number"><?= $club['member_count'] ?></div>
                                        <div class="stat-label">Members</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?= date('M Y', strtotime($club['date_of_registration'])) ?></div>
                                        <div class="stat-label">Registered</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number">
                                            <?= 
                                                $club['status'] == 'approved' ? 'Active' : 
                                                ($club['status'] == 'pending' ? 'Pending' : 'Rejected')
                                            ?>
                                        </div>
                                        <div class="stat-label">Status</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Cluster Advisor:</strong>
                                    <span class="text-muted"><?= htmlspecialchars($club['cluster_advisor']) ?></span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Key Persons:</strong>
                                    <div>
                                        <span class="member-badge badge-primary"><?= htmlspecialchars($club['key_person_name']) ?></span>
                                        <?php if (!empty($club['deputy_key_person_name'])): ?>
                                            <span class="member-badge badge-success"><?= htmlspecialchars($club['deputy_key_person_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="action-buttons">
                                    <a href="club_details.php?id=<?= $club['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                    <?php if ($club['status'] == 'pending' || $club['status'] == 'rejected'): ?>
                                    <a href="edit_club.php?id=<?= $club['id'] ?>" class="btn btn-outline-secondary btn-sm flex-fill">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const clubCards = document.querySelectorAll('.col-lg-6');
        
        clubCards.forEach(card => {
            const clubName = card.querySelector('h3').textContent.toLowerCase();
            if (clubName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Status filter functionality
    document.getElementById('statusFilter').addEventListener('change', function() {
        const status = this.value;
        const clubCards = document.querySelectorAll('.col-lg-6');
        
        clubCards.forEach(card => {
            const cardStatus = card.getAttribute('data-status');
            if (status === '' || cardStatus === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Sort functionality
    document.getElementById('sortFilter').addEventListener('change', function() {
        const sortBy = this.value;
        const container = document.getElementById('clubsContainer');
        const clubCards = Array.from(container.querySelectorAll('.col-lg-6'));
        
        clubCards.sort((a, b) => {
            const aName = a.querySelector('h3').textContent;
            const bName = b.querySelector('h3').textContent;
            const aDateText = a.querySelector('.stat-item:nth-child(2) .stat-number').textContent;
            const bDateText = b.querySelector('.stat-item:nth-child(2) .stat-number').textContent;
            const aStatus = a.getAttribute('data-status');
            const bStatus = b.getAttribute('data-status');
            
            // Convert date text to proper Date object
            const parseDate = (dateText) => {
                const months = {
                    'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
                    'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11
                };
                const [month, year] = dateText.split(' ');
                return new Date(parseInt(year), months[month]);
            };
            
            switch(sortBy) {
                case 'name':
                    return aName.localeCompare(bName);
                case 'newest':
                    return parseDate(bDateText) - parseDate(aDateText);
                case 'oldest':
                    return parseDate(aDateText) - parseDate(bDateText);
                case 'status':
                    return aStatus.localeCompare(bStatus);
                default:
                    return 0;
            }
        });
        
        // Clear container and re-append sorted clubs
        container.innerHTML = '';
        clubCards.forEach(card => container.appendChild(card));
    });

    // Delete confirmation
    function confirmDelete(clubId, clubName) {
        if (confirm(`Are you sure you want to delete the club "${clubName}"? This action cannot be undone.`)) {
            window.location.href = `myclubs.php?delete_club=${clubId}`;
        }
    };

    // Manual dropdown initialization (in case auto-init doesn't work)
    document.addEventListener('DOMContentLoaded', function() {
        var dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(function(dropdown) {
            dropdown.addEventListener('click', function(e) {
                e.preventDefault();
                var menu = this.nextElementSibling;
                menu.classList.toggle('show');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.matches('.dropdown-toggle') && !e.target.closest('.dropdown-menu')) {
                var openMenus = document.querySelectorAll('.dropdown-menu.show');
                openMenus.forEach(function(menu) {
                    menu.classList.remove('show');
                });
            }
        });
    });
</script>
</body>
</html>