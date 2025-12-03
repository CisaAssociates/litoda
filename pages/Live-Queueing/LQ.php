<?php
date_default_timezone_set('Asia/Manila');
include('../../database/db.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Live Queue - LITODA</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* === General Styles === */
* { margin:0; padding:0; box-sizing:border-box; }
body { 
  font-family:"Poppins", sans-serif; 
  background: linear-gradient(135deg,#e0f2fe 0%,#f0fdf4 100%); 
  min-height:100vh; 
  padding:2rem; 
}
.container { 
  max-width:1400px; 
  margin:0 auto; 
}

/* === Now Serving Section === */
.now-serving-section { 
  padding:2rem; 
  background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%); 
  border:2px solid #10b981; 
  border-radius:8px; 
  margin-bottom:1.5rem; 
  text-align:center; 
}

.now-serving-title { 
  text-align:center; 
  color:#065f46; 
  font-size:2rem; 
  font-weight:700; 
  margin-bottom:2rem; 
  text-transform:uppercase; 
  letter-spacing:2px; 
  position:relative; 
  z-index:1; 
}

.serving-cards-container {
  display:flex; 
  gap:2rem; 
  justify-content:center; 
  flex-wrap:wrap;
}

.serving-card { 
  background:#fff; 
  border-radius:16px; 
  padding:2rem; 
  display:flex; 
  align-items:center; 
  justify-content:center; 
  gap:2rem; 
  box-shadow:0 8px 20px rgba(0,0,0,0.1); 
  position:relative; 
  z-index:1; 
  flex-wrap:wrap; 
}

.queue-number-badge { 
  position:absolute; 
  top:-15px; 
  left:50%; 
  transform:translateX(-50%); 
  background:linear-gradient(135deg,#10b981 0%,#059669 100%); 
  color:white; 
  padding:8px 24px; 
  border-radius:20px; 
  font-size:1.2rem; 
  font-weight:700; 
  box-shadow:0 4px 12px rgba(16,185,129,0.4); 
  z-index:2; 
}

.serving-profile { 
  width:120px; 
  height:120px; 
  border-radius:50%; 
  object-fit:cover; 
  box-shadow:0 5px 15px rgba(0,0,0,0.2); 
}

.serving-info { 
  text-align:left; 
}

.serving-name { 
  font-size:2.5rem; 
  font-weight:700; 
  color:#10b981; 
  margin-bottom:0.5rem; 
}

.serving-tricycle { 
  font-size:1.5rem; 
  color:#6b7280; 
  font-weight:600; 
  text-align:center; 
}

.no-serving { 
  text-align:center; 
  color:#6b7280; 
  font-size:1.5rem; 
  padding:2rem; 
  position:relative; 
  z-index:1; 
}

/* === Queue Table Section === */
.queue-section { 
  background:#fff; 
  border-radius:20px; 
  padding:2rem; 
  box-shadow:0 10px 30px rgba(0,0,0,0.08); 
}

.table-responsive {
  width:100%;
  overflow-x:auto;
  -webkit-overflow-scrolling:touch;
}

.queue-table { 
  width:100%; 
  border-collapse:collapse; 
  min-width:700px;
}

.queue-table thead { 
  background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%); 
}

.queue-table th { 
  padding:1rem; 
  text-align:center; 
  color:#065f46; 
  font-weight:600; 
  font-size:0.95rem; 
  text-transform:uppercase; 
  letter-spacing:0.5px; 
  border-bottom:2px solid #10b981; 
  white-space:nowrap;
}

.queue-table td { 
  padding:1rem; 
  text-align:center; 
  border-bottom:1px solid #e5e7eb; 
  font-size:0.9rem; 
  color:#374151; 
}

.queue-table tbody tr { 
  transition: all 0.3s ease; 
}

.queue-table tbody tr:hover { 
  background:#f0fdf4; 
  transform:scale(1.01); 
}

.driver-pic { 
  width:50px; 
  height:50px; 
  border-radius:50%; 
  object-fit:cover; 
}

.queue-number-cell { 
  font-size:1.5rem; 
  font-weight:700; 
  color:#10b981; 
}

.driver-name-cell {
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
}

.status-badge { 
  display:inline-block; 
  padding:6px 16px; 
  border-radius:20px; 
  font-size:0.85rem; 
  font-weight:600; 
  text-transform:capitalize; 
  white-space:nowrap;
}

.status-waiting { 
  background:#facc15; 
  color:#1f2937; 
}

.no-records { 
  text-align:center; 
  padding:3rem; 
  color:#9ca3af; 
  font-size:1.1rem; 
}

/* === Notifications & Next Badge === */
.sms-notification { 
  position:fixed; 
  top:20px; 
  right:20px; 
  padding:16px 24px; 
  border-radius:12px; 
  box-shadow:0 8px 24px rgba(0,0,0,0.15); 
  z-index:9999; 
  opacity:0; 
  transform:translateX(400px); 
  transition:all 0.3s cubic-bezier(0.68,-0.55,0.265,1.55); 
  font-weight:500; 
  max-width:400px; 
}

.sms-notification.show { 
  opacity:1; 
  transform:translateX(0); 
}

.sms-notification-success { 
  background:linear-gradient(135deg,#10b981 0%,#059669 100%); 
  color:white; 
  border:2px solid #059669; 
}

.sms-notification-error { 
  background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%); 
  color:white; 
  border:2px solid #dc2626; 
}

.sms-notification-info { 
  background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%); 
  color:white; 
  border:2px solid #2563eb; 
}

.next-in-line { 
  background: linear-gradient(135deg,#fef3c7 0%,#fde68a 100%) !important; 
  font-weight:600; 
}

.next-badge { 
  display:inline-block; 
  background:#f59e0b; 
  color:white; 
  padding:4px 12px; 
  border-radius:12px; 
  font-size:0.75rem; 
  font-weight:700; 
  text-transform:uppercase;
  animation:pulse 2s infinite; 
}

@keyframes pulse { 
  0%,100% { opacity:1; } 
  50% { opacity:0.6; } 
}

/* Smooth transitions */
.queue-table tbody tr { opacity: 1; transition: opacity 0.3s ease; }
.serving-cards-container { transition: opacity 0.3s ease; }

/* ========================================
   RESPONSIVE DESIGN - ALL DEVICES
   ======================================== */

@media screen and (max-width: 1024px) {
  body { padding:1.5rem; }
  .now-serving-title { font-size:1.8rem; }
  .serving-name { font-size:2rem; }
  .serving-tricycle { font-size:1.3rem; }
  .serving-profile { width:100px; height:100px; }
  .queue-table { min-width:650px; }
}

@media screen and (max-width: 768px) {
  body { padding:1rem; }
  .now-serving-section { padding:1.5rem; }
  .now-serving-title { font-size:1.5rem; margin-bottom:1.5rem; }
  .serving-cards-container { gap:1rem; }
  .serving-card { flex-direction:column; text-align:center; padding:1.5rem; gap:1rem; }
  .serving-info { text-align:center; }
  .serving-name { font-size:1.8rem; }
  .serving-tricycle { font-size:1.2rem; }
  .serving-profile { width:90px; height:90px; }
  .queue-number-badge { font-size:1rem; padding:6px 18px; }
  .queue-section { padding:1rem; }
  .table-responsive { margin:0 -1rem; padding:0 1rem; }
  .queue-table { font-size:0.85rem; min-width:600px; }
  .queue-table th, .queue-table td { padding:0.75rem 0.5rem; }
  .queue-table th { font-size:0.8rem; }
  .driver-pic { width:40px; height:40px; }
  .queue-number-cell { font-size:1.2rem; }
  .status-badge { font-size:0.75rem; padding:4px 12px; }
  .sms-notification { right:10px; left:10px; max-width:none; }
}

@media screen and (max-width: 480px) {
  body { padding:0.75rem; }
  .now-serving-section { padding:1rem; }
  .now-serving-title { font-size:1.3rem; margin-bottom:1rem; letter-spacing:1px; }
  .serving-card { padding:1rem; gap:0.75rem; }
  .serving-profile { width:80px; height:80px; }
  .serving-name { font-size:1.5rem; }
  .serving-tricycle { font-size:1rem; }
  .queue-number-badge { font-size:0.9rem; padding:5px 15px; }
  .queue-section { padding:0.75rem; }
  .table-responsive { margin:0 -0.75rem; padding:0 0.75rem; }
  .queue-table { font-size:0.75rem; min-width:550px; }
  .queue-table th, .queue-table td { padding:0.6rem 0.4rem; }
  .queue-table th { font-size:0.7rem; }
  .driver-pic { width:35px; height:35px; }
  .queue-number-cell { font-size:1rem; }
  .status-badge { font-size:0.7rem; padding:3px 10px; }
  .no-serving, .no-records { font-size:1rem; padding:1.5rem; }
}
</style>
</head>
<body>
<div class="container">

<!-- Now Serving -->
<div class="now-serving-section">
  <h2 class="now-serving-title">Now Serving</h2>
  <div class="no-serving">Loading...</div>
</div>

<!-- Remaining Queue Table -->
<div class="queue-section">
  <div class="table-responsive">
    <table class="queue-table">
      <thead>
        <tr>
          <th>Queue #</th>
          <th>Profile</th>
          <th>Driver Name</th>
          <th>Tricycle No.</th>
          <th>In Queue</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="queue-body">
        <tr><td colspan="6" class="no-records">Loading queue data...</td></tr>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- jQuery MUST load first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../assets/js/LQ/LQ.js"></script>
</body>
</html>
