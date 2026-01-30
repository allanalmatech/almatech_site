// File upload functionality for logo and favicon
function uploadFile(type) {
  const fileInput = document.getElementById(type + '_file');
  fileInput.click();
}

function handleFileSelect(type) {
  const fileInput = document.getElementById(type + '_file');
  const urlInput = document.getElementById('brand_' + type);
  
  if (fileInput.files && fileInput.files[0]) {
    const file = fileInput.files[0];
    
    // Validate file type
    if (type === 'logo' && !file.type.startsWith('image/')) {
      alert('Please select a valid image file for the logo.');
      fileInput.value = '';
      return;
    }
    
    if (type === 'favicon') {
      const faviconTypes = ['image/x-icon', 'image/png', 'image/jpeg', 'image/gif', 'image/vnd.microsoft.icon'];
      if (!faviconTypes.includes(file.type)) {
        alert('Please select a valid favicon file (ICO, PNG, JPG, GIF).');
        fileInput.value = '';
        return;
      }
    }
    
    // Validate file size (max 2MB for favicon, 5MB for logo)
    const maxSize = type === 'favicon' ? 2 * 1024 * 1024 : 5 * 1024 * 1024;
    if (file.size > maxSize) {
      const maxSizeMB = type === 'favicon' ? '2MB' : '5MB';
      alert('File size must be less than ' + maxSizeMB + '.');
      fileInput.value = '';
      return;
    }
    
    // Show preview and success message
    const reader = new FileReader();
    reader.onload = function(e) {
      // Update the URL input to show the file will be processed
      urlInput.value = '[New ' + type + ' file: ' + file.name + ']';
      showUploadMessage(type, 'File selected! Save settings to upload.', 'success');
    };
    reader.readAsDataURL(file);
  }
}

function deleteFile(type) {
  if (confirm('Are you sure you want to delete this ' + type + '?')) {
    const urlInput = document.getElementById('brand_' + type);
    
    // Clear the URL input
    urlInput.value = '';
    
    // Clear the file input
    const fileInput = document.getElementById(type + '_file');
    fileInput.value = '';
    
    showUploadMessage(type, 'File marked for deletion. Save settings to apply.', 'success');
  }
}

function showUploadMessage(type, message, typeClass) {
  const inputGroup = document.getElementById('brand_' + type).closest('.col-md-6');
  
  // Remove existing messages
  const existingAlert = inputGroup.querySelector('.alert');
  if (existingAlert) {
    existingAlert.remove();
  }
  
  // Create alert
  const alert = document.createElement('div');
  alert.className = `alert alert-${typeClass} alert-dismissible fade show mt-2`;
  alert.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  inputGroup.appendChild(alert);
  
  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    if (alert.parentNode) {
      alert.remove();
    }
  }, 5000);
}
