(function () {
  // Cho phép override URL qua cấu hình toàn cục
  const CFG = window.SF_TRANSFER_CONFIG || {};
  const LIST_API = CFG.LIST_API || "../functions/staff_tables_api.php?action=list";
  const TRANSFER_API = CFG.TRANSFER_API || "../functions/staff_transfer_table.php";

  // -------- Helpers --------
  async function jsonOrThrow(resp) {
    const ct = (resp.headers.get("content-type") || "").toLowerCase();
    const text = await resp.text();
    let data = null;
    try { data = JSON.parse(text); } catch {}
    if (!resp.ok || (data && data.ok === false)) {
      const msg = (data && data.message) || text || `HTTP ${resp.status}`;
      throw new Error(msg);
    }
    return data ?? {};
  }

  function promptAdminCreds() {
    return new Promise((resolve) => {
      const wrap = document.createElement("div");
      wrap.style.cssText =
        "position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center";
      wrap.innerHTML = `
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;width:360px;max-width:92%">
          <h3 style="margin:0 0 8px 0;color:#111827;font-size:16px">Xác nhận quyền Admin</h3>
          <div style="display:flex;flex-direction:column;gap:8px">
            <input id="adm-u" placeholder="Tài khoản Admin" style="padding:8px;border:1px solid #d1d5db;border-radius:8px">
            <input id="adm-p" type="password" placeholder="Mật khẩu" style="padding:8px;border:1px solid #d1d5db;border-radius:8px">
          </div>
          <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
            <button id="adm-cancel" class="btn">Hủy</button>
            <button id="adm-ok" class="btn btn-primary">Xác nhận</button>
          </div>
        </div>`;
      document.body.appendChild(wrap);
      const $ = (s) => wrap.querySelector(s);
      $("#adm-u").focus();
      function done(v){ document.body.removeChild(wrap); resolve(v); }
      $("#adm-cancel").onclick = () => done(null);
      $("#adm-ok").onclick = () => {
        const u = $("#adm-u").value.trim();
        const p = $("#adm-p").value;
        if (!u || !p) { alert("Vui lòng nhập đủ tài khoản và mật khẩu Admin."); return; }
        done({ user: u, pass: p });
      };
    });
  }

  async function fetchTables() {
    const resp = await fetch(LIST_API, { credentials: "same-origin" });
    return jsonOrThrow(resp);
  }

  // Fallback lấy số bàn đang mở trong modal (nếu button không kèm data-table)
  function getCurrentDetailTableNumber() {
    const body = document.getElementById("detail-body");
    if (body) {
      const m = (body.textContent || "").match(/Bàn\s+(\d+)/i);
      if (m) return parseInt(m[1], 10);
    }
    return null;
  }

  // -------- UI chọn bàn trống + gọi API chuyển bàn --------
  function pickToTable(fromTable) {
    if (!fromTable || isNaN(fromTable)) {
      const fallback = getCurrentDetailTableNumber();
      fromTable = fallback || fromTable;
      if (!fromTable) {
        const tmp = prompt("Không xác định được bàn nguồn. Nhập số bàn cần chuyển:");
        const n = tmp ? parseInt(tmp, 10) : NaN;
        if (!n) return;
        fromTable = n;
      }
    }

    const wrap = document.createElement("div");
    wrap.style.cssText =
      "position:fixed;inset:0;z-index:2500;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center";
    const card = document.createElement("div");
    card.style.cssText =
      "background:#fff;border:1px solid #e5e7eb;border-radius:12px;width:720px;max-width:95%;max-height:85vh;display:flex;flex-direction:column";
    card.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid #e5e7eb">
        <strong>Chọn bàn trống để chuyển (từ bàn ${fromTable})</strong>
        <button id="tf-close" class="btn">✕</button>
      </div>
      <div style="padding:10px 12px;overflow:auto">
        <div id="tf-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px"></div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;padding:12px 14px;border-top:1px solid #e5e7eb">
        <button id="tf-cancel" class="btn">Hủy</button>
        <button id="tf-ok" class="btn btn-primary" disabled>Chuyển bàn</button>
      </div>
    `;
    wrap.appendChild(card);
    document.body.appendChild(wrap);

    const $ = (s) => card.querySelector(s);
    const grid = $("#tf-grid");
    const btnOk = $("#tf-ok");
    let chosen = null;

    function close(){ document.body.removeChild(wrap); }

    $("#tf-close").onclick = close;
    $("#tf-cancel").onclick = close;

    fetchTables()
      .then((data) => {
        const list = (data && (data.tables || data.data || data)) || [];
        const available = list.filter(t => String(t.status).toLowerCase() === "available");
        if (available.length === 0) {
          grid.innerHTML = `<div style="color:#6b7280">Hiện không còn bàn trống.</div>`;
          return;
        }
        available.forEach(t => {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.style.cssText = "border:1px solid #d1d5db;background:#f9fafb;padding:10px;border-radius:6px;cursor:pointer;text-align:center";
          btn.innerHTML = `
            <div style="font-weight:700">Bàn ${t.table_number}</div>
            <div style="font-size:12px;color:#6b7280">Tầng ${t.floor ?? "-"}</div>`;
          btn.onclick = () => {
            grid.querySelectorAll("button").forEach(b => b.style.outline = "none");
            btn.style.outline = "3px solid #0b72cf";
            chosen = t.table_number;
            btnOk.disabled = false;
          };
          grid.appendChild(btn);
        });
      })
      .catch(err => {
        grid.innerHTML = `<div style="color:#dc2626">Lỗi tải danh sách bàn: ${String(err.message || err)}</div>`;
      });

    btnOk.onclick = async () => {
      if (!chosen) return;
      const creds = await promptAdminCreds();
      if (!creds) return;
      btnOk.disabled = true;
      try {
        const resp = await fetch(TRANSFER_API, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify({
            from_table: fromTable,
            to_table: chosen,
            admin_user: creds.user,
            admin_pass: creds.pass,
          }),
        });
        const json = await jsonOrThrow(resp);
        alert(json.message || "Đã chuyển bàn.");
        close();
        if (typeof window.SF_reload === "function") window.SF_reload();
        else location.reload();
      } catch (e) {
        alert(e.message || e);
        btnOk.disabled = false;
      }
    };
  }

  // -------- Event delegation: chỉ lắng nghe nút chuyển bàn do UI render --------
  document.addEventListener("click", function (e) {
    const trg = e.target && e.target.closest && e.target.closest('[data-action="transfer"]');
    if (!trg) return;
    e.preventDefault();
    let table = parseInt(trg.getAttribute("data-table") || "", 10);
    if (!table || isNaN(table)) table = getCurrentDetailTableNumber();
    if (!table) { alert("Không xác định được bàn nguồn."); return; }
    pickToTable(table);
  }, true);

  // Public API (nếu cần gọi thủ công)
  window.SF_openTransfer = pickToTable;
})();