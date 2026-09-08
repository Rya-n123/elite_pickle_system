<?php
// admin/members.php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index");
    exit();
}

require_once '../config/database.php';

try {
    // Kunin lahat ng members, pinakabago muna
    $stmt = $pdo->query("SELECT * FROM members ORDER BY created_at DESC");
    $members = $stmt->fetchAll();

    // Kunin ang Available Rewards para sa Redeem Dropdown
    $rewStmt = $pdo->query("SELECT id, reward_name, points_required FROM rewards WHERE status = 'Available' ORDER BY points_required ASC");
    $availableRewards = $rewStmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE Pickle Center - Members</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Pansinin na may '../' dahil nasa loob tayo ng admin folder -->
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
            <a href="members" class="active-link">VIP Members</a>
            <a href="rewards">Rewards</a>
            <a href="activities">Activities</a>
            <a href="reports">Reports</a>
            <a href="#" class="logout-btn" onclick="confirmLogout(event, '../logout.php')">Logout</a>
        </div>
    </header>

    <div class="main-container">
        <div class="table-container">
            <div class="flex-between">
                <h2 style="color: #D4AF37;">VIP Members Directory</h2>
                <button class="btn-gold" style="width: auto; margin-top: 0;" onclick="openAddMemberModal()">+ Add New VIP</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>QR Code</th>
                        <th>Name</th>
                        <th>Points</th>
                        <th>Status</th>
                        <th>Next Eligible Redemption</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($members) > 0): ?>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td style="color: #cbd5e1;"><?= htmlspecialchars($member['qr_code']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong>
                                </td>
                                <td>
                                    <strong style="color: #D4AF37; font-size: 16px;"><?= $member['point_balance'] ?></strong>
                                </td>
                                <td>
                                    <?php if ($member['status'] === 'Active'): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-suspended">Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 13px; color: #cbd5e1;">
                                    <?php 
                                        if (empty($member['next_eligible_date']) || $member['next_eligible_date'] <= date('Y-m-d')) {
                                            echo '<span style="color: #4ade80;">Eligible Now</span>';
                                        } else {
                                            echo date('M d, Y', strtotime($member['next_eligible_date']));
                                        }
                                    ?>
                                </td>
                                <td>
                                    <!-- Bagong Redeem Button -->
                                    <button class="action-btn" style="background-color: #f59e0b; color: #fff; margin-right: 5px;" 
                                        onclick="openRedeemModal(<?= $member['id'] ?>, '<?= addslashes($member['first_name'] . ' ' . $member['last_name']) ?>', <?= $member['point_balance'] ?>, '<?= empty($member['next_eligible_date']) ? '' : $member['next_eligible_date'] ?>')">
                                        Redeem
                                    </button>
                                    <button class="action-btn btn-edit" onclick="editMember(<?= $member['id'] ?>, '<?= addslashes($member['first_name']) ?>', '<?= addslashes($member['last_name']) ?>', '<?= addslashes($member['qr_code']) ?>')">Edit</button>
                                    <button class="action-btn btn-toggle" onclick="toggleStatus(<?= $member['id'] ?>, '<?= $member['status'] ?>')">
                                        <?= $member['status'] === 'Active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #cbd5e1; padding: 30px;">No members registered yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>



    <!-- Edit Member Modal -->
    <div class="modal-overlay" id="edit-member-modal" style="display: none;">
        <div class="modal-content">
            <h3 style="color: #D4AF37;">Edit VIP Member</h3>
            <form id="editMemberForm">
                <input type="hidden" id="edit_member_id">
                
                <label>First Name</label>
                <input type="text" id="edit_first_name" required>
                
                <label>Last Name</label>
                <input type="text" id="edit_last_name" required>
                
                <label>QR Code</label>
                <input type="text" id="edit_qr_code" required>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn-gold" style="flex: 1;">Update Member</button>
                    <button type="button" class="btn-gold" style="flex: 1; background-color: #334155; color: #fff;" onclick="closeEditMemberModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal-overlay" id="add-member-modal" style="display: none;">
        <div class="modal-content">
            <h3 style="color: #D4AF37;">Add New VIP Member</h3>
            <form id="addMemberForm">
                <label>First Name</label>
                <input type="text" id="add_first_name" required>
                
                <label>Last Name</label>
                <input type="text" id="add_last_name" required>
                
                <label>QR Code (Scan or Type)</label>
                <input type="text" id="add_qr_code" required placeholder="e.g. ELITE-10001">
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn-gold" style="flex: 1;">Save Member</button>
                    <button type="button" class="btn-gold" style="flex: 1; background-color: #334155; color: #fff;" onclick="closeAddMemberModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Isiningit ang jQuery at DataTables scripts dito -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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

        function openAddMemberModal() {
            document.getElementById('add-member-modal').style.display = 'flex';
            // Auto focus sa QR code input para madali i-scan gamit ang physical scanner kung meron
            setTimeout(() => document.getElementById('add_qr_code').focus(), 100);
        }

        function closeAddMemberModal() {
            document.getElementById('add-member-modal').style.display = 'none';
            document.getElementById('addMemberForm').reset();
        }

        // Handle Form Submission via Fetch API
        document.getElementById('addMemberForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const qr_code = document.getElementById('add_qr_code').value.trim();
            const first_name = document.getElementById('add_first_name').value.trim();
            const last_name = document.getElementById('add_last_name').value.trim();

            fetch('../api/add_member.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr_code, first_name, last_name })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', 'New VIP Member added.', 'success')
                    .then(() => location.reload()); // I-refresh ang page para lumabas sa table
                } else {
                    Swal.fire('Error', data.error, 'error');
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
                text: "Are you sure you want to log out of the system?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b6b',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Yes, log me out',
                background: '#1A2A47',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = logoutUrl;
                }
            });
        }

        // --- Edit Member Logic ---
        function closeEditMemberModal() {
            document.getElementById('edit-member-modal').style.display = 'none';
        }

        function editMember(id, firstName, lastName, qrCode) {
            document.getElementById('edit_member_id').value = id;
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_qr_code').value = qrCode;
            document.getElementById('edit-member-modal').style.display = 'flex';
        }

        document.getElementById('editMemberForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('edit_member_id').value;
            const qr_code = document.getElementById('edit_qr_code').value.trim();
            const first_name = document.getElementById('edit_first_name').value.trim();
            const last_name = document.getElementById('edit_last_name').value.trim();

            fetch('../api/edit_member.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, qr_code, first_name, last_name })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Updated!', 'Member details have been updated.', 'success')
                    .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            });
        });

        // --- Toggle Status Logic ---
        function toggleStatus(id, currentStatus) {
            const action = currentStatus === 'Active' ? 'Suspend' : 'Activate';
            const confirmColor = currentStatus === 'Active' ? '#ff6b6b' : '#4ade80';

            Swal.fire({
                title: `${action} Member?`,
                text: `Are you sure you want to ${action.toLowerCase()} this member?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#334155',
                confirmButtonText: `Yes, ${action}`,
                background: '#1A2A47',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../api/toggle_member.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id, current_status: currentStatus })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success!', `Member has been ${action.toLowerCase()}d.`, 'success')
                            .then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.error, 'error');
                        }
                    });
                }
            });
        }

        // --- Manual Redeem Logic ---
        const availableRewards = <?= json_encode($availableRewards) ?>;

        function openRedeemModal(id, name, points, nextEligible) {
            const today = new Date().toISOString().split('T')[0];
            if (nextEligible && nextEligible > today) {
                Swal.fire({
                    icon: 'warning', title: 'Cooldown Active',
                    text: `${name} is not eligible to redeem until ${nextEligible}.`,
                    background: '#1A2A47', color: '#fff'
                });
                return;
            }

            if (points <= 0) {
                Swal.fire({
                    icon: 'warning', title: 'No Points',
                    text: `${name} has 0 points.`,
                    background: '#1A2A47', color: '#fff'
                });
                return;
            }

            let optionsHtml = '<option value="">-- Select a Reward --</option>';
            availableRewards.forEach(r => {
                const disabled = points < r.points_required ? 'disabled' : '';
                const color = points < r.points_required ? 'color: #ff6b6b;' : 'color: #4ade80;';
                optionsHtml += `<option value="${r.id}" ${disabled} style="${color}">${r.reward_name} (${r.points_required} pts)</option>`;
            });

            Swal.fire({
                title: 'Redeem Reward',
                html: `
                    <div style="color: #cbd5e1; margin-bottom: 15px; font-size: 14px;">
                        Member: <strong style="color: #fff;">${name}</strong><br>
                        Current Points: <strong style="color: #D4AF37; font-size: 18px;">${points}</strong>
                    </div>
                    <select id="reward_select" style="width: 100%; padding: 12px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 6px;">
                        ${optionsHtml}
                    </select>
                    <div style="font-size: 12px; color: #f59e0b; margin-top: 15px; background: rgba(245, 158, 11, 0.1); padding: 10px; border-radius: 4px; border-left: 3px solid #f59e0b; text-align: left;">
                        ⚠️ Any excess points will be forfeited and a 2-month cooldown will automatically apply.
                    </div>
                `,
                background: '#1A2A47', color: '#ffffff',
                showCancelButton: true, confirmButtonColor: '#f59e0b', cancelButtonColor: '#334155',
                confirmButtonText: 'Process Redemption',
                preConfirm: () => {
                    const reward_id = document.getElementById('reward_select').value;
                    if (!reward_id) Swal.showValidationMessage('Please select a reward from the list');
                    return reward_id;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../api/redeem_manual.php', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ member_id: id, reward_id: result.value })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire({
                                icon: 'success', title: 'Success!',
                                text: 'Reward successfully redeemed. Points reset and cooldown applied.',
                                background: '#1A2A47', color: '#fff'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.error, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>