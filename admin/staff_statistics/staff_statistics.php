<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('admin');

$PAGE_TITLE   = 'Staff Statistics';
$CURRENT_PAGE = 'staff_statistics';
$PAGE_CSS     = '/admin/staff_statistics/staff_statistics.css';
include __DIR__ . '/../../includes/layout_header.php';

// Get all staff/admin users
$staff = $pdo->query(
  "SELECT u.id, u.full_name, u.role, u.status,
            (SELECT COUNT(*) FROM orders o WHERE o.processed_by = u.id) AS orders_processed,
            (SELECT COUNT(*) FROM staff_sessions s WHERE s.user_id = u.id) AS total_sessions,
      (SELECT COUNT(*) FROM staff_sessions s WHERE s.user_id = u.id AND s.is_suspicious = 1) AS suspicious_sessions,
            (SELECT COALESCE(SUM(s.duration_minutes), 0) FROM staff_sessions s WHERE s.user_id = u.id) AS total_minutes,
      (SELECT COALESCE(AVG(s.duration_minutes), 0) FROM staff_sessions s WHERE s.user_id = u.id AND s.duration_minutes IS NOT NULL) AS avg_minutes,
            (SELECT s.login_time FROM staff_sessions s WHERE s.user_id = u.id ORDER BY s.login_time DESC LIMIT 1) AS last_login
     FROM users u
     WHERE u.role IN ('admin','staff')
     ORDER BY u.full_name"
)->fetchAll();
?>
<div class="page-header">
  <h1>Staff Statistics</h1>
</div>

<?php if (empty($staff)): ?>
  <div class="empty-state"><div class="es-icon">📊</div><div class="es-title">No staff members yet</div></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Staff Member</th>
          <th>Role</th>
          <th>Status</th>
          <th>Orders Processed</th>
          <th>Sessions</th>
          <th>Total Hours</th>
          <th>Avg Session</th>
          <th>Suspicious</th>
          <th>Last Login</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staff as $s): ?>
          <tr>
            <td><strong><?= e($s['full_name']) ?></strong></td>
            <td><span class="badge"><?= e(ucfirst($s['role'])) ?></span></td>
            <td><span class="badge status-<?= e($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span></td>
            <td><?= (int)$s['orders_processed'] ?></td>
            <td><?= (int)$s['total_sessions'] ?></td>
            <td><?= number_format((int)$s['total_minutes'] / 60, 1) ?>h</td>
            <td><?= number_format((float)$s['avg_minutes'] / 60, 1) ?>h</td>
            <td><span class="badge status-<?= ((int)$s['suspicious_sessions'] > 0) ? 'cancelled' : 'ready' ?>"><?= (int)$s['suspicious_sessions'] ?></span></td>
            <td><?= $s['last_login'] ? date('M j, g:i A', strtotime($s['last_login'])) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php $PAGE_JS = '/admin/staff_statistics/staff_statistics.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
