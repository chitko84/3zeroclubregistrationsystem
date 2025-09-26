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

// Handle form submission (add achievement)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Achievement
    if (isset($_POST['add_achievement'])) {
        $club_id     = intval($_POST['club_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $achieved_on = $_POST['achieved_on'] ?? null;

        // Verify user belongs to the club
        $verify_sql = "SELECT 1 
                       FROM club_members cm 
                       WHERE cm.club_id = ? AND cm.email = ? 
                       LIMIT 1";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("is", $club_id, $user_email);
        $verify_stmt->execute();
        $has_access = $verify_stmt->get_result()->num_rows > 0;

        if (!$has_access) {
            $_SESSION['error'] = "You don't have permission to add achievements for this club.";
            header('Location: achievements.php');
            exit();
        }

        // Photo validations
        $files = $_FILES['photos'] ?? null;

        // Count non-empty uploads
        $fileCount = 0;
        $totalBytes = 0;
        if ($files && isset($files['name']) && is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK && $files['size'][$i] > 0 && $files['name'][$i] !== '') {
                    $fileCount++;
                    $totalBytes += (int)$files['size'][$i];
                }
            }
        }

        if ($fileCount < 1 || $fileCount > 3) {
            $_SESSION['error'] = "Please upload between 1 and 3 photos.";
            header('Location: achievements.php');
            exit();
        }

        // 10 MB total limit
        $maxTotal = 10 * 1024 * 1024;
        if ($totalBytes > $maxTotal) {
            $_SESSION['error'] = "Total photos size must be 10 MB or less.";
            header('Location: achievements.php');
            exit();
        }

        // Insert achievement
        $insert_sql = "INSERT INTO achievements (club_id, title, description, achieved_on, created_by) 
                       VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssi", $club_id, $title, $description, $achieved_on, $user_id);

        if (!$insert_stmt->execute()) {
            $_SESSION['error'] = "Error saving achievement. Please try again.";
            header('Location: achievements.php');
            exit();
        }

        $achievement_id = $insert_stmt->insert_id;

        // Prepare uploads dir
        $uploadDir = __DIR__ . '/../uploads/achievements';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        // Validate and move each file
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $saved = 0;

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK && $files['size'][$i] > 0 && $files['name'][$i] !== '') {
                $tmpPath = $files['tmp_name'][$i];
                $mime = $finfo->file($tmpPath);
                if (!in_array($mime, $allowedMime, true)) {
                    $_SESSION['error'] = "Only JPG, PNG, or WEBP images are allowed.";
                    // Rollback achievement + any saved photos
                    $conn->query("DELETE FROM achievements WHERE id = " . intval($achievement_id));
                    header('Location: achievements.php');
                    exit();
                }

                $ext = match ($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    default      => 'bin'
                };

                $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($files['name'][$i], PATHINFO_FILENAME));
                $newName = uniqid('ach_', true) . '_' . $safeBase . '.' . $ext;
                $destAbs = $uploadDir . '/' . $newName;
                $destRel = 'uploads/achievements/' . $newName; // path to store in DB (relative to web root of this app)

                if (!move_uploaded_file($tmpPath, $destAbs)) {
                    $_SESSION['error'] = "Failed to upload one of the images.";
                    // Rollback
                    $conn->query("DELETE FROM achievements WHERE id = " . intval($achievement_id));
                    header('Location: achievements.php');
                    exit();
                }

                // Save photo record
                $photo_sql  = "INSERT INTO achievement_photos (achievement_id, file_path, original_name) VALUES (?, ?, ?)";
                $photo_stmt = $conn->prepare($photo_sql);
                $orig = $files['name'][$i];
                $photo_stmt->bind_param("iss", $achievement_id, $destRel, $orig);
                $photo_stmt->execute();
                $saved++;
            }
        }

        if ($saved < 1) {
            $_SESSION['error'] = "No valid images were uploaded.";
            // Rollback
            $conn->query("DELETE FROM achievements WHERE id = " . intval($achievement_id));
        } else {
            $_SESSION['success'] = "Achievement added successfully!";
        }

        header('Location: achievements.php');
        exit();
    }
    
    // Edit Achievement
    if (isset($_POST['edit_achievement'])) {
        $achievement_id = intval($_POST['achievement_id'] ?? 0);
        $club_id     = intval($_POST['club_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $achieved_on = $_POST['achieved_on'] ?? null;

        // Verify user owns the achievement
        $verify_sql = "SELECT 1 FROM achievements WHERE id = ? AND created_by = ? LIMIT 1";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("ii", $achievement_id, $user_id);
        $verify_stmt->execute();
        $owns_achievement = $verify_stmt->get_result()->num_rows > 0;

        if (!$owns_achievement) {
            $_SESSION['error'] = "You don't have permission to edit this achievement.";
            header('Location: achievements.php');
            exit();
        }

        // Update achievement
        $update_sql = "UPDATE achievements SET club_id = ?, title = ?, description = ?, achieved_on = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("isssi", $club_id, $title, $description, $achieved_on, $achievement_id);

        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Achievement updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating achievement. Please try again.";
        }

        header('Location: achievements.php');
        exit();
    }
    
    // Delete Achievement
    if (isset($_POST['delete_achievement'])) {
        $achievement_id = intval($_POST['achievement_id'] ?? 0);

        // Verify user owns the achievement
        $verify_sql = "SELECT 1 FROM achievements WHERE id = ? AND created_by = ? LIMIT 1";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("ii", $achievement_id, $user_id);
        $verify_stmt->execute();
        $owns_achievement = $verify_stmt->get_result()->num_rows > 0;

        if (!$owns_achievement) {
            $_SESSION['error'] = "You don't have permission to delete this achievement.";
            header('Location: achievements.php');
            exit();
        }

        // Get photos to delete files
        $photos_sql = "SELECT file_path FROM achievement_photos WHERE achievement_id = ?";
        $photos_stmt = $conn->prepare($photos_sql);
        $photos_stmt->bind_param("i", $achievement_id);
        $photos_stmt->execute();
        $photos_result = $photos_stmt->get_result();
        
        // Delete physical files
        while ($photo = $photos_result->fetch_assoc()) {
            $file_path = __DIR__ . '/../' . $photo['file_path'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }

        // Delete from database
        $delete_sql = "DELETE FROM achievements WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $achievement_id);

        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Achievement deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting achievement. Please try again.";
        }

        header('Location: achievements.php');
        exit();
    }
}

// Fetch achievements for the user's clubs
$achievements = [];
$photosByAchievement = [];

if (!empty($clubs)) {
    $club_ids = array_column($clubs, 'id');
    $placeholders = implode(',', array_fill(0, count($club_ids), '?'));

    $ach_sql = "SELECT a.*, c.group_name 
                FROM achievements a
                JOIN clubs c ON a.club_id = c.id
                WHERE a.club_id IN ($placeholders)
                ORDER BY a.achieved_on DESC, a.created_at DESC";
    $ach_stmt = $conn->prepare($ach_sql);
    $types = str_repeat('i', count($club_ids));
    $ach_stmt->bind_param($types, ...$club_ids);
    $ach_stmt->execute();
    $ach_res = $ach_stmt->get_result();
    while ($row = $ach_res->fetch_assoc()) {
        $achievements[] = $row;
    }

    if (!empty($achievements)) {
        $ach_ids = array_column($achievements, 'id');
        $ph_place = implode(',', array_fill(0, count($ach_ids), '?'));
        $ph_sql = "SELECT id, achievement_id, file_path, original_name 
                   FROM achievement_photos 
                   WHERE achievement_id IN ($ph_place)
                   ORDER BY id ASC";
        $ph_stmt = $conn->prepare($ph_sql);
        $ph_types = str_repeat('i', count($ach_ids));
        $ph_stmt->bind_param($ph_types, ...$ach_ids);
        $ph_stmt->execute();
        $ph_res = $ph_stmt->get_result();
        while ($p = $ph_res->fetch_assoc()) {
            $aid = $p['achievement_id'];
            if (!isset($photosByAchievement[$aid])) $photosByAchievement[$aid] = [];
            $photosByAchievement[$aid][] = $p;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - 3ZERO Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5276;
            --primary-dark: #154360;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --shadow: 0 4px 6px rgba(0,0,0,0.08);
            --radius: 10px;
        }
        body {
            background-color: #f8f9fa;
        }
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .card-ach {
            border: 0;
            border-left: 4px solid var(--primary);
            box-shadow: var(--shadow);
            border-radius: var(--radius);
            overflow: hidden;
            transition: transform 0.2s;
            background: white;
        }
        .card-ach:hover {
            transform: translateY(-2px);
        }
        .card-ach .head {
            background: linear-gradient(135deg, #f8f9fa, #eef2f5);
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-light);
        }
        .thumbs img {
            height: 84px;
            width: 100%;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
        }
        .thumbs .col-4 { 
            margin-bottom: .5rem; 
            padding: 0 5px;
        }
        .empty-state {
            text-align: center;
            color: var(--gray);
            padding: 3rem 1rem;
        }
        .action-buttons {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin-left: 5px;
        }
        .page-header {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            width: 100%;
        }
        @media (max-width: 1200px) {
            .achievements-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }
        @media (max-width: 768px) {
            .main-content {
                padding: 0 10px;
            }
            .achievements-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .page-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            .d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            .action-buttons {
                position: relative;
                top: 0;
                right: 0;
                margin-top: 10px;
                display: flex;
                justify-content: center;
            }
            .card-ach .head div {
                padding-right: 0 !important;
            }
        }
        @media (max-width: 576px) {
            .achievements-grid {
                grid-template-columns: 1fr;
            }
            .thumbs .col-4 {
                flex: 0 0 33.333%;
                max-width: 33.333%;
            }
        }
    </style>
</head>
<body>
<?php include('header.php'); ?>

<div class="main-content container-fluid my-4" id="mainContent">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="mb-2">
                <h1 class="h2 mb-1">Achievements</h1>
                <p class="text-muted mb-0">Add and showcase your club wins (with photos)</p>
            </div>
            <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
                <i class="bi bi-trophy me-2"></i>Add Achievement
            </button>
        </div>
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

    <?php if (empty($achievements)): ?>
        <div class="empty-state">
            <i class="bi bi-image" style="font-size:3rem;"></i>
            <h4 class="mt-3">No achievements yet</h4>
            <p>Start by adding your first achievement — flex a little 😉</p>
        </div>
    <?php else: ?>
        <div class="achievements-grid">
            <?php foreach ($achievements as $a): ?>
                <div class="card-ach position-relative">
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editAchievementModal" 
                                onclick="setEditForm(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['title'])) ?>', '<?= htmlspecialchars(addslashes($a['description'])) ?>', '<?= $a['club_id'] ?>', '<?= $a['achieved_on'] ?>')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteAchievementModal" 
                                onclick="setDeleteId(<?= $a['id'] ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="head d-flex justify-content-between align-items-start">
                        <div style="padding-right: 80px;">
                            <h5 class="mb-1"><?= htmlspecialchars($a['title']) ?></h5>
                            <small class="text-muted">
                                <?= htmlspecialchars($a['group_name']) ?> &middot; 
                                <?= $a['achieved_on'] ? date('M j, Y', strtotime($a['achieved_on'])) : 'Date not set' ?>
                            </small>
                        </div>
                    </div>
                    <div class="p-3">
                        <?php if (!empty($a['description'])): ?>
                            <p class="mb-3"><?= nl2br(htmlspecialchars($a['description'])) ?></p>
                        <?php endif; ?>

                        <?php $phs = $photosByAchievement[$a['id']] ?? []; ?>
                        <?php if (!empty($phs)): ?>
                            <div class="row thumbs">
                                <?php foreach (array_slice($phs, 0, 3) as $p): ?>
                                    <div class="col-4">
                                        <a href="../<?= htmlspecialchars($p['file_path']) ?>" target="_blank" rel="noopener">
                                            <img src="../<?= htmlspecialchars($p['file_path']) ?>" alt="Achievement photo">
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Achievement Modal -->
<div class="modal fade" id="addAchievementModal" tabindex="-1" aria-labelledby="addAchievementLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="achievements.php" enctype="multipart/form-data">
        <div class="modal-header" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;">
          <h5 class="modal-title" id="addAchievementLabel">Add Achievement</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Club</label>
                    <select name="club_id" class="form-select" required>
                        <option value="">Select Club</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['group_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Achieved On</label>
                    <input type="date" name="achieved_on" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., 1st Place at State Hackathon" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description" rows="3" class="form-control" placeholder="Add a short description..."></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Photos (1–3, JPG/PNG/WEBP, total ≤ 10 MB)</label>
                    <input type="file" name="photos[]" id="photos" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple required>
                    <div class="form-text" id="photosHelp">You can select up to 3 images.</div>
                    <div id="preview" class="d-flex gap-2 mt-2 flex-wrap"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-primary" type="submit" name="add_achievement">Save Achievement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Achievement Modal -->
<div class="modal fade" id="editAchievementModal" tabindex="-1" aria-labelledby="editAchievementLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="achievements.php">
        <input type="hidden" name="achievement_id" id="edit_achievement_id">
        <div class="modal-header" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;">
          <h5 class="modal-title" id="editAchievementLabel">Edit Achievement</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Club</label>
                    <select name="club_id" id="edit_club_id" class="form-select" required>
                        <option value="">Select Club</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['group_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Achieved On</label>
                    <input type="date" name="achieved_on" id="edit_achieved_on" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" id="edit_title" class="form-control" placeholder="e.g., 1st Place at State Hackathon" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description" id="edit_description" rows="3" class="form-control" placeholder="Add a short description..."></textarea>
                </div>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Note: Photos cannot be edited. To change photos, delete and recreate the achievement.
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-primary" type="submit" name="edit_achievement">Update Achievement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Achievement Modal -->
<div class="modal fade" id="deleteAchievementModal" tabindex="-1" aria-labelledby="deleteAchievementLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="achievements.php">
        <input type="hidden" name="achievement_id" id="delete_achievement_id">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="deleteAchievementLabel">Confirm Delete</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this achievement? This action cannot be undone.</p>
            <p class="text-muted">All associated photos will also be permanently deleted.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-danger" type="submit" name="delete_achievement">Delete Achievement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Client-side guardrails: max 3 files, total ≤ 10MB; quick previews
document.getElementById('photos')?.addEventListener('change', function() {
    const files = Array.from(this.files || []);
    const preview = document.getElementById('preview');
    preview.innerHTML = '';

    if (files.length < 1) {
        this.setCustomValidity('Please select at least 1 image.');
    } else if (files.length > 3) {
        this.setCustomValidity('You can upload a maximum of 3 images.');
    } else {
        this.setCustomValidity('');
    }

    const total = files.reduce((s, f) => s + f.size, 0);
    if (total > 10 * 1024 * 1024) {
        this.setCustomValidity('Total size exceeds 10 MB.');
    }

    files.slice(0,3).forEach(f => {
        const url = URL.createObjectURL(f);
        const img = document.createElement('img');
        img.src = url;
        img.style.width = '90px';
        img.style.height = '90px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '6px';
        img.onload = () => URL.revokeObjectURL(url);
        preview.appendChild(img);
    });
});

// Edit form functions
function setEditForm(id, title, description, clubId, achievedOn) {
    document.getElementById('edit_achievement_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_club_id').value = clubId;
    document.getElementById('edit_achieved_on').value = achievedOn;
}

function setDeleteId(id) {
    document.getElementById('delete_achievement_id').value = id;
}

// Auto-close alerts
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => {
    try { 
        if (a.classList.contains('alert-dismissible')) {
            const alert = new bootstrap.Alert(a);
            alert.close();
        }
    } catch(e){}
  });
}, 5000);
</script>
</body>
</html>