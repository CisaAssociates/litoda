// Edit Camera functions
async function openEditCamera() {
    try {
        editStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            }
        });
        editVideo.srcObject = editStream;
        editCameraModal.classList.add("show");
        
        editVideo.style.display = 'block';
        editCanvas.style.display = 'none';
        editCaptureBtn.style.display = 'inline-block';
        editRetakeBtn.style.display = 'none';
        editConfirmBtn.style.display = 'none';
    } catch (error) {
        console.error("Error accessing camera:", error);
        showEditStatus("Camera access denied or not available", "error");
    }
}

function closeEditCameraModal() {
    editCameraModal.classList.remove("show");
    if (editStream) {
        editStream.getTracks().forEach(track => track.stop());
        editStream = null;
    }
    editVideo.srcObject = null;
}

async function captureEditPhoto() {
    const context = editCanvas.getContext('2d');
    editCanvas.width = editVideo.videoWidth;
    editCanvas.height = editVideo.videoHeight;
    context.drawImage(editVideo, 0, 0, editCanvas.width, editCanvas.height);
    
    const imageData = editCanvas.toDataURL('image/jpeg', 0.8);
    
    // Validate face count before confirming capture
    showEditStatus("Validating face...", "info");
    editCaptureBtn.disabled = true;
    editCaptureBtn.textContent = "Validating...";
    
    try {
        // Step 1: Validate single face
        const response = await fetch("http://127.0.0.1:5000/validate_single_face", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ image: imageData })
        });
        
        const result = await response.json();
        
        if (result.valid) {
            // Step 2: Check if this is the same person as the existing profile
            const existingPath = document.getElementById('existingImagePath').value;
            
            if (existingPath) {
                showEditStatus("Verifying face match...", "info");
                
                const matchResponse = await fetch("http://127.0.0.1:5000/check_face_match", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        existing_image_path: existingPath,
                        new_image: imageData
                    })
                });
                
                const matchResult = await matchResponse.json();
                
                if (matchResult.same_face) {
                    // Same person - allow update
                    editVideo.style.display = 'none';
                    editCanvas.style.display = 'block';
                    editCaptureBtn.style.display = 'none';
                    editRetakeBtn.style.display = 'inline-block';
                    editConfirmBtn.style.display = 'inline-block';
                    editCapturedImageData = imageData;
                    showEditStatus("✓ Same person detected! Face verified successfully.", "success");
                    showGlobalStatus("✓ Face matched! This is the same person. You can proceed with the update.", "success");
                } else {
                    // Different person - check if it's registered to someone else
                    showEditStatus("Checking for duplicate registration...", "info");
                    
                    const duplicateResponse = await fetch("http://127.0.0.1:5000/check_face_duplicate", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ 
                            image: imageData,
                            exclude_driver_id: currentEditDriverId // Exclude current driver from duplicate check
                        })
                    });
                    
                    const duplicateResult = await duplicateResponse.json();
                    
                    if (duplicateResult.duplicate) {
                        showEditStatus(`This face is registered to: ${duplicateResult.matched_driver}`, "error");
                        showGlobalStatus(`This face is already registered to ${duplicateResult.matched_driver}. Cannot update.`, "error");
                        editCaptureBtn.disabled = false;
                        editCaptureBtn.textContent = "Capture";
                    } else {
                        // Not a duplicate, but different from original - allow it
                        // This handles case where driver wants to change their registered face
                        editVideo.style.display = 'none';
                        editCanvas.style.display = 'block';
                        editCaptureBtn.style.display = 'none';
                        editRetakeBtn.style.display = 'inline-block';
                        editConfirmBtn.style.display = 'inline-block';
                        editCapturedImageData = imageData;
                        showEditStatus("New face validated! You can confirm the update.", "success");
                        showGlobalStatus("Note: This appears to be a different person. Proceeding with update.", "info");
                    }
                }
            } else {
                // No existing image, just proceed
                editVideo.style.display = 'none';
                editCanvas.style.display = 'block';
                editCaptureBtn.style.display = 'none';
                editRetakeBtn.style.display = 'inline-block';
                editConfirmBtn.style.display = 'inline-block';
                editCapturedImageData = imageData;
                showEditStatus("Face validated successfully!", "success");
            }
        } else {
            showEditStatus(result.message || "Invalid face capture", "error");
            showGlobalStatus(result.message || "Invalid face capture", "error");
            editCaptureBtn.disabled = false;
            editCaptureBtn.textContent = "Capture";
        }
    } catch (err) {
        console.error("Face validation error:", err);
        showEditStatus("Error validating face. Please try again.", "error");
        showGlobalStatus("Error connecting to face recognition system", "error");
        editCaptureBtn.disabled = false;
        editCaptureBtn.textContent = "Capture";
    }
}

function retakeEditPhoto() {
    editVideo.style.display = 'block';
    editCanvas.style.display = 'none';
    editCaptureBtn.style.display = 'inline-block';
    editCaptureBtn.disabled = false;
    editCaptureBtn.textContent = "Capture";
    editRetakeBtn.style.display = 'none';
    editConfirmBtn.style.display = 'none';
    editCapturedImageData = null;
}

function confirmEditPhoto() {
    if (editCapturedImageData) {
        // Update the profile picture container with proper styling
        editProfilePictureContainer.innerHTML = `<img src="${editCapturedImageData}" alt="New Profile Picture" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; cursor: pointer;">`;
        editProfileImageData.value = editCapturedImageData;
        showEditStatus("Profile picture updated successfully!", "success");
        showGlobalStatus("Profile picture updated! Remember to click 'Update User' to save changes.", "success");
        closeEditCameraModal();
    }
}

function showEditStatus(message, type) {
    if (editStatusMessage) {
        editStatusMessage.textContent = message;
        editStatusMessage.className = `status-message status-${type}`;
        editStatusMessage.style.display = 'block';
        
        if (type === 'success') {
            setTimeout(() => {
                editStatusMessage.style.display = 'none';
            }, 3000);
        }
    }
}

// Edit form submission with contact validation
if (editUserForm) {
    editUserForm.onsubmit = function(e) {
        const editContactInput = document.getElementById('edit_contact');
        
        // Only validate contact if it has a value (blank is allowed)
        if (editContactInput && editContactInput.value.length > 0 && editContactInput.value.length !== 11) {
            e.preventDefault();
            showEditStatus("Contact number must be exactly 11 digits if provided", "error");
            editContactInput.focus();
            return false;
        }
        
        return true;
    };
}
