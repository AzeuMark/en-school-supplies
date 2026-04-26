// Flagged Users — admin
(function () {
  const tbl = document.getElementById('flagged-tbl');
  const pagEl = document.getElementById('pagination');
  let state = { page: 1 };

  async function load() {
    tbl.innerHTML = '<div class="empty-state">Loading...</div>';
    const params = new URLSearchParams({ page: state.page, status: 'flagged' });
    const res = await fetch(EN.BASE + '/api/users/list.php?' + params.toString());
    const data = await res.json();
    if (!data.ok) { tbl.innerHTML = '<div class="empty-state">Failed to load.</div>'; return; }
    render(data.users);
    Pagination.render(pagEl, { current: data.page, total: data.total_pages, onChange: (p) => { state.page = p; load(); } });
  }

  function render(users) {
    if (!users.length) {
      tbl.innerHTML = '<div class="empty-state"><div class="es-icon">✅</div><div class="es-title">No flagged users</div></div>';
      return;
    }
    tbl.innerHTML = `<div class="table-wrap"><table class="table">
      <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Reason</th><th>Actions</th></tr></thead>
      <tbody>
        ${users.map(u => `
          <tr>
            <td>#${u.id}</td>
            <td>${EN.escapeHtml(u.full_name)}</td>
            <td>${EN.escapeHtml(u.email)}</td>
            <td><span class="badge">${u.role}</span></td>
            <td><span class="text-muted">${EN.escapeHtml(u.flag_reason || '—')}</span></td>
            <td><div class="actions">
              <button class="btn btn-sm" data-unflag="${u.id}" data-name="${EN.escapeHtml(u.full_name)}">✓ Unflag</button>
              <button class="btn btn-sm btn-danger" data-del="${u.id}" data-name="${EN.escapeHtml(u.full_name)}">🗑️ Delete</button>
            </div></td>
          </tr>`).join('')}
      </tbody></table></div>`;
  }

  tbl.addEventListener('click', async (e) => {
    const uf = e.target.closest('[data-unflag]');
    if (uf) {
      const ok = await Modal.confirm({ title: 'Unflag user?', message: `Reactivate ${uf.dataset.name}?`, confirmText: 'Unflag' });
      if (!ok) return;
      try { await EN.api('/api/users/unflag_user.php', { body: { id: +uf.dataset.unflag } }); EN.toast('User unflagged.', 'success'); load(); } catch (_) {}
    }
    const del = e.target.closest('[data-del]');
    if (del) {
      const ok = await Modal.confirm({ title: 'Delete user?', message: `Permanently remove ${del.dataset.name}.`, confirmText: 'Delete', danger: true });
      if (!ok) return;
      try { await EN.api('/api/users/delete_user.php', { body: { id: +del.dataset.del } }); EN.toast('User deleted.', 'success'); load(); } catch (_) {}
    }
  });

  load();
})();
