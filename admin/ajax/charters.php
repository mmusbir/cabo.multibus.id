<?php
/**
 * admin/ajax/charters.php - Handle charters page data
 */

global $conn;

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

$where = "WHERE start_date >= CURRENT_DATE";
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
        $vehicle = trim(($r['nopol'] ?? '-') . ' ' . ($r['merek'] ?? ''));
        $tripDate = !empty($r['start_date']) ? date('d M Y', strtotime($r['start_date'])) : '-';
        $tripDateShort = !empty($r['start_date']) ? strtoupper(date('d M', strtotime($r['start_date']))) : '-';
        $tripHour = !empty($r['departure_time']) ? substr($r['departure_time'], 0, 5) : '--:--';
        $depTimeFormatted = formatTimeWithLabel($r['departure_time']);
        $createdAgo = charter_relative_time($r['created_at'] ?? '');

        $startDate = new DateTime($r['start_date']);
        $endDate = new DateTime($r['end_date']);
        $interval = $startDate->diff($endDate);
        $durationDays = $interval->days + 1;

        $bopStatus = $r['bop_status'] ?? 'pending';
        $bopLabel = ($bopStatus === 'done') ? 'Lunas Semua' : 'Belum Lunas';
        $stateClass = ($bopStatus === 'done') ? 'ready' : 'warning';
        $layanan = $r['layanan'] ?? 'Regular';
        $routeLine = trim(($r['pickup_point'] ?? '-') . ' - ' . ($r['drop_point'] ?? '-'));
        $driverName = trim($r['driver_name'] ?? '-') !== '' ? $r['driver_name'] : '-';
        $sourceLabel = !empty($r['company_name']) ? 'ADMIN' : 'END USER';
        $routeFrom = trim($r['pickup_point'] ?? '-');
        $routeTo = trim($r['drop_point'] ?? '-');
        $routeFromShort = charter_short_place($routeFrom);
        $routeToShort = charter_short_place($routeTo);
        $cardVariantCycle = $index % 4;
        $cardVariantClass = match ($cardVariantCycle) {
            0 => 'is-wide',
            1 => 'is-side',
            2 => 'is-compact',
            default => 'is-detail',
        };
        $borderTone = ($bopStatus === 'done') ? 'tone-primary' : 'tone-danger';

        $dataAttrs = 'data-id="' . intval($r['id']) . '" ';
        $dataAttrs .= 'data-name="' . charter_h($r['name'] ?? '') . '" ';
        $dataAttrs .= 'data-company="' . charter_h($r['company_name'] ?? '') . '" ';
        $dataAttrs .= 'data-phone="' . charter_h($r['phone'] ?? '') . '" ';
        $dataAttrs .= 'data-start="' . charter_h($r['start_date'] ?? '') . '" ';
        $dataAttrs .= 'data-end="' . charter_h($r['end_date'] ?? '') . '" ';
        $dataAttrs .= 'data-deptime="' . charter_h($r['departure_time'] ? substr($r['departure_time'], 0, 5) : '') . '" ';
        $dataAttrs .= 'data-deptime-formatted="' . charter_h($depTimeFormatted) . '" ';
        $dataAttrs .= 'data-pickup="' . charter_h($r['pickup_point'] ?? '') . '" ';
        $dataAttrs .= 'data-drop="' . charter_h($r['drop_point'] ?? '') . '" ';
        $dataAttrs .= 'data-unit="' . charter_h($r['unit_id'] ?? '') . '" ';
        $dataAttrs .= 'data-driver="' . charter_h($r['driver_name'] ?? '') . '" ';
        $dataAttrs .= 'data-price="' . $priceRaw . '" ';
        $dataAttrs .= 'data-layanan="' . charter_h($layanan) . '" ';
        $dataAttrs .= 'data-bop_price="' . floatval($r['bop_price'] ?? 0) . '" ';
        $dataAttrs .= 'data-vehicle="' . charter_h($vehicle) . '" ';
        $dataAttrs .= 'data-duration="' . $durationDays . '" ';
        $dataAttrs .= 'data-bop="' . charter_h($bopStatus) . '"';

        echo '<article class="admin-card-compact charter-command-card ' . $cardVariantClass . ' ' . $borderTone . '" ' . $dataAttrs . '>';
        echo '  <div class="charter-command-card-body">';
        echo '    <div class="charter-command-top">';
        echo '      <div class="charter-command-top-copy">';
        echo '        <span class="charter-command-code">' . charter_h('CRT-' . date('Ymd', strtotime($r['start_date'])) . '-' . str_pad((string) intval($r['id']), 3, '0', STR_PAD_LEFT)) . '</span>';
        echo '        <div class="charter-command-badges">';
        echo '          <span class="charter-command-badge source">' . charter_h($sourceLabel) . '</span>';
        echo '          <span class="charter-command-age">' . charter_h($createdAgo) . '</span>';
        echo '        </div>';
        echo '      </div>';
        echo '      <div class="charter-command-state ' . $stateClass . '"><span class="charter-state-dot"></span>' . charter_h($bopLabel) . '</div>';
        echo '    </div>';

        echo '    <div class="charter-command-main">';
        echo '      <div class="charter-command-customer">';
        echo '        <h4 class="charter-command-name">' . charter_h($r['name']) . '</h4>';
        if (!empty($r['company_name'])) {
            echo '        <div class="charter-command-company">' . charter_h($r['company_name']) . '</div>';
        } else {
            echo '        <div class="charter-command-company">' . charter_h($layanan) . '</div>';
        }
        echo '      </div>';

        if ($cardVariantClass === 'is-wide') {
            echo '      <div class="charter-command-route-wide">';
            echo '        <div class="charter-command-route-stop"><span class="charter-command-label">Departure</span><strong>' . charter_h($routeFrom) . '</strong></div>';
            echo '        <i class="fa-solid fa-arrow-right fa-icon"></i>';
            echo '        <div class="charter-command-route-stop"><span class="charter-command-label">Destination</span><strong>' . charter_h($routeTo) . '</strong></div>';
            echo '      </div>';
            echo '      <div class="charter-command-schedule-box">';
            echo '        <span class="charter-command-label">Date &amp; Time</span>';
            echo '        <strong>' . charter_h($tripDate) . '</strong>';
            echo '        <em>' . charter_h($tripHour . ' WITA') . '</em>';
            echo '      </div>';
        } elseif ($cardVariantClass === 'is-side') {
            echo '      <div class="charter-command-side-stack">';
            echo '        <div class="charter-command-inline-row"><span>Route</span><strong>' . charter_h($routeFromShort) . ' <span class="charter-arrow">→</span> ' . charter_h($routeToShort) . '</strong></div>';
            echo '        <div class="charter-command-inline-row"><span>Scheduled</span><strong>' . charter_h($tripDate) . '</strong></div>';
            echo '      </div>';
        } elseif ($cardVariantClass === 'is-compact') {
            echo '      <p class="charter-command-caption">' . charter_h($layanan) . ' - ' . charter_h($durationDays) . ' hari</p>';
            echo '      <div class="charter-command-date-split">';
            echo '        <div><span class="charter-command-label">Depart</span><strong>' . charter_h($tripDateShort) . '</strong></div>';
            echo '        <i class="fa-solid fa-right-left fa-icon"></i>';
            echo '        <div><span class="charter-command-label">Return</span><strong>' . charter_h(!empty($r['end_date']) ? strtoupper(date('d M', strtotime($r['end_date']))) : '-') . '</strong></div>';
            echo '      </div>';
        } else {
            echo '      <div class="charter-command-detail-grid">';
            echo '        <div><span class="charter-command-label">Customer</span><strong>' . charter_h($r['name']) . '</strong></div>';
            echo '        <div><span class="charter-command-label">Route Status</span><div class="charter-command-route-inline"><i class="fa-solid fa-truck fa-icon"></i><strong>' . charter_h($routeFromShort) . ' → ' . charter_h($routeToShort) . '</strong></div></div>';
            echo '      </div>';
        }
        echo '    </div>';

        echo '    <div class="charter-command-footer">';
        echo '      <div class="charter-command-meta">';
        echo '        <span class="charter-command-pill">' . charter_h($sourceLabel) . '</span>';
        echo '        <span class="charter-command-note">' . charter_h($driverName) . ' • ' . charter_h($vehicle) . ' • ' . charter_h($price) . '</span>';
        echo '      </div>';
        echo '      <div class="charter-command-meta-end">' . charter_h($tripDate . ' • ' . $tripHour) . '</div>';
        echo '    </div>';

        echo '    <div class="charter-command-actions">';
        if ($bopStatus !== 'done') {
            echo '      <a href="#" class="charter-command-action success bop-done-btn" data-id="' . intval($r['id']) . '"><i class="fa-solid fa-circle-check fa-icon"></i>BOP</a>';
        }
        echo '      <a href="#" class="charter-command-action copy-charter-btn" data-id="' . intval($r['id']) . '"><i class="fa-regular fa-copy fa-icon"></i>Copy</a>';
        echo '      <a href="#" class="charter-command-action edit-charter-btn" data-id="' . intval($r['id']) . '"><i class="fa-regular fa-pen-to-square fa-icon"></i>Edit</a>';
        echo '      <a href="#" class="charter-command-action danger delete-charter-btn" data-id="' . intval($r['id']) . '" data-name="' . charter_h($r['name']) . '"><i class="fa-regular fa-trash-can fa-icon"></i>Hapus</a>';
        echo '    </div>';
        echo '  </div>';
        echo '</article>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'charters');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
