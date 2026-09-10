<?php
// admin/activities.php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../index"); exit(); }
require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT * FROM activities ORDER BY id ASC");
    $activities = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Activities Page DB Error: " . $e->getMessage());
    die("System error. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE Pickle Center - Activities</title>

    <!-- Heto ang Favicon Code (may ../ sa unahan) -->
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="align-items: flex-start; padding-top: 80px;"> 

    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="../assets/images/logo.jpg" alt="Logo">
            <h1>EL1TE Dashboard</h1>
        </div>
        
        <div class="menu-toggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="nav-links" id="navLinks">
            <a href="index">Dashboard</a>
            <a href="../scan">Scanner</a>
            <a href="members">VIP Members</a>
            <a href="rewards">Rewards</a>
            <a href="activities" class="active-link">Activities</a>
            <a href="reports">Reports</a>
            <a href="#" class="logout-btn" onclick="confirmLogout(event, '../logout.php')">Logout</a>
        </div>
    </header>

    <div class="main-container">
        <div class="table-container">
            <div class="flex-between">
                <h2 style="color: #D4AF37;">Activity Rules</h2>
                <button class="btn-gold" style="width: auto; margin-top: 0;" onclick="openAddActivityModal()">+ Add Activity</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Activity Name</th>
                        <th>Points Awarded</th>
                        <th>Daily Limit</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $act): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($act['activity_name']) ?></strong></td>
                            <td style="color: #4ade80; font-weight: bold;">+<?= $act['points_awarded'] ?> pts</td>
                            <td>
                                <?= $act['has_daily_limit'] == 1 ? '<span style="color:#ff6b6b;">1 per day</span>' : '<span style="color:#cbd5e1;">Unlimited</span>' ?>
                            </td>
                            <td>
                                <?php if ($act['status'] === 'Active'): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-suspended">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="action-btn btn-edit" onclick="editActivity(<?= $act['id'] ?>, '<?= jsAttr($act['activity_name']) ?>', <?= (int)$act['points_awarded'] ?>, <?= (int)$act['has_daily_limit'] ?>)">Edit</button>
                                <button class="action-btn btn-toggle" onclick="toggleActivityStatus(<?= $act['id'] ?>, '<?= $act['status'] ?>')">
                                    <?= $act['status'] === 'Active' ? 'Disable' : 'Enable' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Activity Modal -->
    <div class="modal-overlay" id="add-activity-modal" style="display: none;">
        <div class="modal-content">
            <h3 style="color: #D4AF37;">Add New Activity</h3>
            <form id="addActivityForm">
                <label>Activity Name</label>
                <input type="text" id="add_activity_name" required placeholder="e.g. Tournament Play">
                
                <label>Points to Award</label>
                <input type="number" id="add_points" required min="1" value="1">
                
                <label>Has Daily Limit?</label>
                <select id="add_limit" style="width: 100%; padding: 10px; margin-bottom: 15px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 6px;">
                    <option value="0">No (Unlimited entries per day)</option>
                    <option value="1">Yes (Maximum 1 point per day)</option>
                </select>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn-gold" style="flex: 1;">Save</button>
                    <button type="button" class="btn-gold" style="flex: 1; background-color: #334155; color: #fff;" onclick="closeModals()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Activity Modal -->
    <div class="modal-overlay" id="edit-activity-modal" style="display: none;">
        <div class="modal-content">
            <h3 style="color: #D4AF37;">Edit Activity</h3>
            <form id="editActivityForm">
                <input type="hidden" id="edit_id">
                
                <label>Activity Name</label>
                <input type="text" id="edit_activity_name" required>
                
                <label>Points to Award</label>
                <input type="number" id="edit_points" required min="1">
                
                <label>Has Daily Limit?</label>
                <select id="edit_limit" style="width: 100%; padding: 10px; margin-bottom: 15px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 6px;">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn-gold" style="flex: 1;">Update</button>
                    <button type="button" class="btn-gold" style="flex: 1; background-color: #334155; color: #fff;" onclick="closeModals()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Isiningit ang jQuery at DataTables scripts dito -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('table').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "language": {
                    "search": "Filter records:"
                }
            });
        });
        function toggleMobileMenu() { document.getElementById('navLinks').classList.toggle('show-menu'); }
        function confirmLogout(e, logoutUrl) {
            e.preventDefault();
            Swal.fire({
                title: 'Log out?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ff6b6b', cancelButtonColor: '#334155', confirmButtonText: 'Yes, log me out', background: '#1A2A47', color: '#ffffff'
            }).then((result) => { if (result.isConfirmed) window.location.href = logoutUrl; });
        }

        function closeModals() {
            document.getElementById('add-activity-modal').style.display = 'none';
            document.getElementById('edit-activity-modal').style.display = 'none';
            document.getElementById('addActivityForm').reset();
        }

        // Add Logic
        function openAddActivityModal() { document.getElementById('add-activity-modal').style.display = 'flex'; }
        
        document.getElementById('addActivityForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('../api/add_activity.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({
                    name: document.getElementById('add_activity_name').value.trim(),
                    points: document.getElementById('add_points').value,
                    limit: document.getElementById('add_limit').value
                })
            }).then(r => r.json()).then(data => {
                if (data.success) Swal.fire('Success!', 'Activity added.', 'success').then(() => location.reload());
                else Swal.fire('Error', data.error, 'error');
            });
        });

        // Edit Logic
        function editActivity(id, name, points, limit) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_activity_name').value = name;
            document.getElementById('edit_points').value = points;
            document.getElementById('edit_limit').value = limit;
            document.getElementById('edit-activity-modal').style.display = 'flex';
        }

        document.getElementById('editActivityForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('../api/edit_activity.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({
                    id: document.getElementById('edit_id').value,
                    name: document.getElementById('edit_activity_name').value.trim(),
                    points: document.getElementById('edit_points').value,
                    limit: document.getElementById('edit_limit').value
                })
            }).then(r => r.json()).then(data => {
                if (data.success) Swal.fire('Updated!', 'Activity updated.', 'success').then(() => location.reload());
                else Swal.fire('Error', data.error, 'error');
            });
        });

        // Toggle Logic
        function toggleActivityStatus(id, currentStatus) {
            const action = currentStatus === 'Active' ? 'Disable' : 'Enable';
            Swal.fire({
                title: `${action} Activity?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#D4AF37', cancelButtonColor: '#334155', confirmButtonText: `Yes, ${action}`, background: '#1A2A47', color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../api/toggle_activity.php', {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                        body: JSON.stringify({ id: id, current_status: currentStatus })
                    }).then(r => r.json()).then(data => {
                        if (data.success) location.reload();
                    });
                }
            });
        }
    </script>
<script>window.CSRF_TOKEN = '<?= generateCsrfToken() ?>';</script>
</body>
</html>