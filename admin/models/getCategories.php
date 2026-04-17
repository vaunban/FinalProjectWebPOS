<?php
include(__DIR__ . '/../../config/connect.php');

$view = $_GET['view'] ?? 'categories';

$html = '';

if ($view === 'archive') {
    // Return category archive
    $catArchiveSql = "SELECT archived_id, id AS category_id, name AS category_name, date FROM categories_archive ORDER BY date DESC";
    $catArchiveResult = @$conn->query($catArchiveSql);
    if ($catArchiveResult && $catArchiveResult->num_rows > 0) {
        $html .= '<table border="1">';
        $html .= '<tr>
            <th>Archive ID</th>
            <th>Category ID</th>
            <th>Category Name</th>
            <th>Archived At</th>
            <th>Action</th>
        </tr>';
        while ($row = $catArchiveResult->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $row['archived_id'] . '</td>';
            $html .= '<td>' . $row['category_id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($row['category_name']) . '</td>';
            $html .= '<td>' . $row['date'] . '</td>';
            $html .= '<td><button type="button" class="table-action-button restore-category-archive-button" data-archive-id="' . $row['archived_id'] . '">Restore</button></td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
    } else {
        $html .= '<div class="empty-state">No archived categories found.</div>';
    }
} else {
    // Return active categories
    $catSql = "SELECT id, name FROM categories";
    $catResult = $conn->query($catSql);
    if ($catResult && $catResult->num_rows > 0) {
        $html .= '<table border="1">';
        $html .= '<tr><th class="select-column"><input type="checkbox" id="selectAllCat"></th>
            <th>ID</th>
            <th>Category Name</th>
            <th>Action</th>
        </tr>';
        while ($row = $catResult->fetch_assoc()) {
            $nameStr = htmlspecialchars($row['name'], ENT_QUOTES);
            $html .= '<tr>';
            $html .= '<td class="select-column"><input type="checkbox" class="row-select-cat" data-id="' . $row['id'] . '"></td>';
            $html .= '<td>' . $row['id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
            $html .= '<td><button type="button" class="table-action-button category-edit-button" data-id="' . $row['id'] . '" data-name="' . $nameStr . '">Edit</button> ';
            $html .= '<button type="button" class="table-action-button category-delete-button delete" data-id="' . $row['id'] . '">Delete</button></td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
    } else {
        $html .= '<div class="empty-state">No category data found.</div>';
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'html' => $html]);
exit;
