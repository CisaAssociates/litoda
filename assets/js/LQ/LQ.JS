let currentDispatchQueueId = null;
let previousQueueState = null;
let lastSMSSentTo = null;
let smsInProgress = false;
let isUpdating = false; // Prevent concurrent updates

console.log('🚀 LITODA Queue System Starting...');
console.log('📍 jQuery loaded:', typeof jQuery !== 'undefined');

// ================================
// SEND SMS TO NEXT DRIVER
// ================================
function sendNextDriverSMS(driver) {
  if (smsInProgress) {
    console.log('⏳ SMS already in progress, skipping...');
    return;
  }

  if (lastSMSSentTo === driver.id) {
    console.log('✅ SMS already sent to driver:', driver.id);
    return;
  }

  smsInProgress = true;
  console.log('📤 Sending SMS to driver:', driver.id);

  $.ajax({
    url: '../../api/auth/send_sms.php',
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
        lastSMSSentTo = driver.id;
        showNotification(`📩 SMS sent to ${driver.firstname} ${driver.lastname}`, 'success');
      } else if (response.duplicate) {
        console.log('⚠️ Duplicate SMS prevented by server');
        lastSMSSentTo = driver.id;
        showNotification(`✓ ${driver.firstname} ${driver.lastname} already notified`, 'info');
      } else {
        console.error('❌ SMS Failed:', response.message);
        showNotification(`⚠ SMS failed: ${response.message}`, 'error');
      }
    },
    error: function(xhr, status, error) {
      console.error("❌ SMS AJAX Error:", error);
      showNotification("❌ Failed to send SMS", "error");
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
    return { hasChanges: false, added: [], removed: [], orderChanged: false };
  }

  const prevIds = previousQueueState.map(d => d.id);
  const currIds = currentData.map(d => d.id);

  const added = currIds.filter(id => !prevIds.includes(id));
  const removed = prevIds.filter(id => !currIds.includes(id));
  
  // Check if order changed (without additions/removals)
  const orderChanged = prevIds.length === currIds.length && 
                       prevIds.some((id, idx) => id !== currIds[idx]);

  const hasChanges = added.length > 0 || removed.length > 0 || orderChanged;

  if (hasChanges) {
    console.log('🔄 Queue changes detected:', { 
      added: added.length, 
      removed: removed.length,
      orderChanged 
    });
    
    // New driver added
    if (added.length > 0) {
      added.forEach(id => {
        const newDriver = currentData.find(d => d.id === id);
        if (newDriver) {
          const name = `${newDriver.firstname || ''} ${newDriver.lastname || ''}`.trim();
          showNotification(`✅ ${name} joined the queue`, 'success');
        }
      });
    }
    
    // Driver removed/dispatched
    if (removed.length > 0) {
      removed.forEach(id => {
        const removedDriver = previousQueueState.find(d => d.id === id);
        if (removedDriver && lastSMSSentTo === removedDriver.id) {
          console.log('🔄 Resetting SMS tracking - driver removed');
          lastSMSSentTo = null;
        }
      });
      showNotification(`🚗 Driver dispatched from queue`, 'info');
    }
  }

  previousQueueState = JSON.parse(JSON.stringify(currentData)); // Deep clone
  return { hasChanges, added, removed, orderChanged };
}

// ================================
// LOAD QUEUE DATA (NO PAGE RELOAD)
// ================================
function loadQueueData() {
  // Prevent concurrent updates
  if (isUpdating) {
    console.log('⏸️ Update already in progress, skipping...');
    return;
  }

  isUpdating = true;
  console.log('🔄 Fetching queue data...');
  
  $.ajax({
    url: '../../api/auth/LQ.php',
    type: 'GET',
    data: { action: 'fetch' },
    dataType: 'json',
    cache: false, // Prevent caching
    success: function(response) {
      console.log('✅ Queue data received:', response.data?.length || 0, 'drivers');
      
      if (response.success && response.data && response.data.length > 0) {
        const changes = detectQueueChanges(response.data);
        const onqueueDrivers = response.data.filter(d => d.status === 'Onqueue');

        if (onqueueDrivers.length > 0) {
          const servingDriver = onqueueDrivers[0];
          updateServingSection(servingDriver);

          const remainingDrivers = onqueueDrivers.slice(1);
          if (remainingDrivers.length > 0) {
            const nextDriver = remainingDrivers[0];
            
            // Send SMS to next driver only on significant changes
            if (nextDriver && nextDriver.contact_no) {
              if ((changes.hasChanges && changes.removed.length > 0) || 
                  lastSMSSentTo !== nextDriver.id) {
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
      // Only show error notification if it's not a timeout
      if (status !== 'timeout') {
        showNotification('❌ Connection error. Retrying...', 'error');
      }
    },
    complete: function() {
      isUpdating = false;
    },
    timeout: 5000 // 5 second timeout
  });
}

// ================================
// SHOW NOTIFICATION (IMPROVED)
// ================================
function showNotification(message, type) {
  // Remove existing notifications of the same type to avoid spam
  $(`.sms-notification-${type}`).remove();

  const icons = {
    success: 'check-circle',
    error: 'exclamation-circle',
    info: 'info-circle'
  };

  const notification = $('<div>', {
    class: `sms-notification sms-notification-${type}`,
    html: `
      <div style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-${icons[type] || 'info-circle'}" style="font-size: 20px;"></i>
        <span>${message}</span>
      </div>
    `
  });

  $('body').append(notification);
  setTimeout(() => notification.addClass('show'), 100);
  setTimeout(() => {
    notification.removeClass('show');
    setTimeout(() => notification.remove(), 300);
  }, 4000);
}

// ================================
// UPDATE NOW SERVING SECTION (SMOOTH)
// ================================
function updateServingSection(driver) {
  const profileImg = driver.profile_pic ? '../../' + driver.profile_pic : '../../assets/img/default-profile.png';
  const driverName = `${driver.firstname || ''} ${driver.lastname || ''}`.trim();
  const tricycleNo = driver.tricycle_number || driver.tricycle_no || 'N/A';

  const servingHTML = `
    <h2 class="now-serving-title">Now Serving</h2>
    <div class="serving-cards-container">
      <div class="serving-card">
        <div class="queue-number-badge">1</div>
        <img src="${profileImg}" alt="Profile" class="serving-profile" 
             onerror="this.src='../../assets/img/default-profile.png'">
        <div class="serving-info">
          <div class="serving-name">${driverName}</div>
          <div class="serving-tricycle">${tricycleNo}</div>
        </div>
      </div>
    </div>
  `;

  // Only update if content changed to avoid flickering
  const currentHTML = $('.now-serving-section').html();
  if (currentHTML !== servingHTML) {
    $('.now-serving-section').html(servingHTML);
  }
}

function showNoServing() {
  const noServingHTML = `
    <h2 class="now-serving-title">Now Serving</h2>
    <div class="no-serving">No driver currently serving</div>
  `;
  
  const currentHTML = $('.now-serving-section').html();
  if (currentHTML !== noServingHTML) {
    $('.now-serving-section').html(noServingHTML);
  }
}

function formatQueueTime(queuedAt) {
  if (!queuedAt) return 'N/A';
  const date = new Date(queuedAt);
  let hours = date.getHours();
  const minutes = date.getMinutes();
  const ampm = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12 || 12;
  const minutesStr = minutes < 10 ? '0' + minutes : minutes;
  return `${hours}:${minutesStr} ${ampm}`;
}

// ================================
// UPDATE QUEUE TABLE (OPTIMIZED)
// ================================
function updateQueueTable(drivers) {
  let html = '';
  
  drivers.forEach((driver, index) => {
    const queueNumber = index + 2;
    const profileImg = driver.profile_pic ? '../../' + driver.profile_pic : '../../assets/img/default-profile.png';
    const driverName = `${driver.firstname || ''} ${driver.lastname || ''}`.trim();
    const tricycleNo = driver.tricycle_number || driver.tricycle_no || 'N/A';
    const inQueueTime = formatQueueTime(driver.queued_at);
    const nextClass = index === 0 ? ' next-in-line' : '';
    const nextBadge = index === 0 ? ' <span class="next-badge">NEXT</span>' : '';

    html += `
      <tr class="${nextClass}" data-driver-id="${driver.id}">
        <td class="queue-number-cell">${queueNumber}</td>
        <td><img src="${profileImg}" alt="Profile" class="driver-pic" 
             onerror="this.src='../../assets/img/default-profile.png'"></td>
        <td class="driver-name-cell">${driverName}${nextBadge}</td>
        <td>${tricycleNo}</td>
        <td>${inQueueTime}</td>
        <td><span class="status-badge status-waiting">Waiting</span></td>
      </tr>
    `;
  });

  const finalHTML = html || '<tr><td colspan="6" class="no-records">No drivers waiting in queue</td></tr>';
  
  // Only update if content changed
  const currentHTML = $('#queue-body').html();
  if (currentHTML !== finalHTML) {
    $('#queue-body').html(finalHTML);
  }
}

function showEmptyQueue() {
  const emptyHTML = '<tr><td colspan="6" class="no-records">No drivers waiting in queue</td></tr>';
  const currentHTML = $('#queue-body').html();
  if (currentHTML !== emptyHTML) {
    $('#queue-body').html(emptyHTML);
  }
}

// ================================
// INITIALIZE ON DOCUMENT READY
// ================================
$(document).ready(function() {
  console.log('✅ Document ready - Starting queue system');
  console.log('🔄 Loading initial queue data...');
  
  // Initial load
  loadQueueData();
  
  // Auto-refresh every 3 seconds (NO PAGE RELOAD)
  setInterval(function() {
    loadQueueData();
  }, 3000);
  
  // Reload on window focus (when user returns to tab)
  $(window).on('focus', function() {
    console.log('👁️ Window focused - refreshing data');
    loadQueueData();
  });
  
  console.log('📱 Real-time updates: ACTIVE');
  console.log('⏱️ Refresh interval: 3 seconds');
  console.log('📩 SMS System: PhilSMS Integrated');
  console.log('🚫 Page reload: DISABLED (AJAX only)');
});
