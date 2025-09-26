<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3ZERO Club Dashboard</title>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px 0;
            transition: var(--transition);
            box-shadow: var(--shadow);
            z-index: 100;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar-header h2 {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.5rem;
        }

        .sidebar-header h2 i {
            font-size: 1.8rem;
        }

        .club-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: bold;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--secondary);
        }

        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        /* Top Navigation */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .search-bar {
            display: flex;
            align-items: center;
            background: var(--gray-light);
            border-radius: 30px;
            padding: 8px 15px;
            width: 300px;
        }

        .search-bar input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            margin-left: 10px;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-icon, .user-profile {
            position: relative;
            cursor: pointer;
        }

        .notification-icon i {
            font-size: 1.2rem;
            color: var(--gray);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Dashboard Header */
        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .dashboard-header p {
            color: var(--gray);
        }

        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
        }

        .stat-1 .stat-icon { background: rgba(26, 82, 118, 0.2); color: var(--primary); }
        .stat-2 .stat-icon { background: rgba(40, 180, 99, 0.2); color: var(--secondary); }
        .stat-3 .stat-icon { background: rgba(243, 156, 18, 0.2); color: var(--accent); }
        .stat-4 .stat-icon { background: rgba(108, 117, 125, 0.2); color: var(--gray); }

        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Main Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.2rem;
        }

        .card-header a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .card-body {
            padding: 20px;
        }

        /* Club Activities List */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 1.2rem;
        }

        .activity-info h4 {
            margin-bottom: 5px;
        }

        .activity-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .activity-progress {
            margin-top: 8px;
            height: 5px;
            background: var(--gray-light);
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 5px;
        }

        /* Calendar */
        .calendar {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar th, .calendar td {
            text-align: center;
            padding: 10px;
            border: 1px solid var(--gray-light);
        }

        .calendar th {
            background: var(--gray-light);
            font-weight: 600;
        }

        .calendar .today {
            background: var(--primary);
            color: white;
            border-radius: 50%;
        }

        .calendar .event {
            background: var(--secondary);
            color: white;
            border-radius: 4px;
            font-size: 0.8rem;
            padding: 2px 5px;
            margin-top: 3px;
        }

        /* Activity Feed */
        .feed-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .feed-item:last-child {
            border-bottom: none;
        }

        .feed-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            background: var(--gray-light);
            color: var(--gray);
        }

        .feed-content h4 {
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .feed-content p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .feed-time {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 5px;
        }

        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 5px;
        }

        .badge-primary { background: var(--primary); color: white; }
        .badge-success { background: var(--secondary); color: white; }
        .badge-warning { background: var(--accent); color: white; }

        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-header h2 span, .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 15px;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .sidebar-menu {
                display: flex;
                overflow-x: auto;
            }
            
            .sidebar-menu li {
                flex: 1;
                min-width: 70px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
         <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a></li>
            <li><a href="club_registration.php" class="<?= basename($_SERVER['PHP_SELF']) == 'club_registration.php' ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i> <span>Register Club</span>
            </a></li>
            <li><a href="myclubs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'myclubs.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> <span>My Clubs</span>
            </a></li>
            <li><a href="events.php" class="<?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> <span>Events</span>
            </a></li>
            <li><a href="projects.php" class="<?= basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : '' ?>">
                <i class="fas fa-tasks"></i> <span>Projects</span>
            </a></li>
            <li><a href="achievements.php" class="<?= basename($_SERVER['PHP_SELF']) == 'achievements.php' ? 'active' : '' ?>">
                <i class="fas fa-certificate"></i> <span>Achievements</span>
            </a></li>
            <li><a href="discussions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'discussions.php' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i> <span>Discussions</span>
            </a></li>
            <li><a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user-edit"></i> <span>Profile</span>
            </a></li>
            <li><a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a></li>
            <?php endif; ?>
        </ul>
    </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <div class="top-nav">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search clubs, events, projects...">
                </div>
                <div class="user-actions">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">5</span>
                    </div>
                    <div class="user-profile">
                        <img src="https://i.pravatar.cc/150?img=32" alt="User Profile">
                    </div>
                </div>
            </div>

            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1>Welcome to 3ZERO Club, Alex!</h1>
                <p>Join the movement towards zero poverty, zero unemployment, and zero net carbon emissions.</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card stat-1">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>3</h3>
                        <p>Active Clubs</p>
                    </div>
                </div>
                <div class="stat-card stat-2">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>5</h3>
                        <p>Upcoming Events</p>
                    </div>
                </div>
                <div class="stat-card stat-3">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3>2</h3>
                        <p>Active Projects</p>
                    </div>
                </div>
                <div class="stat-card stat-4">
                    <div class="stat-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="stat-info">
                        <h3>7</h3>
                        <p>Points Earned</p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- My Clubs Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>My Clubs</h3>
                            <a href="#">Join More</a>
                        </div>
                        <div class="card-body">
                            <ul class="activity-list">
                                <li class="activity-item">
                                    <div class="activity-icon" style="background-color: #1a5276;">
                                        <i class="fas fa-recycle"></i>
                                    </div>
                                    <div class="activity-info">
                                        <h4>Sustainability Club <span class="badge badge-primary">President</span></h4>
                                        <p>Next meeting: Sep 30 - Campus Cleanup Drive</p>
                                        <div class="activity-progress">
                                            <div class="progress-bar" style="width: 85%; background-color: #1a5276;"></div>
                                        </div>
                                    </div>
                                </li>
                                <li class="activity-item">
                                    <div class="activity-icon" style="background-color: #28b463;">
                                        <i class="fas fa-hand-holding-heart"></i>
                                    </div>
                                    <div class="activity-info">
                                        <h4>Community Outreach <span class="badge badge-success">Member</span></h4>
                                        <p>Food donation drive - 50% of target achieved</p>
                                        <div class="activity-progress">
                                            <div class="progress-bar" style="width: 50%; background-color: #28b463;"></div>
                                        </div>
                                    </div>
                                </li>
                                <li class="activity-item">
                                    <div class="activity-icon" style="background-color: #f39c12;">
                                        <i class="fas fa-seedling"></i>
                                    </div>
                                    <div class="activity-info">
                                        <h4>Green Campus Initiative <span class="badge badge-warning">Volunteer</span></h4>
                                        <p>Tree planting event on Oct 5</p>
                                        <div class="activity-progress">
                                            <div class="progress-bar" style="width: 30%; background-color: #f39c12;"></div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Recent Activity Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Club Activities</h3>
                            <a href="#">See All</a>
                        </div>
                        <div class="card-body">
                            <div class="feed-item">
                                <div class="feed-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="feed-content">
                                    <h4>New member joined Sustainability Club</h4>
                                    <p>Sarah Johnson has joined your club as a volunteer.</p>
                                    <div class="feed-time">2 hours ago</div>
                                </div>
                            </div>
                            <div class="feed-item">
                                <div class="feed-icon">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div class="feed-content">
                                    <h4>New event scheduled</h4>
                                    <p>"Zero Waste Workshop" has been scheduled for Oct 15.</p>
                                    <div class="feed-time">5 hours ago</div>
                                </div>
                            </div>
                            <div class="feed-item">
                                <div class="feed-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="feed-content">
                                    <h4>Project milestone achieved</h4>
                                    <p>Your "Campus Recycling Program" has reached 500 participants.</p>
                                    <div class="feed-time">1 day ago</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <!-- Calendar Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Club Calendar</h3>
                            <a href="#">View Full</a>
                        </div>
                        <div class="card-body">
                            <table class="calendar">
                                <thead>
                                    <tr>
                                        <th>S</th>
                                        <th>M</th>
                                        <th>T</th>
                                        <th>W</th>
                                        <th>T</th>
                                        <th>F</th>
                                        <th>S</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td>1</td>
                                        <td>2</td>
                                        <td>3</td>
                                        <td>4</td>
                                        <td>5</td>
                                    </tr>
                                    <tr>
                                        <td>6</td>
                                        <td>7</td>
                                        <td>8</td>
                                        <td>9</td>
                                        <td>10</td>
                                        <td>11</td>
                                        <td>12</td>
                                    </tr>
                                    <tr>
                                        <td>13</td>
                                        <td>14</td>
                                        <td>15</td>
                                        <td>16</td>
                                        <td>17</td>
                                        <td>18</td>
                                        <td>19</td>
                                    </tr>
                                    <tr>
                                        <td>20</td>
                                        <td>21</td>
                                        <td>22</td>
                                        <td class="today">23</td>
                                        <td>24</td>
                                        <td>25</td>
                                        <td>26</td>
                                    </tr>
                                    <tr>
                                        <td>27</td>
                                        <td>28</td>
                                        <td>29</td>
                                        <td>30</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="event" style="margin-top: 15px; padding: 10px; background: #e8f6f3; border-radius: 5px;">
                                <strong>Today's Club Events:</strong>
                                <div style="margin-top: 5px;">• Sustainability Club Meeting - 3:00 PM</div>
                                <div>• Green Campus Planning - 5:00 PM</div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Events Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Upcoming Events</h3>
                            <a href="#">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="feed-item">
                                <div class="feed-icon" style="background: #1a5276; color: white;">
                                    <i class="fas fa-recycle"></i>
                                </div>
                                <div class="feed-content">
                                    <h4>Campus Cleanup Drive</h4>
                                    <p>Sep 30, 2023 | Main Campus</p>
                                    <div class="feed-time">7 days left</div>
                                </div>
                            </div>
                            <div class="feed-item">
                                <div class="feed-icon" style="background: #28b463; color: white;">
                                    <i class="fas fa-tree"></i>
                                </div>
                                <div class="feed-content">
                                    <h4>Tree Planting Day</h4>
                                    <p>Oct 5, 2023 | Botanical Garden</p>
                                    <div class="feed-time">12 days left</div>
                                </div>
                            </div>
                            <div class="feed-item">
                                <div class="feed-icon" style="background: #f39c12; color: white;">
                                    <i class="fas fa-leaf"></i>
                                </div>
                                <div class="feed-content">
                                    <h4>Zero Waste Workshop</h4>
                                    <p>Oct 15, 2023 | Student Center</p>
                                    <div class="feed-time">22 days left</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Simple JavaScript for interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
            const sidebar = document.querySelector('.sidebar');
            const menuItems = document.querySelectorAll('.sidebar-menu a');
            
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Simulate notification click
            const notificationIcon = document.querySelector('.notification-icon');
            notificationIcon.addEventListener('click', function() {
                alert('You have 5 new notifications!\n- 2 new club members\n- 1 event reminder\n- 1 project update\n- 1 achievement unlocked');
            });
            
            // Add some interactivity to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    const statText = this.querySelector('p').textContent;
                    alert(`View details for: ${statText}`);
                });
            });
        });
    </script>
</body>
</html>