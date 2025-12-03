let currentDispatchQueueId = null;
let previousQueueState = null;
let lastSMSSentTo = null; // Track last SMS recipient
let smsInProgress = false; // Prevent duplicate sends

console.log('🚀 LITODA Queue System Starting...');
console.log('📍 jQuery loaded:', typeof jQuery !== 'undefined');

// ================================
// SEND SMS TO NEXT DRIVER
// ================================
function sendNextDriverSMS(driver) {
  // Prevent duplicate SMS sends
  if (smsInProgress) {
    console.log('⏳ SMS already in progress, skipping...');
    return;
  }

  // Check if SMS was already sent to this driver
  if (lastSMSSentTo === driver.id) {
    console.log('✅ SMS already sent to driver:', driver.id);
    return;
  }

  smsInProgress = true;
  console.log('📤 Sending SMS to driver:', driver.id);

  $.ajax({
    url: '../../api/sms/send_sms.php',
    type: 'POST',
    data: {
      driver_id: driver.id,
      driver_name: driver.firstname + " " + driver.lastname,
      tricycle_number: driver.tricycle_number,
      contact_no: driver.contact_no
    },
    dataType: 'json',
    success: function(response) {
      console.log("📩 SMS Response:", response);
      
      if (response.success) {
        lastSMSSentTo = driver.id; // Mark as sent
        showNotification(`📩 SMS sent to ${driver.firstname} ${driver.lastname}`, 'success');
      } else if (response.duplicate) {
        console.log('⚠️ Duplicate SMS prevented by server');
        lastSMSSentTo = driver.id; // Mark as sent to prevent retries
        showNotification(`✓ ${driver.firstname} ${driver.lastname} already notified`, 'info');
      } else {
        console.error('❌ SMS Failed:', response.message);
        showNotification(`⚠ SMS failed: ${response.message}`, 'error');
      }
    },
    error: function(xhr, status, error) {
      console.error("❌ SMS AJAX Error:", error);
      showNotification("❌ Failed to send SMS (connection error)", "error");
    },
    complete: function() {
      smsInProgress = false;
    }
  });
}

// ================================
// DETECT CHANGES IN QUEUE
// ================================
function detectQueueChanges(currentData) {
  if (!previousQueueState) {
    previousQueueState = currentData;
    return { hasChanges: false };
  }

  const prevIds = previousQueueState.map(d => d.id).sort();
  const currIds = currentData.map(d => d.id).sort();

  const added = currIds.filter(id => !prevIds.includes(id));
  const removed = prevIds.filter(id => !currIds.includes(id));

  const hasChanges = added.length > 0 || removed.length > 0;

  if (hasChanges) {
    console.log('🔄 Queue changes detected:', { added, removed });
    
    if (added.length > 0) {
      const newDriver = currentData.find(d => d.id === added[0]);
      if (newDriver) {
        const name = `${newDriver.firstname || ''} ${newDriver.lastname || ''}`.trim();
        showNotification(`✅ ${name} joined the queue`, 'success');
      }
    }
    
    if (removed.length > 0) {
      // Reset SMS tracking when queue changes
      const removedDriver = previousQueueState.find(d => d.id === removed[0]);
      if (removedDriver && lastSMSSentTo === removedDriver.id) {
        console.log('🔄 Resetting SMS tracking due to queue change');
        lastSMSSentTo = null; // Reset to allow next driver notification
      }
      showNotification(`🚗 Driver dispatched/removed from queue`, 'info');
    }
  }

  previousQueueState = currentData;
  return { hasChanges, added, removed };
}

// ================================
// LOAD QUEUE DATA DYNAMICALLY
// ================================
function loadQueueData() {
  console.log('🔄 Fetching queue data...');
  
  $.ajax({
    url: '../../api/auth/LQ.php',
    type: 'GET',
    data: { action: 'fetch' },
    dataType: 'json',
    success: function(response) {
      console.log('✅ Queue data received:', response);
      
      if (response.success && response.data.length > 0) {
        const changes = detectQueueChanges(response.data);

        let onqueueDrivers = response.data.filter(d => d.status === 'Onqueue');

        if (onqueueDrivers.length > 0) {
          let servingDriver = onqueueDrivers[0];
          updateServingSection(servingDriver);

          let remainingDrivers = onqueueDrivers.slice(1);
          if (remainingDrivers.length > 0) {

            // 📩 SEND SMS TO NEXT DRIVER (only if not already sent)
            const nextDriver = remainingDrivers[0];
            if (nextDriver && nextDriver.contact_no) {
              // Only send SMS if queue changed or it's a new "next" driver
              if (changes.hasChanges || lastSMSSentTo !== nextDriver.id) {
                sendNextDriverSMS(nextDriver);
              }
            }

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
      console.error('❌ Error loading queue:', error);
      showNotification('❌ Connection error. Retrying...', 'error');
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
    <h2 class="now-serving-title">Now Serving</h2>
    <div class="serving-cards-container">
      <div class="serving-card">
        <div class="queue-number-badge">1</div>
        <img src="${profileImg}" alt="Profile" class="serving-profile" onerror="this.src='../../assets/img/default-profile.png'">
        <div class="serving-info">
          <div class="serving-name">${driverName}</div>
          <div class="serving-tricycle">${tricycleNo}</div>
        </div>
      </div>
    </div>
  `;

  $('.now-serving-section').html(servingHTML);
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
// UPDATE QUEUE TABLE
// ================================
function updateQueueTable(drivers) {
  let html = '';
  drivers.forEach((driver, index) => {
    const queueNumber = index + 2;
    const profileImg = driver.profile_pic ? '../../' + driver.profile_pic : '../../assets/img/default-profile.png';
    const driverName = (driver.firstname || '') + ' ' + (driver.lastname || '');
    const tricycleNo = driver.tricycle_number || driver.tricycle_no || 'N/A';
    const inQueueTime = formatQueueTime(driver.queued_at);
    const nextClass = index === 0 ? ' next-in-line' : '';

    html += `
      <tr class="${nextClass}" data-driver-id="${driver.id}">
        <td class="queue-number-cell">${queueNumber}</td>
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
// INITIALIZE ON DOCUMENT READY
// ================================
$(document).ready(function() {
  console.log('✅ Document ready - Starting queue system');
  console.log('🔄 Loading initial queue data...');
  
  loadQueueData();
  
  setInterval(function() {
    loadQueueData();
  }, 3000);
  
  console.log('📱 Real-time updates: ACTIVE');
  console.log('⏱️ Refresh interval: 3 seconds');
  console.log('📩 SMS System: PhilSMS Integrated');
});
