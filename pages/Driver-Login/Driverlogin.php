<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Recognition - LITODA</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffff 0%, #ffff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .recognition-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
        }

        .card-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 30px;
            text-align: center;
            border-radius: 20px 20px 0 0;
        }

        .card-header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
        }

        .video-container {
            position: relative;
            background: #000;
            aspect-ratio: 4/3;
        }

        #video { 
            width: 100%; 
            height: 100%; 
            object-fit: cover;
            transform: scaleX(-1);
        }
        
        #canvas { display: none; }

        .status-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
            color: white;
            text-align: center;
            font-size: 14px;
        }

        .driver-info {
            padding: 20px 30px;
            background: #f9fafb;
            display: none;
        }

        .driver-info.show { display: block; animation: slideDown 0.3s; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .driver-info h3 {
            color: #10b981;
            font-size: 18px;
            margin-bottom: 15px;
            text-align: center;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-label { color: #6b7280; font-size: 14px; }
        .info-value { color: #1f2937; font-size: 14px; font-weight: 600; }

        .status-message {
            text-align: center;
            font-size: 14px;
            margin-top: 12px;
            font-weight: 600;
            padding: 10px;
            border-radius: 8px;
        }

        .status-success { 
            color: #10b981;
            background: rgba(16,185,129,0.1);
        }
        .status-warning { 
            color: #f59e0b;
            background: rgba(245,158,11,0.1);
        }
        .status-error { 
            color: #ef4444;
            background: rgba(239,68,68,0.1);
        }

        .button-container {
            display: flex;
            gap: 15px;
            padding: 25px 30px 30px;
        }

        .action-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
        }

        .inqueue-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 15px rgba(16,185,129,0.4);
        }

        .inqueue-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16,185,129,0.5);
        }

        .dispatch-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 15px rgba(59,130,246,0.4);
        }

        .dispatch-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59,130,246,0.5);
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 5px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="recognition-card">
        <div class="card-header">
            <h1>Driver Recognition</h1>
        </div>
        
        <div class="video-container">
            <video id="video" autoplay playsinline></video>
            <canvas id="canvas"></canvas>
            <div class="status-overlay" id="statusOverlay">Initializing camera...</div>
        </div>
        
        <div class="driver-info" id="driverInfo">
            <h3>Driver Recognized</h3>
            <div class="info-item">
                <span class="info-label">Name:</span>
                <span class="info-value" id="driverName">-</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tricycle No:</span>
                <span class="info-value" id="driverTricycle">-</span>
            </div>
            <div class="info-item">
                <span class="info-label">Contact:</span>
                <span class="info-value" id="driverContact">-</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value" id="driverStatusValue">-</span>
            </div>
            <div class="status-message" id="driverMessage"></div>
        </div>
        
        <div class="button-container">
            <button class="action-btn inqueue-btn" id="inqueueBt">Inqueue</button>
            <button class="action-btn dispatch-btn" id="dispatchBtn">Dispatch</button>
        </div>
    </div>
    
    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const statusOverlay = document.getElementById('statusOverlay');
        const driverInfo = document.getElementById('driverInfo');
        const driverName = document.getElementById('driverName');
        const driverTricycle = document.getElementById('driverTricycle');
        const driverContact = document.getElementById('driverContact');
        const driverStatusValue = document.getElementById('driverStatusValue');
        const driverMessage = document.getElementById('driverMessage');
        const inqueueBt = document.getElementById('inqueueBt');
        const dispatchBtn = document.getElementById('dispatchBtn');
        
      let currentDriver = null;
        let isProcessing = false;

        const API_URL = FLASK_API_URL;

        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 1280 }, 
                        height: { ideal: 720 },
                        facingMode: 'user',
                        frameRate: { ideal: 30 }
                    } 
                });
                video.srcObject = stream;
                video.onloadedmetadata = () => {
                    statusOverlay.textContent = 'Camera ready. Click Inqueue or Dispatch.';
                };
            } catch (err) {
                statusOverlay.textContent = 'Camera error: ' + err.message;
            }
        }

        async function recognizeFace() {
            await new Promise(resolve => setTimeout(resolve, 200));
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            ctx.save();
            ctx.scale(-1, 1);
            ctx.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
            ctx.restore();
            
            const imageData = canvas.toDataURL('image/jpeg', 0.92);
            
            try {
                const res = await fetch(`${API_URL}/recognize`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ image: imageData })
                });
                return await res.json();
            } catch (error) {
                console.error('API connection error:', error);
                return { success: false, message: 'Cannot connect to face recognition service.' };
            }
        }

        function showDriver(driver, status = 'Available') {
            driverName.textContent = driver.name || 'N/A';
            driverTricycle.textContent = driver.tricycle_number || 'N/A';
            driverContact.textContent = driver.contact_no || 'N/A';
            driverStatusValue.textContent = status;
            driverInfo.classList.add('show');
            currentDriver = driver;
        }

        function showQueueNumber(queueNum) {
            queueNumberBig.textContent = queueNum;
            queueNumberDisplay.classList.add('show');
        }

        function hideQueueNumber() {
            queueNumberDisplay.classList.remove('show');
        }

        function updateDriverMessage(msg, type = 'info') {
            driverMessage.textContent = msg;
            driverMessage.className = `status-message status-${type}`;
        }

        function resetToOriginal() {
            driverInfo.classList.remove('show');
            hideQueueNumber();
            driverName.textContent = '-';
            driverTricycle.textContent = '-';
            driverContact.textContent = '-';
            driverStatusValue.textContent = '-';
            updateDriverMessage('');
            statusOverlay.textContent = 'Camera ready. Click Inqueue or Dispatch.';
            currentDriver = null;
        }

        inqueueBt.addEventListener('click', async () => {
            if (isProcessing) return;
            isProcessing = true;
            inqueueBt.disabled = true;
            inqueueBt.innerHTML = '<span class="loading-spinner"></span>Scanning...';
            updateDriverMessage('');
            driverInfo.classList.remove('show');
            hideQueueNumber();
            statusOverlay.textContent = 'Scanning face...';

            try {
                const result = await recognizeFace();
                
                if (!result.success || !result.recognized) {
                    updateDriverMessage(result.message || 'Face not recognized. Cannot add to queue.', 'error');
                    statusOverlay.textContent = 'Recognition failed.';
                    return;
                }

                showDriver(result.driver, 'Available');
                statusOverlay.textContent = 'Face recognized. Adding to queue...';

                const queueRes = await fetch(`${API_URL}/inqueue`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ driver_id: result.driver.id })
                });
                const queueData = await queueRes.json();

                if (queueData.success) {
                    driverStatusValue.textContent = 'Onqueue';
                    
                    // SHOW QUEUE NUMBER - BIG!
                    if (queueData.queue_number) {
                        showQueueNumber(queueData.queue_number);
                        updateDriverMessage(`✅ Added as Queue #${queueData.queue_number}`, 'success');
                        statusOverlay.textContent = `Added to queue as #${queueData.queue_number}!`;
                    } else {
                        updateDriverMessage(`Added to queue successfully.`, 'success');
                        statusOverlay.textContent = 'Added to queue successfully!';
                    }
                    
                    setTimeout(() => {
                        statusOverlay.textContent = 'Auto reload...';
                        setTimeout(() => {
                            resetToOriginal();
                        }, 500);
                    }, 3000); // Show for 3 seconds
                } else {
                    driverStatusValue.textContent = 'Onqueue';
                    updateDriverMessage(queueData.message || 'Already in queue.', 'warning');
                    statusOverlay.textContent = queueData.message || 'Already in queue';
                }

            } catch (error) {
                console.error('Error:', error);
                updateDriverMessage('Error processing request.', 'error');
                statusOverlay.textContent = 'Error occurred.';
            } finally {
                inqueueBt.disabled = false;
                inqueueBt.textContent = 'Inqueue';
                isProcessing = false;
            }
        });

        dispatchBtn.addEventListener('click', async () => {
            if (isProcessing) return;
            isProcessing = true;
            dispatchBtn.disabled = true;
            dispatchBtn.innerHTML = '<span class="loading-spinner"></span>Scanning...';
            updateDriverMessage('');
            driverInfo.classList.remove('show');
            hideQueueNumber();
            statusOverlay.textContent = 'Scanning face for dispatch...';

            try {
                const result = await recognizeFace();

                if (!result.success || !result.recognized) {
                    updateDriverMessage(result.message || 'Face not recognized. Cannot dispatch.', 'error');
                    statusOverlay.textContent = 'Recognition failed.';
                    return;
                }

                showDriver(result.driver, 'Available');
                statusOverlay.textContent = 'Face recognized. Dispatching...';

                const dispatchRes = await fetch(`${API_URL}/dispatch`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ driver_id: result.driver.id })
                });
                const dispatchData = await dispatchRes.json();

                if (dispatchData.success) {
                    driverStatusValue.textContent = 'Dispatched';
                    updateDriverMessage(`${result.driver.name} dispatched successfully.`, 'success');
                    statusOverlay.textContent = 'Dispatched successfully!';
                    
                    setTimeout(() => {
                        statusOverlay.textContent = 'Auto reload...';
                        setTimeout(() => {
                            resetToOriginal();
                        }, 500);
                    }, 2000);
                } else {
                    updateDriverMessage(dispatchData.message || 'Dispatch failed.', 'warning');
                    statusOverlay.textContent = dispatchData.message || 'Not in queue';
                }

            } catch (error) {
                console.error('Error:', error);
                updateDriverMessage('Error during dispatch.', 'error');
                statusOverlay.textContent = 'Error occurred.';
            } finally {
                dispatchBtn.disabled = false;
                dispatchBtn.textContent = 'Dispatch';
                isProcessing = false;
            }
        });

        window.addEventListener('load', startCamera);
    </script>
</body>
</html>
