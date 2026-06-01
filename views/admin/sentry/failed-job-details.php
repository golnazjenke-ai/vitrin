<?php
/**
 * 🧾 Sentry DLQ Failed Job Details
 */

$pageTitle = 'جزئیات DLQ - Sentry';
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; padding: 20px; color: #2d3748; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 1.9rem; margin-bottom: 6px; }
        .page-header p { color: #4a5568; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
        .actions button, .actions a { border: none; border-radius: 12px; padding: 12px 18px; cursor: pointer; font-weight: 600; color: white; background: #4a5568; text-decoration: none; }
        .actions button.retry { background: #38a169; }
        .actions button.forget { background: #e53e3e; }
        .actions a { background: #2b6cb0; }
        .card { background: white; border-radius: 18px; padding: 26px; box-shadow: 0 2px 24px rgba(0,0,0,0.08); margin-bottom: 18px; }
        .card h2 { font-size: 1.1rem; margin-bottom: 12px; }
        .card p { color: #4a5568; margin-bottom: 8px; }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 18px; }
        .detail-item { background: #f7fafc; border-radius: 14px; padding: 18px; }
        .detail-item strong { display: block; margin-bottom: 6px; color: #2d3748; }
        pre { background: #1a202c; color: #f7fafc; padding: 18px; border-radius: 14px; overflow-x: auto; font-size: 0.9rem; margin-top: 12px; }
        .alert { padding: 16px 18px; border-radius: 16px; background: #faf089; color: #744210; margin-bottom: 18px; }
        .status-pill { display: inline-block; padding: 6px 12px; background: #c6f6d5; color: #22543d; border-radius: 999px; font-size: 0.9rem; }
        .status-pill.critical { background: #fed7d7; color: #c53030; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>جزئیات Job شکست خورده</h1>
        <p>شناسه: <?= number_format($job->id) ?> | صف: <?= e($job->queue) ?> | تاریخ: <?= e($job->failed_at) ?></p>
    </div>

    <div class="actions">
        <button class="retry" onclick="handleAction('retry')">Retry</button>
        <button class="forget" onclick="handleAction('forget')">Delete</button>
        <a href="/admin/sentry/failed-jobs">بازگشت به لیست DLQ</a>
    </div>

    <div id="statusMessage" class="alert" style="display:none;"></div>

    <div class="detail-grid">
        <div class="detail-item">
            <strong>صف</strong>
            <?= e($job->queue) ?>
        </div>
        <div class="detail-item">
            <strong>تاریخ شکست</strong>
            <?= e($job->failed_at) ?>
        </div>
        <div class="detail-item">
            <strong>طول payload</strong>
            <?= number_format(strlen($job->payload ?? '')) ?>
        </div>
    </div>

    <div class="card">
        <h2>Exception / Trace</h2>
        <pre><?= e($job->exception) ?></pre>
    </div>

    <div class="card">
        <h2>Payload</h2>
        <pre><?= e($job->payload) ?></pre>
    </div>

    <script>
        const statusMessage = document.getElementById('statusMessage');

        async function handleAction(action) {
            const url = `/admin/sentry/failed-jobs/<?= $job->id ?>/` + action;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                });
                const data = await res.json();
                statusMessage.style.display = 'block';
                if (data.success) {
                    statusMessage.innerText = action === 'retry' ? 'Job با موفقیت دوباره ارسال شد.' : 'Job با موفقیت حذف شد.';
                    statusMessage.style.background = '#c6f6d5';
                    statusMessage.style.color = '#22543d';
                    if (action === 'forget') {
                        window.location.href = '/admin/sentry/failed-jobs';
                    }
                } else {
                    statusMessage.innerText = data.error || 'عملیات ناموفق بود.';
                    statusMessage.style.background = '#fed7d7';
                    statusMessage.style.color = '#c53030';
                }
            } catch (error) {
                statusMessage.style.display = 'block';
                statusMessage.innerText = 'خطا در ارتباط با سرور.';
                statusMessage.style.background = '#fed7d7';
                statusMessage.style.color = '#c53030';
            }
        }
    </script>
</body>
</html>
