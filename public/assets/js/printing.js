// Global variables
let uploadedFiles = [];
let currentPreviewIndex = 0;

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
  // Event listeners are now handled by print_dynamic.js
});

// Select a service (now handled by print_dynamic.js)
function selectService(serviceName) {
  // This function is now handled by selectPrintService in print_dynamic.js
  // Keeping for backward compatibility
}

// Clear selection
function clearSelection() {
  console.log('clearSelection() called - clearing form fields');
  // Reset form inputs
  document.getElementById('serviceInput').value = '';
  document.getElementById('descriptionInput').value = '';
  document.getElementById('contactNumber').value = '';
  document.getElementById('specialInstructions').value = '';
  console.log('Contact Number value after clear:', document.getElementById('contactNumber').value);
  console.log('Special Instructions value after clear:', document.getElementById('specialInstructions').value);

  // Reset preview
  document.getElementById('selectedService').textContent = 'No Service Selected';
  document.getElementById('serviceDescription').textContent = 'Select a service to see details';
  document.getElementById('previewImage').src = 'https://via.placeholder.com/250x200?text=Select+a+Service';

  // Reset form selects
  document.getElementById('sizeSelect').innerHTML = '<option value="">Select Size</option>';
  document.getElementById('paperType').innerHTML = '<option value="">Select Paper Type</option>';
  document.getElementById('colorType').value = '';
  document.getElementById('quantity').value = '1';

  // Reset price
  document.getElementById('priceDisplay').textContent = '₱0.00';
  document.getElementById('priceInput').value = '0.00'; // Store numeric value for backend

  // Clear file input
  const fileInput = document.getElementById('uploadInput');
  if (fileInput) {
    fileInput.value = '';
  }

  // Clear uploaded files
  uploadedFiles = [];
  updateFileList();
  updatePreviewCarousel();

  // Reset currentService
  window.currentService = null;
}

// Function to estimate pages for pricing (frontend estimation)
function estimateFilePages(file) {
  return new Promise((resolve) => {
    if (file.type.startsWith('image/')) {
      resolve(1); // Each image = 1 page
    } else if (file.type === 'application/pdf') {
      // For PDFs, we can try to count pages client-side
      const fileReader = new FileReader();
      fileReader.onload = function() {
        const typedArray = new Uint8Array(this.result);
        if (typeof pdfjsLib !== 'undefined') {
          pdfjsLib.getDocument(typedArray).promise.then(function(pdf) {
            resolve(pdf.numPages);
          }).catch(function() {
            resolve(1); // Fallback
          });
        } else {
          resolve(1); // Fallback if PDF.js not loaded
        }
      };
      fileReader.readAsArrayBuffer(file);
    } else {
      // For DOCX/DOC, we can't easily count pages client-side
      // Use a default estimate or let backend handle it
      resolve(1); // Default estimate
    }
  });
}

// Calculate total pages from all uploaded files
async function calculateTotalPages() {
  if (uploadedFiles.length === 0) return 0;
  const pagePromises = uploadedFiles.map(file => estimateFilePages(file));
  const pageCounts = await Promise.all(pagePromises);
  return pageCounts.reduce((sum, count) => sum + count, 0);
}

// Update payment method availability based on total pages
async function updatePaymentMethodAvailability() {
  const totalPages = await calculateTotalPages();
  const paymentWarning = document.getElementById('paymentWarning');
  const inPersonRadio = document.getElementById('paymentInPerson');
  const onlineRadio = document.getElementById('paymentOnline');
  const inPersonWrapper = inPersonRadio.closest('.radio-wrapper');
  
  if (totalPages > 30) {
    // Disable In-Person payment for orders > 30 pages
    inPersonRadio.disabled = true;
    onlineRadio.checked = true;
    
    // Add disabled class for styling
    if (inPersonWrapper) {
      inPersonWrapper.classList.add('disabled');
    }
    
    if (paymentWarning) {
      paymentWarning.style.display = 'block';
    }
  } else {
    // Enable In-Person payment for orders <= 30 pages
    inPersonRadio.disabled = false;
    
    // Remove disabled class for styling
    if (inPersonWrapper) {
      inPersonWrapper.classList.remove('disabled');
    }
    
    if (paymentWarning) {
      paymentWarning.style.display = 'none';
    }
  }
}

// Update price calculation: (base price × total pages) × quantity
async function calculateTotalPrice() {
  const priceDisplay = document.getElementById('priceDisplay');
  const priceInput = document.getElementById('priceInput');
  const priceBreakdown = document.getElementById('priceBreakdown');
  
  // Debug: Check current service state
  console.log('calculateTotalPrice - Current Service:', window.currentService);
  
  if (!window.currentService || uploadedFiles.length === 0) {
    priceDisplay.textContent = '₱0.00';
    priceInput.value = '0.00';
    priceBreakdown.textContent = '(Select service and upload files)';
    return;
  }

  // Extract and validate the price
  let basePrice = parseFloat(window.currentService.price);
  
  // Check if basePrice is a valid number
  if (isNaN(basePrice)) {
    console.error('Invalid base price detected:', window.currentService.price);
    // Try to get base price from the service object
    basePrice = parseFloat(window.currentService.base_price);
    
    if (isNaN(basePrice)) {
      console.error('Base price is still invalid, defaulting to 0');
      basePrice = 0;
    }
  }

  const quantity = parseInt(document.getElementById('quantity').value) || 1;
  const colorType = document.getElementById('colorType').value;
  
  // Calculate total pages from all files
  let totalPages = 0;
  const pagePromises = uploadedFiles.map(file => estimateFilePages(file));
  const pageCounts = await Promise.all(pagePromises);
  totalPages = pageCounts.reduce((sum, count) => sum + count, 0);

  // Add ₱5 per page if colored print is selected
  let pricePerPage = basePrice;
  if (colorType === 'colored') {
    pricePerPage += 5;
  }

  // Calculate final price: (price per page × total pages) × quantity
  const pricePerCopy = pricePerPage * totalPages;
  const finalPrice = pricePerCopy * quantity;

  priceDisplay.textContent = `₱${finalPrice.toFixed(2)}`;
  priceInput.value = finalPrice.toFixed(2);
  
  // Update price display to show breakdown
  updatePriceBreakdown(basePrice, totalPages, quantity, finalPrice, colorType);
  
  // Update payment method availability based on total pages
  updatePaymentMethodAvailability();
}

// Show price breakdown with pages included
function updatePriceBreakdown(basePrice, totalPages, quantity, finalPrice, colorType) {
  const priceDisplay = document.getElementById('priceDisplay');
  const priceBreakdown = document.getElementById('priceBreakdown');
  
  // If any value is invalid, show calculating message
  if (isNaN(basePrice) || isNaN(totalPages) || isNaN(quantity) || isNaN(finalPrice)) {
    priceDisplay.innerHTML = `₱0.00`;
    priceBreakdown.textContent = '(Select service and upload files)';
    return;
  }

  priceDisplay.innerHTML = `₱${finalPrice.toFixed(2)}`;
  
  // Build breakdown string
  let breakdownText = '';
  if (colorType === 'colored') {
    const pricePerPage = basePrice + 5;
    breakdownText = `(₱${basePrice.toFixed(2)} + ₱5.00 color charge = ₱${pricePerPage.toFixed(2)}/page × ${totalPages} pages) × ${quantity} copies`;
  } else {
    breakdownText = `(₱${basePrice.toFixed(2)}/page × ${totalPages} pages) × ${quantity} copies`;
  }
  
  priceBreakdown.innerHTML = breakdownText;
}

// Preview uploaded files
async function previewUpload(event) {
  const files = Array.from(event.target.files);
  
  if (files.length === 0) return;

  // Validate number of files
  if (files.length > 10) {
    Swal.fire({
      icon: 'error',
      title: 'Too Many Files',
      text: 'You can upload a maximum of 10 files at once.',
      confirmButtonText: 'OK'
    });
    event.target.value = '';
    return;
  }

  // Validate each file
  const validFiles = [];
  const invalidFiles = [];

  files.forEach(file => {
    if (validateFileType(file)) {
      validFiles.push(file);
    } else {
      invalidFiles.push(file.name);
    }
  });

  // Show error for invalid files
  if (invalidFiles.length > 0) {
    Swal.fire({
      icon: 'error',
      title: 'Invalid File Types',
      html: `The following files were not accepted:<br><strong>${invalidFiles.join(', ')}</strong><br><br>Please upload only WEBP, JPEG, JPG, PDF, PNG, TIFF, or DOCX files.`,
      confirmButtonText: 'OK'
    });
  }

  if (validFiles.length === 0) {
    event.target.value = '';
    return;
  }

  // Add valid files to uploadedFiles array
  uploadedFiles = [...uploadedFiles, ...validFiles];

  // Update UI
  updateFileList();
  updatePreviewCarousel();

  // Preview first file
  if (uploadedFiles.length > 0) {
    currentPreviewIndex = 0;
    previewSingleFile(uploadedFiles[0]);
  }

  // Update price calculation
  await calculateTotalPrice();

  // Clear the file input to allow uploading same files again
  event.target.value = '';
}

// Update file list display with page counts
async function updateFileList() {
  const fileList = document.getElementById('fileList');
  fileList.innerHTML = '';

  if (uploadedFiles.length === 0) {
    fileList.style.display = 'none';
    return;
  }

  fileList.style.display = 'block';

  // Get page counts for all files
  const pageCounts = await Promise.all(
    uploadedFiles.map(file => estimateFilePages(file))
  );

  // Calculate total pages
  const totalPages = pageCounts.reduce((sum, count) => sum + count, 0);

  uploadedFiles.forEach((file, index) => {
    const fileItem = document.createElement('div');
    fileItem.className = 'file-item';
    
    const fileSize = (file.size / 1024 / 1024).toFixed(2);
    const pageCount = pageCounts[index];
    
    fileItem.innerHTML = `
      <div class="file-info">
        <div class="file-name">${file.name}</div>
        <div class="file-details">
          <span class="file-size">${fileSize} MB</span>
          <span class="file-pages">${pageCount} page${pageCount !== 1 ? 's' : ''}</span>
        </div>
      </div>
      <button class="remove-btn" onclick="removeFile(${index})">
        <i class="fas fa-times"></i>
      </button>
    `;
    
    fileList.appendChild(fileItem);
  });

  // Add total pages summary
  const totalItem = document.createElement('div');
  totalItem.className = 'file-total';
  totalItem.innerHTML = `
    <div class="file-info">
      <div class="file-name"><strong>Total Pages</strong></div>
      <div class="file-details">
        <span class="file-pages"><strong>${totalPages} pages</strong></span>
      </div>
    </div>
  `;
  fileList.appendChild(totalItem);
  
  // Update payment method availability based on page count
  await updatePaymentMethodAvailability();
}

// Update preview carousel
function updatePreviewCarousel() {
  const previewContent = document.querySelector('.preview-content');
  let carousel = document.getElementById('previewCarousel');
  
  // Remove existing carousel if any
  if (carousel) {
    carousel.remove();
  }

  if (uploadedFiles.length <= 1) {
    return;
  }

  // Create carousel
  carousel = document.createElement('div');
  carousel.id = 'previewCarousel';
  carousel.className = 'preview-carousel';
  
  uploadedFiles.forEach((file, index) => {
    const previewItem = document.createElement('div');
    previewItem.className = `preview-item ${index === currentPreviewIndex ? 'current-preview' : ''}`;
    previewItem.onclick = () => switchPreview(index);
    
    // Create thumbnail
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.alt = file.name;
        previewItem.appendChild(img);
      };
      reader.readAsDataURL(file);
    } else {
      const placeholder = document.createElement('div');
      placeholder.style.width = '100px';
      placeholder.style.height = '100px';
      placeholder.style.background = '#f8f9fa';
      placeholder.style.display = 'flex';
      placeholder.style.alignItems = 'center';
      placeholder.style.justifyContent = 'center';
      placeholder.style.border = '2px solid #ddd';
      placeholder.style.borderRadius = '4px';
      
      const icon = document.createElement('i');
      if (file.type === 'application/pdf') {
        icon.className = 'fas fa-file-pdf';
        icon.style.color = '#dc3545';
        icon.style.fontSize = '24px';
      } else if (file.type.includes('word')) {
        icon.className = 'fas fa-file-word';
        icon.style.color = '#2b579a';
        icon.style.fontSize = '24px';
      } else {
        icon.className = 'fas fa-file';
        icon.style.color = '#6c757d';
        icon.style.fontSize = '24px';
      }
      
      placeholder.appendChild(icon);
      previewItem.appendChild(placeholder);
    }
    
    const fileName = document.createElement('div');
    fileName.className = 'file-name';
    fileName.textContent = file.name.length > 12 ? file.name.substring(0, 12) + '...' : file.name;
    previewItem.appendChild(fileName);
    
    carousel.appendChild(previewItem);
  });
  
  // Insert carousel before the preview info
  const previewInfo = document.querySelector('.preview-info');
  previewContent.insertBefore(carousel, previewInfo);
}

// Switch preview to specific file
function switchPreview(index) {
  currentPreviewIndex = index;
  previewSingleFile(uploadedFiles[index]);
  updatePreviewCarousel();
}

// Preview single file
function previewSingleFile(file) {
  // Check file type and handle preview accordingly
  if (file.type.startsWith('image/')) {
    // For images, show the image preview
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('previewImage').src = e.target.result;
    };
    reader.readAsDataURL(file);
  } else if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
    // For PDF files, show first page as preview
    previewPDF(file);
  } else if (file.type.includes('word') || file.name.toLowerCase().endsWith('.docx') || file.name.toLowerCase().endsWith('.doc')) {
    // For Word documents, show document info
    previewWordDocument(file);
  } else {
    // For other accepted file types, show file name
    document.getElementById('previewImage').src = 'https://via.placeholder.com/250x200?text=' + encodeURIComponent(file.name);
  }
}

// Remove file from list
async function removeFile(index) {
  uploadedFiles.splice(index, 1);
  
  // Update current preview index
  if (currentPreviewIndex >= uploadedFiles.length) {
    currentPreviewIndex = Math.max(0, uploadedFiles.length - 1);
  }
  
  updateFileList();
  updatePreviewCarousel();
  
  if (uploadedFiles.length > 0) {
    previewSingleFile(uploadedFiles[currentPreviewIndex]);
  } else {
    document.getElementById('previewImage').src = 'https://via.placeholder.com/250x200?text=Select+a+Service';
  }

  // Update price calculation
  await calculateTotalPrice();
}

// Validate file type
function validateFileType(file) {
  const allowedTypes = [
    'image/webp',
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/tiff',
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
    'application/msword', // DOC
    'application/octet-stream' // Fallback for some DOCX files
  ];
  
  const allowedExtensions = ['.webp', '.jpeg', '.jpg', '.png', '.tiff', '.tif', '.pdf', '.docx', '.doc'];
  
  // Check MIME type first
  if (allowedTypes.includes(file.type)) {
    return true;
  }
  
  // Check file extension as fallback (more reliable for DOCX)
  const fileName = file.name.toLowerCase();
  const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
  
  if (hasValidExtension) {
    return true;
  }
  
  // Additional check for DOCX files that might have unusual MIME types
  if (fileName.endsWith('.docx') || fileName.endsWith('.doc')) {
    return true;
  }
  
  return false;
}

// Preview PDF file (first page)
function previewPDF(file) {
  const fileReader = new FileReader();
  
  fileReader.onload = function() {
    // Load PDF.js
    if (typeof pdfjsLib === 'undefined') {
      // Load PDF.js dynamically if not already loaded
      loadPDFJS().then(() => {
        renderPDFPreview(fileReader.result);
      });
    } else {
      renderPDFPreview(fileReader.result);
    }
  };
  
  fileReader.readAsArrayBuffer(file);
}

// Load PDF.js library dynamically
function loadPDFJS() {
  return new Promise((resolve, reject) => {
    if (typeof pdfjsLib !== 'undefined') {
      resolve();
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js';
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
  });
}

// Render PDF preview
function renderPDFPreview(data) {
  // Set PDF.js worker path
  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

  const loadingTask = pdfjsLib.getDocument({ data: data });
  
  loadingTask.promise.then(function(pdf) {
    // Get first page
    pdf.getPage(1).then(function(page) {
      const scale = 1.5;
      const viewport = page.getViewport({ scale: scale });

      // Create canvas for PDF rendering
      const canvas = document.createElement('canvas');
      const context = canvas.getContext('2d');
      canvas.height = viewport.height;
      canvas.width = viewport.width;

      // Render PDF page to canvas
      const renderContext = {
        canvasContext: context,
        viewport: viewport
      };

      page.render(renderContext).promise.then(function() {
        // Convert canvas to data URL and set as preview
        document.getElementById('previewImage').src = canvas.toDataURL();
      });
    });
  }).catch(function(error) {
    console.error('Error rendering PDF:', error);
    // Fallback to placeholder if PDF rendering fails
    document.getElementById('previewImage').src = 'https://via.placeholder.com/250x200?text=PDF+Preview+Failed';
  });
}

// Preview Word document (shows document info since we can't render content easily)
function previewWordDocument(file) {
  const reader = new FileReader();
  
  reader.onload = function(e) {
    // For Word documents, we'll show a nice preview with document info
    const fileSize = (file.size / 1024 / 1024).toFixed(2);
    const fileExtension = file.name.split('.').pop().toUpperCase();
    
    // Create a canvas with document info
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = 250;
    canvas.height = 200;
    
    // Draw background
    ctx.fillStyle = '#f8f9fa';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Draw document icon (blue for Word documents)
    ctx.fillStyle = '#2b579a'; // Word blue
    ctx.fillRect(20, 30, 40, 50);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(25, 35, 30, 40);
    
    // Draw "W" icon (like Word logo)
    ctx.fillStyle = '#2b579a';
    ctx.font = 'bold 20px Arial';
    ctx.fillText('W', 35, 60);
    
    // Draw text info
    ctx.fillStyle = '#333333';
    ctx.font = 'bold 14px Arial';
    ctx.fillText('Word Document', 80, 45);
    
    ctx.font = '12px Arial';
    ctx.fillText(`Type: ${fileExtension}`, 80, 65);
    ctx.fillText(`Size: ${fileSize} MB`, 80, 85);
    ctx.fillText(`Name: ${file.name.substring(0, 12)}${file.name.length > 12 ? '...' : ''}`, 80, 105);
    ctx.fillText('Click to view full details', 80, 125);
    
    // Convert to data URL
    document.getElementById('previewImage').src = canvas.toDataURL();
  };
  
  reader.onerror = function(error) {
    console.error('Error reading Word file:', error);
    // Fallback to simple placeholder
    document.getElementById('previewImage').src = 'https://via.placeholder.com/250x200?text=Word+Document';
  };
  
  reader.readAsArrayBuffer(file);
}

// Place order
function placeOrder(event) {
  event.preventDefault();

  // Check if user is logged in
  if (!isLoggedIn) {
    Swal.fire({
      icon: 'warning',
      title: 'Login Required',
      text: 'Please sign in to place an order.',
      confirmButtonText: 'Sign In',
      showCancelButton: true,
      cancelButtonText: 'Cancel',
      allowOutsideClick: false
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'signin.php';
      }
    });
    return;
  }

  // Validate form
  const service = document.getElementById('serviceInput').value;
  const size = document.getElementById('sizeSelect').value;
  const paperType = document.getElementById('paperType').value;
  const colorType = document.getElementById('colorType').value;
  const quantity = document.getElementById('quantity').value;
  const contactNumber = document.getElementById('contactNumber').value;

  if (!service || !size || !paperType || !colorType || !quantity || !contactNumber || uploadedFiles.length === 0) {
    Swal.fire({
      icon: 'error',
      title: 'Missing Information',
      text: 'Please fill in all required fields and upload at least one file.',
      confirmButtonText: 'OK',
      allowOutsideClick: false
    });
    return;
  }

// Clean input (remove everything except numbers)
const cleanContact = contactNumber.replace(/\D/g, '');

// Contact number validation (must be exactly 11 digits starting with 09)
const phonePattern = /^09\d{9}$/;

if (!phonePattern.test(cleanContact)) {
  Swal.fire({
    icon: 'error',
    title: 'Invalid Contact Number',
    text: 'Please enter a valid 11-digit number starting with 09.',
    confirmButtonText: 'OK',
    allowOutsideClick: false
  });
  return;
}



  // Validate file types
  const invalidFiles = uploadedFiles.filter(file => !validateFileType(file));
  if (invalidFiles.length > 0) {
    Swal.fire({
      icon: 'error',
      title: 'Invalid File Types',
      text: 'Some files are not in accepted formats. Please remove them and try again.',
      confirmButtonText: 'OK'
    });
    return;
  }

  // Check stock if applicable
  if (window.currentService && window.currentService.stock_quantity < quantity) {
    Swal.fire({
      icon: 'error',
      title: 'Insufficient Stock',
      text: `Only ${window.currentService.stock_quantity} items available for ${window.currentService.service_name}.`,
      confirmButtonText: 'OK',
      allowOutsideClick: false
    });
    return;
  }

  // Show loading modal
  Swal.fire({
    title: 'Processing Your Order...',
    html: `Please wait while we process your printing order with ${uploadedFiles.length} file(s).<br><br><i class="fas fa-spinner fa-spin"></i>`,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    confirmButtonColor: '#F5276C'
  });

  // In placeOrder function - add this before creating FormData
console.log('=== FORM SUBMISSION DEBUG ===');
const paperSelect = document.getElementById('paperType');
console.log('Paper Type Select Element:', paperSelect);
console.log('Selected Value:', paperSelect.value);
console.log('Selected Index:', paperSelect.selectedIndex);
console.log('Selected Option:', paperSelect.options[paperSelect.selectedIndex]);

// Verify the value is not empty
if (!paperSelect.value) {
  console.error('PAPER TYPE IS EMPTY! This will cause database issues.');
  Swal.fire({
    icon: 'error',
    title: 'Paper Type Required',
    text: 'Please select a paper type before submitting.',
    confirmButtonText: 'OK'
  });
  return;
}

// Log all form values
console.log('Service:', document.getElementById('serviceInput').value);
console.log('Size:', document.getElementById('sizeSelect').value);
console.log('Paper Type:', document.getElementById('paperType').value);
console.log('Quantity:', document.getElementById('quantity').value);

  // Get payment method
  const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
  
  // Create FormData for file upload
  const formData = new FormData();
  formData.append('service', service);
  formData.append('description', document.getElementById('descriptionInput').value);
  formData.append('size', size);
  formData.append('paper_type', paperType);
  formData.append('color_type', colorType);
  formData.append('quantity', quantity);
  formData.append('price', document.getElementById('priceInput').value);
  formData.append('contact_number', contactNumber);
  formData.append('special_instructions', document.getElementById('specialInstructions').value);
  formData.append('payment_method', paymentMethod);
  
  // Append all files
  uploadedFiles.forEach((file, index) => {
    formData.append(`images[]`, file);
  });
  
  // Close the loading modal and show payment method info before proceeding
  Swal.close();
  
  // Show payment method specific information
  if (paymentMethod === 'online') {
    Swal.fire({
      icon: 'info',
      title: 'Online Payment Process',
      html: `
        <div style="text-align: left; font-size: 0.95rem; line-height: 1.8;">
          <p><strong>How the payment process works:</strong></p>
          <ol style="margin: 15px 0; padding-left: 20px;">
            <li>Your order will be submitted and reviewed by our team</li>
            <li>Once approved, your order status will change to <strong>"Validated"</strong></li>
            <li>You will receive a notification when your order is ready for payment</li>
            <li>Go to <strong>Profile → Manage Printing Orders</strong> and click the <strong>Pay</strong> button to complete payment</li>
            <li>After payment is confirmed, your order will be <strong>Processed</strong></li>
            <li>You will receive a receipt via email</li>
          </ol>
          <p style="background-color: #e7f3ff; padding: 10px; border-radius: 5px; margin-top: 15px;">
            <i class="fas fa-info-circle"></i> <strong>Note:</strong> Please ensure you complete payment within 7 days of order validation to avoid cancellation.
          </p>
        </div>
      `,
      confirmButtonText: 'Proceed with Order',
      cancelButtonText: 'Cancel',
      showCancelButton: true,
      confirmButtonColor: '#F5276C',
      cancelButtonColor: '#6c757d',
      allowOutsideClick: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Show processing modal and submit form
        Swal.fire({
          title: 'Processing Your Order...',
          html: `Please wait while we process your printing order with ${uploadedFiles.length} file(s).<br><br><i class="fas fa-spinner fa-spin"></i>`,
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          confirmButtonColor: '#F5276C'
        });
        submitPrintOrder(formData);
      }
    });
  } else if (paymentMethod === 'in_person') {
    Swal.fire({
      icon: 'info',
      title: 'In-Person Payment Process',
      html: `
        <div style="text-align: left; font-size: 0.95rem; line-height: 1.8;">
          <p><strong>How the payment process works:</strong></p>
          <ol style="margin: 15px 0; padding-left: 20px;">
            <li>Your order will be submitted and reviewed by our team</li>
            <li>Once approved, your order status will change to <strong>"Validated"</strong></li>
            <li>Your order will be <strong>immediately processed</strong> after validation</li>
            <li>Wait for your order status to change to <strong>"Ready for Pick-Up"</strong></li>
            <li>Pick up your order and <strong>pay in-person</strong> at our office</li>
            <li>You will receive your receipt upon payment</li>
          </ol>
          <p style="background-color: #fff3cd; padding: 10px; border-radius: 5px; margin-top: 15px;">
            <i class="fas fa-clock"></i> <strong>Note:</strong> Ensure you pick up your order within 7 days of the "Ready for Pick-Up" status to avoid additional storage charges.
          </p>
        </div>
      `,
      confirmButtonText: 'Proceed with Order',
      cancelButtonText: 'Cancel',
      showCancelButton: true,
      confirmButtonColor: '#F5276C',
      cancelButtonColor: '#6c757d',
      allowOutsideClick: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Show processing modal and submit form
        Swal.fire({
          title: 'Processing Your Order...',
          html: `Please wait while we process your printing order with ${uploadedFiles.length} file(s).<br><br><i class="fas fa-spinner fa-spin"></i>`,
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          confirmButtonColor: '#F5276C'
        });
        submitPrintOrder(formData);
      }
    });
  }
}

// Scroll to services section
function scrollToServices() {
  document.querySelector('.printing-layout').scrollIntoView({
    behavior: 'smooth'
  });
}

// Submit print order via AJAX
function submitPrintOrder(formData) {
  fetch('print_order.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    console.log('Response status:', response.status);
    // Check if it's a redirect (302) or direct response
    if (response.redirected) {
      // Handle redirect - check URL for message parameter
      const urlParams = new URL(response.url).searchParams;
      const message = urlParams.get('message');

      if (message === 'email_sent') {
        Swal.fire({
          icon: 'success',
          title: 'Order Submitted Successfully!',
          html: `Thank you for your printing order with ${uploadedFiles.length} file(s)!<br><br>A confirmation email has been sent to your email address.<br><br><strong>What happens next?</strong><br>• Our team will review your order<br>• You will receive updates on processing status<br>• Your prints will be ready for pickup`,
          confirmButtonColor: '#F5276C',
          confirmButtonText: 'Continue'
        }).then(() => {
          closeOrderModal();
          clearSelection(); 
        });
      } else if (message === 'order_success_email_failed') {
        Swal.fire({
          icon: 'success',
          title: 'Order Submitted Successfully!',
          html: `Thank you for your printing order with ${uploadedFiles.length} file(s)!<br><br><strong>Note:</strong> Your order was saved but there was an issue sending the confirmation email. Our team will contact you shortly.`,
          confirmButtonColor: '#F5276C',
          confirmButtonText: 'Continue'
        }).then(() => {
          closeOrderModal();
          clearSelection();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Order Processing Error',
          text: 'There was an issue processing your order. Please try again.',
          confirmButtonColor: '#F5276C'
        });
      }
    } else {
      // Handle direct response (shouldn't happen with current setup)
      return response.text().then(text => {
        console.log('Response text:', text);
        Swal.fire({
          icon: 'error',
          title: 'Unexpected Response',
          text: 'Received unexpected response from server.',
          confirmButtonColor: '#F5276C'
        });
      });
    }
  })
  .catch(error => {
    console.error('Fetch error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Connection Error',
      text: 'Could not connect to server. Please check your connection and try again.',
      confirmButtonColor: '#F5276C'
    });
  });
}