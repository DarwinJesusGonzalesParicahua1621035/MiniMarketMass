document.querySelectorAll('[data-confirm]').forEach(f =>
  f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

const q = document.querySelector('[data-filtro]');
if (q) {
  const t = document.getElementById(q.dataset.filtro);
  q.addEventListener('input', () => {
    const v = q.value.toLowerCase();
    t.querySelectorAll('tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none');
  });
}

const m = document.getElementById('menu');
if (m) m.onclick = () => document.getElementById('aside').classList.toggle('open');