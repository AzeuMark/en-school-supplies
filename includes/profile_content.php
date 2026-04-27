<?php
// Shared profile form. Included by all role profile pages after layout_header.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';

global $pdo;
$uid = (int)$_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT id, full_name, username, email, phone, profile_image, theme_preference, role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$me = $stmt->fetch();

$avatar_url = (!empty($me['profile_image']) && file_exists(APP_ROOT . '/' . $me['profile_image']))
    ? url('/' . ltrim($me['profile_image'], '/'))
    : null;
$initials = strtoupper(mb_substr($me['full_name'] ?? '?', 0, 1));
$role_label = ucfirst($me['role'] ?? 'User');
?>
<div class="page-header">
  <div class="page-header-main">
    <h1>My Profile</h1>
    <p class="page-subtitle">Manage your account information and security settings.</p>
  </div>
</div>

<div class="profile-layout">

  <!-- ── Left: Avatar card ──────────────────────────────── -->
  <aside class="profile-aside">
    <div class="card profile-avatar-card">
      <form id="avatar-form" enctype="multipart/form-data">
        <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp" hidden>
        <button type="button" class="avatar-upload-zone" id="avatar-trigger" title="Click to change avatar" aria-label="Change avatar">
          <span class="avatar avatar-xl" id="avatar-preview">
            <?php if ($avatar_url): ?>
              <img src="<?= e($avatar_url) ?>" alt="">
            <?php else: ?>
              <?= e($initials) ?>
            <?php endif; ?>
          </span>
          <span class="avatar-overlay" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            Change
          </span>
        </button>
      </form>
      <h3 class="profile-aside-name" id="profile-name-label"><?= e($me['full_name']) ?></h3>
      <span class="profile-aside-role"><?= e($role_label) ?></span>
      <p class="profile-aside-hint">JPG, PNG, or WebP · Max 1 MB</p>
    </div>

    <!-- Account meta -->
    <div class="card profile-meta-card">
      <div class="profile-meta-row">
        <span class="profile-meta-icon">👤</span>
        <div>
          <div class="profile-meta-label">Username</div>
          <div class="profile-meta-value" id="meta-username"><?= e($me['username']) ?></div>
        </div>
      </div>
      <div class="profile-meta-row">
        <span class="profile-meta-icon">✉️</span>
        <div>
          <div class="profile-meta-label">Email</div>
          <div class="profile-meta-value"><?= e($me['email']) ?></div>
        </div>
      </div>
      <div class="profile-meta-row">
        <span class="profile-meta-icon">📱</span>
        <div>
          <div class="profile-meta-label">Phone</div>
          <div class="profile-meta-value"><?= e($me['phone'] ?: '—') ?></div>
        </div>
      </div>
    </div>
  </aside>

  <!-- ── Right: Tabbed form ─────────────────────────────── -->
  <div class="profile-main">

    <!-- Tabs -->
    <div class="profile-tabs" role="tablist">
      <button class="profile-tab active" data-tab="info" role="tab" aria-selected="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Profile Info
      </button>
      <button class="profile-tab" data-tab="security" role="tab" aria-selected="false">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Security
      </button>
    </div>

    <form id="profile-form">

      <!-- ── Tab: Profile Info ── -->
      <div class="profile-tab-panel" id="tab-info">
        <div class="card profile-section-card">
          <div class="profile-section-head">
            <h3>Personal Information</h3>
            <p>Update your name, contact details and login username.</p>
          </div>
          <div class="profile-fields-grid">
            <div class="field">
              <label for="full_name">Full Name</label>
              <input class="input" id="full_name" name="full_name" required maxlength="150" value="<?= e($me['full_name']) ?>" placeholder="Your full name">
            </div>
            <div class="field">
              <label for="username">Username</label>
              <input class="input" id="username" name="username" required maxlength="50" value="<?= e($me['username']) ?>" placeholder="your_username">
            </div>
            <div class="field">
              <label for="email">Email Address</label>
              <input class="input" id="email" name="email" type="email" required maxlength="150" value="<?= e($me['email']) ?>" placeholder="you@example.com">
            </div>
            <div class="field">
              <label for="phone">Phone Number</label>
              <input class="input" id="phone" name="phone" required maxlength="20" minlength="7" inputmode="tel" pattern="[0-9+\-\s().]{7,20}" title="7–20 characters: digits, spaces, +, -, (, ) only" value="<?= e($me['phone']) ?>" placeholder="+63 9XX XXX XXXX">
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tab: Security ── -->
      <div class="profile-tab-panel hidden" id="tab-security">
        <div class="card profile-section-card">
          <div class="profile-section-head">
            <h3>Change Password</h3>
            <p>Leave all fields blank to keep your current password.</p>
          </div>
          <div class="profile-security-grid">
            <div class="field profile-security-full">
              <label for="current_password">Current Password</label>
              <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" placeholder="Enter your current password">
            </div>
            <div class="field">
              <label for="new_password">New Password</label>
              <input class="input" id="new_password" name="new_password" type="password" autocomplete="new-password" placeholder="New password">
            </div>
            <div class="field">
              <label for="new_password2">Confirm New Password</label>
              <input class="input" id="new_password2" name="new_password2" type="password" autocomplete="new-password" placeholder="Repeat new password">
            </div>
          </div>
        </div>
      </div>

      <div class="profile-save-wrap" data-save-container>
        <button type="submit" class="btn" id="profile-save-btn" disabled>No Changes</button>
      </div>

    </form>
  </div><!-- /.profile-main -->
</div><!-- /.profile-layout -->

<script>
(function () {
  const profileForm    = document.getElementById('profile-form');
  const avatarInput    = document.getElementById('avatar-input');
  const avatarTrigger  = document.getElementById('avatar-trigger');
  const avatarPreview  = document.getElementById('avatar-preview');
  const saveBtn        = document.getElementById('profile-save-btn');
  const saveContainer  = profileForm.querySelector('[data-save-container]');
  const profileNameLabel = document.getElementById('profile-name-label');
  const metaUsername   = document.getElementById('meta-username');

  let pendingAvatarFile = null;
  let pendingAvatarPreviewUrl = null;

  /* ── Tabs ──────────────────────────────────────────── */
  document.querySelectorAll('.profile-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.profile-tab').forEach(function (t) {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('.profile-tab-panel').forEach(function (p) {
        p.classList.add('hidden');
      });
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');
      const panel = document.getElementById('tab-' + tab.dataset.tab);
      if (panel) panel.classList.remove('hidden');
    });
  });

  /* ── Dirty-state tracking ──────────────────────────── */
  function getFormState() {
    return JSON.stringify({
      username: profileForm.username.value,
      full_name: profileForm.full_name.value,
      email: profileForm.email.value,
      phone: profileForm.phone.value,
      current_password: profileForm.current_password.value,
      new_password: profileForm.new_password.value,
      new_password2: profileForm.new_password2.value,
      avatar_pending: pendingAvatarFile ? pendingAvatarFile.name : '',
    });
  }

  let initialState = getFormState();

  function updateSaveState() {
    const isDirty = getFormState() !== initialState;
    saveBtn.disabled = !isDirty;
    saveBtn.textContent = isDirty ? 'Save Changes' : 'No Changes';
    if (saveContainer) {
      saveContainer.classList.toggle('profile-save-floating', isDirty);
    }
  }

  /* ── Name / chip sync ──────────────────────────────── */
  function updateNameDisplays(fullName) {
    if (profileNameLabel) profileNameLabel.textContent = fullName;
    const chipName = document.querySelector('.profile-chip .pc-name');
    if (chipName) chipName.textContent = fullName;
  }

  /* ── Avatar preview ────────────────────────────────── */
  function setAvatarPreview(src) {
    avatarPreview.innerHTML = `<img src="${src}" alt="">`;
  }

  avatarTrigger.addEventListener('click', function () { avatarInput.click(); });

  avatarInput.addEventListener('change', function () {
    const f = avatarInput.files[0];
    if (!f) {
      pendingAvatarFile = null;
      if (pendingAvatarPreviewUrl) { URL.revokeObjectURL(pendingAvatarPreviewUrl); pendingAvatarPreviewUrl = null; }
      updateSaveState();
      return;
    }
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(f.type)) { window.EN.toast('Only JPG, PNG, or WebP images are allowed.', 'error'); avatarInput.value = ''; return; }
    if (f.size > 1024 * 1024)      { window.EN.toast('Image must be 1 MB or smaller.', 'error'); avatarInput.value = ''; return; }

    pendingAvatarFile = f;
    if (pendingAvatarPreviewUrl) URL.revokeObjectURL(pendingAvatarPreviewUrl);
    pendingAvatarPreviewUrl = URL.createObjectURL(f);
    setAvatarPreview(pendingAvatarPreviewUrl);
    updateSaveState();
  });

  ['input', 'change'].forEach(function (evt) {
    profileForm.querySelectorAll('input').forEach(function (el) { el.addEventListener(evt, updateSaveState); });
  });

  /* ── Submit ────────────────────────────────────────── */
  profileForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (saveBtn.disabled) return;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    try {
      const body = {
        username: profileForm.username.value,
        full_name: profileForm.full_name.value,
        email: profileForm.email.value,
        phone: profileForm.phone.value,
        current_password: profileForm.current_password.value,
        new_password: profileForm.new_password.value,
        new_password2: profileForm.new_password2.value,
      };
      const data = await window.EN.api('/api/profile/update.php', { body });

      if (pendingAvatarFile) {
        const fd = new FormData();
        fd.append('avatar', pendingAvatarFile);
        const avatarRes = await window.EN.api('/api/profile/upload_avatar.php', { formData: fd });
        const freshUrl = `${EN.BASE}${avatarRes.url}?t=${Date.now()}`;
        setAvatarPreview(freshUrl);
        const chipAvatar = document.querySelector('.profile-chip .avatar');
        if (chipAvatar) chipAvatar.innerHTML = `<img src="${freshUrl}" alt="">`;
      }

      updateNameDisplays(profileForm.full_name.value);
      if (metaUsername) metaUsername.textContent = profileForm.username.value;

      profileForm.current_password.value = '';
      profileForm.new_password.value = '';
      profileForm.new_password2.value = '';
      avatarInput.value = '';
      pendingAvatarFile = null;
      if (pendingAvatarPreviewUrl) { URL.revokeObjectURL(pendingAvatarPreviewUrl); pendingAvatarPreviewUrl = null; }

      initialState = getFormState();
      updateSaveState();
      window.EN.toast(data.message || 'Profile updated.', 'success');
    } catch (_) {
      updateSaveState();
    }
  });

  updateSaveState();
})();
</script>
