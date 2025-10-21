(function () {
  function init() {
    const grid = document.getElementById('tables-grid');
    if (!grid) return;

    const LIST_API = (window.SF_CONFIG && window.SF_CONFIG.LIST_API) || '../functions/staff_tables_api.php?action=list';
    const ORDER_API = (window.SF_CONFIG && window.SF_CONFIG.ORDER_API) || '../functions/staff_order_api.php';
    const CALL_API  = (window.SF_CONFIG && window.SF_CONFIG.CALL_API)  || '../functions/call_staff_api.php';
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

// ==== Audio + Event detection (MP3 files) ====
let firstBootSeeded = false;
const seenOrderIds = new Set();
const seenCallOpenIds = new Set();

// Đường dẫn mp3: lấy từ SF_CONFIG nếu có, fallback mặc định
const SOUNDS = (window.SF_CONFIG && window.SF_CONFIG.SOUNDS) || {
  order: '../assets/audio/order.mp3',
  help: '../assets/audio/help.mp3'
};

// Tạo audio players
const audioPlayers = {
  order: new Audio(SOUNDS.order),
  help: new Audio(SOUNDS.help)
};
// Thiết lập chung
Object.values(audioPlayers).forEach(a => {
  if (!a) return;
  a.preload = 'auto';
  a.crossOrigin = 'anonymous';
  a.volume = 0.7; // chỉnh âm lượng tại đây (0.0 - 1.0)
});

// Mở khóa audio sau lần tương tác đầu (chính sách trình duyệt)
function unlockAudioOnce() {
  ['order','help'].forEach(k => {
    const a = audioPlayers[k];
    if (!a) return;
    try {
      a.muted = true;
      const p = a.play();
      if (p && typeof p.then === 'function') {
        p.then(() => { a.pause(); a.currentTime = 0; a.muted = false; }).catch(() => {
          a.pause(); a.currentTime = 0; a.muted = false;
        });
      } else {
        a.pause(); a.currentTime = 0; a.muted = false;
      }
    } catch (e) { /* ignore */ }
  });
  window.removeEventListener('pointerdown', unlockAudioOnce);
  window.removeEventListener('keydown', unlockAudioOnce);
}
window.addEventListener('pointerdown', unlockAudioOnce, { once: true });
window.addEventListener('keydown', unlockAudioOnce, { once: true });

// Phát âm
function playOrderSound() {
  const a = audioPlayers.order;
  if (!a) return;
  try { a.currentTime = 0; a.play().catch(() => {}); } catch {}
}
function playHelpSound() {
  const a = audioPlayers.help;
  if (!a) return;
  try { a.currentTime = 0; a.play().catch(() => {}); } catch {}
}

// So khớp dữ liệu để phát hiện “đơn mới” và “trợ giúp mới”
function detectEvents(newData) {
  const tables = Array.isArray(newData?.tables) ? newData.tables : [];

  const currentOrderIds = [];
  const currentCallOpenIds = [];

  for (const t of tables) {
    if (t?.current_order?.id) currentOrderIds.push(Number(t.current_order.id));
    if (t?.has_call && t?.call && t.call.status === 'open' && t.call.id) {
      currentCallOpenIds.push(Number(t.call.id));
    }
  }

  // Lần đầu seed để không phát âm ồ ạt
  if (!firstBootSeeded) {
    currentOrderIds.forEach(id => seenOrderIds.add(id));
    currentCallOpenIds.forEach(id => seenCallOpenIds.add(id));
    firstBootSeeded = true;
    return;
  }

  // Tìm order mới và call OPEN mới
  const newOrders = currentOrderIds.filter(id => !seenOrderIds.has(id));
  const newCalls  = currentCallOpenIds.filter(id => !seenCallOpenIds.has(id));

  if (newOrders.length > 0) playOrderSound();
  if (newCalls.length > 0)  playHelpSound();

  // Cập nhật bộ nhớ
  currentOrderIds.forEach(id => seenOrderIds.add(id));
  currentCallOpenIds.forEach(id => seenCallOpenIds.add(id));
}

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
    function fmtTimeShort(s) {
      if (!s) return '';
      const str = s.replace(' ', 'T');
      const d = new Date(str);
      if (isNaN(d.getTime())) return s;
      return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }

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
        const hasCall = !!t.has_call;
        const card = document.createElement('div');
        card.className = `table-card ${statusClass}` + (hasCall ? ' has-call' : '');
        const callLine = hasCall
          ? `<div class="call-badge">${t.call.status === 'open' ? 'Gọi nhân viên' : 'Đang tiếp nhận'} • ${t.call.call_wait_mins}p</div>`
          : '';
        const orderInfo = t.current_order
          ? `<div style="color:#5b6574;font-size:13px">
               Đặt lúc ${fmtTimeShort(t.current_order.ordered_at)} • Chờ ${t.current_order.wait_mins}p
             </div>
             <div style="color:#5b6574;font-size:13px">Tổng cần thu: ${fmtMoney(t.current_order.total)}</div>`
          : '';

        card.innerHTML = `
          ${iconTable()}
          <div class="table-meta">
            <span><strong>Bàn ${t.table_number}</strong></span>
            <span>•</span>
            <span>Tầng ${t.floor}</span>
          </div>
          ${callLine}
          ${orderInfo}
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

      // Header call info (nếu có)
      const callInfo = table.has_call
        ? `<div style="margin-bottom:8px;padding:8px 10px;background:#fff7ed;border:1px solid #ffedd5;border-radius:8px;color:#92400e">
             <strong>${table.call.status === 'open' ? 'Gọi nhân viên' : 'Đang tiếp nhận'}</strong>
             • ${table.call.call_wait_mins} phút
             ${table.call.note ? ` • Ghi chú: ${table.call.note}` : ''}
           </div>`
        : '';

      if (!table.current_order) {
        bodyEl.innerHTML = `
          ${callInfo}
          <div>Bàn <strong>${table.table_number}</strong> • Tầng <strong>${table.floor}</strong></div>
          <div style="margin-top:8px;color:#5b6574">Chưa có đơn chưa thanh toán cho bàn này.</div>
        `;
        // Hành động cho call (nếu có) ngay cả khi không có order
        const callBtns = table.has_call
          ? `<button class="btn" id="btn-call-ack">Tiếp nhận</button>
             <button class="btn btn-primary" id="btn-call-resolve">Đã xong</button>`
          : '';
        actionsEl.innerHTML = callBtns;
        wireCallButtons(table);
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
        ${callInfo}
        <div style="margin-bottom:8px">
          <div>Bàn <strong>${table.table_number}</strong> • Tầng <strong>${table.floor}</strong></div>
          ${o.ordered_at ? `<div style="color:#5b6574">Đặt lúc ${fmtTimeShort(o.ordered_at)} • Đã chờ ${o.wait_mins} phút</div>` : ''}
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

      // Hai lựa chọn thanh toán + nút xử lý call
      const transferUrl = `${PAYMENT_URL}?order_id=${encodeURIComponent(o.id)}`;
      const callBtns = table.has_call
        ? `<button class="btn" id="btn-call-ack">Tiếp nhận</button>
           <button class="btn btn-primary" id="btn-call-resolve">Đã xong</button>`
        : '';
      actionsEl.innerHTML = `
        <button class="btn btn-primary" id="btn-pay-cash">Thanh toán thành công</button>
        <a class="btn" id="btn-pay-transfer" href="${transferUrl}" target="_blank" rel="noopener">Thanh toán bằng chuyển khoản</a>
        ${callBtns}
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

      wireCallButtons(table);
    }

    function wireCallButtons(table){
      if (!table.has_call) return;
      const btnAck = document.getElementById('btn-call-ack');
      const btnDone = document.getElementById('btn-call-resolve');

      if (btnAck && table.call.status === 'open') {
        btnAck.style.display = '';
        btnAck.onclick = async () => {
          btnAck.disabled = true;
          try {
            const resp = await fetch(`${CALL_API}?action=ack`, {
              method: 'POST',
              headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ id: table.call.id })
            });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'Không thể tiếp nhận');
            await refresh();
            // reopen detail for updated state
            const t = lastData.tables.find(x => x.table_number === table.table_number);
            if (t) openDetail(t);
          } catch (e) {
            alert(e.message || 'Có lỗi xảy ra.');
          } finally {
            btnAck.disabled = false;
          }
        };
      } else if (btnAck) {
        // nếu không phải 'open' thì ẩn nút tiếp nhận
        btnAck.style.display = 'none';
      }

      if (btnDone) {
        btnDone.onclick = async () => {
          btnDone.disabled = true;
          try {
            const resp = await fetch(`${CALL_API}?action=resolve`, {
              method: 'POST',
              headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ id: table.call.id })
            });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'Không thể hoàn tất');
            await refresh();
            // Close modal nếu không còn call
            const t = lastData.tables.find(x => x.table_number === table.table_number);
            if (t && !t.has_call) {
              // nếu hết call và không có gì khác cần xem
              // vẫn giữ modal để staff tiếp tục thao tác thanh toán nếu muốn
            }
            // cập nhật lại modal
            if (t) openDetail(t);
          } catch (e) {
            alert(e.message || 'Có lỗi xảy ra.');
          } finally {
                        btnDone.disabled = false;
                      }
                    };
                  }
                }
            
                function closeDetail() {
                  backdrop.style.display = 'none';
                  selectedTableNumber = null;
                }
            
                if (btnClose) btnClose.onclick = closeDetail;
                if (backdrop) backdrop.onclick = e => { if (e.target === backdrop) closeDetail(); };
            
                async function refresh() {
                  try {
                    const resp = await fetch(LIST_API);
                    const data = await resp.json();
                    if (!data.success) throw new Error(data.message || 'API error');
                    detectEvents(data); // <-- phát hiện và phát âm nếu có sự kiện mới
                    lastData = data;
                    render();
                  } catch (e) {
                    console.error('Refresh error:', e);
                    alert('Không thể tải dữ liệu: ' + e.message);
                  }
                }
            
                function render() {
                  if (!lastData) return;
                  renderFloors(lastData.floors || []);
                  renderGrid();
                  if (selectedFloor === 'all') {
                    setSummaryAll(lastData.summary || {});
                  } else {
                    const floorAgg = (lastData.floors || []).find(f => String(f.floor) === String(selectedFloor));
                    setSummaryFloor(floorAgg);
                  }
                }
            
                if (btnRefresh) btnRefresh.onclick = refresh;
            
                // Auto refresh
                function startTimer() {
                  if (timer) clearInterval(timer);
                  timer = setInterval(refresh, 5000);
                }
                function stopTimer() {
                  if (timer) clearInterval(timer);
                  timer = null;
                }
            
                startTimer();
                refresh();
            
                window.addEventListener('beforeunload', stopTimer);
              }
            
              if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
              } else {
                init();
              }
            })();