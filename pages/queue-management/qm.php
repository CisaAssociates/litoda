<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../../database/db.php');
require_once '../../api/auth/auth_guard.php';

// Fetch the driver currently driving (first in queue - "Now Driving")
$servingSql = "
    SELECT q.id, q.status, q.queued_at,
           d.firstname, d.lastname, d.tricycle_number, d.profile_pic
    FROM queue q
    LEFT JOIN drivers d ON q.driver_id = d.id
    WHERE q.status = 'Onqueue'
    AND DATE(q.queued_at) = CURDATE()
    ORDER BY q.queued_at ASC
    LIMIT 1
";
$servingResult = $conn->query($servingSql);
$servingDriver = $servingResult && $servingResult->num_rows > 0 ? $servingResult->fetch_assoc() : null;

// Fetch remaining queued drivers (excluding the first one)
$queueSql = "
    SELECT q.id, q.status, q.queued_at,
           d.firstname, d.lastname, d.tricycle_number, d.profile_pic
    FROM queue q
    LEFT JOIN drivers d ON q.driver_id = d.id
    WHERE q.status = 'Onqueue'
    AND DATE(q.queued_at) = CURDATE()
    ORDER BY q.queued_at ASC
    LIMIT 999 OFFSET 1
";
$queueResult = $conn->query($queueSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Queue Management - LTODA</title>
  <link rel="stylesheet" href="../../assets/css/navbar/navbar.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
body {
  margin: 0;
  font-family: "Poppins", sans-serif;
  background: #f5f5f5;
}

.queue-container {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 1.5rem;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}

.current-serving {
  padding: 2rem;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  border: 2px solid #10b981;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  text-align: center;
}

.current-serving h3 {
  margin-bottom: 1rem;
  font-size: 1.5rem;
  color: #065f46;
}

.serving-card {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  background: #fff;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  flex-wrap: wrap;
}

.serving-pic {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  object-fit: cover;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.serving-info h4 {
  margin: 0;
  font-size: 1.2rem;
  color: #1f2937;
}

.serving-info p {
  margin: 0.5rem 0 0 0;
  font-size: 1rem;
  color: #6b7280;
}

/* Table Container for Horizontal Scroll */
.table-container {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.queue-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
  min-width: 600px;
}

.queue-table th,
.queue-table td {
  padding: 10px;
  text-align: center;
  border: 1px solid #ddd;
}

.queue-table th {
  background: #f0fdf4;
  color: #065f46;
  font-weight: 600;
  white-space: nowrap;
}

.driver-pic {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
}

.status-badge {
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  background: #fef3c7;
  color: #92400e;
  display: inline-block;
  white-space: nowrap;
}

.dispatch-btn {
  padding: 8px 16px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 0.3s ease;
  font-family: "Poppins", sans-serif;
  white-space: nowrap;
}

.dispatch-btn:hover {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.dispatch-btn:active {
  transform: translateY(0);
}

.no-records {
  text-align: center;
  padding: 1rem;
  color: #9ca3af;
}

/* Modal Styles */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  animation: fadeIn 0.3s ease;
}

.modal-content {
  background-color: #ffffff;
  margin: 15% auto;
  padding: 2rem;
  border-radius: 12px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  animation: slideIn 0.3s ease;
  text-align: center;
}

.modal-header {
  font-size: 1.3rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1rem;
}

.modal-body {
  font-size: 1rem;
  color: #6b7280;
  margin-bottom: 1.5rem;
}

.modal-buttons {
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}

.modal-btn {
  padding: 10px 24px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 600;
  font-family: "Poppins", sans-serif;
  transition: all 0.3s ease;
}

.modal-btn-confirm {
  background: #10b981;
  color: white;
}

.modal-btn-confirm:hover {
  background: #059669;
}

.modal-btn-cancel {
  background: #e5e7eb;
  color: #374151;
}

.modal-btn-cancel:hover {
  background: #d1d5db;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from { 
    transform: translateY(-50px);
    opacity: 0;
  }
  to { 
    transform: translateY(0);
    opacity: 1;
  }
}

/* ========================================
   RESPONSIVE DESIGN - ALL DEVICES
   ======================================== */

/* Tablet Devices (768px - 1024px) */
@media screen and (max-width: 1024px) {
  .queue-container {
    margin: 1.5rem;
    padding: 1.25rem;
  }

  .current-serving {
    padding: 1.75rem;
  }

  .serving-pic {
    width: 85px;
    height: 85px;
  }

  .queue-table {
    font-size: 0.9rem;
  }
}

/* Tablet and Mobile Large (481px - 768px) */
@media screen and (max-width: 768px) {
  .queue-container {
    margin: 1rem;
    padding: 1rem;
  }

  .current-serving {
    padding: 1.5rem;
  }

  .current-serving h3 {
    font-size: 1.2rem;
  }

  .serving-card {
    flex-direction: column;
    padding: 1rem;
    gap: 15px;
  }

  .serving-pic {
    width: 80px;
    height: 80px;
  }

  .serving-info h4 {
    font-size: 1.1rem;
  }

  .serving-info p {
    font-size: 0.9rem;
  }

  .table-container {
    margin: 0 -1rem;
    padding: 0 1rem;
  }

  .queue-table {
    font-size: 0.85rem;
    min-width: 550px;
  }

  .queue-table th,
  .queue-table td {
    padding: 8px 6px;
  }

  .driver-pic {
    width: 40px;
    height: 40px;
  }

  .status-badge {
    font-size: 0.75rem;
    padding: 4px 10px;
  }

  .dispatch-btn {
    padding: 6px 12px;
    font-size: 0.75rem;
  }

  .modal-content {
    width: 85%;
    padding: 1.5rem;
  }

  .modal-header {
    font-size: 1.2rem;
  }

  .modal-body {
    font-size: 0.95rem;
  }

  .modal-btn {
    padding: 9px 20px;
    font-size: 0.85rem;
  }
}

/* Mobile Small (321px - 480px) */
@media screen and (max-width: 480px) {
  .queue-container {
    margin: 0.5rem;
    padding: 0.75rem;
  }

  .current-serving {
    padding: 1rem;
  }

  .current-serving h3 {
    font-size: 1rem;
    margin-bottom: 0.75rem;
  }

  .serving-card {
    padding: 0.75rem;
    gap: 12px;
  }

  .serving-pic {
    width: 70px;
    height: 70px;
  }

  .serving-info h4 {
    font-size: 1rem;
  }

  .serving-info p {
    font-size: 0.85rem;
  }

  .table-container {
    margin: 0 -0.75rem;
    padding: 0 0.75rem;
  }

  .queue-table {
    font-size: 0.75rem;
    min-width: 500px;
  }

  .queue-table th,
  .queue-table td {
    padding: 6px 4px;
  }

  .queue-table th {
    font-size: 0.7rem;
  }

  .driver-pic {
    width: 35px;
    height: 35px;
  }

  .status-badge {
    font-size: 0.7rem;
    padding: 3px 8px;
  }

  .dispatch-btn {
    padding: 5px 10px;
    font-size: 0.7rem;
  }

  .modal-content {
    width: 90%;
    max-width: 320px;
    padding: 1.25rem;
    margin: 25% auto;
  }

  .modal-header {
    font-size: 1.1rem;
  }

  .modal-body {
    font-size: 0.9rem;
    margin-bottom: 1.25rem;
  }

  .modal-buttons {
    flex-direction: column;
    gap: 8px;
  }

  .modal-btn {
    width: 100%;
    padding: 10px;
    font-size: 0.85rem;
  }
}

/* Extra Small Devices (< 321px) */
@media screen and (max-width: 320px) {
  .queue-container {
    margin: 0.25rem;
    padding: 0.5rem;
  }

  .current-serving {
    padding: 0.75rem;
  }

  .current-serving h3 {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
  }

  .serving-card {
    padding: 0.5rem;
    gap: 10px;
  }

  .serving-pic {
    width: 60px;
    height: 60px;
  }

  .serving-info h4 {
    font-size: 0.9rem;
  }

  .serving-info p {
    font-size: 0.75rem;
  }

  .table-container {
    margin: 0 -0.5rem;
    padding: 0 0.5rem;
  }

  .queue-table {
    font-size: 0.7rem;
    min-width: 450px;
  }

  .queue-table th,
  .queue-table td {
    padding: 5px 3px;
  }

  .driver-pic {
    width: 30px;
    height: 30px;
  }

  .status-badge {
    font-size: 0.65rem;
    padding: 2px 6px;
  }

  .dispatch-btn {
    padding: 4px 8px;
    font-size: 0.65rem;
  }

  .modal-content {
    width: 95%;
    padding: 1rem;
  }

  .modal-header {
    font-size: 1rem;
  }

  .modal-body {
    font-size: 0.85rem;
  }

  .modal-btn {
    padding: 8px;
    font-size: 0.8rem;
  }
}

/* Landscape Mode for Mobile */
@media screen and (max-height: 500px) and (orientation: landscape) {
  .modal-content {
    margin: 5% auto;
    max-height: 90vh;
    overflow-y: auto;
  }

  .current-serving {
    padding: 1rem;
  }

  .serving-card {
    flex-direction: row;
    gap: 15px;
  }
}

/* Touch Device Improvements */
@media (hover: none) and (pointer: coarse) {
  .dispatch-btn,
  .modal-btn {
    min-height: 44px;
    min-width: 44px;
  }

  .queue-table {
    -webkit-overflow-scrolling: touch;
  }
}

/* Print Styles */
@media print {
  .dispatch-btn,
  .modal {
    display: none;
  }

  .queue-container {
    box-shadow: none;
    margin: 0;
    padding: 1rem;
  }

  .current-serving {
    page-break-inside: avoid;
  }

  .queue-table {
    page-break-inside: auto;
  }

  .queue-table tr {
    page-break-inside: avoid;
  }
}
  </style>
</head>
<body>
<?php include('../../assets/components/navbar.php'); ?>

<div class="queue-container">
  <div class="current-serving" id="serving-section">
    <h3>Now Serving</h3>
    
    <?php if ($servingDriver): ?>
      <div class="serving-card">
        <img src="<?php 
          echo !empty($servingDriver['profile_pic']) && file_exists('../../' . $servingDriver['profile_pic']) 
              ? '../../' . $servingDriver['profile_pic'] 
              : '../../assets/img/default-profile.png'; 
        ?>" 
        alt="Profile" class="serving-pic">
        
        <div class="serving-info">
          <h4><?php echo htmlspecialchars($servingDriver['firstname'] . " " . $servingDriver['lastname']); ?></h4>
          <p><?php echo htmlspecialchars($servingDriver['tricycle_number'] ?? 'N/A'); ?></p>
        </div>
        
        <button class="dispatch-btn" onclick="dispatchDriver(<?php echo $servingDriver['id']; ?>)" style="margin-left: 20px;">
          <i class="fas fa-paper-plane"></i> Dispatch
        </button>
      </div>
    <?php else: ?>
      <p class="no-records"></p>
    <?php endif; ?>
  </div>

  <!-- Table wrapper for responsive scrolling -->
  <div class="table-container">
    <table class="queue-table">
      <thead>
        <tr>
          <th>Profile</th>
          <th>Driver Name</th>
          <th>Tricycle No.</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="queue-body">
        <?php if ($queueResult && $queueResult->num_rows > 0): ?>
          <?php while ($row = $queueResult->fetch_assoc()): ?>
            <tr>
              <td>
                <img src="<?php 
                  echo !empty($row['profile_pic']) && file_exists('../../' . $row['profile_pic']) 
                      ? '../../' . $row['profile_pic'] 
                      : '../../assets/img/default-profile.png'; 
                ?>" 
                alt="Profile" class="driver-pic">
              </td>
              <td><?php echo htmlspecialchars($row['firstname'] . " " . $row['lastname']); ?></td>
              <td><?php echo htmlspecialchars($row['tricycle_number'] ?? 'N/A'); ?></td>
              <td><span class="status-badge">Waiting</span></td>
              <td>
                <button class="dispatch-btn" onclick="dispatchDriver(<?php echo $row['id']; ?>)">
                  <i></i> Dispatch
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" class="no-records"></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Dispatch Confirmation Modal -->
<div id="dispatchModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <i class="" style="color: #10b981; margin-right: 8px;"></i>
      Confirm Dispatch
    </div>
    <div class="modal-body">
      Are you sure you want to dispatch this driver?
    </div>
    <div class="modal-buttons">
      <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
      <button class="modal-btn modal-btn-confirm" onclick="confirmDispatch()">Dispatch</button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentDispatchQueueId = null;
let refreshInterval;

function dispatchDriver(queueId) {
  currentDispatchQueueId = queueId;
  document.getElementById('dispatchModal').style.display = 'block';
  clearInterval(refreshInterval);
}

function closeModal() {
  document.getElementById('dispatchModal').style.display = 'none';
  currentDispatchQueueId = null;
  startAutoRefresh();
}

window.onclick = function(event) {
  const modal = document.getElementById('dispatchModal');
  if (event.target == modal) {
    closeModal();
  }
}

function confirmDispatch() {
  if (!currentDispatchQueueId) return;

  $.ajax({
    url: '../../api/auth/dispatch_driver.php',
    type: 'POST',
    data: { 
      queue_id: currentDispatchQueueId,
      action: 'dispatch'
    },
    dataType: 'json',
    success: function(response) {
      closeModal();
      
      if (response.success) {
        showNotification('✅ Driver dispatched successfully!', 'success');
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showNotification('❌ Error: ' + (response.message || 'Failed to dispatch driver'), 'error');
      }
    },
    error: function(xhr, status, error) {
      closeModal();
      console.error('Dispatch error:', error);
      showNotification('❌ Error: Unable to dispatch driver. Please try again.', 'error');
    }
  });
}

function showNotification(message, type) {
  $('.notification-toast').remove();
  
  const bgColor = type === 'success' ? '#10b981' : '#ef4444';
  const notification = $('<div>', {
    class: 'notification-toast',
    html: message,
    css: {
      position: 'fixed',
      top: '20px',
      right: '20px',
      background: bgColor,
      color: 'white',
      padding: '16px 24px',
      borderRadius: '8px',
      boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
      zIndex: 10000,
      fontSize: '0.95rem',
      fontWeight: '600',
      animation: 'slideInRight 0.3s ease',
      maxWidth: '400px'
    }
  });
  
  $('body').append(notification);
  
  if ($('#notification-animation').length === 0) {
    $('<style id="notification-animation">@keyframes slideInRight { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }</style>').appendTo('head');
  }
  
  setTimeout(() => {
    notification.fadeOut(300, function() { $(this).remove(); });
  }, 4000);
}

function startAutoRefresh() {
  clearInterval(refreshInterval);
  refreshInterval = setInterval(function() {
    location.reload();
  }, 5000);
}

$(document).ready(function() {
  console.log('🚀 LTODA Queue Management System Started');
  console.log('🔄 Auto-refresh active (every 5 seconds)');
  startAutoRefresh();
});
</script>
</body>
</html>