// Admin — Manage Users
(function () {
  const tbl   = document.getElementById('users-tbl');
  const pagEl = document.getElementById('pagination');
  const search = document.getElementById('search');
  const fRole = document.getElementById('filter-role');
  const fDate = document.getElementById('filter-date');
  const sortPreset = document.getElementById('sort-preset');
  const tabs = document.querySelector('[data-status-tabs]');
  const filterSummaryEl = document.getElementById('filter-summary');
  const resetBtn = document.querySelector('[data-reset-filters]');
  const currentAdminId = window.__CURRENT_ADMIN_ID || 0;

  let state = { page: 1, q: '', role: 'all', status: '', dateFilter: 'all', sort: 'newest' };
  let searchTimer = null;

  const statusLabelMap = {
    '': 'All Statuses',
    active: 'Active',
    pending: 'Pending',
    flagged: 'Flagged',
  };

  const roleLabelMap = {
    all: 'All Roles',
    admin: 'Admin',
    staff: 'Staff',
    customer: 'Customer',
  };

  const dateLabelMap = {
    all: 'All Time',
    today: 'Today',
    week: 'Last 7 Days',
    month: 'Last 30 Days',
  };

  const sortLabelMap = {
    newest: 'Newest First',
    oldest: 'Oldest First',
    name_asc: 'Name (A-Z)',
    name_desc: 'Name (Z-A)',
    role_asc: 'Role (A-Z)',
    role_desc: 'Role (Z-A)',
  };

  function updateFilterSummary() {
    if (!filterSummaryEl) return;
    const parts = [];
    if (state.q) parts.push(`Search: "${state.q}"`);
    if (state.status) parts.push(`Status: ${statusLabelMap[state.status]}`);
    if (state.role !== 'all') parts.push(`Role: ${roleLabelMap[state.role] || state.role}`);
    if (state.dateFilter !== 'all') parts.push(`Date: ${dateLabelMap[state.dateFilter]}`);
    const filtersText = parts.length ? parts.join(' • ') : 'Showing all users';
    const sortText = sortLabelMap[state.sort] || sortLabelMap.newest;
    filterSummaryEl.textContent = `${filtersText} • Sorted by ${sortText}`;
  }

  async function load() {
    tbl.innerHTML = '<div class="empty-state">Loading...</div>';
    const params = new URLSearchParams({
      page: state.page,
      q: state.q,
      role: state.role,
      status: state.status,
      date_filter: state.dateFilter,
      sort: state.sort,
    });
    const res = await fetch(EN.BASE + '/api/users/list.php?' + params.toString());
    const data = await res.json();
    if (!data.ok) { tbl.innerHTML = '<div class="empty-state">Failed to load.</div>'; return; }
    render(data.users);
    Pagination.render(pagEl, { current: data.page, total: data.total_pages, onChange: (p) => { state.page = p; load(); } });
    updateFilterSummary();
  }

  function render(users) {
    if (!users.length) { tbl.innerHTML = '<div class="empty-state"><div class="es-icon">👥</div><div class="es-title">No users found</div></div>'; return; }
    tbl.innerHTML = `<div class="table-wrap"><table class="table">
      <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
      <tbody>
        ${users.map(u => {
          const isSelf = u.id === currentAdminId;
          return `
          <tr>
            <td>#${u.id}</td>
            <td>${EN.escapeHtml(u.username || '')}</td>
            <td>${EN.escapeHtml(u.full_name)}</td>
            <td>${EN.escapeHtml(u.email)}</td>
            <td>${EN.escapeHtml(u.phone)}</td>
            <td><span class="badge">${u.role}</span></td>
            <td><span class="badge status-${u.status}">${u.status}</span></td>
            <td>${u.status === 'flagged' ? `<span class="text-muted">${EN.escapeHtml(u.flag_reason || 'No reason provided')}</span>` : (u.status === 'pending' ? '<span class="text-muted">Awaiting approval</span>' : '<span class="text-muted">—</span>')}</td>
            <td><div class="actions">
              ${isSelf
                ? '<span class="badge badge-info action-protected" title="You cannot edit your own account from this page">🔒 Protected</span>'
                : `<button class="btn btn-sm btn-secondary" data-edit='${JSON.stringify(u).replace(/'/g,"&#39;")}' title="Edit">✏️</button>`
              }
              ${!isSelf && u.status === 'pending' ? `<button class="btn btn-sm" data-approve="${u.id}" data-name="${EN.escapeHtml(u.full_name)}" title="Approve">✓ Approve</button>` : ''}
              ${!isSelf && u.role !== 'admin' && u.status === 'flagged' ? `<button class="btn btn-sm" data-unflag="${u.id}" data-name="${EN.escapeHtml(u.full_name)}" title="Unflag">↺ Unflag</button>` : ''}
              ${!isSelf && u.role !== 'admin' && u.status !== 'flagged' ? `<button class="btn btn-sm btn-warning" data-flag="${u.id}" data-name="${EN.escapeHtml(u.full_name)}" title="Flag">🚩</button>` : ''}
              ${!isSelf && u.role !== 'admin' ? `<button class="btn btn-sm btn-danger" data-del="${u.id}" data-name="${EN.escapeHtml(u.full_name)}" title="Delete">🗑️</button>` : ''}
            </div></td>
          </tr>`
        }).join('')}
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

    // Role switch event listeners
    const roleSwitch = bd.querySelector('[data-role-switch]');
    if (roleSwitch) {
      const roleBtns = roleSwitch.querySelectorAll('[data-role]');
      const roleInput = roleSwitch.querySelector('[data-f="role"]');
      roleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          roleBtns.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          if (roleInput) roleInput.value = btn.dataset.role;
        });
      });
    }

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

      // Role switch event listeners for edit modal
      const roleSwitch = bd.querySelector('[data-role-switch]');
      if (roleSwitch) {
        const roleBtns = roleSwitch.querySelectorAll('[data-role]');
        const roleInput = roleSwitch.querySelector('[data-f="role"]');
        roleBtns.forEach(btn => {
          btn.addEventListener('click', () => {
            roleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (roleInput) roleInput.value = btn.dataset.role;
          });
        });
      }

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
    const ap = e.target.closest('[data-approve]');
    if (ap) {
      const ok = await Modal.confirm({ title: 'Approve account?', message: `Allow ${ap.dataset.name} to log in.`, confirmText: 'Approve' });
      if (!ok) return;
      try { await EN.api('/api/users/approve_user.php', { body: { id: +ap.dataset.approve } }); EN.toast('User approved.', 'success'); load(); } catch (_) {}
      return;
    }
    const uf = e.target.closest('[data-unflag]');
    if (uf) {
      const ok = await Modal.confirm({ title: 'Unflag user?', message: `Reactivate ${uf.dataset.name}?`, confirmText: 'Unflag' });
      if (!ok) return;
      try { await EN.api('/api/users/unflag_user.php', { body: { id: +uf.dataset.unflag } }); EN.toast('User unflagged.', 'success'); load(); } catch (_) {}
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
    const isStaff = u.role === 'staff';
    const isCustomer = u.role === 'customer' || (!edit && !u.role);
    return `
      <div class="field"><label>Full Name</label><input class="input" data-f="full_name" required maxlength="150" value="${EN.escapeHtml(u.full_name||'')}"></div>
      <div class="field"><label>Email</label><input class="input" data-f="email" type="email" required maxlength="150" value="${EN.escapeHtml(u.email||'')}"></div>
      <div class="field"><label>Phone</label><input class="input" data-f="phone" required maxlength="20" value="${EN.escapeHtml(u.phone||'')}"></div>
      <div class="field"><label>Username</label><input class="input" data-f="username" required maxlength="50" value="${EN.escapeHtml(u.username||'')}"><div class="field-help">3-50 characters. Letters, numbers, and underscores only.</div></div>
      <div class="field"><label>Role</label>
        <div class="role-switch" data-role-switch>
          ${edit && u.role === 'admin' ? '<input type="hidden" data-f="role" value="admin"><div class="role-badge role-admin">Admin</div>' : `
          <button type="button" class="role-btn ${isStaff ? 'active' : ''}" data-role="staff">Staff</button>
          <button type="button" class="role-btn ${isCustomer ? 'active' : ''}" data-role="customer">Customer</button>
          <input type="hidden" data-f="role" value="${u.role || 'customer'}">`}
        </div>
      </div>
      <div class="field"><label>Password ${edit?'<span class="text-muted">(leave blank to keep)</span>':''}</label>
        <input class="input" data-f="password" type="password" ${edit?'':'required'}>
      </div>`;
  }
  function readForm(bd, edit = false) {
    const out = {};
    bd.querySelectorAll('[data-f]').forEach(el => out[el.dataset.f] = el.value);
    return out;
  }

  // Status tabs
  if (tabs) {
    tabs.addEventListener('click', (e) => {
      const b = e.target.closest('button[data-status]');
      if (!b) return;
      tabs.querySelectorAll('button').forEach((x) => x.classList.remove('active'));
      b.classList.add('active');
      state.status = b.dataset.status;
      state.page = 1;
      load();
    });
  }

  // Search
  search.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { state.q = search.value.trim(); state.page = 1; load(); }, 300);
  });

  // Role filter
  if (fRole) {
    fRole.addEventListener('change', () => { state.role = fRole.value; state.page = 1; load(); });
  }

  // Date filter
  if (fDate) {
    fDate.addEventListener('change', () => { state.dateFilter = fDate.value; state.page = 1; load(); });
  }

  // Sort
  if (sortPreset) {
    sortPreset.addEventListener('change', () => { state.sort = sortPreset.value || 'newest'; state.page = 1; load(); });
  }

  // Reset filters
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      state = { page: 1, q: '', role: 'all', status: '', dateFilter: 'all', sort: 'newest' };
      if (search) search.value = '';
      if (fRole) fRole.value = 'all';
      if (fDate) fDate.value = 'all';
      if (sortPreset) sortPreset.value = 'newest';
      if (tabs) {
        tabs.querySelectorAll('button').forEach((x) => x.classList.remove('active'));
        const allTab = tabs.querySelector('button[data-status=""]');
        if (allTab) allTab.classList.add('active');
      }
      load();
    });
  }

  updateFilterSummary();
  load();
})();
