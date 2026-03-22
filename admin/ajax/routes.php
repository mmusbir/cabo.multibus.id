<?php
/**
 * admin/ajax/routes.php - Handle routes page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$type = isset($_GET['type']) && $_GET['type'] === 'carter' ? 'carter' : 'reguler';
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$table = ($type === 'carter') ? 'master_carter' : 'routes';

// Base WHERE
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $like = '%' . $search . '%';
    if ($type === 'carter') {
        $where .= " AND (name LIKE ? OR origin LIKE ? OR destination LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    } else {
        $where .= " AND (name LIKE ? OR origin LIKE ? OR destination LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
}

try {
    // Count
    $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM $table $where");
    $stmtc->execute($params);
    $total = intval($stmtc->fetch()['cnt']);

    // Select
    $stmt = $conn->prepare("SELECT * FROM $table $where ORDER BY name LIMIT ? OFFSET ?");
    
    // Bind search params
    foreach($params as $i => $v) {
        $stmt->bindValue($i + 1, $v);
    }
    // Bind limit/offset as integers
    $stmt->bindValue(count($params) + 1, (int)$per_page, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (empty($rows)) {
        echo '<div class="small admin-grid-message admin-grid-message-muted">Data tidak ditemukan</div>';
    } else {
        foreach ($rows as $r) {
            echo '<div class="admin-card-compact">';
            echo '  <div class="acc-header">';
            echo '    <div class="acc-title">' . htmlspecialchars($r['name']) . '</div>';
            echo '    <div class="acc-id">#' . intval($r['id']) . '</div>';
            echo '  </div>';
            
            // Show Origin/Destination if exist
            if (!empty($r['origin']) || !empty($r['destination'])) {
                echo ' <div class="acc-row admin-row-muted">';
                echo '   <span>' . htmlspecialchars($r['origin'] ?? '') . '</span> → <span>' . htmlspecialchars($r['destination'] ?? '') . '</span>';
                echo ' </div>';
            }

            if ($type === 'carter') {
                echo '  <div class="acc-body">';
                echo '    <div class="acc-row"><div class="acc-label">Layanan</div><div class="acc-val">' . htmlspecialchars($r['duration'] ?? '') . '</div></div>';
                echo '    <div class="acc-row"><div class="acc-label">Sewa</div><div class="acc-val">Rp ' . number_format($r['rental_price'] ?? 0, 0, ',', '.') . '</div></div>';
                echo '    <div class="acc-row"><div class="acc-label">BOP</div><div class="acc-val">Rp ' . number_format($r['bop_price'] ?? 0, 0, ',', '.') . '</div></div>';
                echo '  </div>';
            }
            echo '  <div class="acc-actions">';
            $edit_param = ($type === 'carter') ? 'edit_carter' : 'edit_route';
            $del_param = ($type === 'carter') ? 'delete_carter' : 'delete_route';
            echo '    <a class="acc-btn" href="admin.php?' . $edit_param . '=' . intval($r['id']) . '#routes">Edit</a>';
            echo '    <a class="acc-btn danger" href="admin.php?' . $del_param . '=' . intval($r['id']) . '#routes" onclick="event.preventDefault(); customConfirm(\'Hapus rute?\', () => { window.location.href = this.href; }, \'Hapus Rute\', \'danger\')">Hapus</a>';
            echo '  </div>';
            echo '</div>';
        }
    }
    $rows_html = ob_get_clean();
    $pag_html = render_pagination_ajax($total, $per_page, $page, 'routes');

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
    exit;

} catch (Exception $e) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
