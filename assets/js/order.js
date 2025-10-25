(function () {
  // ========== Search nhanh theo tên món ==========
  const searchInput = document.getElementById('searchInput');
  const grid = document.getElementById('productGrid');

  function applySearch() {
    if (!searchInput || !grid) return;
    const q = (searchInput.value || '').trim().toLowerCase();
    grid.querySelectorAll('.product-card').forEach(card => {
      const name = (card.getAttribute('data-name') || '').toLowerCase();
      card.style.display = name.includes(q) ? '' : 'none';
    });
  }
  if (searchInput && grid) {
    searchInput.addEventListener('input', applySearch);
    applySearch();
  }

  // ========== Modal xác nhận gọi món ==========
  const form = document.getElementById('checkoutForm') || document.querySelector('.checkout-form');
  let allowSubmit = false;

  function parseMoney(str) {
    const n = String(str || '').replace(/[^\d]/g, '');
    return n ? Number(n) : 0;
  }

  function extractCartFromBill() {
    const rows = document.querySelectorAll('.bill-table .bill-row:not(.bill-head)');
    const out = [];
    rows.forEach(row => {
      const nameEl = row.querySelector('.col.name');
      const qtyEl = row.querySelector('.qty-num');
      const amountEl = row.querySelector('.col.amount');
      if (!nameEl || !qtyEl || !amountEl) return;

      const name = nameEl.textContent.trim();
      const qty = parseInt(qtyEl.textContent.trim(), 10) || 0;
      const amount = parseMoney(amountEl.textContent);
      const unit = qty > 0 ? Math.round(amount / qty) : amount;

      if (name && qty > 0) {
        out.push({ name, quantity: qty, price: unit, line: unit * qty });
      }
    });
    return out;
  }

  function ensureConfirmStyles() {
    if (document.getElementById('order-confirm-style')) return;
    const css = `
      .ocf-backdrop{position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center}
      .ocf-card{background:#fff;border:1px solid #e6e8ee;border-radius:12px;width:560px;max-width:92%;overflow:hidden;box-shadow:0 10px 30px rgba(2,6,23,.18)}
      .ocf-head{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #e6e8ee}
      .ocf-head h3{margin:0;font-size:18px;color:#2a3b59}
      .ocf-close{border:1px solid #e6e8ee;background:#fff;border-radius:8px;padding:2px 8px;cursor:pointer}
      .ocf-body{padding:12px 16px}
      .ocf-table{width:100%;border-collapse:collapse}
      .ocf-table th,.ocf-table td{border-bottom:1px solid #e6e8ee;padding:8px 10px}
      .ocf-right{text-align:right}
      .ocf-actions{display:flex;justify-content:flex-end;gap:8px;padding:12px 16px;border-top:1px solid #e6e8ee}
      .ocf-btn{border:1px solid #e6e8ee;background:#fff;border-radius:8px;padding:8px 12px;cursor:pointer}
      .ocf-btn.primary{background:#0b72cf;border-color:#0b72cf;color:#fff}
    `;
    const style = document.createElement('style');
    style.id = 'order-confirm-style';
    style.textContent = css;
    document.head.appendChild(style);
  }

  function fmtMoney(n) {
    try { return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(n) + ' đ'; }
    catch { return String(n) + ' đ'; }
  }

  function buildConfirmDOM(items) {
    ensureConfirmStyles();
    const backdrop = document.createElement('div');
    backdrop.className = 'ocf-backdrop';
    backdrop.id = 'order-confirm-backdrop';

    const card = document.createElement('div');
    card.className = 'ocf-card';
    card.innerHTML = `
      <div class="ocf-head">
        <h3>Xác nhận gọi món</h3>
        <button type="button" class="ocf-close" aria-label="Đóng">×</button>
      </div>
      <div class="ocf-body">
        <table class="ocf-table">
          <thead>
            <tr>
              <th>Món</th>
              <th class="ocf-right">SL</th>
              <th class="ocf-right">Đơn giá</th>
              <th class="ocf-right">Thành tiền</th>
            </tr>
          </thead>
          <tbody></tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="ocf-right"><strong>Tổng</strong></td>
              <td class="ocf-right"><strong class="ocf-total">0 đ</strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="ocf-actions">
        <button type="button" class="ocf-btn ocf-cancel">Từ chối</button>
        <button type="button" class="ocf-btn primary ocf-ok">Xác nhận</button>
      </div>
    `;
    backdrop.appendChild(card);

    // Fill table
    const tbody = card.querySelector('tbody');
    let sum = 0;
    items.forEach(it => {
      sum += it.line || (it.price * it.quantity);
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${String(it.name || '').replace(/</g, '&lt;')}</td>
        <td class="ocf-right">x${it.quantity}</td>
        <td class="ocf-right">${fmtMoney(it.price)}</td>
        <td class="ocf-right">${fmtMoney(it.line || (it.price * it.quantity))}</td>
      `;
      tbody.appendChild(tr);
    });
    card.querySelector('.ocf-total').textContent = fmtMoney(sum);

    // Wire buttons
    const close = () => { backdrop.remove(); };
    card.querySelector('.ocf-close').addEventListener('click', close);
    card.querySelector('.ocf-cancel').addEventListener('click', close);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) close(); });
    window.addEventListener('keydown', function escHandler(e){
      if (e.key === 'Escape') { close(); window.removeEventListener('keydown', escHandler); }
    });

    // Submit (fix): dùng requestSubmit để kèm name/value của nút submit
    const okBtn = card.querySelector('.ocf-ok');
    okBtn.addEventListener('click', () => {
      if (!form || okBtn.disabled) return;
      okBtn.disabled = true;

      // Tìm nút submit chính (để kèm name="action" value="place_order")
      const submitBtn =
        form.querySelector('button[name="action"][value="place_order"]') ||
        form.querySelector('button[type="submit"]') ||
        form.querySelector('input[type="submit"]');

      allowSubmit = true;

      if (typeof form.requestSubmit === 'function') {
        // Ưu tiên requestSubmit để gửi đúng name/value của nút
        try {
          if (submitBtn) form.requestSubmit(submitBtn);
          else form.requestSubmit(); // vẫn kích hoạt submit event
        } catch {
          // Fallback tiếp phía dưới
        }
      }

      // Fallback cho trình duyệt cũ: tự thêm hidden 'action=place_order' nếu cần rồi submit
      if (!document.hidden) {
        const needsAction =
          submitBtn && submitBtn.getAttribute('name') === 'action' && submitBtn.getAttribute('value') === 'place_order';
        if (!('requestSubmit' in HTMLFormElement.prototype)) {
          if (needsAction) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'action';
            hidden.value = 'place_order';
            form.appendChild(hidden);
          }
          form.submit();
        }
      }

      close();
    });

    return backdrop;
  }

  function openConfirm() {
    const items = extractCartFromBill();
    if (!items.length) {
      alert('Vui lòng chọn món trước khi gọi món.');
      return;
    }
    const dom = buildConfirmDOM(items);
    document.body.appendChild(dom);
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      if (allowSubmit) return; // cho submit thực sự
      e.preventDefault();
      openConfirm();
    });
  }
})();