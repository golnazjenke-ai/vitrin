<?php
/**
 * 📦 Sentry Outbox DLQ List
 */

$pageTitle = 'Outbox DLQ - Sentry';
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
        tbody tr:hover { background: #f7fafc; cursor: default; }
        .filter-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .pagination { margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .pagination a { padding: 10px 14px; border-radius: 10px; border: 1px solid #e2e8f0; text-decoration: none; color: #2d3748; }
        .pagination a.active { background: #667eea; color: white; border-color: #667eea; }
        .small-text { color: #718096; font-size: 0.9rem; }
        .code-snippet { display: block; background: #f7fafc; padding: 10px 12px; border-radius: 10px; font-family: monospace; color: #2d3748; max-width: 100%; overflow-x: auto; font-size: 0.87rem; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Outbox DLQ</h1>
        <p>رویدادهای Outbox که پس از تلاش‌های متعدد به وضعیت DLQ یا failed منتقل شده‌اند.</p>
    </div>

    <div class="top-bar">
        <a href="/admin/sentry">بازگشت به داشبورد Sentry</a>
        <a href="/admin/sentry/failed-jobs">DLQ / Failed Jobs</a>
        <a href="/admin/sentry/alerts">مدیریت هشدارها</a>
        <span class="small-text">این صفحه خطاهای پیام Outbox را نمایش می‌دهد.</span>
    </div>

    <div class="card-grid">
        <div class="card">
            <h3>کل رویدادهای Outbox DLQ</h3>
            <div class="value"><?= number_format($summary['total'] ?? 0) ?></div>
            <div class="small-text">تعداد رکوردهای DLQ و failed در جدول `outbox_events`.</div>
        </div>
        <div class="card">
            <h3>24 ساعت گذشته</h3>
            <div class="value"><?= number_format($summary['recent_24h'] ?? 0) ?></div>
            <div class="small-text">تعداد رکوردهای جدید DLQ در 24 ساعت اخیر.</div>
        </div>
        <div class="card">
            <h3>قدیمی‌ترین خطا</h3>
            <div class="value"><?= e($summary['oldest_failed_at'] ? date('Y-m-d H:i', strtotime($summary['oldest_failed_at'])) : '-') ?></div>
            <div class="small-text">اولین زمانی که یک رکورد Outbox DLQ ثبت شده است.</div>
        </div>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>نوع رویداد</th>
                    <th>وضعیت</th>
                    <th>تلاش‌ها</th>
                    <th>آخرین خطا</th>
                    <th>آخرین بروزرسانی</th>
                    <th>Payload</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($outbox['items'])): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:30px;color:#718096;">
                            ✅ هیچ رکورد Outbox DLQ یافت نشد.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($outbox['items'] as $event): ?>
                    <?php
                        $errorSnippet = e(mb_substr($event->last_error ?? '-', 0, 120));
                        $payloadSnippet = e(mb_substr($event->payload ?? '', 0, 120));
                    ?>
                    <tr>
                        <td><strong><?= e($event->id) ?></strong></td>
                        <td><?= e($event->event_type) ?></td>
                        <td><span class="badge"><?= e(strtoupper($event->status)) ?></span></td>
                        <td><?= e($event->attempts) ?></td>
                        <td><span class="code-snippet" style="background:#fff5f5;color:#9b2c2c;max-width:230px"><?= $errorSnippet ?></span></td>
                        <td><small><?= e(date('Y-m-d H:i', strtotime($event->updated_at ?? $event->created_at ?? 'now'))) ?></small></td>
                        <td><span class="code-snippet" style="background:#f0f4ff;color:#2c5282;max-width:240px"><?= $payloadSnippet ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (($outbox['total'] ?? 0) > 0): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= ($outbox['total_pages'] ?? 1); $i++): ?>
            <?php $url = '/admin/sentry/outbox-dlq?page=' . $i; ?>
            <a class="<?= $i === ($outbox['page'] ?? 1) ? 'active' : '' ?>" href="<?= $url ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</body>
</html>
