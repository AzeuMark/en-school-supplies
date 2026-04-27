<?php
// Shared profile form. Included by all role profile pages after layout_header.
// Expects: $user (from get_current_user_data()), full row from `users` for fresh data.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';

global $pdo;
$uid = (int)$_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT id, full_name, username, email, phone, profile_image, theme_preference, role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$me = $stmt->fetch();

$avatar_url = (!empty($me['profile_image']) && file_exists(APP_ROOT . '/' . $me['profile_image']))
    ? '/' . ltrim($me['profile_image'], '/')
    : null;
$initials = strtoupper(mb_substr($me['full_name'] ?? '?', 0, 1));
?>
<div class="page-header">
  <div class="page-header-main">
    <h1>My Profile</h1>
    <p class="page-subtitle">Update your account details and preview profile changes before saving.</p>
  </div>
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
    <h3 id="profile-name-label" style="margin:8px 0 4px"><?= e($me['full_name']) ?></h3>
    <div class="text-muted" style="margin-bottom:12px"><?= e(ucfirst($me['role'])) ?></div>
    <form id="avatar-form" enctype="multipart/form-data">
      <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp" style="display:none">
      <button type="button" class="btn btn-secondary btn-block" id="avatar-trigger">Choose Avatar</button>
    </form>
    <div class="field-help mt-2">JPG, PNG, or WebP. Max 1 MB.</div>
  </div>

  <!-- Profile form -->
  <div class="card">
    <h3 class="card-title mb-4">Account Details</h3>
    <form id="profile-form">
      <div class="field">
        <label for="username">Username</label>
        <input class="input" id="username" name="username" required maxlength="50" value="<?= e($me['username']) ?>">
        <div class="field-help">Use 3-50 characters: letters, numbers, and underscores only.</div>
      </div>
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
      <div class="profile-save-wrap" data-save-container>
        <button type="submit" class="btn" id="profile-save-btn" disabled>No Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const profileForm = document.getElementById('profile-form');
  const avatarInput = document.getElementById('avatar-input');
  const avatarTrigger = document.getElementById('avatar-trigger');
  const avatarPreview = document.getElementById('avatar-preview');
  const saveBtn = document.getElementById('profile-save-btn');
  const saveContainer = profileForm.querySelector('[data-save-container]');
  const profileNameLabel = document.getElementById('profile-name-label');

  let pendingAvatarFile = null;
  let pendingAvatarPreviewUrl = null;

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

  function updateNameDisplays(fullName) {
    if (profileNameLabel) {
      profileNameLabel.textContent = fullName;
    }
    const chipName = document.querySelector('.profile-chip .pc-name');
    if (chipName) {
      chipName.textContent = fullName;
    }
  }

  function setAvatarPreview(src) {
    avatarPreview.innerHTML = `<img src="${src}" alt="">`;
  }

  avatarTrigger.addEventListener('click', function () {
    avatarInput.click();
  });

  avatarInput.addEventListener('change', function () {
    const f = avatarInput.files[0];
    if (!f) {
      pendingAvatarFile = null;
      if (pendingAvatarPreviewUrl) {
        URL.revokeObjectURL(pendingAvatarPreviewUrl);
        pendingAvatarPreviewUrl = null;
      }
      updateSaveState();
      return;
    }

    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(f.type)) {
      window.EN.toast('Only JPG, PNG, or WebP images are allowed.', 'error');
      avatarInput.value = '';
      return;
    }
    if (f.size > 1024 * 1024) {
      window.EN.toast('Image must be 1 MB or smaller.', 'error');
      avatarInput.value = '';
      return;
    }

    pendingAvatarFile = f;
    if (pendingAvatarPreviewUrl) {
      URL.revokeObjectURL(pendingAvatarPreviewUrl);
    }
    pendingAvatarPreviewUrl = URL.createObjectURL(f);
    setAvatarPreview(pendingAvatarPreviewUrl);
    updateSaveState();
  });

  ['input', 'change'].forEach(function (evt) {
    profileForm.querySelectorAll('input').forEach(function (el) {
      el.addEventListener(evt, updateSaveState);
    });
  });

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
        const freshUrl = `${avatarRes.url}?t=${Date.now()}`;
        setAvatarPreview(freshUrl);
        const chipAvatar = document.querySelector('.profile-chip .avatar');
        if (chipAvatar) {
          chipAvatar.innerHTML = `<img src="${freshUrl}" alt="">`;
        }
      }

      updateNameDisplays(profileForm.full_name.value);
      profileForm.current_password.value = '';
      profileForm.new_password.value = '';
      profileForm.new_password2.value = '';
      avatarInput.value = '';
      pendingAvatarFile = null;

      if (pendingAvatarPreviewUrl) {
        URL.revokeObjectURL(pendingAvatarPreviewUrl);
        pendingAvatarPreviewUrl = null;
      }

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
