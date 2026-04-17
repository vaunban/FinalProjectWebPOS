<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: ../../index.php");
    exit();
}

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
    <title>Account</title>
    <link rel="stylesheet" href="../views/css/accountsstyle.css">
</head>
<body>
 
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

        <main class="mainshift">
            <header class="page-header">
                <div class="mainshift-top-title">
                    <h1>Account Management</h1>
                </div>

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

                    <div class="add-user-container">
                        <button type="button" class="archive-btn" onclick="window.location.href='archived_accounts.php'">Archived</button>
                        <button type="button" class="add-user-btn" onclick="openModal()">+ Add User</button>
                    </div>
                </div>
            </header>

            <table class="table-container">
                <tr>
                    <th> USER ID </th>
                    <th> USERNAME </th>
                    <th> ROLE </th>
                    <th> ACTIONS </th>
                </tr>

                <?php
                include (__DIR__ . '/../../config/connect.php');

                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
                
                $sql = "SELECT * FROM users WHERE 1=1";

                if(!empty($search)){
                    $sql .= " AND username LIKE ?";
                }

                if(!empty($roleFilter)){
                    $sql .= " AND role = ?";
                }

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

                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['username'] . "</td>";
                    echo "<td>" . $row['role'] . "</td>";
                    echo "<td> <button class='action-edit' onclick='openEditModal(".$row['id'].", \"".$row['username']."\", \"".$row['role']."\")'>Edit</button>"."<button class='action-delete' onclick='confirmDelete(".$row['id'].")'>Archive</button></td>";
                    echo "</tr>";
                }
                ?>
                

            </table>
            
            <!-- delete confirmation -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
                    <h3>Confirm Archive</h3>
                    <p>Are you sure you want to archive this user?</p>
                    <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button class="delete-btn" onclick="confirmDeleteAction()">Archive</button>
                </div>
            </div>

            <!-- edit user modal -->
            <div id="editUserModal" class="modal">
                <div class="modal-content">
                    <button class="close-btn" onclick="closeEditModal()">&times;</button>
                    <h3>Edit User</h3>
                    <form action="../models/editaccount.php" method="POST">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        
                        <label for="edit_username">Username</label>
                        <input type="text" id="edit_username" name="username" required>

                        <label for="edit_password">Password</label>
                        <input type="password" name="password" id="password" required>

                        <label for="edit_role">Role</label>
                        <select id="edit_role" name="role" required>
                            <option value="" disabled selected>Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="cashier">Cashier</option>
                        </select>
                        <div>
                            <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                            <button type="submit" class="add-btn">Update User</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- add user modal -->
            <div id="addUserModal" class="modal">
                <div class="modal-content">
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                    <h3>Add New User</h3>
                    <form action="../models/accountinsert.php" method="POST">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>

                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>

                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="" disabled selected>Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="cashier">Cashier</option>
                        </select>
                        <div>
                            <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                            <button type="submit" class="add-btn">Add User</button>
                        </div>
                    </form>
                </div>

            </div>

        </main>

        <!-- Popup Alerts -->
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

        <!-- Popup Scripts -->
         <script>
            function openModal() {
                document.getElementById('addUserModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('addUserModal').style.display = 'none';
            }

            function closePopup() {
                document.getElementById('alertPopup').style.display = 'none';
            }
        </script>

        <script>
            function openEditModal(userId, username, role) {
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_username').value = username;
                document.getElementById('edit_role').value = role;
                document.getElementById('editUserModal').style.display = 'block';
            }

            function closeEditModal() {
                document.getElementById('editUserModal').style.display = 'none';
            }
        </script>

        <script>
            let userIdToDelete = null;

            function confirmDelete(userId) {
                userIdToDelete = userId;
                document.getElementById('deleteModal').style.display = 'block';
            }

            function closeDeleteModal() {
                userIdToDelete = null;
                document.getElementById('deleteModal').style.display = 'none';
            }

            function confirmDeleteAction() {
                if (userIdToDelete) {
                    window.location.href = `../models/accounts_archive.php?id=${userIdToDelete}`;
                }
            }
        </script>


        <script>
        function clearFilters() {
            document.getElementById('name').value = '';
            document.querySelector('select[name="role"]').value = '';
            window.location.href = 'accounts.php';
        }
        </script>
        <script src="../../public/js/lowStockAlert.js"></script>
</body>
</html>