<?php
/**
 * admin/ajax/charters.php - Handle charters page data
 */

global $conn;

$perfStartedAt = perf_timer_start();

function charter_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function charter_relative_time(?string $raw): string
{
    if (!$raw) {
        return '-';
    }
    try {
        $created = new DateTime($raw);
        $now = new DateTime('now');
        $diff = $created->diff($now);
        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        }
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
        if ($diff->i > 0) {
            return $diff->i . ' min ago';
        }
        return 'Just now';
    } catch (Exception $e) {
        return '-';
    }
}

function charter_short_place(?string $value): string
{
    $text = trim((string) $value);
    if ($text === '') {
        return '-';
    }
    if (preg_match('/\(([^)]+)\)/', $text, $matches)) {
        return trim($matches[1]);
    }
    $parts = preg_split('/[,|-]/', $text);
    return trim($parts[0] ?? $text);
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$scope = isset($_GET['scope']) ? $_GET['scope'] : 'active';

if ($scope === 'history') {
    // History: past charters or completed charters
    $where = "WHERE (start_date < CURRENT_DATE AND bop_status = 'done')";
} else {
    // Active: upcoming, today, or unpaid
    $where = "WHERE (start_date >= CURRENT_DATE OR bop_status != 'done')";
}

$params = [];

if ($search !== '') {
    $where .= " AND (name LIKE ? OR company_name LIKE ? OR phone LIKE ? OR driver_name LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

try {
    $countSql = "SELECT COUNT(*) AS cnt FROM charters $where";
    $stmt = $conn->prepare($countSql);
    $stmt->execute($params);
    $total = intval(($stmt->fetch(PDO::FETCH_ASSOC))['cnt'] ?? 0);

    $sql = "SELECT c.*, u.nopol, u.merek
            FROM charters c
            LEFT JOIN units u ON c.unit_id = u.id
            $where
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?";

    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'charter_query_failed',
        'detail' => $e->getMessage(),
    ]);
    exit;
}

ob_start();
if (empty($rows)) {
    echo '<div class="small admin-empty-state admin-grid-message">Data carter tidak ditemukan</div>';
} else {
    foreach ($rows as $index => $r) {
        $priceRaw = floatval($r['price'] ?? 0);
        $price = 'Rp ' . number_format($priceRaw, 0, ',', '.');
        $vehicle = trim(($r['nopol'] ?? '-') . ' - ' . ($r['merek'] ?? 'Unit'));
        $tripDate = !empty($r['start_date']) ? strtoupper(date('d M Y', strtotime($r['start_date']))) : '-';
        $tripEndDate = !empty($r['end_date']) ? strtoupper(date('d M Y', strtotime($r['end_date']))) : '-';
        $tripHour = !empty($r['departure_time']) ? substr($r['departure_time'], 0, 5) : '--:--';
        $dpRaw = floatval($r['down_payment'] ?? 0);
        $dpFmt = 'Rp ' . number_format($dpRaw, 0, ',', '.');
        $payStatus = trim($r['payment_status'] ?? 'Belum Bayar');
        $payStatusColor = match($payStatus) { 'Lunas' => '#10b981', 'DP' => '#f59e0b', default => '#94a3b8' };

        $startDate = new DateTime($r['start_date']);
        $endDate = new DateTime($r['end_date']);
        $interval = $startDate->diff($endDate);
        $durationDays = $interval->days + 1;

        $bopStatus = $r['bop_status'] ?? 'pending';
        $bopLabel = ($bopStatus === 'done') ? 'Lunas Semua' : 'Belum Lunas';
        $statusClass = ($bopStatus === 'done') ? 'paid' : 'pending';
        $layanan = $r['layanan'] ?? 'Regular';
        $driverName = trim($r['driver_name'] ?? '-') !== '' ? $r['driver_name'] : '-';
        $sourceLabel = !empty($r['company_name']) ? 'ADMIN' : 'USER';
        $routeFrom = trim($r['pickup_point'] ?? '-');
        $routeTo = trim($r['drop_point'] ?? '-');

        $dataAttrs = 'data-id="' . intval($r['id']) . '" ';
        $dataAttrs .= 'data-name="' . charter_h($r['name'] ?? '') . '" ';
        $dataAttrs .= 'data-company="' . charter_h($r['company_name'] ?? '') . '" ';
        $dataAttrs .= 'data-phone="' . charter_h($r['phone'] ?? '') . '" ';
        $dataAttrs .= 'data-start="' . charter_h($r['start_date'] ?? '') . '" ';
        $dataAttrs .= 'data-end="' . charter_h($r['end_date'] ?? '') . '" ';
        $dataAttrs .= 'data-deptime="' . charter_h($tripHour) . '" ';
        $dataAttrs .= 'data-pickup="' . charter_h($routeFrom) . '" ';
        $dataAttrs .= 'data-drop="' . charter_h($routeTo) . '" ';
        $dataAttrs .= 'data-unit="' . charter_h($r['unit_id'] ?? '') . '" ';
        $dataAttrs .= 'data-driver="' . charter_h($r['driver_name'] ?? '') . '" ';
        $dataAttrs .= 'data-price="' . $priceRaw . '" ';
        $dataAttrs .= 'data-layanan="' . charter_h($layanan) . '" ';
        $dataAttrs .= 'data-bop_price="' . floatval($r['bop_price'] ?? 0) . '" ';
        $dataAttrs .= 'data-vehicle="' . charter_h($vehicle) . '" ';
        $dataAttrs .= 'data-duration="' . $durationDays . '" ';
        $dataAttrs .= 'data-dp="' . $dpRaw . '" ';
        $dataAttrs .= 'data-payment_status="' . charter_h($payStatus) . '" ';
        $dataAttrs .= 'data-bop="' . charter_h($bopStatus) . '"';

        $code = charter_h('CRT-' . date('ymd', strtotime($r['start_date'])) . '-' . str_pad((string) intval($r['id']), 3, '0', STR_PAD_LEFT));

        $accentColor = $bopStatus === 'done' ? '#10b981' : '#f59e0b';
        $accentBg = $bopStatus === 'done' ? 'rgba(16, 185, 129, 0.05)' : 'rgba(245, 158, 11, 0.05)';

        echo '<div class="admin-bs-card mb-3" style="border-radius: 20px; overflow: hidden; border: 1px solid var(--border-color); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); position: relative; background: var(--card-bg); grid-column: 1 / -1;" ' . $dataAttrs . '>';
        
        // Left accent bar
        echo '  <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 6px; background: ' . $accentColor . ';"></div>';

        // Header
        echo '  <div class="d-flex justify-content-between align-items-center" style="padding: 14px 20px; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.01);">';
        echo '    <div class="d-flex align-items-center gap-3">';
        echo '      <div style="background: ' . $accentBg . '; color: ' . $accentColor . '; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px;">';
        echo '        <i class="fa-solid fa-file-contract"></i>';
        echo '      </div>';
        echo '      <span style="font-weight: 700; color: var(--text-main); font-size: 15px; letter-spacing: -0.2px;">' . $code . '</span>';
        if ($sourceLabel === 'ADMIN') {
            echo '  <span style="background: rgba(var(--neu-primary-rgb), 0.1); color: var(--neu-primary); padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700;">ADMIN</span>';
        }
        echo '    </div>';
        echo '    <div style="background: ' . ($bopStatus === 'done' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)') . '; color: ' . $accentColor . '; padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">' . htmlspecialchars($bopLabel) . '</div>';
        echo '  </div>';

        // Body
        echo '  <div style="padding: 20px 20px 20px 26px;">';
        
        echo '    <div class="row g-4">';
        
        // Left: Customer & Route
        echo '      <div class="col-md-7">';
        echo '        <div style="margin-bottom: 20px;">';
        echo '          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px;">Customer</div>';
        echo '          <h4 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">' . charter_h($r['name']) . '</h4>';
        echo '          <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;"><i class="fa-solid fa-phone" style="margin-right: 8px; font-size: 12px;"></i>' . charter_h($r['phone']) . '</div>';
        echo '        </div>';

        echo '        <div style="position: relative; padding-left: 20px;">';
        echo '          <div style="position: absolute; left: 4px; top: 8px; bottom: 8px; width: 2px; background: repeating-linear-gradient(to bottom, var(--border-color), var(--border-color) 4px, transparent 4px, transparent 8px);"></div>';
        echo '          <div style="margin-bottom: 12px; position: relative;">';
        echo '            <div style="position: absolute; left: -20px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--primary-color); border: 2px solid var(--card-bg);"></div>';
        echo '            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px;">Penjemputan</div>';
        echo '            <div style="font-weight: 600; color: var(--text-main); font-size: 14px;">' . charter_h($routeFrom) . '</div>';
        echo '          </div>';
        echo '          <div style="position: relative;">';
        echo '            <div style="position: absolute; left: -20px; top: 4px; width: 10px; height: 10px; border-radius: 2px; background: #ef4444; border: 2px solid var(--card-bg);"></div>';
        echo '            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px;">Tujuan / Destinasi</div>';
        echo '            <div style="font-weight: 600; color: var(--text-main); font-size: 14px;">' . charter_h($routeTo) . '</div>';
        echo '          </div>';
        echo '        </div>';
        echo '      </div>';

        // Right: Schedule & Info
        echo '      <div class="col-md-5">';
        echo '        <div style="background: var(--bg-body); border-radius: 16px; padding: 16px; height: 100%; border: 1px solid var(--border-color);">';
                echo '          <div style="margin-bottom: 16px;">';
        echo '            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">Jadwal Perjalanan</div>';
        echo '            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">';
        echo '              <i class="fa-solid fa-bus" style="color: var(--primary-color); font-size: 11px; width: 14px;"></i>';
        echo '              <div style="background: var(--primary-color); color: #fff; padding: 3px 9px; border-radius: 7px; font-weight: 800; font-size: 12px;">' . charter_h($tripDate) . '</div>';
        echo '              <div style="font-weight: 700; color: var(--text-main); font-size: 12px;"><i class="fa-regular fa-clock" style="margin-right: 4px; color: var(--text-muted);"></i>' . charter_h($tripHour) . '</div>';
        echo '            </div>';
        echo '            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">';
        echo '              <i class="fa-solid fa-location-dot" style="color: #ef4444; font-size: 11px; width: 14px;"></i>';
        echo '              <div style="background: rgba(239,68,68,0.12); color: #ef4444; padding: 3px 9px; border-radius: 7px; font-weight: 800; font-size: 12px;">' . charter_h($tripEndDate) . '</div>';
        echo '            </div>';
        echo '            <div style="font-size: 12px; font-weight: 600; color: var(--text-main);"><span style="color: var(--primary-color);">' . charter_h($durationDays) . ' Hari</span> &bull; ' . charter_h($layanan) . '</div>';
        echo '          </div>';

        echo '          <div style="display: flex; flex-direction: column; gap: 8px;">';
        echo '            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-main);">';
        echo '              <i class="fa-solid fa-bus" style="width: 16px; color: var(--text-muted);"></i> <span>' . charter_h($vehicle) . '</span>';
        echo '            </div>';
        echo '            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-main);">';
        echo '              <i class="fa-solid fa-user-tie" style="width: 16px; color: var(--text-muted);"></i> <span>' . charter_h($driverName) . '</span>';
        echo '            </div>';
        echo '          </div>';
        echo '        </div>';
        echo '      </div>';
        
        echo '    </div>';

        echo '  </div>'; // End Body

        // Footer
        echo '  <div style="padding: 16px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(var(--neu-primary-rgb), 0.02);">';
        echo '    <div style="display: flex; gap: 20px; align-items: flex-end;">';
        echo '      <div>';
        echo '        <div style="font-size: 10px; color: var(--text-muted); font-weight: 800; text-transform: uppercase; margin-bottom: 1px; letter-spacing: 0.5px;">Total Biaya Carter</div>';
        echo '        <div style="font-size: 20px; font-weight: 900; color: var(--primary-color); letter-spacing: -0.5px;">' . $price . '</div>';
        echo '      </div>';
        echo '      <div>';
        echo '        <div style="font-size: 10px; color: var(--text-muted); font-weight: 800; text-transform: uppercase; margin-bottom: 1px; letter-spacing: 0.5px;">DP Dibayar</div>';
        echo '        <div style="display: flex; align-items: center; gap: 6px;">';
        echo '          <span style="font-size: 15px; font-weight: 800; color: var(--text-main);">' . charter_h($dpFmt) . '</span>';
        echo '          <span style="background: ' . $payStatusColor . '22; color: ' . $payStatusColor . '; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 6px; text-transform: uppercase;">' . charter_h($payStatus) . '</span>';
        echo '        </div>';
        echo '      </div>';
        echo '    </div>';
        echo '    <div style="display: flex; gap: 8px;">';
        if ($bopStatus !== 'done') {
            echo '      <button class="btn bop-done-btn" data-id="' . intval($r['id']) . '" title="Tandai BOP Lunas" style="background: #10b981; color: #fff; border-radius: 12px; padding: 8px 16px; font-size: 13px; font-weight: 700; border: none; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);"><i class="fa-solid fa-check-double" style="margin-right: 6px;"></i> Lunas</button>';
        }
        echo '      <button class="btn copy-charter-btn" data-id="' . intval($r['id']) . '" title="Salin Detail" style="background: var(--bg-body); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 12px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-copy"></i></button>';
        echo '      <button class="btn edit-charter-btn" data-id="' . intval($r['id']) . '" title="Edit Charter" style="background: var(--bg-body); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 12px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-pen-to-square"></i></button>';
        echo '      <button class="btn delete-charter-btn" data-id="' . intval($r['id']) . '" data-name="' . charter_h($r['name']) . '" title="Hapus" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-trash"></i></button>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'charters');

header('Content-Type: application/json');
perf_finish('admin.chartersPage', $perfStartedAt, [
    'page' => $page,
    'per_page' => $per_page,
    'search' => $search !== '',
    'rows' => count($rows),
    'total' => $total,
], 120);
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
