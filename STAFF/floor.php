<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../functions/loginStaff.php");
  exit();
}
require '../functions/checkloginStaff.php';
checkRole(['admin', 'staff']);

ob_start();
?>
<script>
  // Cấu hình endpoint cho JS
  window.SF_CONFIG = {
    LIST_API: '../functions/staff_tables_api.php?action=list',
    ORDER_API: '../functions/staff_order_api.php',
    // File thanh toán nằm trong thư mục STAFF
    PAYMENT_URL: 'payment.php',
    CALL_API: '../functions/call_staff_api.php',
    SOUNDS: {
      order: '../assets/audio/order.mp3',
      help:  '../assets/audio/help.mp3'
    }
  };
</script>

<style>
  /* Thêm màu cho 2 chú thích mới, tái dùng style .dot sẵn có */
  .dot-green { background:#16a34a !important; border-color:#16a34a !important; }
  .dot-orange{ background:#f59e0b !important; border-color:#f59e0b !important; }
  .legend { display:flex; gap:14px; align-items:center; }
  .legend span { display:inline-flex; align-items:center; gap:6px; color:#2a3b59; }
</style>

<div class="floor-page">
  <div class="topbar">
    <div class="crumbs">
      <span class="scope" id="crumb-scope">Toàn bộ nhà hàng</span>
      <span class="sep">›</span>
      <span class="floor-name" id="crumb-floor">Tầng: Tất cả</span>
      <span class="summary" id="crumb-summary">Trống 0/0 bàn - 0 ghế</span>
    </div>

    <!-- Legend: thêm 2 chú thích (xanh lá = gọi thêm món, cam = cần trợ giúp), và bỏ nút refresh -->
    <div class="legend">
      <span><i class="dot dot-blue"></i> Bàn trống</span>
      <span><i class="dot dot-grey"></i> Bàn đang phục vụ</span>
      <span><i class="dot dot-green"></i> Khách gọi thêm món</span>
      <span><i class="dot dot-orange"></i> Khách cần trợ giúp</span>
    </div>
  </div>

  <div class="layout">
    <aside class="sidebar">
      <a href="javascript:void(0)" class="floor-item active" data-floor="all" id="floor-all">
        <span class="icon"></span>
        <span>Tất cả</span>
        <span class="count" id="count-all">(0)</span>
      </a>
      <div id="floor-list"></div>
    </aside>

    <section class="canvas">
      <div class="tables-grid" id="tables-grid"></div>
    </section>
  </div>
</div>

<!-- Modal chi tiết + thanh toán -->
<div id="detail-backdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1000;align-items:center;justify-content:center">
  <div id="detail-card" style="background:#fff;border:1px solid #e6e8ee;border-radius:12px;max-width:640px;width:92%;padding:14px 16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
      <h3 style="margin:0;color:#2a3b59;font-size:18px">Chi tiết đơn</h3>
      <button id="btn-detail-close" style="background:#fff;border:1px solid #d0d7de;border-radius:8px;padding:4px 10px;cursor:pointer">Đóng</button>
    </div>
    <div id="detail-body" style="max-height:60vh;overflow:auto;color:#2a3b59"></div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px" id="detail-actions"></div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/masterStaff.php';