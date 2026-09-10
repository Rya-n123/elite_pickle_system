<?php
// admin/rewards.php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index"); exit();
}

require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT * FROM rewards ORDER BY points_required ASC");
    $rewards = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Rewards Page DB Error: " . $e->getMessage());
    die("System error. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE Pickle Center - Rewards</title>

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
            <a href="rewards" class="active-link">Rewards</a>
            <a href="activities">Activities</a>
            <a href="reports">Reports</a>
            <a href="#" class="logout-btn" onclick="confirmLogout(event, '../logout.php')">Logout</a>
        </div>
    </header>

    <div class="main-container">
        <div class="table-container">
            <div class="flex-between">
                <h2 style="color: #D4AF37;">Rewards Inventory</h2>
                <button class="btn-gold" style="width: auto; margin-top: 0;" onclick="openAddRewardModal()">+ Add Reward</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Reward Name</th>
                        <th>Points Required</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rewards as $reward): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($reward['reward_name']) ?></strong></td>
                            <td style="color: #D4AF37; font-weight: bold;"><?= $reward['points_required'] ?> pts</td>
                            <td>
                                <?php if ($reward['status'] === 'Available'): ?>
                                    <span class="badge badge-active">Available</span>
                                <?php elseif ($reward['status'] === 'Out of Stock'): ?>
                                    <span class="badge badge-suspended" style="color:#f59e0b; border-color:#f59e0b; background:rgba(245,158,11,0.1);">Out of Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-suspended">Hidden</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="action-btn btn-edit" onclick="editReward(<?= $reward['id'] ?>, '<?= jsAttr($reward['reward_name']) ?>', <?= (int)$reward['points_required'] ?>, '<?= jsAttr($reward['status']) ?>')">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Reward Modal -->
    <div class="modal-overlay" id="add-reward-modal" style="display: none;">
        <div class="modal-content">
            <h3 style="color: #D4AF37;">Add New Reward</h3>
            <form id="addRewardForm">
                <label>Reward Name</label>
                <input type="text" id="add_reward_name" required placeholder="e.g. EL1TE Towel">
                
                <label>Points Required</label>
                <input type="number" id="add_points_required" required min="1">
                
                <label>Status</label>
                <select id="add_status" style="width: 100%; padding: 10px; margin-bottom: 15px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 6px;">
                    <option value="Available">Available</option>
                    <option value="Out of Stock">Out of Stock</option>
                    <option value="Hidden">Hidden</option>
                </select>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn-gold" style="flex: 1;">Save Reward</button>
                    <button type="button" class="btn-gold" style="flex: 1; background-color: #334155; color: #fff;" onclick="closeAddRewardModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Reward Modal -->
    <div class="modal-overlay" id="edit-reward-modal" style="display: none;">
        <div class="modal-content">
            <h3 style="color: #D4AF37;">Edit Reward</h3>
            <form id="editRewardForm">
                <input type="hidden" id="edit_reward_id">
                
                <label>Reward Name</label>
                <input type="text" id="edit_reward_name" required>
                
                <label>Points Required</label>
                <input type="number" id="edit_points_required" required min="1">
                
                <label>Status</label>
                <select id="edit_status" style="width: 100%; padding: 10px; margin-bottom: 15px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 6px;">
                    <option value="Available">Available</option>
                    <option value="Out of Stock">Out of Stock</option>
                    <option value="Hidden">Hidden</option>
                </select>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn-gold" style="flex: 1;">Update Reward</button>
                    <button type="button" class="btn-gold" style="flex: 1; background-color: #334155; color: #fff;" onclick="closeEditRewardModal()">Cancel</button>
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
                    "search": "Filter records:",
                    "emptyTable": "No rewards added to inventory yet." // Pwede mong idagdag ito para parehas ng style!
                }
            });
        });
        function toggleMobileMenu() {
            const nav = document.getElementById('navLinks');
            nav.classList.toggle('show-menu');
        }

        function confirmLogout(e, logoutUrl) {
            e.preventDefault();
            Swal.fire({
                title: 'Log out?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b6b',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Yes, log me out',
                background: '#1A2A47',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = logoutUrl;
            });
        }

        // --- Add Reward Logic ---
        function openAddRewardModal() {
            document.getElementById('add-reward-modal').style.display = 'flex';
        }

        function closeAddRewardModal() {
            document.getElementById('add-reward-modal').style.display = 'none';
            document.getElementById('addRewardForm').reset();
        }

        document.getElementById('addRewardForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const reward_name = document.getElementById('add_reward_name').value.trim();
            const points_required = document.getElementById('add_points_required').value;
            const status = document.getElementById('add_status').value;

            fetch('../api/add_reward.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({ reward_name, points_required, status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', 'New reward added.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            });
        });

        // --- Edit Reward Logic ---
        function openEditRewardModal() {
            document.getElementById('edit-reward-modal').style.display = 'flex';
        }

        function closeEditRewardModal() {
            document.getElementById('edit-reward-modal').style.display = 'none';
        }

        function editReward(id, name, points, status) {
            document.getElementById('edit_reward_id').value = id;
            document.getElementById('edit_reward_name').value = name;
            document.getElementById('edit_points_required').value = points;
            document.getElementById('edit_status').value = status;
            openEditRewardModal();
        }

        document.getElementById('editRewardForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit_reward_id').value;
            const reward_name = document.getElementById('edit_reward_name').value.trim();
            const points_required = document.getElementById('edit_points_required').value;
            const status = document.getElementById('edit_status').value;

            fetch('../api/edit_reward.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({ id, reward_name, points_required, status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Updated!', 'Reward has been updated.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            });
        });
    </script>
<script>window.CSRF_TOKEN = '<?= generateCsrfToken() ?>';</script>
</body>
</html>