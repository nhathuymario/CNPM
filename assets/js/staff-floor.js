(function () {
  function init() {
    const grid = document.getElementById('tables-grid');
    if (!grid) return;

    const LIST_API = (window.SF_CONFIG && window.SF_CONFIG.LIST_API) || '../functions/staff_tables_api.php?action=list';
    const ORDER_API = (window.SF_CONFIG && window.SF_CONFIG.ORDER_API) || '../functions/staff_order_api.php';
    const PAYMENT_URL = (window.SF_CONFIG && window.SF_CONFIG.PAYMENT_URL) || 'payment.php';

    const floorListWrap = document.getElementById('floor-list');
    const countAll = document.getElementById('count-all');
    const crumbScope = document.getElementById('crumb-scope');
    const crumbFloor = document.getElementById('crumb-floor');
    const crumbSummary = document.getElementById('crumb-summary');
    const btnRefresh = document.getElementById('btn-refresh');

    let lastData = null;
    let selectedFloor = 'all';
    let selectedTableNumber = null;
    let timer = null;

    function iconTable() {
      return `
        <div class="table-icon">
          <span>🍽️</span>
          <span class="center">╬</span>
          <span>🪑</span>
        </div>
      `;
    }
    function fmtMoney(v) { try { return new Intl.NumberFormat('vi-VN').format(v) + 'đ'; } catch { return v + 'đ'; } }

    function setSummaryAll(sum) {
      crumbSummary.textContent = `Trống ${sum.free_tables}/${sum.total_tables} bàn - ${sum.total_seats} ghế`;
    }
    function setSummaryFloor(floorAgg) {
      if (!floorAgg) { crumbFloor.textContent = 'Tầng: Tất cả'; return; }
      crumbFloor.textContent = `Tầng: ${floorAgg.floor}`;
      crumbSummary.textContent = `Trống ${floorAgg.free_tables}/${floorAgg.total_tables} bàn - ${floorAgg.total_seats} ghế`;
    }

    function renderFloors(floors) {
      countAll.textContent = `(${(lastData?.summary?.free_tables) || 0})`;
      floorListWrap.innerHTML = '';
      floors.forEach(f => {
        const a = document.createElement('a');
        a.href = 'javascript:void(0)';
        a.className = 'floor-item' + (String(selectedFloor) === String(f.floor) ? ' active' : '');
        a.dataset.floor = String(f.floor);
        a.innerHTML = `
          <span class="icon"></span>
          <span>Tầng ${f.floor}</span>
          <span class="count">(${f.free_tables})</span>
        `;
        a.onclick = () => { selectedFloor = String(f.floor); selectedTableNumber = null; render(); };
        floorListWrap.appendChild(a);
      });

      const allBtn = document.getElementById('floor-all');
      if (allBtn) {
        if (selectedFloor === 'all') allBtn.classList.add('active'); else allBtn.classList.remove('active');
        allBtn.onclick = () => { selectedFloor = 'all'; selectedTableNumber = null; render(); };
      }
    }

    function renderGrid() {
      const allTables = lastData?.tables || [];
      const tables = allTables.filter(t => selectedFloor === 'all' || String(t.floor) === String(selectedFloor));
      grid.innerHTML = '';

      if (!tables.length) {
        const div = document.createElement('div');
        div.className = 'empty';
        div.textContent = 'Không có bàn ở tầng đã chọn.';
        grid.appendChild(div);
        return;
      }

      tables.forEach(t => {
        const statusClass = t.is_busy ? 'serving' : 'available';
        const card = document.createElement('div');
        card.className = `table-card ${statusClass}`;
        card.innerHTML = `
          ${iconTable()}
          <div class="table-meta">
            <span><strong>Bàn ${t.table_number}</strong></span>
            <span>•</span>
            <span>Tầng ${t.floor}</span>
          </div>
          ${t.current_order ? `<div style="color:#5b6574;font-size:13px">Tổng cần thu: ${fmtMoney(t.current_order.total)}</div>` : ''}
        `;
        card.onclick = () => openDetail(t);
        grid.appendChild(card);
      });
    }

    // Modal detail
    const backdrop = document.getElementById('detail-backdrop');
    const bodyEl = document.getElementById('detail-body');
    const actionsEl = document.getElementById('detail-actions');
    const btnClose = document.getElementById('btn-detail-close');

    function openDetail(table) {
      selectedTableNumber = table.table_number;
      backdrop.style.display = 'flex';

      if (!table.current_order) {
        bodyEl.innerHTML = `
          <div> Bàn <strong>${table.table_number}</strong> • Tầng <strong>${table.floor}</strong></div>
          <div style="margin-top:8px;color:#5b6574">Chưa có đơn chưa thanh toán cho bàn này.</div>
        `;
        actionsEl.innerHTML = '';
        return;
      }

      const o = table.current_order;
      const rows = (o.items || []).map(i => `
        <tr>
          <td>${i.name}</td>
          <td class="t-right">x${i.quantity}</td>
          <td class="t-right">${fmtMoney(i.price)}</td>
          <td class="t-right">${fmtMoney(i.price * i.quantity)}</td>
        </tr>
      `).join('');

      bodyEl.innerHTML = `
        <div style="margin-bottom:8px">
          <div>Bàn <strong>${table.table_number}</strong> • Tầng <strong>${table.floor}</strong></div>
          <div style="color:#5b6574">PTTT hiện tại: ${o.payment_method}${o.ref_code ? ` (Mã: ${o.ref_code})` : ''}</div>
          <div style="color:#5b6574">Trạng thái đơn: ${o.status}</div>
        </div>
        <table class="detail-table">
          <thead><tr><th>Món</th><th class="t-right">SL</th><th class="t-right">Đơn giá</th><th class="t-right">Thành tiền</th></tr></thead>
          <tbody>${rows || '<tr><td colspan="4">(Không có món)</td></tr>'}</tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="t-right"><strong>Tổng cần thu</strong></td>
              <td class="t-right"><strong>${fmtMoney(o.total)}</strong></td>
            </tr>
          </tfoot>
        </table>
      `;

      // Luôn hiển thị 2 lựa chọn
      const transferUrl = `${PAYMENT_URL}?order_id=${encodeURIComponent(o.id)}`;
      actionsEl.innerHTML = `
        <button class="btn btn-primary" id="btn-pay-cash">Thanh toán thành công</button>
        <a class="btn" id="btn-pay-transfer" href="${transferUrl}" target="_blank" rel="noopener">Thanh toán bằng chuyển khoản</a>
      `;

      // Tiền mặt → mark_paid
      const btnCash = document.getElementById('btn-pay-cash');
      btnCash.onclick = async () => {
        btnCash.disabled = true;
        try {
          const resp = await fetch(`${ORDER_API}?action=mark_paid`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ order_id: o.id })
          });
          const data = await resp.json();
          if (!data.success) throw new Error(data.message || 'Thanh toán thất bại');
          await refresh();
          backdrop.style.display = 'none';
          alert('Đã xác nhận thanh toán (tiền mặt) và trả bàn về trống.');
        } catch (e) {
          alert(e.message || 'Có lỗi xảy ra.');
        } finally {
          btnCash.disabled = false;
        }
      };

      // Chuyển khoản → mở trang payment (xác nhận ở trang đó)
      const btnTransfer = document.getElementById('btn-pay-transfer');
      // Không gắn handler bổ sung; mở tab mới theo href
    }

    function closeDetail(){
      backdrop.style.display = 'none';
      bodyEl.innerHTML = '';
      actionsEl.innerHTML = '';
    }
    btnClose?.addEventListener('click', closeDetail);
    backdrop?.addEventListener('click', (e)=>{ if (e.target === backdrop) closeDetail(); });

    function render() {
      crumbScope.textContent = 'Toàn bộ nhà hàng';
      if (selectedFloor === 'all') {
        setSummaryAll(lastData?.summary || {total_tables:0,total_seats:0,free_tables:0});
        crumbFloor.textContent = 'Tầng: Tất cả';
      } else {
        const agg = (lastData?.floors || []).find(x => String(x.floor) === String(selectedFloor));
        setSummaryFloor(agg || null);
      }
      renderGrid();
      renderFloors(lastData?.floors || []);
    }

    async function refresh() {
      try {
        const res = await fetch(LIST_API, { cache: 'no-store' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Tải dữ liệu thất bại');
        lastData = data;
        render();
        if (selectedTableNumber != null) {
          const t = (data.tables || []).find(x => x.table_number === selectedTableNumber);
          if (t) openDetail(t);
        }
      } catch (e) {
        console.error(e);
      }
    }

    btnRefresh?.addEventListener('click', refresh);
    refresh();
    timer = setInterval(refresh, 10000);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();