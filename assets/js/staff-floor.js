(function () {
  function init() {
    const grid = document.getElementById("tables-grid");
    if (!grid) return;

    const LIST_API =
      (window.SF_CONFIG && window.SF_CONFIG.LIST_API) ||
      "../functions/staff_tables_api.php?action=list";
    const ORDER_API =
      (window.SF_CONFIG && window.SF_CONFIG.ORDER_API) ||
      "../functions/staff_order_api.php";
    const CALL_API =
      (window.SF_CONFIG && window.SF_CONFIG.CALL_API) ||
      "../functions/call_staff_api.php";
    const PAYMENT_URL =
      (window.SF_CONFIG && window.SF_CONFIG.PAYMENT_URL) || "payment.php";

    const floorListWrap = document.getElementById("floor-list");
    const countAll = document.getElementById("count-all");
    const crumbScope = document.getElementById("crumb-scope");
    const crumbFloor = document.getElementById("crumb-floor");
    const crumbSummary = document.getElementById("crumb-summary");
    const btnRefresh = document.getElementById("btn-refresh");

    let lastData = null;
    let selectedFloor = "all";
    let selectedTableNumber = null;
    let timer = null;

    async function jsonOrText(resp) {
      const ct = (resp.headers.get("content-type") || "").toLowerCase();
      if (ct.includes("application/json")) return resp.json();
      const txt = await resp.text();
      throw new Error(txt || `HTTP ${resp.status}`);
    }

    // ====== Audio (rút gọn) ======
    const SOUNDS = (window.SF_CONFIG && window.SF_CONFIG.SOUNDS) || {
      order: "../assets/audio/order.mp3",
      help: "../assets/audio/help.mp3",
    };
    const audioPlayers = {
      order: new Audio(SOUNDS.order),
      help: new Audio(SOUNDS.help),
    };
    Object.values(audioPlayers).forEach((a) => {
      if (a) {
        a.preload = "auto";
        a.crossOrigin = "anonymous";
        a.volume = 0.7;
      }
    });
    function unlockAudioOnce() {
      ["order", "help"].forEach((k) => {
        const a = audioPlayers[k];
        if (!a) return;
        try {
          a.muted = true;
          const p = a.play();
          if (p && typeof p.then === "function") {
            p.then(() => {
              a.pause();
              a.currentTime = 0;
              a.muted = false;
            }).catch(() => {
              a.pause();
              a.currentTime = 0;
              a.muted = false;
            });
          } else {
            a.pause();
            a.currentTime = 0;
            a.muted = false;
          }
        } catch {}
      });
      window.removeEventListener("pointerdown", unlockAudioOnce);
      window.removeEventListener("keydown", unlockAudioOnce);
    }
    window.addEventListener("pointerdown", unlockAudioOnce, { once: true });
    window.addEventListener("keydown", unlockAudioOnce, { once: true });

    // Phát hiện sự kiện
    let firstBootSeeded = false;
    const seenOrderIds = new Set();
    const seenCallOpenIds = new Set();

    // Dấu hiệu "vừa thêm món" (highlight bàn khi đơn hiện tại tăng tổng tiền)
    const ADDED_TTL_MS = 2 * 60 * 1000; // 2 phút
    const orderTotalsSeen = new Map(); // order_id -> last total
    const orderAddedFlash = new Map(); // order_id -> expire timestamp

    function cleanupAddedFlash() {
      const now = Date.now();
      for (const [id, ts] of orderAddedFlash.entries()) {
        if (now > ts) orderAddedFlash.delete(id);
      }
    }

    function playOrderSound() {
      const a = audioPlayers.order;
      if (a) {
        try {
          a.currentTime = 0;
          a.play().catch(() => {});
        } catch {}
      }
    }
    function playHelpSound() {
      const a = audioPlayers.help;
      if (a) {
        try {
          a.currentTime = 0;
          a.play().catch(() => {});
        } catch {}
      }
    }

    function detectEvents(newData) {
      const tables = Array.isArray(newData?.tables) ? newData.tables : [];

      const currentOrderIds = [];
      const currentCallOpenIds = [];
      const currentOrderTotals = []; // {id,total}

      for (const t of tables) {
        if (t?.current_order?.id) {
          const oid = Number(t.current_order.id);
          currentOrderIds.push(oid);
          currentOrderTotals.push({
            id: oid,
            total: Number(t.current_order.total || 0),
          });
        }
        if (t?.has_call && t?.call && t.call.status === "open" && t.call.id) {
          currentCallOpenIds.push(Number(t.call.id));
        }
      }

      // Lần đầu seed: không phát âm cũng không highlight
      if (!firstBootSeeded) {
        currentOrderIds.forEach((id) => seenOrderIds.add(id));
        currentCallOpenIds.forEach((id) => seenCallOpenIds.add(id));
        currentOrderTotals.forEach(({ id, total }) =>
          orderTotalsSeen.set(id, total)
        );
        firstBootSeeded = true;
        return;
      }

      // Sự kiện "đơn mới" và "call mới"
      const newOrders = currentOrderIds.filter((id) => !seenOrderIds.has(id));
      const newCalls = currentCallOpenIds.filter(
        (id) => !seenCallOpenIds.has(id)
      );
      if (newOrders.length > 0) playOrderSound();
      if (newCalls.length > 0) playHelpSound();
      currentOrderIds.forEach((id) => seenOrderIds.add(id));
      currentCallOpenIds.forEach((id) => seenCallOpenIds.add(id));

      // Sự kiện "vừa thêm món": total tăng so với lần trước
      const now = Date.now();
      for (const { id, total } of currentOrderTotals) {
        const prev = orderTotalsSeen.has(id)
          ? Number(orderTotalsSeen.get(id))
          : undefined;
        if (prev !== undefined && total > prev) {
          orderAddedFlash.set(id, now + ADDED_TTL_MS);
        }
        orderTotalsSeen.set(id, total);
      }
      cleanupAddedFlash();
    }

    // Nhận kết quả từ payment.php (global listener, không dùng alert nữa)
    // CSS cho badge "Món mới"
    function ensureAddedBadgeStyles() {
      if (document.getElementById("sf-added-badge-style")) return;
      const css = `
        .table-card { position: relative; }
        .table-card.added-now {
          box-shadow: 0 0 0 2px #22c55e inset;
          border-radius: 12px;
        }
        .table-card.added-now::after {
          content: '';
          position: absolute;
          inset: -2px;
          border-radius: 12px;
          border: 2px solid rgba(34,197,94,0.55);
          animation: sfPulseAdded 1.6s ease-out infinite;
          pointer-events: none;
        }
        @keyframes sfPulseAdded {
          0% { opacity: 0.9; transform: scale(1); }
          100% { opacity: 0; transform: scale(1.03); }
        }
        .badge-added {
          position: absolute;
          top: 6px;
          right: 6px;
          background: #16a34a;
          color: #fff;
          font-size: 11px;
          line-height: 1;
          padding: 4px 6px;
          border-radius: 999px;
          box-shadow: 0 1px 2px rgba(0,0,0,0.08);
          z-index: 1;
        }
      `;
      const style = document.createElement("style");
      style.id = "sf-added-badge-style";
      style.textContent = css;
      document.head.appendChild(style);
    }
    ensureAddedBadgeStyles();

    // Nhận kết quả từ payment.php
    window.addEventListener("message", async (ev) => {
      const d = ev && ev.data;
      if (!d || typeof d !== "object") return;
      if (d.type === "staff-payment-success") {
        if (d.order_id) {
          try {
            const url = `${ORDER_API}?action=mark_paid&order_id=${encodeURIComponent(
              d.order_id
            )}&method=bank_transfer`;
            const resp = await fetch(url, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                order_id: d.order_id,
                method: "bank_transfer",
              }),
            });
            try {
              await jsonOrText(resp);
            } catch {}
          } catch (err) {
            // ignore network errors here
          }
          try {
            if (lastData && Array.isArray(lastData.tables)) {
              const t = lastData.tables.find(
                (x) =>
                  x.current_order &&
                  Number(x.current_order.id) === Number(d.order_id)
              );
              if (t?.current_order)
                t.current_order.payment_method = "bank_transfer";
            }
          } catch {}
        }
        await refresh();
        const bd = document.getElementById("detail-backdrop");
        if (bd) bd.style.display = "none";

        // Không dùng alert (popup). Ghi log thay thế:
        console.info("staff-payment-success received", d.order_id);
      }
    });

    function iconTable() {
      return `<div class="table-icon"><span>🍽️</span><span class="center">╬</span><span>🪑</span></div>`;
    }
    function fmtMoney(v) {
      try {
        const formatted = new Intl.NumberFormat("vi-VN", {
          maximumFractionDigits: 0,
        }).format(v);
        return formatted + "\u00A0đ";
      } catch {
        return String(v) + "\u00A0đ";
      }
    }
    function fmtTimeShort(s) {
      if (!s) return "";
      const d = new Date((s || "").replace(" ", "T"));
      return isNaN(d)
        ? s
        : d.toLocaleTimeString("vi-VN", { hour: "2-digit", minute: "2-digit" });
    }

    function setSummaryAll(sum) {
      crumbSummary.textContent = `Trống ${sum.free_tables}/${sum.total_tables} bàn - ${sum.total_seats} ghế`;
    }
    function setSummaryFloor(floorAgg) {
      if (!floorAgg) {
        crumbFloor.textContent = "Tầng: Tất cả";
        return;
      }
      crumbFloor.textContent = `Tầng: ${floorAgg.floor}`;
      crumbSummary.textContent = `Trống ${floorAgg.free_tables}/${floorAgg.total_tables} bàn - ${floorAgg.total_seats} ghế`;
    }

    function renderFloors(floors) {
      countAll.textContent = `(${lastData?.summary?.free_tables || 0})`;
      floorListWrap.innerHTML = "";
      floors.forEach((f) => {
        const a = document.createElement("a");
        a.href = "javascript:void(0)";
        a.className =
          "floor-item" +
          (String(selectedFloor) === String(f.floor) ? " active" : "");
        a.dataset.floor = String(f.floor);
        a.innerHTML = `<span class="icon"></span><span>Tầng ${f.floor}</span><span class="count">(${f.free_tables})</span>`;
        a.onclick = () => {
          selectedFloor = String(f.floor);
          selectedTableNumber = null;
          render();
        };
        floorListWrap.appendChild(a);
      });
      const allBtn = document.getElementById("floor-all");
      if (allBtn) {
        if (selectedFloor === "all") allBtn.classList.add("active");
        else allBtn.classList.remove("active");
        allBtn.onclick = () => {
          selectedFloor = "all";
          selectedTableNumber = null;
          render();
        };
      }
    }

    function renderGrid() {
      const allTables = lastData?.tables || [];
      const tables = allTables.filter(
        (t) =>
          selectedFloor === "all" || String(t.floor) === String(selectedFloor)
      );
      grid.innerHTML = "";
      if (!tables.length) {
        const div = document.createElement("div");
        div.className = "empty";
        div.textContent = "Không có bàn ở tầng đã chọn.";
        grid.appendChild(div);
        return;
      }

      const now = Date.now();

      tables.forEach((t) => {
        const statusClass = t.is_busy ? "serving" : "available";
        const hasCall = !!t.has_call;

        const card = document.createElement("div");
        card.className =
          `table-card ${statusClass}` + (hasCall ? " has-call" : "");

        // Bàn vừa thêm món? (flash theo order_id)
        let addedNow = false;
        const oid = t?.current_order?.id ? Number(t.current_order.id) : null;
        if (oid && orderAddedFlash.has(oid) && now < orderAddedFlash.get(oid)) {
          addedNow = true;
          card.classList.add("added-now");
        }

        const callLine = hasCall
          ? `<div class="call-badge">${
              t.call.status === "open" ? "Gọi nhân viên" : "Đang tiếp nhận"
            } • ${t.call.call_wait_mins}p</div>`
          : "";
        const orderInfo = t.current_order
          ? `
          <div style="color:#5b6574;font-size:13px">Đặt lúc ${fmtTimeShort(
            t.current_order.ordered_at
          )} • Chờ ${t.current_order.wait_mins}p</div>
          <div style="color:#5b6574;font-size:13px">Tổng cần thu: ${fmtMoney(
            t.current_order.total
          )}</div>`
          : "";

        const addedBadge = addedNow
          ? `<span class="badge-added">Món mới</span>`
          : "";

        card.innerHTML = `${iconTable()}
          ${addedBadge}
          <div class="table-meta"><span><strong>Bàn ${
            t.table_number
          }</strong></span><span>•</span><span>Tầng ${t.floor}</span></div>
          ${callLine}${orderInfo}`;

        card.onclick = () => openDetail(t);
        grid.appendChild(card);
      });
    }

    // Mini modal: xác thực Admin + số lượng + lý do
    // Mini modal: xác thực Admin + số lượng + lý do (đã bỏ gợi ý lý do mặc định)
function promptAdminQty(currentQty = 1) {
  return new Promise((resolve) => {
    const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
    const wrap = document.createElement("div");
    wrap.style.cssText =
      "position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center";

    const qtyBlock = `
      <div>
        <label style="display:block;margin:8px 0 4px 2px;color:#374151;font-size:12px">Số lượng cần hủy</label>
        <input id="adm-delqty" type="number" min="1" max="${currentQty || 1}" value="${currentQty > 1 ? 1 : 1}"
               style="padding:8px;border:1px solid #d1d5db;border-radius:8px;width:120px">
        <span style="margin-left:6px;color:#6b7280">/ ${currentQty || 1}</span>
      </div>`;

    wrap.innerHTML = `
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;width:360px;max-width:92%">
        <h3 style="margin:0 0 8px 0;color:#111827;font-size:16px">Xác nhận quyền Admin</h3>
        <div style="display:flex;flex-direction:column;gap:8px">
          <input id="adm-user" placeholder="Tài khoản Admin"
                 style="padding:8px;border:1px solid #d1d5db;border-radius:8px">
          <input id="adm-pass" type="password" placeholder="Mật khẩu"
                 style="padding:8px;border:1px solid #d1d5db;border-radius:8px">
          ${qtyBlock}
          <!-- Chỉ giữ placeholder mờ, KHÔNG set value mặc định -->
          <input id="adm-reason" placeholder="Lý do hủy món"
                 style="padding:8px;border:1px solid #d1d5db;border-radius:8px">
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
          <button id="adm-cancel" class="btn">Hủy</button>
          <button id="adm-ok" class="btn btn-primary">Xác nhận</button>
        </div>
      </div>`;

    document.body.appendChild(wrap);
    const $ = (s) => wrap.querySelector(s);
    $("#adm-user").focus();

    function done(v) {
      document.body.removeChild(wrap);
      resolve(v);
    }
    $("#adm-cancel").onclick = () => done(null);
    $("#adm-ok").onclick = () => {
      const u = $("#adm-user").value.trim();
      const p = $("#adm-pass").value;
      const r = $("#adm-reason").value.trim(); // người dùng tự nhập, không gợi ý
      let dq = parseInt($("#adm-delqty").value, 10);
      dq = clamp(dq, 1, currentQty || 1);
      done({ user: u, pass: p, qty: dq, reason: r });
    };
  });
}

    // Modal detail
    const backdrop = document.getElementById("detail-backdrop");
    const bodyEl = document.getElementById("detail-body");
    const actionsEl = document.getElementById("detail-actions");
    const btnClose = document.getElementById("btn-detail-close");

    function openDetail(table) {
      selectedTableNumber = table.table_number;
      backdrop.style.display = "flex";

      const callInfo = table.has_call
        ? `<div style="margin-bottom:8px;padding:8px 10px;background:#fff7ed;border:1px solid #ffedd5;border-radius:8px;color:#92400e">
             <strong>${
               table.call.status === "open" ? "Gọi nhân viên" : "Đang tiếp nhận"
             }</strong>
             • ${table.call.call_wait_mins} phút
             ${table.call.note ? ` • Ghi chú: ${table.call.note}` : ""}
           </div>`
        : "";

      if (!table.current_order) {
        bodyEl.innerHTML = `${callInfo}
          <div>Bàn <strong>${table.table_number}</strong> • Tầng <strong>${table.floor}</strong></div>
          <div style="margin-top:8px;color:#5b6574">Chưa có đơn chưa thanh toán cho bàn này.</div>`;
        
        // Render "Mở rộng" button with popover for call + transfer actions
        const moreActions = `
          <button class="btn" id="btn-call-ack" data-in-popover="true" ${table.has_call ? '' : 'style="display:none"'}>Tiếp nhận</button>
          <button class="btn btn-primary" id="btn-call-resolve" data-in-popover="true" ${table.has_call ? '' : 'style="display:none"'}>Đã xong</button>
          <button class="btn" data-action="transfer" data-table="${table.table_number}" data-in-popover="true">Chuyển bàn</button>
        `;
        
        actionsEl.innerHTML = `
          <div class="sf-more-actions">
            <button class="btn" id="btn-more-toggle">Mở rộng</button>
            <div class="sf-more-popover" id="more-popover" style="display:none">
              ${moreActions}
            </div>
          </div>
        `;
        
        wireMoreButton();
        wireCallButtons(table);
        return;
      }

      const o = table.current_order;

      // Styles + colgroup để canh đều cột
      const tableStyles = `
        <style>
          .sf-detail-table { width:100%; border-collapse:collapse; table-layout:auto; }
          .sf-detail-table th, .sf-detail-table td { padding:8px 10px; border-bottom:1px solid #eef2f7; vertical-align:middle; }
          .sf-col-name { white-space: normal; word-break: break-word; overflow-wrap: anywhere; }
          .sf-money { white-space: nowrap; }
          .sf-text-right { text-align:right; }
          .sf-btn-del { background:#fff; color:#dc2626; border:1px solid #fecaca; padding:6px 10px; border-radius:8px; cursor:pointer; user-select:none; }
        </style>
      `;

      // Hàng món (đã bỏ dòng “PTTT hiện tại”)
      const rows = (o.items || [])
        .map(
          (i) => `
        <tr data-oi="${i.order_item_id || ""}">
          <td class="sf-col-name">${i.name}</td>
          <td class="sf-text-right">x${i.quantity}</td>
          <td class="sf-text-right"><span class="sf-money">${fmtMoney(
            i.price
          )}</span></td>
          <td class="sf-text-right"><span class="sf-money">${fmtMoney(
            i.price * i.quantity
          )}</span></td>
          <td class="sf-text-right">
            <button type="button"
                    class="sf-btn-del btn-del-item"
                    data-oi="${i.order_item_id || ""}"
                    data-dish="${i.id || ""}"
                    data-name="${(i.name || "").replace(/"/g, "&quot;")}"
                    data-qty="${i.quantity}"
                    data-price="${i.price}">
              Xóa
            </button>
          </td>
        </tr>
      `
        )
        .join("");

      bodyEl.innerHTML = `
        ${tableStyles}
        ${callInfo}
        <div style="margin-bottom:8px">
          <div>Bàn <strong>${table.table_number}</strong> • Tầng <strong>${
        table.floor
      }</strong></div>
          ${
            o.ordered_at
              ? `<div style="color:#5b6574">Đặt lúc ${fmtTimeShort(
                  o.ordered_at
                )} • Đã chờ ${o.wait_mins} phút</div>`
              : ""
          }
          <div style="color:#5b6574">Trạng thái đơn: ${o.status}</div>
        </div>
        <table class="sf-detail-table">
          <colgroup>
            <col />                 <!-- Món -->
            <col style="width:72px" />   <!-- SL -->
            <col style="width:110px" />  <!-- Đơn giá -->
            <col style="width:130px" />  <!-- Thành tiền -->
            <col style="width:76px" />   <!-- Xóa -->
          </colgroup>
          <thead>
            <tr>
              <th>Món</th>
              <th class="sf-text-right">SL</th>
              <th class="sf-text-right">Đơn giá</th>
              <th class="sf-text-right">Thành tiền</th>
              <th class="sf-text-right">Xóa</th>
            </tr>
          </thead>
          <tbody>${
            rows || '<tr><td colspan="5">(Không có món)</td></tr>'
          }</tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="sf-text-right"><strong>Tổng cần thu</strong></td>
              <td class="sf-text-right"><strong class="sf-money">${fmtMoney(
                o.total
              )}</strong></td>
            </tr>
          </tfoot>
        </table>
      `;

      // Xóa theo số lượng (có/không có order_item_id)
      const tbody = bodyEl.querySelector("tbody");
      if (tbody) {
        tbody.addEventListener("click", async (e) => {
          const btn = e.target.closest(".btn-del-item");
          if (!btn) return;
          e.stopPropagation();

          const currentQty = parseInt(btn.dataset.qty || "1", 10) || 1;
          const auth = await promptAdminQty(
            currentQty,
            `Xóa ${btn.dataset.name || ""} x${currentQty}`
          );
          if (!auth) return;

          const delQty = Math.max(
            1,
            Math.min(currentQty, parseInt(auth.delQty || "1", 10) || 1)
          );
          const payload = {
            order_id: o.id,
            order_item_id: parseInt(btn.dataset.oi || "0", 10) || 0,
            admin_username: auth.username,
            admin_password: auth.password,
            reason: auth.reason || null,
            delete_qty: delQty,
            // fallback JSON
            dish_id: btn.dataset.dish ? parseInt(btn.dataset.dish, 10) : null,
            quantity: currentQty,
            price: btn.dataset.price ? parseFloat(btn.dataset.price) : null,
            name: btn.dataset.name || null,
          };

          btn.disabled = true;
          try {
            const resp = await fetch(`${ORDER_API}?action=delete_item`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(payload),
            });
            const data = await jsonOrText(resp);
            if (!data.success)
              throw new Error(data.message || "Xóa không thành công");
            await refresh();
            const t = lastData.tables.find(
              (x) => String(x.table_number) === String(table.table_number)
            );
            if (t) openDetail(t);
            alert(`Đã hủy ${delQty} món.`);
          } catch (err) {
            alert(err.message || "Có lỗi khi xóa món.");
          } finally {
            btn.disabled = false;
          }
        });
      }

      // Hành động thanh toán + "Mở rộng" button with popover for call + transfer
      const transferUrl = `${PAYMENT_URL}?order_id=${encodeURIComponent(
        o.id
      )}&method=bank_transfer`;
      
      const moreActions = `
        <button class="btn" id="btn-call-ack" data-in-popover="true" ${table.has_call && table.call.status === 'open' ? '' : 'style="display:none"'}>Tiếp nhận</button>
        <button class="btn btn-primary" id="btn-call-resolve" data-in-popover="true" ${table.has_call ? '' : 'style="display:none"'}>Đã xong</button>
        <button class="btn" data-action="transfer" data-table="${table.table_number}" data-in-popover="true">Chuyển bàn</button>
      `;
      
      actionsEl.innerHTML = `
        <button class="btn btn-primary" id="btn-pay-cash">Thanh toán thành công</button>
        <a class="btn" id="btn-pay-transfer" href="${transferUrl}">Thanh toán bằng chuyển khoản</a>
        <div class="sf-more-actions">
          <button class="btn" id="btn-more-toggle">Mở rộng</button>
          <div class="sf-more-popover" id="more-popover" style="display:none">
            ${moreActions}
          </div>
        </div>
      `;

      const btnCash = document.getElementById("btn-pay-cash");
      btnCash.onclick = async () => {
        btnCash.disabled = true;
        try {
          const url = `${ORDER_API}?action=mark_paid&order_id=${encodeURIComponent(
            o.id
          )}&method=cash`;
          const resp = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ order_id: o.id, method: "cash" }),
          });
          const data = await jsonOrText(resp);
          if (!data.success)
            throw new Error(data.message || "Thanh toán thất bại");
          await refresh();
          backdrop.style.display = "none";
          // alert removed: no confirmation popup for cash payments
        } catch (e) {
          alert(e.message || "Có lỗi xảy ra.");
        } finally {
          btnCash.disabled = false;
        }
      };

      const btnTransfer = document.getElementById("btn-pay-transfer");
      if (btnTransfer) {
        btnTransfer.addEventListener("click", (e) => {
          e.preventDefault();
          const w = window.open(
            transferUrl,
            "staff-payment",
            "width=520,height=720"
          );
          if (!w) window.location.href = transferUrl;
        });
      }

      wireMoreButton();

      // NOTE: removed inner window.message listener here (handled by the global listener above)
      // Nhận kết quả từ payment.php (bản trong modal) — giữ nguyên như hiện tại
      window.addEventListener("message", async (ev) => {
        const d = ev && ev.data;
        if (!d || typeof d !== "object") return;
        if (d.type === "staff-payment-success") {
          if (d.order_id) {
            try {
              const url = `${ORDER_API}?action=mark_paid&order_id=${encodeURIComponent(
                d.order_id
              )}&method=bank_transfer`;
              const resp = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                  order_id: d.order_id,
                  method: "bank_transfer",
                }),
              });
              try {
                await jsonOrText(resp);
              } catch {}
            } catch {}
            try {
              if (lastData && Array.isArray(lastData.tables)) {
                const t = lastData.tables.find(
                  (x) =>
                    x.current_order &&
                    Number(x.current_order.id) === Number(d.order_id)
                );
                if (t?.current_order)
                  t.current_order.payment_method = "bank_transfer";
              }
            } catch {}
          }
          await refresh();
          const bd = document.getElementById("detail-backdrop");
          if (bd) bd.style.display = "none";
          alert("Đã xác nhận thanh toán (chuyển khoản).");
        }
      });

      wireCallButtons(table);
    }

    function wireMoreButton() {
      const btnToggle = document.getElementById("btn-more-toggle");
      const popover = document.getElementById("more-popover");
      if (!btnToggle || !popover) return;

      btnToggle.onclick = (e) => {
        e.stopPropagation();
        const isVisible = popover.style.display !== "none";
        popover.style.display = isVisible ? "none" : "block";
      };

      // Close popover when clicking outside
      const closeHandler = (e) => {
        if (
          popover.style.display === "block" &&
          !popover.contains(e.target) &&
          !btnToggle.contains(e.target)
        ) {
          popover.style.display = "none";
        }
      };
      document.addEventListener("click", closeHandler);
      
      // Cleanup when modal closes
      const backdrop = document.getElementById("detail-backdrop");
      if (backdrop) {
        const observer = new MutationObserver(() => {
          if (backdrop.style.display === "none") {
            document.removeEventListener("click", closeHandler);
            observer.disconnect();
          }
        });
        observer.observe(backdrop, { attributes: true, attributeFilter: ["style"] });
      }
    }

    function wireCallButtons(table) {
      if (!table.has_call) return;
      const btnAck = document.getElementById("btn-call-ack");
      const btnDone = document.getElementById("btn-call-resolve");

      if (btnAck && table.call.status === "open") {
        btnAck.style.display = "";
        btnAck.onclick = async () => {
          btnAck.disabled = true;
          try {
            const resp = await fetch(`${CALL_API}?action=ack`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id: table.call.id }),
            });
            const data = await jsonOrText(resp);
            if (!data.success)
              throw new Error(data.message || "Không thể tiếp nhận");
            await refresh();
            const t = lastData.tables.find(
              (x) => x.table_number === table.table_number
            );
            if (t) openDetail(t);
          } catch (e) {
            alert(e.message || "Có lỗi xảy ra.");
          } finally {
            btnAck.disabled = false;
          }
        };
      } else if (btnAck) {
        btnAck.style.display = "none";
      }

      if (btnDone) {
        btnDone.onclick = async () => {
          btnDone.disabled = true;
          try {
            const resp = await fetch(`${CALL_API}?action=resolve`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id: table.call.id }),
            });
            const data = await jsonOrText(resp);
            if (!data.success)
              throw new Error(data.message || "Không thể hoàn tất");
            await refresh();
            const t = lastData.tables.find(
              (x) => x.table_number === table.table_number
            );
            if (t) openDetail(t);
          } catch (e) {
            alert(e.message || "Có lỗi xảy ra.");
          } finally {
            btnDone.disabled = false;
          }
        };
      }
    }

    function closeDetail() {
      backdrop.style.display = "none";
      selectedTableNumber = null;
    }
    if (btnClose) btnClose.onclick = closeDetail;
    if (backdrop)
      backdrop.onclick = (e) => {
        if (e.target === backdrop) closeDetail();
      };

    async function refresh() {
      try {
        const resp = await fetch(LIST_API);
        const data = await jsonOrText(resp);
        detectEvents(data);
        lastData = data;
        render();
      } catch (e) {
        console.error("Refresh error:", e);
        alert("Không thể tải dữ liệu: " + (e.message || e));
      }
    }

    // Expose refresh for external use (e.g., staff_transfer_table.js)
    window.SF_REFRESH = refresh;

    function render() {
      if (!lastData) return;
      renderFloors(lastData.floors || []);
      renderGrid();
      if (selectedFloor === "all") setSummaryAll(lastData.summary || {});
      else {
        const floorAgg = (lastData.floors || []).find(
          (f) => String(f.floor) === String(selectedFloor)
        );
        setSummaryFloor(floorAgg);
      }
    }

    if (btnRefresh) btnRefresh.onclick = refresh;

    function startTimer() {
      if (timer) clearInterval(timer);
      timer = setInterval(refresh, 1000);
    }
    function stopTimer() {
      if (timer) clearInterval(timer);
      timer = null;
    }

    startTimer();
    refresh();
    window.addEventListener("beforeunload", stopTimer);
  }

  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
  else init();
})();
