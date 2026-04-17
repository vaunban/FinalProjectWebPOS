<?php
/**
 * archived_accounts.php
 * Admin archived accounts page for the MERKADO POS system.
 * Displays archived (soft-deleted) user accounts with options to restore or permanently delete them.
 * Only accessible to users with the 'admin' role.
 */

// Start session and verify login
session_start();

// Redirect to login if not logged in
if(!isset($_SESSION['username'])){
    header("Location: ../../index.php");
    exit();
}

// Redirect to cashier page if user is not an admin
if($_SESSION['role'] != 'admin'){
    header("Location: ../../cashier/controllers/cashier.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Accounts</title>
    <!-- Reuses the same styles as the active accounts page -->
    <link rel="stylesheet" href="../views/css/accountsstyle.css">
</head>
<body>
 
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/merkado-icon.png" alt="MERKADO logo">
                <h2><a href="admin.php">MERKADO</a></h2>
            </div>
                <ul class="sidebar-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="transactions.php">Transactions</a></li>
                    <li><a href="accounts.php">Accounts</a></li>
                    <li><a href="../models/adminlogout.php">Log Out</a></li>
                </ul>
        </div>

        <!-- Main Content Area -->
        <main class="mainshift">
            <header class="page-header">
                <div class="mainshift-top-title">
                    <h1>Account Management</h1>
                </div>

                <!-- Toolbar — search, role filter, and back button -->
                <div class="toolbar">
                    <form class="role-name-container" method="GET">
                        <input class="searchbar" type="text" id="name" name="search" placeholder="Search by Name">

                        <select name="role" onchange="this.form.submit()">
                            <option value="" hidden selected>Filter: Role</option>
                            <option value="admin">Admin</option>
                            <option value="cashier">Cashier</option>
                        </select>

                        <button class="reset-btn" type="button" onclick="clearFilters()">Reset</button>

                    </form>

                    <div class="back-container">
                        <button type="button" class="back-btn" onclick="window.location.href='accounts.php'">Go Back</button>
                    </div>
                </div>
            </header>

            <!-- Archived Users Table -->
            <table class="table-container">
                <tr>
                    <th> USER ID </th>
                    <th> USERNAME </th>
                    <th> ROLE </th>
                    <th> ARCHIVED AT </th>
                    <th> ACTIONS </th>
                </tr>

                <?php
                // Fetch archived accounts with optional search/role filters
                include(__DIR__ . '/../../config/connect.php');

                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
                
                // Build dynamic SQL query
                $sql = "SELECT * FROM accounts_archive WHERE 1=1";

                if(!empty($search)){
                    $sql .= " AND username LIKE ?";
                }

                if(!empty($roleFilter)){
                    $sql .= " AND role = ?";
                }

                // Prepare and bind parameters
                $stmt = $conn->prepare($sql);

                if(!empty($search) && !empty($roleFilter)){
                    $searchParam = "%$search%";
                    $stmt->bind_param("ss", $searchParam, $roleFilter);
                } elseif(!empty($search)){
                    $searchParam = "%$search%";
                    $stmt->bind_param("s", $searchParam);
                } elseif(!empty($roleFilter)){
                    $stmt->bind_param("s", $roleFilter);
                }

                // Execute and display archived users
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['username'] . "</td>";
                    echo "<td>" . $row['role'] . "</td>";
                    echo "<td>" . $row['archived_at'] . "</td>";
                    echo "<td> <button class='action-restore' onclick='restoreUser(".$row['id'].")'>Restore</button>"."<button class='action-delete' onclick='confirmDelete(".$row['id'].")'>Delete</button></td>";
                    echo "</tr>";
                }
                ?>
                

            </table>
            
            <!-- Permanent Delete Confirmation Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
                    <h3>Confirm Delete</h3>
                    <p>Are you sure you want to delete this user?</p>
                    <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button class="delete-btn" onclick="confirmDeleteAction()">Delete</button>
                </div>
            </div>

            <!-- Restore Confirmation Modal -->
            <div id="restoreModal" class="modal">
                <div class="modal-content">
                    <button class="close-btn" onclick="closeRestoreModal()">&times;</button>
                    <h3>Confirm Restore</h3>
                    <p>Are you sure you want to restore this user?</p>
                    <button class="cancel-btn" onclick="closeRestoreModal()">Cancel</button>
                    <button class="restore-btn" onclick="confirmRestoreAction()">Restore</button>
                </div>
            </div>

        </main>

        <!-- Popup Alert — shows success/error messages from session -->
        <?php
            $popupMessage = "";
            $popupType = "";

        if(isset($_SESSION['success'])){
                $popupMessage = $_SESSION['success'];
                $popupType = "success";
                unset($_SESSION['success']);
        }
            if(isset($_SESSION['error'])){
                $popupMessage = $_SESSION['error'];
                $popupType = "error";
                unset($_SESSION['error']);
            }
        ?>

        <?php if(!empty($popupMessage)): ?>
            <div id="alertPopup" class="popup-overlay">
                <div class="popup-box <?php echo $popupType; ?>">
                    <button class="popup-close" onclick="closePopup()">&times;</button>
                    <h3><?php echo $popupType == 'success' ? 'Success' : 'Error'; ?></h3>
                    <p><?php echo $popupMessage; ?></p>
                    <button class="popup-ok" onclick="closePopup()">OK</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Scripts -->
        <script>
            // Open/close add user modal (inherited from accounts page styling)
            function openModal() {
                document.getElementById('addUserModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('addUserModal').style.display = 'none';
            }

            // Close the popup alert notification
            function closePopup() {
                document.getElementById('alertPopup').style.display = 'none';
            }
        </script>

        <script>
            // Track the user ID to be permanently deleted
            let userIdToDelete = null;

            // Open delete confirmation modal
            function confirmDelete(userId) {
                userIdToDelete = userId;
                document.getElementById('deleteModal').style.display = 'block';
            }

            // Close delete confirmation modal
            function closeDeleteModal() {
                userIdToDelete = null;
                document.getElementById('deleteModal').style.display = 'none';
            }

            // Perform permanent delete — redirects to accountdelete.php
            function confirmDeleteAction() {
                if (userIdToDelete) {
                    window.location.href = `../models/accountdelete.php?id=${userIdToDelete}`;
                }
            }
        </script>

        <script>
            // Track the user ID to be restored
            let userIdToRestore = null;

            // Open restore confirmation modal
            function restoreUser(userId) {
                userIdToRestore = userId;
                document.getElementById('restoreModal').style.display = 'block';
            }

            // Close restore confirmation modal
            function closeRestoreModal() {
                userIdToRestore = null;
                document.getElementById('restoreModal').style.display = 'none';
            }

            // Perform restore — redirects to restoreaccount.php
            function confirmRestoreAction() {
                if (userIdToRestore) {
                    window.location.href = `../models/restoreaccount.php?id=${userIdToRestore}`;
                }
            }
        </script>
</body>
</html>