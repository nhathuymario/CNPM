(function(){
  // Search filter by dish name (client-side)
  const searchInput = document.getElementById('searchInput');
  const grid = document.getElementById('productGrid');
  if (searchInput && grid) {
    searchInput.addEventListener('input', function(){
      const q = (this.value || '').trim().toLowerCase();
      grid.querySelectorAll('.product-card').forEach(card => {
        const name = card.getAttribute('data-name') || '';
        card.style.display = name.includes(q) ? '' : 'none';
      });
    });
  }

  // Tabs (UI only — hiện chưa có category ở DB, nên chỉ xử lý active state)
  const tabs = document.querySelectorAll('.category-tabs .tab');
  tabs.forEach(t => t.addEventListener('click', () => {
    tabs.forEach(x => x.classList.remove('active'));
    t.classList.add('active');
  }));
})();