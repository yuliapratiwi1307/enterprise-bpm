'use strict';
// Isolated PROD-02 step 3 reference image manager; no Step 4 attachment interaction.
document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('[data-sms-ref-root]');
  if (!root || !root.dataset.uploadUrl) return;
  const input = root.querySelector('#sms-ref-input');
  const grid = root.querySelector('[data-sms-ref-grid]');
  const status = root.querySelector('[data-sms-ref-status]');
  const token = document.querySelector('input[name="_token"]')?.value;
  if (!input || !grid || !status || !token) return;
  const notice = (text) => { status.textContent = text; };
  const addItem = (item) => {
    const container = document.createElement('div');
    container.className = 'sms-ref-item';
    container.dataset.refId = String(item.id);
    const anchor = document.createElement('a');
    anchor.href = item.url; anchor.target = '_blank'; anchor.rel = 'noopener';
    const img = document.createElement('img');
    img.src = item.url; img.alt = item.name; img.loading = 'lazy';
    anchor.append(img);
    const name = document.createElement('span');
    name.className = 'sms-ref-filename'; name.textContent = item.name; name.title = item.name;
    const del = document.createElement('button');
    del.type = 'button'; del.className = 'sms-ref-delete';
    del.dataset.refDeleteUrl = item.delete_url; del.textContent = 'Hapus';
    del.setAttribute('aria-label', `Hapus ${item.name}`);
    container.append(anchor, name, del);
    grid.append(container);
  };
  input.addEventListener('change', async () => {
    const files = [...input.files];
    if (grid.children.length + files.length > 10) {
      notice('Maksimal 10 gambar aktif. Hapus gambar yang tidak diperlukan terlebih dahulu.');
      input.value = ''; return;
    }
    input.disabled = true;
    for (const file of files) {
      if (!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 10 * 1024 * 1024) {
        notice(`File ${file.name} bukan JPG/PNG/WEBP atau melebihi 10 MB.`); continue;
      }
      const data = new FormData();
      data.append('reference_image', file);
      notice(`Mengunggah ${file.name}…`);
      try {
        const response = await fetch(root.dataset.uploadUrl, {
          method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'},
          body: data, credentials: 'same-origin'
        });
        const payload = await response.json();
        if (!response.ok) throw Error(payload?.errors?.reference_image?.[0] || payload?.message || `HTTP ${response.status}`);
        addItem(payload); notice(`${file.name} berhasil disimpan.`);
      } catch (error) { notice(`Gagal mengunggah ${file.name}: ${error.message}`); }
    }
    input.disabled = false; input.value = '';
  });
  grid.addEventListener('click', async (event) => {
    const button = event.target.closest('button[data-ref-delete-url]');
    if (!button || !grid.contains(button)) return;
    if (!window.confirm('Hapus gambar ini dari daftar aktif? File privat tetap disimpan untuk retensi.')) return;
    button.disabled = true;
    try {
      const response = await fetch(button.dataset.refDeleteUrl, {
        method: 'DELETE', headers: {'X-CSRF-TOKEN':token,'Accept':'application/json'},
        credentials: 'same-origin'
      });
      if (!response.ok) throw Error(`HTTP ${response.status}`);
      button.closest('.sms-ref-item').remove(); notice('Gambar dihapus dari daftar aktif.');
    } catch (error) { button.disabled = false; notice(`Gagal menghapus gambar: ${error.message}`); }
  });
});
