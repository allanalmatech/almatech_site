<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config.php';

require_admin_login();
$admin = current_admin();

db()->exec("CREATE TABLE IF NOT EXISTS gadget_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(140) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(190) NULL,
    gadget_name VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    status ENUM('pending', 'done') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gadget_requests_status (status),
    INDEX idx_gadget_requests_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function request_status_label(string $status): string
{
    return $status === 'done' ? 'Done' : 'Pending';
}

function request_status_badge(string $status): string
{
    return $status === 'done' ? 'success' : 'warning text-dark';
}

function parse_selected_ids(array $rawIds): array
{
    $ids = [];
    foreach ($rawIds as $raw) {
        $id = (int)$raw;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

function delete_request_rows(array $ids): int
{
    if (!$ids) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $imgStmt = db()->prepare("SELECT image_path FROM gadget_requests WHERE id IN ({$placeholders})");
    $imgStmt->execute($ids);
    $rows = $imgStmt->fetchAll();

    $deleteStmt = db()->prepare("DELETE FROM gadget_requests WHERE id IN ({$placeholders})");
    $deleteStmt->execute($ids);
    $deleted = $deleteStmt->rowCount();

    foreach ($rows as $row) {
        remove_gadget_request_image_file((string)($row['image_path'] ?? ''));
    }

    return $deleted;
}

function update_request_status_rows(array $ids, string $status): int
{
    if (!$ids) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$status], $ids);
    $stmt = db()->prepare("UPDATE gadget_requests SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})");
    $stmt->execute($params);
    return $stmt->rowCount();
}

function pdf_escape_text(string $value): string
{
    $value = str_replace('\\', '\\\\', $value);
    $value = str_replace('(', '\\(', $value);
    $value = str_replace(')', '\\)', $value);
    return preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
}

function build_requests_pdf(array $rows): string
{
    $lines = [];
    $lines[] = 'Alma Tech Consults - Gadget Requests Report';
    $lines[] = 'Generated: ' . date('Y-m-d H:i:s');
    $lines[] = str_repeat('-', 100);

    foreach ($rows as $row) {
        $line = sprintf(
            '#%d | %s | %s | %s | %s | %s',
            (int)$row['id'],
            (string)$row['full_name'],
            (string)$row['phone'],
            (string)$row['gadget_name'],
            strtoupper((string)$row['status']),
            (string)$row['created_at']
        );
        $lines[] = substr($line, 0, 140);

        $desc = trim((string)$row['description']);
        if ($desc !== '') {
            $lines[] = 'Desc: ' . substr($desc, 0, 120);
        }
        $lines[] = '';
    }

    if (count($lines) <= 3) {
        $lines[] = 'No rows found for this export.';
    }

    $pageHeight = 842;
    $startY = 800;
    $lineHeight = 14;
    $bottomY = 48;
    $pages = [];
    $current = [];
    $y = $startY;

    foreach ($lines as $line) {
        if ($y < $bottomY) {
            $pages[] = $current;
            $current = [];
            $y = $startY;
        }
        $current[] = 'BT /F1 10 Tf 40 ' . $y . ' Td (' . pdf_escape_text($line) . ') Tj ET';
        $y -= $lineHeight;
    }
    if ($current) {
        $pages[] = $current;
    }

    $objectCount = 2 + (count($pages) * 2) + 1;
    $fontObj = $objectCount;
    $objects = [];

    $kids = [];
    $objNum = 3;
    foreach ($pages as $pageLines) {
        $pageObj = $objNum;
        $contentObj = $objNum + 1;
        $kids[] = $pageObj . ' 0 R';

        $stream = implode("\n", $pageLines) . "\n";
        $objects[$pageObj] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 {$pageHeight}] /Resources << /Font << /F1 {$fontObj} 0 R >> >> /Contents {$contentObj} 0 R >>";
        $objects[$contentObj] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";

        $objNum += 2;
    }

    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($pages) . ' >>';
    $objects[$fontObj] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $num => $content) {
        $offsets[$num] = strlen($pdf);
        $pdf .= $num . " 0 obj\n" . $content . "\nendobj\n";
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . ($fontObj + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($i = 1; $i <= $fontObj; $i++) {
        $offset = $offsets[$i] ?? 0;
        $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
    }

    $pdf .= "trailer\n<< /Size " . ($fontObj + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
    return $pdf;
}

$q = trim((string)($_GET['q'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, (int)setting('products_per_page', '15'));

if ($statusFilter !== '' && !in_array($statusFilter, ['pending', 'done'], true)) {
    $statusFilter = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_request();

    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'bulk_apply') {
        $bulkAction = trim((string)($_POST['bulk_action'] ?? ''));
        $ids = parse_selected_ids((array)($_POST['selected_ids'] ?? []));

        if (!$ids) {
            set_flash('warning', 'Please select at least one request.');
            redirect_to(admin_url('requests.php'));
        }

        if ($bulkAction === 'mark_pending') {
            $count = update_request_status_rows($ids, 'pending');
            set_flash('success', 'Marked ' . $count . ' request(s) as pending.');
        } elseif ($bulkAction === 'mark_done') {
            $count = update_request_status_rows($ids, 'done');
            set_flash('success', 'Marked ' . $count . ' request(s) as done.');
        } elseif ($bulkAction === 'delete') {
            $count = delete_request_rows($ids);
            set_flash('success', 'Deleted ' . $count . ' request(s).');
        } else {
            set_flash('warning', 'Choose a valid bulk action.');
        }

        redirect_to(admin_url('requests.php'));
    }

    if ($action === 'single_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'pending'));
        if (!in_array($status, ['pending', 'done'], true) || $id <= 0) {
            set_flash('danger', 'Invalid request status update.');
            redirect_to(admin_url('requests.php'));
        }

        update_request_status_rows([$id], $status);
        set_flash('success', 'Request status updated.');
        redirect_to(admin_url('requests.php'));
    }

    if ($action === 'single_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash('danger', 'Invalid request selected.');
            redirect_to(admin_url('requests.php'));
        }

        delete_request_rows([$id]);
        set_flash('success', 'Request deleted.');
        redirect_to(admin_url('requests.php'));
    }
}

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(full_name LIKE :q OR phone LIKE :q OR email LIKE :q OR gadget_name LIKE :q OR description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

if ($statusFilter !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $statusFilter;
}

if ($from !== '') {
    $where[] = 'DATE(created_at) >= :date_from';
    $params[':date_from'] = $from;
}

if ($to !== '') {
    $where[] = 'DATE(created_at) <= :date_to';
    $params[':date_to'] = $to;
}

$whereSql = implode(' AND ', $where);

$export = trim((string)($_GET['export'] ?? ''));
if ($export === 'xls' || $export === 'pdf') {
    $exportStmt = db()->prepare("SELECT * FROM gadget_requests WHERE {$whereSql} ORDER BY created_at DESC");
    $exportStmt->execute($params);
    $rows = $exportStmt->fetchAll();

    if ($export === 'xls') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="gadget_requests_' . date('Ymd_His') . '.xls"');

        $out = fopen('php://output', 'w');
        fwrite($out, "ID\tName\tPhone\tEmail\tGadget\tDescription\tStatus\tImage\tCreated At\n");
        foreach ($rows as $row) {
            fwrite($out, implode("\t", [
                (string)$row['id'],
                (string)$row['full_name'],
                (string)$row['phone'],
                (string)($row['email'] ?? ''),
                (string)$row['gadget_name'],
                preg_replace('/[\r\n\t]+/', ' ', (string)$row['description']) ?? '',
                (string)$row['status'],
                (string)($row['image_path'] ?? ''),
                (string)$row['created_at'],
            ]) . "\n");
        }
        fclose($out);
        exit;
    }

    $pdf = build_requests_pdf($rows);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="gadget_requests_' . date('Ymd_His') . '.pdf"');
    echo $pdf;
    exit;
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM gadget_requests WHERE {$whereSql}");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$pagination = pagination_meta($totalItems, $page, $perPage);

$listStmt = db()->prepare("SELECT * FROM gadget_requests WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll();

$queryBase = [
    'q' => $q,
    'status' => $statusFilter,
    'from' => $from,
    'to' => $to,
];
$exportXlsUrl = '?' . http_build_query(array_merge($queryBase, ['export' => 'xls']));
$exportPdfUrl = '?' . http_build_query(array_merge($queryBase, ['export' => 'pdf']));

$page_title = 'Gadget Requests | Admin';
$page_heading = 'Gadget Requests';
$page_subtitle = 'Review, update, and export customer gadget requests';
$active_admin = 'shop_requests';

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

$flash = get_flash();
?>

<div class="admin-main">
    <?php require_once __DIR__ . '/includes/admin_topbar.php'; ?>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e((string)$flash['type']) ?> mb-3"><?= e((string)$flash['message']) ?></div>
    <?php endif; ?>

    <div class="admin-card p-4">
        <div class="card table-card p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="mb-0">Shop Requests</h4>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-success" href="<?= e($exportXlsUrl) ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export XLS</a>
                    <a class="btn btn-outline-danger" href="<?= e($exportPdfUrl) ?>"><i class="bi bi-file-earmark-pdf me-1"></i>Export PDF</a>
                </div>
            </div>

            <form class="row g-2 mb-3" method="get">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="Search name, gadget, phone, description" value="<?= e($q) ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="done" <?= $statusFilter === 'done' ? 'selected' : '' ?>>Done</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-outline-orange" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary" href="<?= e(admin_url('requests.php')) ?>">Reset</a>
                </div>
            </form>

            <form method="post" id="requestsBulkForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="bulk_apply">

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <select name="bulk_action" class="form-select" style="max-width: 230px;" required>
                        <option value="">Bulk Action</option>
                        <option value="mark_pending">Mark Pending</option>
                        <option value="mark_done">Mark Done</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-orange"><i class="bi bi-check2-square me-1"></i>Apply</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                        <tr>
                            <th style="width: 42px;"><input type="checkbox" id="checkAllRequests"></th>
                            <th style="width: 60px;">#</th>
                            <th>Customer</th>
                            <th>Gadget</th>
                            <th>Description</th>
                            <th style="width: 90px;">Image</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 160px;">Date</th>
                            <th class="text-end" style="width: 220px;">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">No gadget requests found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($rows as $row): ?>
                            <?php $imgUrl = gadget_request_image_url((string)($row['image_path'] ?? '')); ?>
                            <tr>
                                <td><input class="request-check" type="checkbox" name="selected_ids[]" value="<?= (int)$row['id'] ?>"></td>
                                <td class="text-muted"><?= (int)$row['id'] ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e((string)$row['full_name']) ?></div>
                                    <div class="small text-muted"><?= e((string)$row['phone']) ?></div>
                                    <?php if (!empty($row['email'])): ?><div class="small text-muted"><?= e((string)$row['email']) ?></div><?php endif; ?>
                                </td>
                                <td><?= e((string)$row['gadget_name']) ?></td>
                                <?php $descText = (string)$row['description']; ?>
                                <td class="small text-muted"><?= e(substr($descText, 0, 110) . (strlen($descText) > 110 ? '...' : '')) ?></td>
                                <td>
                                    <?php if ($imgUrl !== ''): ?>
                                        <a href="<?= e($imgUrl) ?>" target="_blank" rel="noopener">
                                            <img src="<?= e($imgUrl) ?>" alt="Request image" style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= request_status_badge((string)$row['status']) ?>"><?= e(request_status_label((string)$row['status'])) ?></span></td>
                                <td class="small text-muted"><?= e((string)$row['created_at']) ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="single_status">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <input type="hidden" name="status" value="<?= (string)$row['status'] === 'done' ? 'pending' : 'done' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-orange"><?= (string)$row['status'] === 'done' ? 'Mark Pending' : 'Mark Done' ?></button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this request?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="single_delete">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <nav class="mt-3">
                <ul class="pagination mb-0">
                    <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(pagination_url($queryBase, $pagination['current_page'] - 1)) ?>">Previous</a>
                    </li>
                    <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                        <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(pagination_url($queryBase, $p)) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(pagination_url($queryBase, $pagination['current_page'] + 1)) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkAll = document.getElementById('checkAllRequests');
    var checkboxes = document.querySelectorAll('.request-check');

    if (!checkAll) return;

    checkAll.addEventListener('change', function () {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = checkAll.checked;
        });
    });

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var allChecked = true;
            checkboxes.forEach(function (item) {
                if (!item.checked) {
                    allChecked = false;
                }
            });
            checkAll.checked = allChecked;
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
