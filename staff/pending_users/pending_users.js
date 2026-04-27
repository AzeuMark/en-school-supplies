// Staff — Pending Users
(function () {
  const tbl          = document.getElementById('users-tbl');
  const pagEl        = document.getElementById('pagination');
  const search       = document.getElementById('search');
  const sortPreset   = document.getElementById('sort-preset');
  const filterSummaryEl = document.getElementById('filter-summary');
  const resetBtn     = document.querySelector('[data-reset-filters]');

  let state = { page: 1, q: '', sort: 'newest' };
  let searchTimer = null;

  const sortLabelMap = {
    newest:    'Newest First',
    oldest:    'Oldest First',
    name_asc:  'Name (A-Z)',
    name_desc: 'Name (Z-A)',
  };

  function updateFilterSummary() {
    if (!filterSummaryEl) return;
    const parts = [];
    if (state.q) parts.push(`Search: "${state.q}"`);
    const filtersText = parts.length ? parts.join(' • ') : 'Showing all pending customers';
    const sortText = sortLabelMap[state.sort] || sortLabelMap.newest;
    filterSummaryEl.textContent = `${filtersText} • Sorted by ${sortText}`;
  }

  async function load() {
    tbl.innerHTML = '<div class="empty-state">Loading...</div>';
    const params = new URLSearchParams({ page: state.page, q: state.q, sort: state.sort });
    const res  = await fetch(EN.BASE + '/api/users/staff_list_pending.php?' + params.toString());
    const data = await res.json();
    if (!data.ok) { tbl.innerHTML = '<div class="empty-state">Failed to load.</div>'; return; }
    render(data.users, data.total);
    Pagination.render(pagEl, { current: data.page, total: data.total_pages, onChange: (p) => { state.page = p; load(); } });
    updateFilterSummary();
  }

  function render(users, total) {
    if (!users.length) {
      tbl.innerHTML = '<div class="empty-state"><div class="es-icon">✅</div><div class="es-title">No pending accounts</div><div>All customer accounts are approved.</div></div>';
      return;
    }
    tbl.innerHTML = `<div class="table-wrap"><table class="table">
      <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Actions</th></tr></thead>
      <tbody>
        ${users.map(u => `
          <tr>
            <td>#${u.id}</td>
            <td>${EN.escapeHtml(u.username || '')}</td>
            <td>${EN.escapeHtml(u.full_name)}</td>
            <td>${EN.escapeHtml(u.email)}</td>
            <td>${EN.escapeHtml(u.phone)}</td>
            <td class="text-muted">${u.created_at ? new Date(u.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
            <td><div class="actions">
              <button class="btn btn-sm btn-icon action-approve-btn" data-approve="${u.id}" data-name="${EN.escapeHtml(u.full_name)}" title="Approve" aria-label="Approve user">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              </button>
              <button class="btn btn-sm btn-icon btn-danger" data-del="${u.id}" data-name="${EN.escapeHtml(u.full_name)}" title="Delete" aria-label="Delete user">🗑️</button>
            </div></td>
          </tr>`
        ).join('')}
      </tbody></table></div>`;
  }

  tbl.addEventListener('click', async (e) => {
    const ap = e.target.closest('[data-approve]');
    if (ap) {
      const ok = await Modal.confirm({ title: 'Approve account?', message: `Allow ${ap.dataset.name} to log in.`, confirmText: 'Approve' });
      if (!ok) return;
      try { await EN.api('/api/users/staff_approve_user.php', { body: { id: +ap.dataset.approve } }); EN.toast('User approved.', 'success'); load(); } catch (_) {}
      return;
    }
    const del = e.target.closest('[data-del]');
    if (del) {
      const ok = await Modal.confirm({ title: 'Delete user?', message: `This will permanently delete ${del.dataset.name}.`, confirmText: 'Delete', danger: true });
      if (!ok) return;
      try { await EN.api('/api/users/staff_delete_user.php', { body: { id: +del.dataset.del } }); EN.toast('User deleted.', 'success'); load(); } catch (_) {}
    }
  });

  // Search
  search.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { state.q = search.value.trim(); state.page = 1; load(); }, 300);
  });

  // Sort
  if (sortPreset) {
    sortPreset.addEventListener('change', () => { state.sort = sortPreset.value || 'newest'; state.page = 1; load(); });
  }

  // Reset
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      state = { page: 1, q: '', sort: 'newest' };
      if (search) search.value = '';
      if (sortPreset) sortPreset.value = 'newest';
      load();
    });
  }

  updateFilterSummary();
  load();
})();
