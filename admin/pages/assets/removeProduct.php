<?php
include (__DIR__ . '/../../../connect.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: ../inventory.php');
    exit;
}

$id = intval($_POST['id']);

$select = $conn->prepare('SELECT icon_filename FROM products WHERE id = ?');
$select->bind_param('i', $id);
$select->execute();
$result = $select->get_result();
if ($result->num_rows === 0) {
    header('Location: ../inventory.php');
    exit;
}

$row = $result->fetch_assoc();
if (!empty($row['icon_filename'])) {
    $imagePath = __DIR__ . '/../../../cashier/cashierassets/images/' . basename($row['icon_filename']);
    if (file_exists($imagePath)) {
        @unlink($imagePath);
    }
}

$delete = $conn->prepare('DELETE FROM products WHERE id = ?');
$delete->bind_param('i', $id);
if ($delete->execute()) {
    header('Location: ../inventory.php');
    exit;
}

echo 'Error deleting product: ' . $conn->error;
echo '<br><a href="../inventory.php">Go Back</a>';
