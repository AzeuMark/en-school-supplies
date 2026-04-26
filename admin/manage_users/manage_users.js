// Admin — Manage Users
(function () {
  const tbl   = document.getElementById('users-tbl');
  const pagEl = document.getElementById('pagination');
  const search = document.getElementById('search');
  const fRole = document.getElementById('filter-role');
  const fStatus = document.getElementById('filter-status');

  let state = { page: 1, q: '', role: '', status: '' };

  async function load() {
    tbl.innerHTML = '<div class="empty-state">Loading...</div>';
    const params = new URLSearchParams({ page: state.page, q: state.q, role: state.role, status: state.status });
    const res = await fetch(EN.BASE + '/api/users/list.php?' + params.toString());
    const data = await res.json();
    if (!data.ok) { tbl.innerHTML = '<div class="empty-state">Failed to load.</div>'; return; }
    render(data.users);
    Pagination.render(pagEl, { current: data.page, total: data.total_pages, onChange: (p) => { state.page = p; load(); } });
  }

  function render(users) {
    if (!users.length) { tbl.innerHTML = '<div class="empty-state"><div class="es-icon">👥</div><div class="es-title">No users found</div></div>'; return; }
    tbl.innerHTML = `<div class="table-wrap"><table class="table">
      <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        ${users.map(u => `
          <tr>
            <td>#${u.id}</td>
            <td>${EN.escapeHtml(u.username || '')}</td>
            <td>${EN.escapeHtml(u.full_name)}</td>
            <td>${EN.escapeHtml(u.email)}</td>
            <td>${EN.escapeHtml(u.phone)}</td>
            <td><span class="badge">${u.role}</span></td>
            <td><span class="badge status-${u.status}">${u.status}</span></td>
            <td><div class="actions">
              <button class="btn btn-sm btn-secondary" data-edit='${JSON.stringify(u).replace(/'/g,"&#39;")}' title="Edit">✏️</button>
              ${u.role !== 'admin' && u.status !== 'flagged' ? `<button class="btn btn-sm btn-warning" data-flag="${u.id}" data-name="${EN.escapeHtml(u.full_name)}" title="Flag">🚩</button>` : ''}
              ${u.role !== 'admin' ? `<button class="btn btn-sm btn-danger" data-del="${u.id}" data-name="${EN.escapeHtml(u.full_name)}" title="Delete">🗑️</button>` : ''}
            </div></td>
          </tr>`).join('')}
      </tbody></table></div>`;
  }

  // Add user modal
  document.querySelector('[data-add-user]').addEventListener('click', () => {
    const bd = Modal.show({
      title: 'Add User',
      html: userFormHtml(),
      footer: `<button class="btn btn-secondary" data-modal-close>Cancel</button>
               <button class="btn" data-save-add>Add User</button>`,
    });
    bd.querySelector('[data-save-add]').addEventListener('click', async () => {
      const body = readForm(bd);
      try { await EN.api('/api/users/add_user.php', { body }); Modal.close(bd); EN.toast('User created.', 'success'); load(); } catch (_) {}
    });
  });

  tbl.addEventListener('click', async (e) => {
    const ed = e.target.closest('[data-edit]');
    if (ed) {
      const u = JSON.parse(ed.dataset.edit.replace(/&#39;/g,"'"));
      const bd = Modal.show({
        title: 'Edit User',
        html: userFormHtml(u, true),
        footer: `<button class="btn btn-secondary" data-modal-close>Cancel</button>
                 <button class="btn" data-save-edit>Save Changes</button>`,
      });
      bd.querySelector('[data-save-edit]').addEventListener('click', async () => {
        const body = readForm(bd, true);
        body.id = u.id;
        try { await EN.api('/api/users/edit_user.php', { body }); Modal.close(bd); EN.toast('User updated.', 'success'); load(); } catch (_) {}
      });
      return;
    }
    const fl = e.target.closest('[data-flag]');
    if (fl) {
      const id = +fl.dataset.flag;
      const name = fl.dataset.name;
      const bd = Modal.show({
        title: `Flag ${name}?`,
        html: `<p class="text-muted">The user will be blocked from logging in. Provide a reason:</p>
               <textarea class="textarea" id="flag-reason" required placeholder="Reason for flagging..."></textarea>`,
        footer: `<button class="btn btn-secondary" data-modal-close>Cancel</button>
                 <button class="btn btn-warning" data-confirm-flag>Flag User</button>`,
      });
      bd.querySelector('[data-confirm-flag]').addEventListener('click', async () => {
        const reason = bd.querySelector('#flag-reason').value.trim();
        if (!reason) { EN.toast('Please enter a reason.', 'error'); return; }
        try { await EN.api('/api/users/flag_user.php', { body: { id, reason } }); Modal.close(bd); EN.toast('User flagged.', 'success'); load(); } catch (_) {}
      });
      return;
    }
    const del = e.target.closest('[data-del]');
    if (del) {
      const id = +del.dataset.del;
      const ok = await Modal.confirm({ title: 'Delete user?', message: `This will permanently delete ${del.dataset.name}.`, confirmText: 'Delete', danger: true });
      if (!ok) return;
      try { await EN.api('/api/users/delete_user.php', { body: { id } }); EN.toast('User deleted.', 'success'); load(); } catch (_) {}
    }
  });

  function userFormHtml(u = {}, edit = false) {
    return `
      <div class="field"><label>Username</label><input class="input" data-f="username" required maxlength="50" value="${EN.escapeHtml(u.username||'')}"><div class="field-help">3-50 characters. Letters, numbers, and underscores only.</div></div>
      <div class="field"><label>Full Name</label><input class="input" data-f="full_name" required maxlength="150" value="${EN.escapeHtml(u.full_name||'')}"></div>
      <div class="field"><label>Email</label><input class="input" data-f="email" type="email" required maxlength="150" value="${EN.escapeHtml(u.email||'')}"></div>
      <div class="field"><label>Phone</label><input class="input" data-f="phone" required maxlength="20" value="${EN.escapeHtml(u.phone||'')}"></div>
      <div class="field"><label>Role</label>
        <select class="select-native" data-f="role" required>
          ${edit && u.role === 'admin' ? '<option value="admin" selected>Admin</option>' : ''}
          <option value="staff" ${u.role==='staff'?'selected':''}>Staff</option>
          <option value="customer" ${u.role==='customer'?'selected':''}>Customer</option>
        </select>
      </div>
      <div class="field"><label>Password ${edit?'<span class="text-muted">(leave blank to keep)</span>':''}</label>
        <input class="input" data-f="password" type="password" ${edit?'':'required minlength="8"'}>
        <div class="field-help">Min 8 chars with letters and numbers.</div>
      </div>`;
  }
  function readForm(bd, edit = false) {
    const out = {};
    bd.querySelectorAll('[data-f]').forEach(el => out[el.dataset.f] = el.value);
    return out;
  }

  search.addEventListener('input', () => {
    clearTimeout(window._dbu); window._dbu = setTimeout(() => { state.q = search.value.trim(); state.page = 1; load(); }, 300);
  });
  fRole.addEventListener('change', () => { state.role = fRole.value; state.page = 1; load(); });
  fStatus.addEventListener('change', () => { state.status = fStatus.value; state.page = 1; load(); });

  load();
})();
