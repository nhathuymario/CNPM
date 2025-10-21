(() => {
  // BASE_URL lấy từ PHP nếu có, fallback '/CNPM/'
  const BASE_URL = (window.BASE_URL && String(window.BASE_URL)) || '/CNPM/';
  const HELP_API = new URL('functions/call_staff_api.php?action=call', BASE_URL).toString();

  function getTableNumber(sourceEl) {
    // 1) Ưu tiên từ data-table của chính nút
    const ds = sourceEl?.dataset?.table;
    if (ds && !Number.isNaN(parseInt(ds, 10))) return parseInt(ds, 10);

    // 2) Hidden input name="table_number"
    const hiddenNum = document.querySelector('[name="table_number"]');
    if (hiddenNum && hiddenNum.value && !Number.isNaN(parseInt(hiddenNum.value, 10))) {
      return parseInt(hiddenNum.value, 10);
    }

    // 3) Hidden input name="table" (trang Order của bạn đang dùng)
    const hidden = document.querySelector('[name="table"]');
    if (hidden && hidden.value && !Number.isNaN(parseInt(hidden.value, 10))) {
      return parseInt(hidden.value, 10);
    }

    // 4) Query param ?table=...
    const t = new URLSearchParams(location.search).get('table');
    if (t && !Number.isNaN(parseInt(t, 10))) return parseInt(t, 10);

    // 5) Biến global (tuỳ chọn)
    if (window.ORDER_TABLE_NUMBER && !Number.isNaN(parseInt(window.ORDER_TABLE_NUMBER, 10))) {
      return parseInt(window.ORDER_TABLE_NUMBER, 10);
    }
    return NaN;
  }

  async function callStaff(tableNumber, note = '') {
    const resp = await fetch(HELP_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ table_number: tableNumber, note })
    });
    const data = await resp.json().catch(() => ({}));
    if (!resp.ok || !data.success) {
      throw new Error(data.message || `Gọi trợ giúp thất bại (HTTP ${resp.status})`);
    }
    return data;
  }

  function onClick(e) {
    const btn = e.target.closest('[data-action="help"]');
    if (!btn) return;

    e.preventDefault(); // chặn điều hướng nếu là <a>

    const tableNumber = getTableNumber(btn);
    if (!Number.isFinite(tableNumber) || tableNumber <= 0) {
      alert('Không xác định được số bàn để gọi trợ giúp.');
      return;
    }

    const note = btn.dataset.note || '';

    // Nếu là <button> có thuộc tính disabled
    if ('disabled' in btn) btn.disabled = true;
    btn.classList.add('is-loading');

    callStaff(tableNumber, note)
      .then(() => {
        btn.dataset.state = 'sent';
        alert('Đã gửi yêu cầu trợ giúp. Nhân viên sẽ đến ngay!');
      })
      .catch(err => {
        console.error('call-staff error:', err);
        alert(err.message || 'Có lỗi xảy ra khi gọi trợ giúp.');
      })
      .finally(() => {
        if ('disabled' in btn) btn.disabled = false;
        btn.classList.remove('is-loading');
      });
  }

  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  ready(() => {
    // Lắng nghe cho mọi phần tử mang data-action="help" (kể cả trên header)
    document.addEventListener('click', onClick);
    console.debug('[order-help] READY. BASE_URL =', BASE_URL, 'HELP_API =', HELP_API);
  });

  // Hàm global nếu cần onclick="callStaffNow(1)"
  window.callStaffNow = async function(elOrNum) {
    const tableNumber = typeof elOrNum === 'number' ? elOrNum : getTableNumber(elOrNum);
    if (!Number.isFinite(tableNumber) || tableNumber <= 0) {
      alert('Không xác định được số bàn để gọi trợ giúp.');
      return;
    }
    try {
      await callStaff(tableNumber);
      alert('Đã gửi yêu cầu trợ giúp. Nhân viên sẽ đến ngay!');
    } catch (e) {
      alert(e.message || 'Có lỗi xảy ra khi gọi trợ giúp.');
    }
  };
})();