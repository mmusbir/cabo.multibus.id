<?php
/**
 * admin/ajax/units.php - Paginated units list for admin
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $like = '%' . $search . '%';
    $where .= " AND (nopol ILIKE ? OR merek ILIKE ? OR type ILIKE ? )";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

try {
    $countSql = "SELECT COUNT(*) AS cnt FROM units " . $where;
    $stmtc = $conn->prepare($countSql);
    $stmtc->execute($params);
    $total = intval($stmtc->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

    $sql = "SELECT id, nopol, merek, type, tahun, kapasitas, status FROM units " . $where . " ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $i = 1;
    foreach ($params as $p) {
        $stmt->bindValue($i++, $p, PDO::PARAM_STR);
    }
    $stmt->bindValue($i++, (int) $per_page, PDO::PARAM_INT);
    $stmt->bindValue($i++, (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (empty($rows)) {
        echo '<tr><td colspan="7" class="customers-table-empty">Data tidak ditemukan</td></tr>';
    } else {
        foreach ($rows as $u) {
            echo '<tr data-table-row="1">';
            echo '<td><span class="customers-table-id">#' . intval($u['id']) . '</span></td>';
            echo '<td><div class="customers-table-name">' . htmlspecialchars($u['nopol']) . '</div><div class="customers-table-muted">' . htmlspecialchars($u['merek']) . '</div></td>';
            echo '<td>' . htmlspecialchars($u['type']) . '</td>';
            echo '<td>' . htmlspecialchars($u['tahun']) . '</td>';
            echo '<td>' . htmlspecialchars($u['kapasitas']) . ' kursi</td>';
            echo '<td>' . htmlspecialchars($u['status']) . '</td>';
            echo '<td><div class="customers-table-actions">';
            echo '<a href="admin.php?edit_unit=' . intval($u['id']) . '#units' . '" class="acc-btn">Edit</a>';
            echo '<button class="acc-btn edit-layout-btn" data-id="' . intval($u['id']) . '" data-nopol="' . htmlspecialchars($u['nopol']) . '" data-kapasitas="' . htmlspecialchars($u['kapasitas']) . '">Layout</button>';
            echo '<form method="post" class="admin-inline-form" onsubmit="event.preventDefault(); customConfirm(\'Hapus unit ini?\', () => this.submit(), \"Hapus Unit\", \"danger\");">';
            echo '<input type="hidden" name="unit_id" value="' . intval($u['id']) . '">';
            echo '<button type="submit" name="delete_unit" class="acc-btn danger">Hapus</button>';
            echo '</form>';
            echo '</div></td>';
            echo '</tr>';
        }
    }
    $rows_html = ob_get_clean();
    $pag_html = render_pagination_ajax($total, $per_page, $page, 'units');

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
    exit;
} catch (Exception $e) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
