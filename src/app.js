const input = document.getElementById('search');
if (input) input.addEventListener('input', () => {
  const q = input.value.trim().toLowerCase();
  document.querySelectorAll('[data-search]').forEach(card => {
    card.style.display = card.dataset.search.includes(q) ? '' : 'none';
  });
});
