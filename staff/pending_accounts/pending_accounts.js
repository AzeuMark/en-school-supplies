// Staff — Pending Accounts (staff can only approve, not delete)
(function () {
  const tbl = document.getElementById('pending-tbl');
  const pagEl = document.getElementById('pagination');
  let state = { page: 1 };

  async function load() {
    tbl.innerHTML = '<div class="empty-state">Loading...</div>';
    const params = new URLSearchParams({ page: state.page, status: 'pending' });
    const res = await fetch(EN.BASE + '/api/users/list.php?' + params.toString());
    const data = await res.json();
    if (!data.ok) { tbl.innerHTML = '<div class="empty-state">Failed to load.</div>'; return; }
    render(data.users);
    Pagination.render(pagEl, { current: data.page, total: data.total_pages, onChange: (p) => { state.page = p; load(); } });
  }

  function render(users) {
    if (!users.length) {
      tbl.innerHTML = '<div class="empty-state"><div class="es-icon">✅</div><div class="es-title">No pending accounts</div><div>Newly registered customers will appear here.</div></div>';
      return;
    }
    tbl.innerHTML = `<div class="table-wrap"><table class="table">
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Actions</th></tr></thead>
      <tbody>
        ${users.map(u => `
          <tr>
            <td>${EN.escapeHtml(u.full_name)}</td>
            <td>${EN.escapeHtml(u.email)}</td>
            <td>${EN.escapeHtml(u.phone)}</td>
            <td>${new Date(u.created_at.replace(' ','T')).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'})}</td>
            <td>
              <button class="btn btn-sm" data-approve="${u.id}" data-name="${EN.escapeHtml(u.full_name)}">✓ Approve</button>
            </td>
          </tr>`).join('')}
      </tbody></table></div>`;
  }

  tbl.addEventListener('click', async (e) => {
    const ap = e.target.closest('[data-approve]');
    if (ap) {
      const ok = await Modal.confirm({ title: 'Approve account?', message: `Allow ${ap.dataset.name} to log in.`, confirmText: 'Approve' });
      if (!ok) return;
      try { await EN.api('/api/users/approve_user.php', { body: { id: +ap.dataset.approve } }); EN.toast('Approved.', 'success'); load(); } catch (_) {}
    }
  });

  load();
})();
