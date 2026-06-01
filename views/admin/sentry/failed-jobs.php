<?php
/**
 * 📦 Sentry DLQ / Failed Jobs List
 */

$pageTitle = 'DLQ مانیتورینگ - Sentry';
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
        .page-header { margin-bottom: 25px; }
        .page-header h1 { font-size: 1.9rem; margin-bottom: 8px; }
        .page-header p { color: #4a5568; }
        .top-bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 20px; }
        .top-bar a, .top-bar button { background: white; border: 1px solid #e2e8f0; color: #2d3748; border-radius: 10px; padding: 10px 16px; text-decoration: none; cursor: pointer; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 20px; }
        .card { background: white; border-radius: 16px; padding: 22px; box-shadow: 0 2px 14px rgba(0,0,0,0.08); }
        .card h3 { font-size: 0.95rem; color: #4a5568; margin-bottom: 10px; }
        .card .value { font-size: 2.4rem; font-weight: 700; margin-bottom: 8px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.8rem; background: #e2e8f0; color: #2d3748; }
        .table-card { background: white; border-radius: 16px; padding: 0; overflow: hidden; box-shadow: 0 2px 14px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f7fafc; }
        th, td { padding: 14px 16px; text-align: right; border-bottom: 1px solid #edf2f7; }
        th { color: #4a5568; font-weight: 600; font-size: 0.9rem; }
        tbody tr:hover { background: #f7fafc; cursor: pointer; }
        .meta { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .filter-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .filter-form select, .filter-form button { min-width: 180px; }
        .pagination { margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .pagination a { padding: 10px 14px; border-radius: 10px; border: 1px solid #e2e8f0; text-decoration: none; color: #2d3748; }
        .pagination a.active { background: #667eea; color: white; border-color: #667eea; }
        .small-text { color: #718096; font-size: 0.9rem; }
        pre { background: #1a202c; color: #f7fafc; padding: 16px; border-radius: 12px; overflow-x: auto; font-size: 0.88rem; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>DLQ / Failed Jobs</h1>
        <p>همه‌ی jobهای شکست خورده در جدول `failed_jobs` و وضعیت DLQ.</p>
    </div>

    <div class="top-bar">
        <a href="/admin/sentry">بازگشت به داشبورد Sentry</a>
        <a href="/admin/sentry/issues">مشاهده خطاها</a>
        <a href="/admin/sentry/alerts">مدیریت هشدارها</a>
        <div class="small-text">فیلتر صف:</div>
        <form class="filter-form" method="get" action="/admin/sentry/failed-jobs">
            <select name="queue">
                <option value="">همه صف‌ها</option>
                <?php foreach ($queue_counts as $q): ?>
                    <option value="<?= e($q->queue) ?>" <?= ($q->queue === ($failed_jobs['queue'] ?? null)) ? 'selected' : '' ?>><?= e($q->queue) ?> (<?= number_format($q->count) ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit">اعمال فیلتر</button>
        </form>
    </div>

    <div class="card-grid">
        <div class="card">
            <h3>کل DLQ</h3>
            <div class="value"><?= number_format($summary['total'] ?? 0) ?></div>
            <div class="small-text">Jobهای شکست خورده در جدول</div>
        </div>
        <div class="card">
            <h3>24 ساعت گذشته</h3>
            <div class="value"><?= number_format($summary['recent_24h'] ?? 0) ?></div>
            <div class="small-text">Jobهای جدید شکست خورده</div>
        </div>
        <div class="card">
            <h3>وضعیت DLQ</h3>
            <div class="value"><?= e(strtoupper($summary['status'] ?? 'healthy')) ?></div>
            <div class="small-text">بر اساس تعداد کلی DLQ</div>
        </div>
    </div>

    <!-- Queue Breakdown -->
    <?php if (!empty($queue_counts)): ?>
    <div class="card" style="margin-bottom:20px;">
        <h2>توزیع صف‌ها</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:15px;">
            <?php foreach ($queue_counts as $q): ?>
                <div style="background:#f7fafc;padding:12px;border-radius:12px;border-right:4px solid #667eea;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <strong><?= e($q->queue) ?></strong>
                        <span style="background:#667eea;color:white;padding:4px 8px;border-radius:999px;font-size:0.85rem;font-weight:600;">
                            <?= number_format($q->count) ?>
                        </span>
                    </div>
                    <div style="width:100%;height:6px;background:#e2e8f0;border-radius:999px;margin-top:8px;overflow:hidden;">
                        <div style="height:100%;background:#667eea;width:<?= min(100, ($q->count / (max(1, $summary['total'] ?? 1))) * 100) ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>صف</th>
                    <th>تاریخ شکست</th>
                    <th>دلیل خطا</th>
                    <th>اندازه</th>
                    <th>اقدامات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($failed_jobs['items'])): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:30px;color:#718096;">
                            ✅ DLQ خالی است! هیچ job شکست خورده‌ای وجود ندارد.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($failed_jobs['items'] as $idx => $job): ?>
                    <?php 
                        $exception = $job->exception;
                        $firstLine = explode("\n", $exception)[0];
                        $displayError = strlen($firstLine) > 80 ? substr($firstLine, 0, 80) . '...' : $firstLine;
                        $payloadSize = strlen($job->payload ?? '');
                    ?>
                    <tr onclick="window.location.href='/admin/sentry/failed-jobs/<?= $job->id ?>'">
                        <td><strong><?= $job->id ?></strong></td>
                        <td>
                            <span style="background:#e2e8f0;padding:4px 8px;border-radius:6px;font-size:0.85rem;">
                                <?= e($job->queue) ?>
                            </span>
                        </td>
                        <td>
                            <small style="color:#718096;">
                                <?= e(date('Y-m-d H:i', strtotime($job->failed_at))) ?>
                            </small>
                        </td>
                        <td>
                            <code style="background:#f7fafc;padding:2px 6px;border-radius:4px;font-size:0.85rem;color:#e53e3e;">
                                <?= e($displayError) ?>
                            </code>
                        </td>
                        <td>
                            <small><?= $payloadSize < 1024 ? $payloadSize . 'B' : round($payloadSize / 1024, 1) . 'KB' ?></small>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="/admin/sentry/failed-jobs/<?= $job->id ?>" style="color:#3182ce;text-decoration:none;font-size:0.85rem;">جزئیات</a>
                                <button onclick="event.stopPropagation();retryJob(<?= $job->id ?>)" style="border:none;background:#38a169;color:white;padding:4px 8px;border-radius:6px;cursor:pointer;font-size:0.85rem;">Retry</button>
                                <button onclick="event.stopPropagation();deleteJob(<?= $job->id ?>)" style="border:none;background:#e53e3e;color:white;padding:4px 8px;border-radius:6px;cursor:pointer;font-size:0.85rem;">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (($failed_jobs['total'] ?? 0) > 0): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= ($failed_jobs['total_pages'] ?? 1); $i++): ?>
            <?php
                $query = $_GET;
                $query['page'] = $i;
                $url = '/admin/sentry/failed-jobs?' . http_build_query($query);
            ?>
            <a class="<?= $i === ($failed_jobs['page'] ?? 1) ? 'active' : '' ?>" href="<?= $url ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <script>
        async function retryJob(jobId) {
            if (!confirm('آیا می‌خواهید این job را دوباره ارسال کنید؟')) return;
            try {
                const res = await fetch(`/admin/sentry/failed-jobs/${jobId}/retry`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    alert('Job با موفقیت دوباره ارسال شد.');
                    window.location.reload();
                } else {
                    alert('خطا: ' + (data.error || 'عملیات ناموفق بود'));
                }
            } catch (e) {
                alert('خطا در ارتباط: ' + e.message);
            }
        }

        async function deleteJob(jobId) {
            if (!confirm('آیا می‌خواهید این job را حذف کنید؟')) return;
            try {
                const res = await fetch(`/admin/sentry/failed-jobs/${jobId}/forget`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    alert('Job با موفقیت حذف شد.');
                    window.location.reload();
                } else {
                    alert('خطا: ' + (data.error || 'عملیات ناموفق بود'));
                }
            } catch (e) {
                alert('خطا در ارتباط: ' + e.message);
            }
        }
    </script>
</body>
</html>
