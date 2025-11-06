(function () {
  // Transfer table logic: provides UI for table picker and admin authentication
  // Event delegation: listens for clicks on elements with data-action="transfer"

  const TRANSFER_API =
    (window.SF_CONFIG && window.SF_CONFIG.TRANSFER_API) ||
    "../functions/staff_transfer_table.php";
  const LIST_API =
    (window.SF_CONFIG && window.SF_CONFIG.LIST_API) ||
    "../functions/staff_tables_api.php?action=list";

  // Event delegation: listen for transfer button clicks
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest('[data-action="transfer"]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    const fromTable = btn.dataset.table ? parseInt(btn.dataset.table, 10) : 0;
    if (!fromTable) {
      alert("Không xác định được bàn nguồn");
      return;
    }

    // Fetch available tables
    let availableTables = [];
    try {
      const resp = await fetch(LIST_API);
      const data = await resp.json();
      if (data.success && Array.isArray(data.tables)) {
        availableTables = data.tables
          .filter((t) => !t.is_busy && t.table_number !== fromTable)
          .map((t) => ({
            table_number: t.table_number,
            floor: t.floor,
            capacity: t.capacity,
          }));
      }
    } catch (err) {
      console.error("Error fetching tables:", err);
    }

    if (availableTables.length === 0) {
      alert("Không có bàn trống để chuyển");
      return;
    }

    // Show table picker + admin auth modal
    const result = await showTransferModal(fromTable, availableTables);
    if (!result) return;

    // Call transfer API
    btn.disabled = true;
    try {
      const resp = await fetch(TRANSFER_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          from_table: fromTable,
          to_table: result.to_table,
          admin_username: result.admin_username,
          admin_password: result.admin_password,
        }),
      });
      const data = await resp.json();
      if (!data.success) {
        throw new Error(data.message || "Chuyển bàn thất bại");
      }
      alert(data.message || "Đã chuyển bàn thành công");
      // Trigger refresh if available
      if (window.SF_REFRESH) window.SF_REFRESH();
      // Close detail modal if open
      const backdrop = document.getElementById("detail-backdrop");
      if (backdrop) backdrop.style.display = "none";
    } catch (err) {
      alert(err.message || "Có lỗi khi chuyển bàn");
    } finally {
      btn.disabled = false;
    }
  });

  function showTransferModal(fromTable, availableTables) {
    return new Promise((resolve) => {
      const wrap = document.createElement("div");
      wrap.style.cssText =
        "position:fixed;inset:0;z-index:2100;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center";

      const tableOptions = availableTables
        .map(
          (t) =>
            `<option value="${t.table_number}">Bàn ${t.table_number} (Tầng ${t.floor}, ${t.capacity} ghế)</option>`
        )
        .join("");

      wrap.innerHTML = `
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 20px;width:400px;max-width:92%">
          <h3 style="margin:0 0 12px 0;color:#111827;font-size:16px">Chuyển bàn ${fromTable}</h3>
          <div style="display:flex;flex-direction:column;gap:10px">
            <div>
              <label style="display:block;margin-bottom:4px;color:#374151;font-size:13px">Chọn bàn đích</label>
              <select id="transfer-to-table" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px">
                ${tableOptions}
              </select>
            </div>
            <div>
              <label style="display:block;margin-bottom:4px;color:#374151;font-size:13px">Tài khoản Admin</label>
              <input id="transfer-admin-user" type="text" placeholder="Username"
                     style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px">
            </div>
            <div>
              <label style="display:block;margin-bottom:4px;color:#374151;font-size:13px">Mật khẩu</label>
              <input id="transfer-admin-pass" type="password" placeholder="Password"
                     style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px">
            </div>
          </div>
          <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
            <button id="transfer-cancel" class="btn">Hủy</button>
            <button id="transfer-ok" class="btn btn-primary">Xác nhận</button>
          </div>
        </div>`;

      document.body.appendChild(wrap);
      const $ = (s) => wrap.querySelector(s);
      $("#transfer-admin-user").focus();

      function done(v) {
        document.body.removeChild(wrap);
        resolve(v);
      }

      $("#transfer-cancel").onclick = () => done(null);
      $("#transfer-ok").onclick = () => {
        const to_table = parseInt($("#transfer-to-table").value, 10);
        const admin_username = $("#transfer-admin-user").value.trim();
        const admin_password = $("#transfer-admin-pass").value;
        if (!admin_username || !admin_password) {
          alert("Vui lòng nhập đầy đủ thông tin Admin");
          return;
        }
        done({ to_table, admin_username, admin_password });
      };
    });
  }
})();
