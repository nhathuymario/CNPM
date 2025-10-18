document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('callStaffBtn');
  if (!btn) return;

  let busy = false;
  const cooldownMs = 60000;

  const setState = (disabled, text) => {
    btn.disabled = disabled;
    const label = btn.querySelector('span');
    if (label) label.textContent = text;
  };

  const showToast = (msg, isErr) => {
    let t = document.getElementById('helpToast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'helpToast';
      t.style.position = 'fixed';
      t.style.bottom = '16px';
      t.style.left = '50%';
      t.style.transform = 'translateX(-50%)';
      t.style.background = '#d4f5e9';
      t.style.color = '#1f2937';
      t.style.border = '1px solid #9adecc';
      t.style.padding = '10px 14px';
      t.style.borderRadius = '8px';
      t.style.boxShadow = '0 2px 10px rgba(0,0,0,.08)';
      t.style.zIndex = '9999';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.background = isErr ? '#ffd2d2' : '#d4f5e9';
    t.style.borderColor = isErr ? '#ff9c9c' : '#9adecc';
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 2500);
  };

  btn.addEventListener('click', async () => {
    if (busy) return;
    const table = btn.dataset.table || '';
    const k = btn.dataset.k || '';

    if (!table) {
      showToast('Thiếu thông tin bàn. Vui lòng quét QR tại bàn.', true);
      return;
    }

    busy = true;
    setState(true, 'Đang gọi...');

    try {
      const res = await fetch('../functions/call_staff.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: new URLSearchParams({ table, k })
      });

      const json = await res.json().catch(() => ({}));
      if (json && json.ok) {
        showToast(json.message || 'Đã gọi nhân viên.');
        setState(true, 'Đã gọi');
        setTimeout(() => {
          setState(false, 'Trợ giúp');
          busy = false;
        }, cooldownMs);
      } else {
        showToast(json.message || 'Không gửi được yêu cầu. Vui lòng thử lại.', true);
        setState(false, 'Trợ giúp');
        busy = false;
      }
    } catch (e) {
      showToast('Lỗi kết nối. Vui lòng thử lại.', true);
      setState(false, 'Trợ giúp');
      busy = false;
    }
  });
});