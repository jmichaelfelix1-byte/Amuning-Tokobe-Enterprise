<?php 
session_start();

$page_title = 'Printing Services | Amuning Tokobe Enterprise';
$additional_css = ['print.css'];
$additional_js = ['printing.js', 'print_dynamic.js'];
$current_page = 'print.php';
include 'includes/header.php'; ?>

<script>
  // Pass login status to JavaScript
  var isLoggedIn = <?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'user') ? 'true' : 'false'; ?>;

  // Contact number input validation
  document.addEventListener('DOMContentLoaded', function() {
    const contactInput = document.getElementById('contactNumber');
    
    if (contactInput) {
      // Only allow numbers to be typed
      contactInput.addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, '');
        
        // Enforce "09" prefix
        if (value && !value.startsWith('09')) {
          value = '09' + value.replace(/^0?9?/, '');
        }
        
        // Enforce max length of 11
        if (value.length > 11) {
          value = value.slice(0, 11);
        }
        
        this.value = value;
      });
      
      // Prevent non-numeric characters from being pasted
      contactInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const numericOnly = pastedText.replace(/[^0-9]/g, '');
        this.value = numericOnly.slice(0, 11);
      });
    }
  });

  // Check for message parameters and show SweetAlert
  const urlParams = new URLSearchParams(window.location.search);
  const message = urlParams.get('message');

  if (message) {
    if (message === 'order_success') {
      Swal.fire({
        icon: 'success',
        title: 'Order Placed Successfully!',
        text: 'Your printing order has been submitted.',
        confirmButtonText: 'OK',
        allowOutsideClick: false
      }).then((result) => {
        if (result.isConfirmed) {
          closeOrderModal();
        }
      });
    } else if (message === 'email_sent') {
      Swal.fire({
        icon: 'success',
        title: 'Order Confirmed!',
        html: 'Your printing order has been submitted successfully!<br><br><strong>Confirmation Email Sent:</strong><br>We\'ve sent a confirmation email with your order details.',
        confirmButtonText: 'Great!',
        allowOutsideClick: false
      }).then((result) => {
        if (result.isConfirmed) {
          closeOrderModal();
        }
      });
    } else if (message === 'order_success_email_failed') {
      Swal.fire({
        icon: 'warning',
        title: 'Order Placed - Email Issue',
        html: 'Your order was saved successfully, but we couldn\'t send the confirmation email.<br><br>Please check your order status in your account.',
        confirmButtonText: 'OK',
        allowOutsideClick: false
      }).then((result) => {
        if (result.isConfirmed) {
          closeOrderModal();
        }
      });
    } else if (message === 'order_failed') {
      Swal.fire({
        icon: 'error',
        title: 'Order Failed',
        text: 'There was an error saving your order. Please try again.',
        confirmButtonText: 'OK',
        allowOutsideClick: false
      });
    } else if (message === 'login_required') {
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
    } else if (message === 'validation_error') {
      Swal.fire({
        icon: 'error',
        title: 'Missing Information',
        text: 'Please fill in all required fields.',
        confirmButtonText: 'OK',
        allowOutsideClick: false
      });
    }
  }

  // Show booking policy popup on page load
  function showBookingPolicyPopup() {
    const now = Date.now();
    const lastSessionTime = sessionStorage.getItem('printingPolicySessionTime');
    const popupDisabled = sessionStorage.getItem('printingPolicyPopupDisabled');
    
    // If more than 30 minutes have passed, treat it as a new session
    // This ensures popup shows even if sessionStorage somehow persists
    const isNewSession = !lastSessionTime || (now - parseInt(lastSessionTime)) > 30 * 60 * 1000;
    
    // Only show popup if:
    // 1. This is detected as a new session OR
    // 2. Popup hasn't been disabled for this session
    if (isNewSession || popupDisabled !== 'true') {
      // Set or update the session time
      sessionStorage.setItem('printingPolicySessionTime', now.toString());
      // Clear the disabled flag for this new session
      sessionStorage.removeItem('printingPolicyPopupDisabled');
    } else if (popupDisabled === 'true') {
      // Popup is disabled for this session
      return;
    }

    Swal.fire({
      title: 'Important Booking Information',
      html: `
        <div style="text-align: left;">
          <p style="margin-bottom: 15px; color: #333; line-height: 1.6;">
            After ordering, please wait for your order to be validated. Only once it has been approved, can you proceed with payment, and your order will be processed officially after payment is completed.
          </p>
          <div style="display: flex; align-items: center; gap: 10px; justify-content: center; margin-top: 20px;">
            <input type="checkbox" id="dontShowAgainCheckbox" style="width: 18px; height: 18px; cursor: pointer;">
            <label for="dontShowAgainCheckbox" style="cursor: pointer; margin: 0; font-size: 14px; color: #555;">
              Don't show this again on this session
            </label>
          </div>
        </div>
      `,
      icon: 'info',
      confirmButtonText: 'I Understand',
      confirmButtonColor: '#F5276C',
      allowOutsideClick: false,
      didOpen: function(modal) {
        // Add event listener to checkbox
        const checkbox = document.getElementById('dontShowAgainCheckbox');
        if (checkbox) {
          checkbox.addEventListener('change', function() {
            if (this.checked) {
              sessionStorage.setItem('printingPolicyPopupDisabled', 'true');
            }
          });
        }
      }
    });
  }

  // Call the popup on page load
  document.addEventListener('DOMContentLoaded', function() {
    showBookingPolicyPopup();
  });
</script>

  <!-- Interactive Banner -->
  <section class="banner">
    <div class="banner-content">
      <h1>Printing Services</h1>
      <p>High-quality printing solutions for all your needs</p>
      <div class="banner-buttons">
        <button class="btn-primary" onclick="scrollToServices()">View Services</button>
      </div>
    </div>
  </section>

  <!-- Main Printing Layout -->
  <section class="printing-layout">
    <!-- Left: Clickable Services -->
    <div class="services-left">
      <h2 style="color: white;">Printing Services</h2>
      <div class="services-grid">
        <!-- Services will be loaded dynamically -->
      </div>
    </div>
  </section>

  <!-- Order Form Modal (Hidden by default) -->
  <div id="orderFormModal" class="order-form-modal" style="display: none;">
    <div class="order-form-modal-content">
      <!-- Close button -->
      <button class="modal-close-btn" onclick="closeOrderModal()">&times;</button>

      <!-- Preview Box -->
      <div class="preview-box">
        <div class="preview-header">
          <h3>Preview</h3>
          <button class="clear-btn" onclick="clearSelection()">Clear</button>
        </div>
        <div class="preview-content">
          <img id="previewImage" src="https://via.placeholder.com/250x200?text=Select+a+Service" alt="Preview">
          <div class="preview-info">
            <h4 id="selectedService">No Service Selected</h4>
            <p id="serviceDescription">Select a service to see details</p>
          </div>
        </div>
      </div>

      <!-- Order Form -->
      <form action="print_order.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" id="serviceInput" name="service">
        <input type="hidden" id="descriptionInput" name="description">
        <div class="options-box">
          <h3>Customize Your Order</h3>

          <div class="option-group">
            <label for="sizeSelect">Size:</label>
            <select id="sizeSelect" name="size">
              <option value="">Select Size</option>
            </select>
          </div>

          <div class="option-group">
            <label for="paperType">Paper Type:</label>
            <select id="paperType" name="paper_type">
              <option value="">Select Paper Type</option>
            </select>
          </div>

          <div class="option-group">
            <label for="colorType">Print Color:</label>
            <select id="colorType" name="color_type">
              <option value="">Select Color Type</option>
              <option value="colored">Colored Print</option>
              <option value="black_and_white">Black & White</option>
            </select>
          </div>

          <div class="option-group">
            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" min="1" value="1">
          </div>

          <div class="price-display">
            <p>
              <strong>Total Price: <span id="priceDisplay">₱0.00</span></strong>
              <br>
              <small style="color: #666;" id="priceBreakdown">(Select service and upload files)</small>
            </p>
            <input type="hidden" id="priceInput" name="price" value="0.00">
          </div>

          <div class="upload-instructions">
            <h4>File Upload Guidelines:</h4>
            <ul class="instruction-list">
              <li>🖼️ <strong>For images:</strong> Upload WEBP, JPG, PNG, or TIFF - full preview available</li>
              <li>📄 <strong>For PDFs:</strong> First page will be shown as preview</li>
              <li>📝 <strong>For Word documents:</strong> Document info will be displayed</li>
              <li>📎 <strong>Multiple files:</strong> You can upload up to 10 files at once</li>
              <li>✅ <strong>Accepted formats:</strong> WEBP, JPEG, JPG, PDF, PNG, TIFF, DOCX</li>
            </ul>
          </div>

          <div class="upload-section">
            <label class="upload-btn">
              <i class="fas fa-upload"></i> Upload Your Files
              <input type="file" id="uploadInput" name="images[]" onchange="previewUpload(event)" 
                    accept=".webp,.jpeg,.jpg,.pdf,.png,.tiff,.tif,.docx,.doc" multiple>
            </label>
            <p class="upload-note">Accepted formats: WEBP, JPEG, JPG, PDF, PNG, TIFF, DOCX (Max 10 files, 10MB each)</p>
            <div id="fileList" class="file-list"></div>
          </div>

          <div class="option-group">
            <label>Payment Method:</label>
            <div class="payment-method-group">
              <div class="radio-wrapper">
                <input type="radio" id="paymentOnline" name="payment_method" value="online" checked required>
                <label for="paymentOnline" class="radio-label">
                  <i class="fas fa-credit-card"></i> Online Payment
                  <span class="radio-desc">Pay online after order is validated</span>
                </label>
              </div>
              <div class="radio-wrapper">
                <input type="radio" id="paymentInPerson" name="payment_method" value="in_person" required>
                <label for="paymentInPerson" class="radio-label">
                  <i class="fas fa-handshake"></i> In-Person Payment
                  <span class="radio-desc">Pay when picking up your order</span>
                </label>
              </div>
              <div id="paymentWarning" style="display: none; margin-top: 10px; padding: 10px; background-color: #ffe5e5; border-left: 4px solid #dc3545; color: #721c24; font-size: 0.9rem; border-radius: 3px;">
                <i class="fas fa-exclamation-circle"></i> <strong>Note:</strong> This order exceeds 30 pages. Online payment is the only available method for large orders.
              </div>
            </div>
          </div>

          <div class="option-group">
            <label for="contactNumber">Contact Number:</label>
            <input type="text" id="contactNumber" name="contact_number" required 
                  inputmode="numeric"
                  minlength="11"
                  maxlength="11"
                  pattern="^09\d{9}$" 
                  title="Please enter a valid contact number starting with 09 (11 digits total)"
                  placeholder="e.g., 09123456789"
                  class="form-input">
          </div>

          <div class="option-group">
            <label for="specialInstructions">Special Instructions (Optional):</label>
            <div class="instructions-hint">
              <p>For multiple images, please specify:</p>
              <ul>
                <li>Number of images per page</li>
                <li>Preferred image arrangement</li>
                <li>Any specific sizing requirements</li>
                <li>Page orientation (Portrait/Landscape)</li>
              </ul>
            </div>
            <textarea id="specialInstructions" name="special_instructions" 
                      rows="5" 
                      placeholder="e.g., 'Please print 4 images per page in 2x2 grid layout. All images should be 3.5x5 inches each.'"
                      class="form-textarea"></textarea>
          </div>

          <button type="button" class="order-btn" onclick="placeOrder(event)">Place Order</button>
        </div>
      </form>
    </div>
  </div>

<?php include 'includes/footer.php'; ?>
<!-- PDF.js for PDF preview -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
<script src="assets/js/script.js"></script>
<script src="assets/js/printing.js"></script>
<script src="assets/js/print_dynamic.js"></script>