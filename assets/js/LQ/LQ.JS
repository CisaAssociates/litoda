let currentDispatchQueueId = null;

// ================================
// SEND SMS TO NEXT DRIVER AFTER DISPATCH
// ================================
function sendSMSToNextDriverAfterDispatch() {
  console.log('🔍 Finding next driver to notify...');

  $.ajax({
    url: '../../api/auth/LQ.php',
    type: 'GET',
    data: { action: 'fetch' },
    dataType: 'json',
    success: function(response) {
      if (response.success && response.data.length > 0) {
        let onqueueDrivers = response.data.filter(d => d.status === 'Onqueue');

        // The second driver in queue (index 1) is the "next"
        if (onqueueDrivers.length > 1) {
          const nextDriver = onqueueDrivers[1];
          console.log('📱 Sending SMS to:', nextDriver);
          sendSMSToDriver(nextDriver);
        } else {
          console.log('ℹ️ No next driver to notify');
        }
      }
    },
    error: function(xhr, status, error) {
      console.error('Error fetching queue for SMS:', error);
    }
  });
}

// ================================
// SEND SMS TO A SPECIFIC DRIVER
// ================================
function sendSMSToDriver(driver) {
  if (!driver || !driver.contact_no) {
    console.warn('⚠️ Driver has no contact number');
    showNotification('⚠️ Next driver has no contact number', 'error');
    return;
  }

  const driverName = (driver.firstname || '') + ' ' + (driver.lastname || '');
  const tricycleNo = driver.tricycle_number || driver.tricycle_no || '';

  console.log('📤 Sending SMS to:', driverName, 'at', driver.contact_no);

  // Show sending notification
  showNotification(`📱 Sending SMS to ${driverName}...`, 'info');

  $.ajax({
    url: '../../api/auth/send_sms.php',
    type: 'POST',
    data: {
      driver_id: driver.id,
      driver_name: driverName,
      tricycle_number: tricycleNo,
      contact_no: driver.contact_no
    },
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        console.log('✅ SMS sent successfully to:', driverName);
        showNotification(`✅ SMS sent to ${driverName} - You are NEXT!`, 'success');
      } else if (response.duplicate) {
        console.log('ℹ️ SMS already sent recently to:', driverName);
        showNotification(`ℹ️ ${driverName} was already notified`, 'info');
      } else {
        console.error('❌ SMS failed:', response.message);
        showNotification(`❌ Failed to send SMS: ${response.message}`, 'error');
      }
    },
    error: function(xhr, status, error) {
      console.error('❌ SMS request error:', error);
      showNotification('❌ Error sending SMS. Please check connection.', 'error');
    }
  });
}

// ================================
// LOAD QUEUE DATA DYNAMICALLY
// ================================
function loadQueueData() {
  $.ajax({
    url: '../../api/auth/LQ.php',
    type: 'GET',
    data: { action: 'fetch' },
    dataType: 'json',
    success: function(response) {
      if (response.success && response.data.length > 0) {
        let onqueueDrivers = response.data.filter(d => d.status === 'Onqueue');

        if (onqueueDrivers.length > 0) {
          let servingDriver = onqueueDrivers[0];
          updateServingSection(servingDriver);

          let remainingDrivers = onqueueDrivers.slice(1);
          if (remainingDrivers.length > 0) {
            updateQueueTable(remainingDrivers);
          } else {
            showEmptyQueue();
          }
        } else {
          showNoServing();
          showEmptyQueue();
        }
      } else {
        showNoServing();
        showEmptyQueue();
      }
    },
    error: function(xhr, status, error) {
      console.error('Error loading queue data:', error);
    }
  });
}

// ================================
// SHOW NOTIFICATION
// ================================
function showNotification(message, type) {
  $('.sms-notification').remove();

  let icon = 'info-circle';
  if (type === 'success') icon = 'check-circle';
  else if (type === 'error') icon = 'exclamation-circle';
  else if (type === 'info') icon = 'info-circle';

  const notification = $('<div>', {
    class: `sms-notification sms-notification-${type}`,
    html: `
      <div style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-${icon}" style="font-size: 20px;"></i>
        <span>${message}</span>
      </div>
    `
  });

  $('body').append(notification);

  setTimeout(() => notification.addClass('show'), 100);

  setTimeout(() => {
    notification.removeClass('show');
    setTimeout(() => notification.remove(), 300);
  }, 5000);
}

// ================================
// UPDATE NOW SERVING SECTION
// ================================
function updateServingSection(driver) {
  const profileImg = driver.profile_pic ? '../../' + driver.profile_pic : '../../assets/img/default-profile.png';
  const driverName = (driver.firstname || '') + ' ' + (driver.lastname || '');
  const tricycleNo = driver.tricycle_number || driver.tricycle_no || 'N/A';

  const servingHTML = `
    <div class="serving-card">
      <div class="queue-number-badge">#1</div>
      <img src="${profileImg}" alt="Profile" class="serving-profile" onerror="this.src='../../assets/img/default-profile.png'">
      <div class="serving-info">
        <div class="serving-name">${driverName}</div>
        <div class="serving-tricycle">${tricycleNo}</div>
      </div>
    </div>
  `;

  $('.now-serving-section').html(`
    <h2 class="now-serving-title">Now Serving</h2>
    ${servingHTML}
  `);
}

function showNoServing() {
  $('.now-serving-section').html(`
    <h2 class="now-serving-title">Now Serving</h2>
    <div class="no-serving"></div>
  `);
}

function formatQueueTime(queuedAt) {
  if (!queuedAt) return 'N/A';
  const date = new Date(queuedAt);
  let hours = date.getHours();
  const minutes = date.getMinutes();
  const ampm = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12;
  hours = hours ? hours : 12;
  const minutesStr = minutes < 10 ? '0' + minutes : minutes;
  return hours + ':' + minutesStr + ' ' + ampm;
}

// ================================
// UPDATE REMAINING QUEUE TABLE
// ================================
function updateQueueTable(drivers) {
  let html = '';
  drivers.forEach((driver, index) => {
    const queueNumber = index + 2; // #2 onward
    const profileImg = driver.profile_pic ? '../../' + driver.profile_pic : '../../assets/img/default-profile.png';
    const driverName = (driver.firstname || '') + ' ' + (driver.lastname || '');
    const tricycleNo = driver.tricycle_number || driver.tricycle_no || 'N/A';
    const inQueueTime = formatQueueTime(driver.queued_at);
    const nextClass = index === 0 ? ' next-in-line' : '';

    html += `
      <tr class="${nextClass}">
        <td class="queue-number-cell">#${queueNumber}</td>
        <td><img src="${profileImg}" alt="Profile" class="driver-pic" onerror="this.src='../../assets/img/default-profile.png'"></td>
        <td>${driverName}${index === 0 ? ' <span class="next-badge">NEXT</span>' : ''}</td>
        <td>${tricycleNo}</td>
        <td>${inQueueTime}</td>
        <td><span class="status-badge status-waiting">Waiting</span></td>
      </tr>
    `;
  });

  $('#queue-body').html(html.length > 0 ? html : '<tr><td colspan="6" class="no-records">No drivers waiting in queue</td></tr>');
}

function showEmptyQueue() {
  $('#queue-body').html('<tr><td colspan="6" class="no-records">No drivers waiting in queue</td></tr>');
}

// ================================
// ADD STYLES FOR NOTIFICATIONS & NEXT BADGE
// ================================
function addEnhancedStyles() {
  const styles = `
    <style>
      .sms-notification { position: fixed; top: 20px; right: 20px; padding: 16px 24px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); z-index: 9999; opacity: 0; transform: translateX(400px); transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55); font-weight: 500; max-width: 400px; }
      .sms-notification.show { opacity: 1; transform: translateX(0); }
      .sms-notification-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: 2px solid #059669; }
      .sms-notification-error { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: 2px solid #dc2626; }
      .sms-notification-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: 2px solid #2563eb; }
      .next-in-line { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important; font-weight: 600; }
      .next-badge { display: inline-block; background: #f59e0b; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; margin-left: 8px; animation: pulse 2s infinite; }
      @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
    </style>
  `;

  if ($('#sms-notification-styles').length === 0) {
    $(styles).attr('id', 'sms-notification-styles').appendTo('head');
  }
}

// ================================
// INITIALIZE
// ================================
$(document).ready(function() {
  console.log('🚀 LITODA Queue System - SMS After Dispatch');

  addEnhancedStyles();
  loadQueueData();
  setInterval(loadQueueData, 5000);

  console.log('📱 SMS: Will be sent after dispatch');
  console.log('🔄 Auto-refresh: Every 5 seconds');
});
