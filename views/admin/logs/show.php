<?php
$title = $title ?? 'جزئیات لاگ';
$layout = 'admin';
ob_start();

$logEntry = [];
if (is_array($log)) {
    $logEntry = $log;
} elseif (is_object($log)) {
    $logEntry = (array) $log;
}

$rawMetadata = $logEntry['metadata'] ?? null;
$metadataJson = null;
if (is_string($rawMetadata) && $rawMetadata !== '') {
    $decoded = json_decode($rawMetadata, true);
    if (is_array($decoded)) {
        $metadataJson = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        $metadataJson = $rawMetadata;
    }
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">جزئیات لاگ</h5>
            <small class="text-muted">نوع: <?= e($type) ?></small>
        </div>
        <a href="/admin/logs/<?= e($type) ?>" class="btn btn-sm btn-secondary">بازگشت</a>
    </div>

    <div class="card-body">
        <?php if (empty($logEntry)): ?>
            <div class="alert alert-warning">لاگ مورد نظر یافت نشد.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered mb-4">
                    <tbody>
                        <tr>
                            <th>شناسه</th>
                            <td><?= e($logEntry['id'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>نوع</th>
                            <td><?= e($logEntry['type'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>سطح</th>
                            <td><?= e($logEntry['level'] ?? $logEntry['action'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>متن</th>
                            <td><?= e($logEntry['message'] ?? $logEntry['description'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>کاربر</th>
                            <td><?= e($logEntry['full_name'] ?? $logEntry['user_id'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>ایمیل</th>
                            <td><?= e($logEntry['email'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>آی‌پی</th>
                            <td><code><?= e($logEntry['ip_address'] ?? '-') ?></code></td>
                        </tr>
                        <tr>
                            <th>User Agent</th>
                            <td><pre class="m-0" style="white-space: pre-wrap;"><?= e($logEntry['user_agent'] ?? '-') ?></pre></td>
                        </tr>
                        <tr>
                            <th>تاریخ</th>
                            <td><?= e($logEntry['created_at'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>درخواست</th>
                            <td><?= e($logEntry['request_id'] ?? '-') ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="mb-3">
                    <h6>متادیتا</h6>
                    <pre class="bg-light p-3 rounded" style="white-space: pre-wrap; word-break: break-word;">
<?= e($metadataJson ?? '-'); ?>
                    </pre>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include view_path('layouts.' . $layout);
