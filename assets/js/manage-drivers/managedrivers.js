// ============================================
// COMPLETE JAVASCRIPT - managedrivers.js
// ============================================

// Modal elements
const modal = document.getElementById("userModal");
const openBtn = document.getElementById("openModal");
const closeBtn = document.getElementById("closeModal");

// Camera modal elements
const cameraModal = document.getElementById("cameraModal");
const profilePictureContainer = document.getElementById("profilePictureContainer");
const closeCameraBtn = document.getElementById("closeCameraModal");
const cancelCameraBtn = document.getElementById("cancelCameraBtn");

// Camera elements
const video = document.getElementById("cameraVideo");
const canvas = document.getElementById("capturedCanvas");
const captureBtn = document.getElementById("captureBtn");
const retakeBtn = document.getElementById("retakeBtn");
const confirmBtn = document.getElementById("confirmBtn");

// Form elements
const submitBtn = document.getElementById("submitBtn");
const profileImageData = document.getElementById("profileImageData");
const statusMessage = document.getElementById("statusMessage");
const userForm = document.getElementById("userForm");

let stream = null;
let capturedImageData = null;

// API Base URL
const API_BASE_URL = (typeof FLASK_API_URL !== 'undefined') ? FLASK_API_URL : '/py-api';

// Main modal controls
openBtn.onclick = () => modal.classList.add("show");
closeBtn.onclick = () => {
    modal.classList.remove("show");
    resetForm();
};

window.onclick = (e) => {
    if (e.target === modal) {
        modal.classList.remove("show");
        resetForm();
    }
    if (e.target === cameraModal) {
        closeCameraModal();
    }
};

if (profilePictureContainer) {
    profilePictureContainer.onclick = openCamera;
}

if (closeCameraBtn) closeCameraBtn.onclick = closeCameraModal;
if (cancelCameraBtn) cancelCameraBtn.onclick = closeCameraModal;
if (captureBtn) captureBtn.onclick = capturePhoto;
if (retakeBtn) retakeBtn.onclick = retakePhoto;
if (confirmBtn) confirmBtn.onclick = confirmPhoto;

// Camera functions for ADD
async function openCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
        });
        video.srcObject = stream;
        cameraModal.classList.add("show");
        video.style.display = 'block';
        canvas.style.display = 'none';
        captureBtn.style.display = 'inline-block';
        retakeBtn.style.display = 'none';
        confirmBtn.style.display = 'none';
    } catch (error) {
        console.error("Error accessing camera:", error);
        showStatus("Camera access denied or not available", "error");
    }
}

function closeCameraModal() {
    cameraModal.classList.remove("show");
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    video.srcObject = null;
}

async function capturePhoto() {
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/jpeg', 0.8);
    
    showStatus("Validating face...", "info");
    captureBtn.disabled = true;
    captureBtn.textContent = "Validating...";
    
    try {
        const response = await fetch(`${API_BASE_URL}/validate_single_face`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ image: imageData })
        });
        const result = await response.json();
        
        if (result.valid) {
            showStatus("Checking for duplicate faces...", "info");
            const duplicateResponse = await fetch(`${API_BASE_URL}/check_face_duplicate`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ image: imageData })
            });
            const duplicateResult = await duplicateResponse.json();
            
            if (duplicateResult.duplicate) {
                showStatus(`Face already registered to: ${duplicateResult.matched_driver}`, "error");
                showGlobalStatus(`This face is already registered to ${duplicateResult.matched_driver}. Please use a different person.`, "error");
                captureBtn.disabled = false;
                captureBtn.textContent = "Capture";
            } else {
                video.style.display = 'none';
                canvas.style.display = 'block';
                captureBtn.style.display = 'none';
                retakeBtn.style.display = 'inline-block';
                confirmBtn.style.display = 'inline-block';
                capturedImageData = imageData;
                showStatus("Face validated successfully!", "success");
            }
        } else {
            showStatus(result.message || "Invalid face capture", "error");
            showGlobalStatus(result.message || "Invalid face capture", "error");
            captureBtn.disabled = false;
            captureBtn.textContent = "Capture";
        }
    } catch (err) {
        console.error("Face validation error:", err);
        showStatus("Error validating face. Please try again.", "error");
        showGlobalStatus("Cannot connect to face recognition system.", "error");
        captureBtn.disabled = false;
        captureBtn.textContent = "Capture";
    }
}

function retakePhoto() {
    video.style.display = 'block';
    canvas.style.display = 'none';
    captureBtn.style.display = 'inline-block';
    captureBtn.disabled = false;
    captureBtn.textContent = "Capture";
    retakeBtn.style.display = 'none';
    confirmBtn.style.display = 'none';
    capturedImageData = null;
}

function confirmPhoto() {
    if (capturedImageData) {
        profilePictureContainer.innerHTML = `<img src="${capturedImageData}" alt="Profile Picture" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">`;
        profileImageData.value = capturedImageData;
        submitBtn.disabled = false;
        showStatus("Profile picture captured successfully!", "success");
        closeCameraModal();
    }
}

function showStatus(message, type) {
    if (statusMessage) {
        statusMessage.textContent = message;
        statusMessage.className = `status-message status-${type}`;
        statusMessage.style.display = 'block';
        if (type === 'success') {
            setTimeout(() => { statusMessage.style.display = 'none'; }, 3000);
        }
    }
}

function resetForm() {
    if (userForm) userForm.reset();
    if (profilePictureContainer) {
        profilePictureContainer.innerHTML = `<div class="placeholder"><span class="icon"></span><p>Take Photo</p></div>`;
    }
    if (profileImageData) profileImageData.value = '';
    if (submitBtn) submitBtn.disabled = true;
    if (statusMessage) statusMessage.style.display = 'none';
    capturedImageData = null;
}

if (userForm) {
    userForm.onsubmit = function(e) {
        const contactInput = document.getElementById('contactnumber');
        if (!capturedImageData) {
            e.preventDefault();
            showStatus("Please take a profile picture before submitting", "error");
            return false;
        }
        if (contactInput && contactInput.value.length > 0 && contactInput.value.length !== 11) {
            e.preventDefault();
            showStatus("Contact number must be exactly 11 digits if provided", "error");
            contactInput.focus();
            return false;
        }
        return true;
    };
}

if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    showStatus("Camera not supported on this device", "error");
    if (profilePictureContainer) {
        profilePictureContainer.style.cursor = 'not-allowed';
        profilePictureContainer.onclick = null;
    }
}

function showGlobalStatus(message, type) {
    const existing = document.querySelectorAll('.global-notification');
    existing.forEach(el => el.remove());
    const globalStatus = document.createElement('div');
    globalStatus.className = `global-notification status-${type}`;
    globalStatus.innerHTML = `<div style="display: flex; align-items: center; gap: 12px;"><i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}" style="font-size: 20px;"></i><span style="flex: 1;">${message}</span></div>`;
    document.body.appendChild(globalStatus);
    setTimeout(() => { globalStatus.classList.add('show'); }, 100);
    setTimeout(() => {
        globalStatus.classList.remove('show');
        setTimeout(() => { if (document.body.contains(globalStatus)) document.body.removeChild(globalStatus); }, 300);
    }, 4000);
}

document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('global-notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'global-notification-styles';
        styles.textContent = `.global-notification{position:fixed;top:20px;right:20px;z-index:9999;min-width:350px;max-width:500px;padding:16px 24px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.15);font-weight:500;font-family:'Poppins',sans-serif;opacity:0;transform:translateX(400px);transition:all 0.3s cubic-bezier(0.68,-0.55,0.265,1.55);backdrop-filter:blur(10px)}.global-notification.show{opacity:1;transform:translateX(0)}.global-notification.status-success{background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;border:2px solid #059669}.global-notification.status-error{background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);color:white;border:2px solid #dc2626}.global-notification.status-info{background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);color:white;border:2px solid #2563eb}`;
        document.head.appendChild(styles);
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');
    
    if (success === 'user_added') {
        showGlobalStatus('Driver added successfully!', 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (success === 'user_updated') {
        showGlobalStatus('Driver updated successfully!', 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (success === 'user_deleted') {
        showGlobalStatus('Driver deleted successfully!', 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (error) {
        let errorMessage;
        const errorMap = {
            'missing_fields': 'Please fill in all required fields',
            'database_error': 'Database error occurred',
            'file_upload_failed': 'Failed to upload profile picture',
            'invalid_image_data': 'Invalid image data provided',
            'no_image_provided': 'Profile picture is required',
            'invalid_contact': 'Contact number must be exactly 11 digits',
            'delete_failed': 'Failed to delete driver',
            'invalid_image_type': 'Invalid image type',
            'update_failed': 'Failed to update driver',
            'database_insert_failed': 'Failed to add driver to database',
            'duplicate_fullname': 'A driver with this full name already exists',
            'duplicate_contact': 'This contact number is already registered to another driver',
            'duplicate_face': 'This face is already registered to another driver',
            'face_mismatch': 'Face mismatch! The new photo must be of the same registered driver.',
            'duplicate_submission': 'Form already submitted. Please wait...'
        };
        errorMessage = error.startsWith('duplicate_driver') ? 'Driver with same name and plate number already exists' : (errorMap[error] || 'An error occurred');
        showGlobalStatus(errorMessage, 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    const contactInput = document.getElementById('contactnumber');
    if (contactInput) {
        contactInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
            this.style.borderColor = this.value.length === 11 ? '#10b981' : (this.value.length === 0 ? '#d1d5db' : '#f59e0b');
            this.style.boxShadow = this.value.length === 11 ? '0 0 0 3px rgba(16,185,129,0.1)' : (this.value.length === 0 ? 'none' : '0 0 0 3px rgba(245,158,11,0.1)');
        });
        contactInput.addEventListener('blur', function() {
            if (this.value.length > 0 && this.value.length !== 11) {
                showStatus('Contact number must be exactly 11 digits if provided', 'error');
                this.style.borderColor = '#ef4444';
                this.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.1)';
            }
        });
        contactInput.addEventListener('focus', function() {
            if (this.value.length === 0 || this.value.length === 11) {
                this.style.borderColor = '#3b82f6';
                this.style.boxShadow = '0 0 0 3px rgba(59,130,246,0.1)';
            }
        });
    }
    
    const editContactInput = document.getElementById('edit_contact');
    if (editContactInput) {
        editContactInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
            this.style.borderColor = this.value.length === 11 ? '#10b981' : (this.value.length === 0 ? '#d1d5db' : '#f59e0b');
            this.style.boxShadow = this.value.length === 11 ? '0 0 0 3px rgba(16,185,129,0.1)' : (this.value.length === 0 ? 'none' : '0 0 0 3px rgba(245,158,11,0.1)');
        });
        editContactInput.addEventListener('blur', function() {
            if (this.value.length > 0 && this.value.length !== 11) {
                showEditStatus('Contact number must be exactly 11 digits if provided', 'error');
                this.style.borderColor = '#ef4444';
                this.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.1)';
            }
        });
        editContactInput.addEventListener('focus', function() {
            if (this.value.length === 0 || this.value.length === 11) {
                this.style.borderColor = '#3b82f6';
                this.style.boxShadow = '0 0 0 3px rgba(59,130,246,0.1)';
            }
        });
    }
});

function toggleActionMenu(event, button) {
    event.stopPropagation();
    const dropdown = button.nextElementSibling;
    const allDropdowns = document.querySelectorAll('.action-dropdown');
    allDropdowns.forEach(dd => { if (dd !== dropdown) dd.classList.remove('show'); });
    const rect = button.getBoundingClientRect();
    dropdown.style.top = (rect.bottom + 5) + 'px';
    dropdown.style.left = (rect.left - 80) + 'px';
    dropdown.classList.toggle('show');
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.action-menu-container')) {
        document.querySelectorAll('.action-dropdown').forEach(dd => dd.classList.remove('show'));
    }
});

function deleteDriver(driverId) {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => { Swal.showLoading(); }
            });
            fetch(`../../api/manage-drivers/deletedriver.php?id=${driverId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.close();
                        window.location.href = window.location.pathname + '?success=user_deleted';
                    } else {
                        Swal.fire({ icon: 'error', title: 'Delete Failed', text: data.message || 'Failed to delete driver', confirmButtonColor: "#3085d6" });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while deleting the driver', confirmButtonColor: "#3085d6" });
                });
        }
    });
}

// EDIT FUNCTIONALITY
const editModal = document.getElementById("editModal");
const closeEditModal = document.getElementById("closeEditModal");
const editUserForm = document.getElementById("editUserForm");
const editSubmitBtn = document.getElementById("editSubmitBtn");
const editCameraModal = document.getElementById("editCameraModal");
const editProfilePictureContainer = document.getElementById("editProfilePictureContainer");
const closeEditCameraBtn = document.getElementById("closeEditCameraModal");
const cancelEditCameraBtn = document.getElementById("cancelEditCameraBtn");
const editVideo = document.getElementById("editCameraVideo");
const editCanvas = document.getElementById("editCapturedCanvas");
const editCaptureBtn = document.getElementById("editCaptureBtn");
const editRetakeBtn = document.getElementById("editRetakeBtn");
const editConfirmBtn = document.getElementById("editConfirmBtn");
const editProfileImageData = document.getElementById("editProfileImageData");
const editStatusMessage = document.getElementById("editStatusMessage");

let editStream = null;
let editCapturedImageData = null;
let currentEditDriverId = null;
let isSubmittingEdit = false;

if (closeEditModal) {
    closeEditModal.onclick = () => { editModal.classList.remove("show"); };
}

window.addEventListener('click', (e) => {
    if (e.target === editModal) editModal.classList.remove("show");
    if (e.target === editCameraModal) closeEditCameraModal();
});

function editDriver(driverId) {
    console.log('Edit driver:', driverId);
    currentEditDriverId = driverId;
    showEditStatus("Loading driver data...", "info");
    fetch(`../../api/manage-drivers/getuser.php?id=${driverId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditForm(data.data);
                editModal.classList.add("show");
            } else {
                showGlobalStatus(data.message || 'Failed to load driver data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showGlobalStatus('An error occurred while loading driver data', 'error');
        });
}

function populateEditForm(driver) {
    document.getElementById('edit_driver_id').value = driver.id;
    document.getElementById('edit_firstname').value = driver.firstname;
    document.getElementById('edit_middlename').value = driver.middlename || '';
    document.getElementById('edit_lastname').value = driver.lastname;
    document.getElementById('edit_platenumber').value = driver.tricycle_number;
    document.getElementById('edit_contact').value = driver.contact_no || '';
    
    const submissionToken = Date.now().toString();
    let tokenInput = document.getElementById('submission_token');
    if (!tokenInput) {
        tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'submission_token';
        tokenInput.id = 'submission_token';
        editUserForm.appendChild(tokenInput);
    }
    tokenInput.value = submissionToken;
    
    if (driver.profile_pic) {
        document.getElementById('existingImagePath').value = driver.profile_pic;
        editProfilePictureContainer.innerHTML = `<img src="${driver.profile_pic}" alt="Profile Picture" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; cursor: pointer;">`;
    } else {
        editProfilePictureContainer.innerHTML = `<div class="placeholder" style="width: 150px; height: 150px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed #d1d5db; border-radius: 50%; cursor: pointer;"><span class="icon"></span><p style="margin: 5px 0 0 0; font-size: 12px;">Change Photo</p></div>`;
    }
    editStatusMessage.style.display = 'none';
    editCapturedImageData = null;
    editProfileImageData.value = '';
    isSubmittingEdit = false;
}

if (editProfilePictureContainer) editProfilePictureContainer.onclick = openEditCamera;
if (closeEditCameraBtn) closeEditCameraBtn.onclick = closeEditCameraModal;
if (cancelEditCameraBtn) cancelEditCameraBtn.onclick = closeEditCameraModal;
if (editCaptureBtn) editCaptureBtn.onclick = captureEditPhoto;
if (editRetakeBtn) editRetakeBtn.onclick = retakeEditPhoto;
if (editConfirmBtn) editConfirmBtn.onclick = confirmEditPhoto;

async function openEditCamera() {
    try {
        editStream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
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
    
    showEditStatus("Validating face...", "info");
    setEditCaptureButtonState(true, "Validating...");
    
    try {
        const validateRes = await fetch(`${API_BASE_URL}/validate_single_face`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ image: imageData })
        });
        const validateData = await validateRes.json();
        
        if (!validateData.valid) {
            handleEditError(validateData.message || "No valid face detected.");
            return;
        }
        
        const existingPath = document.getElementById("existingImagePath").value;
        
        if (existingPath) {
            showEditStatus("Verifying if same person...", "info");
            const matchRes = await fetch(`${API_BASE_URL}/check_face_match`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ existing_image_path: existingPath, new_image: imageData })
            });
            const matchData = await matchRes.json();
            
            if (!matchData.same_face) {
                handleEditError("This is NOT the same registered driver!");
                showGlobalStatus("❌ Face mismatch. Update blocked.", "error");
                return;
            }
            
            editCapturedImageData = imageData;
            editVideo.style.display = "none";
            editCanvas.style.display = "block";
            editCaptureBtn.style.display = "none";
            editRetakeBtn.style.display = "inline-block";
            editConfirmBtn.style.display = "inline-block";
            showEditStatus("✓ Same person verified!", "success");
            showGlobalStatus("✓ Face match confirmed. You can update the profile.", "success");
        } else {
            editCapturedImageData = imageData;
            editVideo.style.display = "none";
            editCanvas.style.display = "block";
            editCaptureBtn.style.display = "none";
            editRetakeBtn.style.display = "inline-block";
            editConfirmBtn.style.display = "inline-block";
            showEditStatus("Face validated successfully!", "success");
        }
    } catch (error) {
        console.error("Edit Photo Validation Error:", error);
        handleEditError("Cannot connect to face recognition server.");
    } finally {
        setEditCaptureButtonState(false, "Capture");
    }
}

function handleEditError(message) {
    showEditStatus(message, "error");
    showGlobalStatus(message, "error");
    editCanvas.style.display = "none";
    editVideo.style.display = "block";
    editCaptureBtn.style.display = "inline-block";
    editRetakeBtn.style.display = "none";
    editConfirmBtn.style.display = "none";
    editCapturedImageData = null;
    setEditCaptureButtonState(false, "Capture");
}

function setEditCaptureButtonState(isDisabled, text) {
    editCaptureBtn.disabled = isDisabled;
    editCaptureBtn.textContent = text;
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
        editProfilePictureContainer.innerHTML = `<img src="${editCapturedImageData}" alt="New Profile Picture" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; cursor: pointer;">`;
        editProfileImageData.value = editCapturedImageData;
        showEditStatus("Profile picture updated successfully!", "success");
        showGlobalStatus("✓ Profile picture updated! Click 'Update User' to save changes.", "success");
        closeEditCameraModal();
    }
}

function showEditStatus(message, type) {
    if (editStatusMessage) {
        editStatusMessage.textContent = message;
        editStatusMessage.className = `status-message status-${type}`;
        editStatusMessage.style.display = 'block';
        if (type === 'success') {
            setTimeout(() => { editStatusMessage.style.display = 'none'; }, 3000);
        }
    }
}

// Replace the editUserForm.onsubmit function with this fixed version:

if (editUserForm) {
    editUserForm.onsubmit = function(e) {
        e.preventDefault();
        
        if (isSubmittingEdit) {
            console.log('Form already submitting');
            return false;
        }
        
        const editContactInput = document.getElementById('edit_contact');
        if (editContactInput && editContactInput.value.length > 0 && editContactInput.value.length !== 11) {
            showEditStatus("Contact number must be exactly 11 digits if provided", "error");
            showGlobalStatus("Contact number must be exactly 11 digits if provided", "error");
            editContactInput.focus();
            return false;
        }
        
        isSubmittingEdit = true;
        editSubmitBtn.disabled = true;
        editSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        // Show loading status
        showGlobalStatus("Saving changes...", "info");
        
        // Submit form
        const formData = new FormData(editUserForm);
        fetch('../../api/manage-drivers/updateuser.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                throw new Error('Server responded with ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            // Parse response to check for errors
            console.log('Server response:', text);
            
            // Close modal immediately
            editModal.classList.remove("show");
            
            // Show success message
            showGlobalStatus('Driver updated successfully!', 'success');
            
            // Wait a bit for the database to complete, then reload
            setTimeout(() => {
                window.location.href = window.location.pathname + '?success=user_updated';
            }, 500);
        })
        .catch(error => {
            console.error('Error:', error);
            showGlobalStatus('An error occurred while updating the driver', 'error');
            isSubmittingEdit = false;
            editSubmitBtn.disabled = false;
            editSubmitBtn.innerHTML = '<i class="fas fa-save"></i> Update User';
            // Don't close modal on error so user can try again
        });
        
        return false;
    };
}
