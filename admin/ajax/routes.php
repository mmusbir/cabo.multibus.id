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

$where = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where .= " AND (name ILIKE ? OR origin ILIKE ? OR destination ILIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

try {
    $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM $table $where");
    $stmtc->execute($params);
    $total = intval($stmtc->fetch()['cnt']);

    $stmt = $conn->prepare("SELECT * FROM $table $where ORDER BY name LIMIT ? OFFSET ?");
    foreach ($params as $i => $v) {
        $stmt->bindValue($i + 1, $v);
    }
    $stmt->bindValue(count($params) + 1, (int) $per_page, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (empty($rows)) {
        $cols = ($type === 'carter') ? 9 : 7;
        echo '<tr><td colspan="' . $cols . '" class="customers-table-empty">Data tidak ditemukan</td></tr>';
    } else {
        foreach ($rows as $r) {
            $edit_param = ($type === 'carter') ? 'edit_carter' : 'edit_route';
            $del_param = ($type === 'carter') ? 'delete_carter' : 'delete_route';
            $service = $type === 'carter' ? htmlspecialchars($r['duration'] ?? '-') : '-';
            $price = $type === 'carter'
                ? 'Rp ' . number_format($r['rental_price'] ?? 0, 0, ',', '.')
                : '-';

            $hash = ($type === 'carter') ? '#routes_carter' : '#routes';

            echo '<tr>';
            echo '  <td><span class="customers-table-id">#' . intval($r['id']) . '</span></td>';
            echo '  <td class="customers-table-name">' . htmlspecialchars($r['name']) . '</td>';
            echo '  <td>' . htmlspecialchars($r['origin'] ?? '-') . '</td>';
            echo '  <td>' . htmlspecialchars($r['destination'] ?? '-') . '</td>';
            echo '  <td>' . $service . '</td>';
            echo '  <td class="customers-table-phone">' . $price . '</td>';
            
            if ($type === 'carter') {
                $bop_val = 'Rp ' . number_format($r['bop_price'] ?? 0, 0, ',', '.');
                $notes = htmlspecialchars($r['notes'] ?? '-');
                echo '  <td class="customers-table-phone">' . $bop_val . '</td>';
                echo '  <td>' . $notes . '</td>';
            }
            
            echo '  <td>';
            echo '    <div class="customers-table-actions">';
            echo '      <button type="button" class="acc-btn edit-route-btn" data-id="' . intval($r['id']) . '" data-type="' . $type . '">Edit</button>';
            echo '      <button type="button" class="acc-btn danger delete-route-btn" data-id="' . intval($r['id']) . '" data-type="' . $type . '">Hapus</button>';
            echo '    </div>';
            echo '  </td>';
            echo '</tr>';
        }
    }
    $rows_html = ob_get_clean();
    $pag_html = render_pagination_ajax($total, $per_page, $page, ($type === 'carter' ? 'routes_carter' : 'routes'));

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
    exit;
} catch (Exception $e) {
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
