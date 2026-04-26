<?php
// Shared profile form. Included by all role profile pages after layout_header.
// Expects: $user (from get_current_user_data()), full row from `users` for fresh data.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';

global $pdo;
$uid = (int)$_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT id, full_name, email, phone, profile_image, theme_preference, role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$me = $stmt->fetch();

$avatar_url = (!empty($me['profile_image']) && file_exists(APP_ROOT . '/' . $me['profile_image']))
    ? '/' . ltrim($me['profile_image'], '/')
    : null;
$initials = strtoupper(mb_substr($me['full_name'] ?? '?', 0, 1));
?>
<div class="page-header">
  <h1>My Profile</h1>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:24px;align-items:start">
  <!-- Avatar card -->
  <div class="card" style="text-align:center">
    <div style="position:relative;display:inline-block;margin-bottom:12px">
      <span class="avatar avatar-lg" id="avatar-preview">
        <?php if ($avatar_url): ?>
          <img src="<?= e($avatar_url) ?>" alt="">
        <?php else: ?>
          <?= e($initials) ?>
        <?php endif; ?>
      </span>
    </div>
    <h3 style="margin:8px 0 4px"><?= e($me['full_name']) ?></h3>
    <div class="text-muted" style="margin-bottom:12px"><?= e(ucfirst($me['role'])) ?></div>
    <form id="avatar-form" enctype="multipart/form-data">
      <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp" style="display:none">
      <button type="button" class="btn btn-secondary btn-block" onclick="document.getElementById('avatar-input').click()">Change Avatar</button>
    </form>
    <div class="field-help mt-2">JPG, PNG, or WebP. Max 1 MB.</div>
  </div>

  <!-- Profile form -->
  <div class="card">
    <h3 class="card-title mb-4">Account Details</h3>
    <form id="profile-form">
      <div class="field">
        <label for="full_name">Full Name</label>
        <input class="input" id="full_name" name="full_name" required maxlength="150" value="<?= e($me['full_name']) ?>">
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input class="input" id="email" name="email" type="email" required maxlength="150" value="<?= e($me['email']) ?>">
      </div>
      <div class="field">
        <label for="phone">Phone</label>
        <input class="input" id="phone" name="phone" required maxlength="20" value="<?= e($me['phone']) ?>">
      </div>

      <hr>
      <h4 class="mb-3">Change Password (optional)</h4>
      <div class="field">
        <label for="current_password">Current Password</label>
        <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field">
          <label for="new_password">New Password</label>
          <input class="input" id="new_password" name="new_password" type="password" autocomplete="new-password">
        </div>
        <div class="field">
          <label for="new_password2">Confirm New Password</label>
          <input class="input" id="new_password2" name="new_password2" type="password" autocomplete="new-password">
        </div>
      </div>
      <button type="submit" class="btn">Save Changes</button>
    </form>
  </div>
</div>

<script>
(function () {
  // Avatar upload
  const input = document.getElementById('avatar-input');
  input.addEventListener('change', async () => {
    const f = input.files[0];
    if (!f) return;
    if (f.size > 1024 * 1024) { window.EN.toast('Image must be 1 MB or smaller.', 'error'); return; }
    const fd = new FormData();
    fd.append('avatar', f);
    try {
      const data = await window.EN.api('/api/profile/upload_avatar.php', { formData: fd });
      const preview = document.getElementById('avatar-preview');
      preview.innerHTML = `<img src="${data.url}?t=${Date.now()}" alt="">`;
      // Also update navbar chip
      const chipImg = document.querySelector('.profile-chip .avatar');
      if (chipImg) chipImg.innerHTML = `<img src="${data.url}?t=${Date.now()}" alt="">`;
      window.EN.toast('Avatar updated.', 'success');
    } catch (_) {}
  });

  // Profile form
  document.getElementById('profile-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = e.target;
    const body = {
      full_name: f.full_name.value,
      email: f.email.value,
      phone: f.phone.value,
      current_password: f.current_password.value,
      new_password: f.new_password.value,
      new_password2: f.new_password2.value,
    };
    try {
      const data = await window.EN.api('/api/profile/update.php', { body });
      window.EN.toast(data.message || 'Saved.', 'success');
      f.current_password.value = ''; f.new_password.value = ''; f.new_password2.value = '';
    } catch (_) {}
  });
})();
</script>
