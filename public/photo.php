<?php 
session_start();

$page_title = 'Photo Services | Amuning Tokobe Enterprise';
$additional_css = ['photo.css'];
$additional_js = ['photo.js', 'photo_dynamic.js'];
include 'includes/header.php'; ?>


<script>
  // Pass login status to JavaScript
  var isLoggedIn = <?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'user') ? 'true' : 'false'; ?>;

  // Mobile number input validation
  document.addEventListener('DOMContentLoaded', function() {
    const mobileInput = document.getElementById('modalMobile');
    
    if (mobileInput) {
      // Only allow numbers to be typed
      mobileInput.addEventListener('input', function(e) {
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
      mobileInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const numericOnly = pastedText.replace(/[^0-9]/g, '');
        this.value = numericOnly.slice(0, 11);
      });
    }
  });
</script>

<style>
  
</style>
  <!-- Interactive Banner -->
  <section class="banner">
    <div class="banner-content">
      <h1>Photo Services</h1>
      <p>Fun photobooth services for your memorable moments</p>
      <div class="banner-buttons">
        <button class="btn-primary" onclick="scrollToServices()">View Services</button>
      </div>
    </div>
  </section>

  <!-- Main Photo Services Layout -->
  <section class="printing-layout">
    <!-- Left: Clickable Services -->
    <div class="services-left">
      <h2 style="color: white;">Photo Services</h2>
      <div class="services-grid">
        <!-- Services will be loaded dynamically -->
      </div>
    </div>

    <!-- Right: Details for Every Service -->
    <div class="details-right">
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

  <form action="print_order.php" method="POST" enctype="multipart/form-data">
  <input type="hidden" id="serviceInput" name="service">
  <input type="hidden" id="descriptionInput" name="description">
  <div class="options-box">
    <h3>Customize Your Package</h3>

    <div class="options-grid">
      <div class="option-group">
        <label for="sizeSelect">Coverage Duration:</label>
        <select id="sizeSelect" name="size">
          <option value="2-hours">2 Hours</option>
          <option value="4-hours">4 Hours</option>
          <option value="6-hours">6 Hours</option>
          <option value="8-hours">8 Hours (Full Day)</option>
          <option value="12-hours">12 Hours</option>
        </select>
      </div>

      <div class="option-group">
        <label for="paperType">Package Type:</label>
        <select id="paperType" name="paper">
          <option value="basic">Basic (Photos Only)</option>
          <option value="standard">Standard (Photos + Editing)</option>
        </select>
      </div>
    </div>

    <div class="option-group full-width-product">
      <label for="productSelect">Product:</label>
      <select id="productSelect" name="product">
        <option value="classic">Classic Photo Booth</option>
      </select>
    </div>

    <div class="price-display">
      <p>Estimated Price: <span id="priceDisplay">₱0.00</span></p>
      <!-- Hidden input to send price value -->
      <input type="hidden" id="priceInput" name="price" value="₱0.00">
    </div>

    <div class="button-group">
      <button type="button" class="order-btn fill-btn" onclick="showBookingModal()">Fill up Form</button>
    </div>

  </div>
</form>
  </section>

  <!-- Booking Modal -->
  <div id="bookingModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Complete Your Booking</h2>
        <span class="close" onclick="closeModal()">&times;</span>
      </div>
      <form id="bookingForm" onsubmit="return submitBooking(event);">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label for="modalName">Full Name <span class="required">*</span></label>
              <input type="text" id="modalName" name="name" value="<?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : ''; ?>" readonly disabled required>
            </div>
            <div class="form-group">
              <label for="modalEmail">Email Address <span class="required">*</span></label>
              <input type="email" id="modalEmail" name="email" value="<?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''; ?>" readonly disabled required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modalMobile">Mobile Number <span class="required">*</span></label>
              <input type="text" id="modalMobile" name="mobile" 
                     inputmode="numeric"
                     maxlength="11"
                     pattern="^09\d{9}$" 
                     title="Please enter a valid mobile number starting with 09 (11 digits total)"
                     placeholder="e.g., 09123456789"
                     required>
            </div>
            <div class="form-group">
              <label for="modalEventType">Type of Event <span class="required">*</span></label>
              <input type="text" id="modalEventType" name="eventType" readonly disabled>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modalDuration">Coverage Duration</label>
              <input type="text" id="modalDuration" disabled>
            </div>
            <div class="form-group">
              <label for="modalPackage">Package Type</label>
              <input type="text" id="modalPackage" disabled>
            </div>
          </div>

          <div class="form-group">
            <label for="modalProduct">Product <span class="required">*</span></label>
            <input type="text" id="modalProduct" name="product" readonly disabled required>
          </div>

          <div class="form-group">
            <label for="modalPrice">Estimated Price</label>
            <input type="text" id="modalPrice" disabled>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modalEventDate">Date of Event <span class="required">*</span></label>
              <input type="text" id="modalEventDate" name="eventDate" class="flatpickr" readonly required>
              <small class="input-help"><span><i class="fas fa-calendar"></i></span> Bookings require 3 days advance notice. Red dates with 🚫 are fully booked</small>
            </div>
            <div class="form-group">
              <label for="modalTime">Time of Service <span class="required">*</span></label>
              <input type="time" id="modalTime" name="time" required>
            </div>
          </div>

          <div class="form-group">
            <label for="modalVenue">Event Venue <span class="required">*</span></label>
            <input type="text" id="modalVenue" name="venue" placeholder="Venue name" required>
          </div>

          <div class="form-group">
            <label for="modalStreet">Street Address <span class="required">*</span></label>
            <input type="text" id="modalStreet" name="street" placeholder="Street address" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modalCity">City <span class="required">*</span></label>
              <select id="modalCity" name="city" required onchange="updateBarangays()">
                <option value="">-- Select City --</option>
                <option value="Angono">Angono</option>
                <option value="Antipolo">Antipolo</option>
                <option value="Baras">Baras</option>
                <option value="Binangonan">Binangonan</option>
                <option value="Cainta">Cainta</option>
                <option value="Cardona">Cardona</option>
                <option value="Jalajala">Jalajala</option>
                <option value="Morong">Morong</option>
                <option value="Pililla">Pililla</option>
                <option value="Rodriguez">Rodriguez</option>
                <option value="San Mateo">San Mateo</option>
                <option value="Tanay">Tanay</option>
                <option value="Taytay">Taytay</option>
                <option value="Teresa">Teresa</option>
              </select>
            </div>
            <div class="form-group">
              <label for="modalRegion">Province <span class="required">*</span></label>
              <input type="text" id="modalRegion" value="Rizal" readonly disabled required>
              <input type="hidden" name="region" value="Rizal">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modalBarangay">Barangay <span class="required">*</span></label>
              <select id="modalBarangay" name="barangay" required onchange="updatePostalCode(); updateLocationMap()">
                <option value="">-- Select Barangay --</option>
              </select>
            </div>
            <div class="form-group">
              <label for="modalPostal">Postal Code <span class="required">*</span></label>
              <input type="text" id="modalPostal" name="postal" readonly disabled required>
            </div>
          </div>

          <!-- Location Map -->
          <div class="form-group map-container">
            <label>Service Location Map</label>
            <div id="locationMap" style="height: 400px; border-radius: 8px; border: 1px solid #ddd;"></div>
            <small class="input-help"><span><i class="fas fa-map-marker-alt"></i></span> Red marker = Business | Blue marker = Your location</small>
          </div>

          <!-- Travel Fee Display -->
          <div id="travelFeeDisplay" class="travel-fee-display" style="display: none;">
            <div class="travel-fee-header">
              <i class="fas fa-truck"></i> Travel Fee
            </div>
            <div id="travelFeeContent" class="travel-fee-content">
              <!-- Content will be populated by JavaScript -->
            </div>
          </div>

          <div class="form-group">
            <label for="modalRemarks">Remarks</label>
            <textarea id="modalRemarks" name="remarks" rows="4" placeholder="Additional notes or special requests..."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn-done">Submit Booking</button>
        </div>
      </form>
    </div>
  </div>
<script src="assets/js/photo.js"></script>
<script src="assets/js/script.js"></script>
  <script>
    // Duration multipliers
    const durationMultipliers = {
      '2-hours': 1.0,
      '4-hours': 1.8,
      '6-hours': 2.5,
      '8-hours': 3.2,
      '12-hours': 4.5
    };

    // Product multipliers
    const productMultipliers = {
      'classic': 1.0,
      '360': 1.5,
      'mirror': 1.3,
      'roaming': 1.2,
      'full': 2.0
    };

    // Product labels for display fields
    const productLabels = {
      'classic': 'Classic Photo Booth',
    };

    const durationLabels = {
      '2-hours': '2 Hours',
      '4-hours': '4 Hours',
      '6-hours': '6 Hours',
      '8-hours': '8 Hours (Full Day)',
      '12-hours': '12 Hours'
    };

    const packageLabels = {
      'basic': 'Basic (Photos Only)',
      'standard': 'Standard (Photos + Editing)',
    };

    function syncSummaryFields() {
      const durationSelect = document.getElementById('sizeSelect');
      const packageSelect = document.getElementById('paperType');

      const durationInput = document.getElementById('modalDuration');
      const packageInput = document.getElementById('modalPackage');
      const priceInput = document.getElementById('modalPrice');

      if (durationInput && durationSelect) {
        durationInput.value = durationLabels[durationSelect.value] || '';
      }

      if (packageInput && packageSelect) {
        packageInput.value = packageLabels[packageSelect.value] || '';
      }

      if (priceInput) {
        priceInput.value = document.getElementById('priceDisplay').textContent;
      }
    }

    function getSelectedService() {
      return (typeof window.selectedService !== 'undefined' && window.selectedService)
        ? window.selectedService
        : null;
    }

    // Update price calculation
    function updatePrice() {
      const activeService = getSelectedService();
      if (!activeService) return;

      const duration = document.getElementById('sizeSelect').value;
      const packageType = document.getElementById('paperType').value;
      const product = document.getElementById('productSelect').value;
      
      // Get base price from service data
      let basePrice = serviceData[activeService].packages[packageType].price;
      
      // Apply duration multiplier
      let durationPrice = basePrice * durationMultipliers[duration];
      
      // Apply product multiplier
      let totalPrice = durationPrice * productMultipliers[product];

      const formatted = `₱${totalPrice.toFixed(2)}`;

      // Update displayed price
      document.getElementById('priceDisplay').textContent = formatted;

      const priceInput = document.getElementById('priceInput');
      if (priceInput) {
        priceInput.value = formatted;
      }

      syncSummaryFields();
    }

    // Ensure modal functions are available
    function showBookingModal() {
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
    return; // Stop the function here
  }
      const activeService = getSelectedService();

      if (!activeService) {
        Swal.fire({
          icon: 'warning',
          title: 'Select a service',
          text: 'Please choose a service before filling out the form.'
        });
        return;
      }
      
      const modal = document.getElementById('bookingModal');
      const eventTypeInput = document.getElementById('modalEventType');
      const modalProductInput = document.getElementById('modalProduct');

      if (modal && eventTypeInput && modalProductInput) {
        eventTypeInput.value = activeService + ' Photobooth';

        // Pre-select product in modal based on right side selection
        const selectedProduct = document.getElementById('productSelect').value;
        const mappedProduct = productLabels[selectedProduct] || '';
        modalProductInput.value = mappedProduct;

        syncSummaryFields();

        modal.style.display = 'block';
      } else {
        console.error('Modal elements not found');
      }
    }

    function closeModal() {
      const modal = document.getElementById('bookingModal');
      if (modal) {
        modal.style.display = 'none';
        // Reset order summary visibility
        const orderSummary = document.getElementById('orderSummary');
        if (orderSummary) {
          orderSummary.style.display = 'none';
        }
      }
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      const modal = document.getElementById('bookingModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    });

    function submitBooking(event) {
      event.preventDefault();
      event.stopPropagation();

      const bookingForm = document.getElementById('bookingForm');
      if (!bookingForm.checkValidity()) {
        Swal.fire({
          icon: 'error',
          title: 'Complete required fields',
          text: 'Please fill out all required fields before submitting the form.',
          confirmButtonColor: '#F5276C'
        }).then(() => {
          bookingForm.reportValidity();
        });
        return false;
      }

      const formData = {
        name: document.getElementById('modalName').value,
        email: document.getElementById('modalEmail').value,
        mobile: document.getElementById('modalMobile').value,
        eventType: document.getElementById('modalEventType').value,
        product: document.getElementById('modalProduct').value,
        eventDate: document.getElementById('modalEventDate').value,
        timeOfService: document.getElementById('modalTime').value,
        venue: document.getElementById('modalVenue').value,
        street: document.getElementById('modalStreet').value,
        city: document.getElementById('modalCity').value,
        barangay: document.getElementById('modalBarangay').value,
        region: document.getElementById('modalRegion').value,
        postal: document.getElementById('modalPostal').value,
        country: document.getElementById('modalCountry')?.value || 'Philippines',
        remarks: document.getElementById('modalRemarks').value,
        packageType: document.getElementById('modalPackage').value,
        duration: document.getElementById('modalDuration').value,
        price: document.getElementById('modalPrice').value,
        travelFee: window.currentTravelFee || '0'
      };
      
      // Send data to server
      console.log('Submitting booking data:', formData);
      
      // Show loading alert
      Swal.fire({
        title: 'Processing Your Booking...',
        html: 'Please wait while we process your photo booking request.<br><br><i class="fas fa-spinner fa-spin"></i>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        confirmButtonColor: '#F5276C'
      });
      
      fetch('process_photo_booking.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      })
      .then(response => {
        console.log('Response status:', response.status);
        return response.text();
      })
      .then(text => {
        console.log('Response text:', text);
        try {
          const data = JSON.parse(text);
          console.log('Parsed data:', data);
          
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Booking Submitted Successfully!',
              html: `Thank you for your booking, <strong>${formData.name}</strong>!<br><br>A confirmation email has been sent to <strong>${formData.email}</strong> with your booking details and payment instructions.<br><br><strong>Booking ID:</strong> #${data.booking_id || 'N/A'}<br><strong>Status:</strong> Pending Payment`,
              confirmButtonColor: '#F5276C',
              confirmButtonText: 'Continue'
            }).then(() => {
              closeModal();
              document.getElementById('bookingForm').reset();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Booking Failed',
              text: data.message || 'An error occurred. Please try again.',
              confirmButtonColor: '#F5276C'
            });
          }
        } catch (e) {
          console.error('JSON parse error:', e);
          Swal.fire({
            icon: 'error',
            title: 'Server Error',
            text: 'Server returned invalid response: ' + text.substring(0, 100),
            confirmButtonColor: '#F5276C'
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
      
      return false; // Prevent form from submitting normally
    }

    // Add event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
      // Add listeners to update price
      const productSelect = document.getElementById('productSelect');
      const durationSelect = document.getElementById('sizeSelect');
      const packageSelect = document.getElementById('paperType');
      const syncModalProduct = () => {
        const modalProductInput = document.getElementById('modalProduct');
        if (!modalProductInput || !productSelect) return;
        const mappedProduct = productLabels[productSelect.value] || '';
        modalProductInput.value = mappedProduct;
      };

      if (productSelect) {
        productSelect.addEventListener('change', () => {
          updatePrice();
          syncModalProduct();
          syncSummaryFields();
        });
        syncModalProduct();
      }

      if (durationSelect) {
        durationSelect.addEventListener('change', () => {
          updatePrice();
          syncSummaryFields();
        });
      }

      if (packageSelect) {
        packageSelect.addEventListener('change', () => {
          updatePrice();
          syncSummaryFields();
        });
      }

      syncSummaryFields();
    });

    // Function to show order summary when date and time are selected
    function updateOrderSummary() {
      const eventDate = document.getElementById('modalEventDate').value;
      const eventTime = document.getElementById('modalTime').value;
      
      if (eventDate && eventTime) {
        showOrderSummary(eventDate, eventTime);
      }
    }

    function showOrderSummary(eventDate, eventTime) {
      const orderSummary = document.getElementById('orderSummary');
      if (!orderSummary) return;

      // Get current date/time
      const now = new Date();
      const invoiceDateStr = now.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });

      // Calculate due date (1 day before event)
      const eventDateTime = new Date(eventDate + 'T' + eventTime);
      const dueDateTime = new Date(eventDateTime);
      dueDateTime.setDate(dueDateTime.getDate() - 1);
      const dueDateStr = dueDateTime.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });

      // Set dates
      document.getElementById('invoiceDate').textContent = invoiceDateStr;
      document.getElementById('dueDate').textContent = dueDateStr;

      // Get service details
      const serviceName = document.getElementById('modalEventType').value;
      const duration = document.getElementById('modalDuration').value;
      const packageType = document.getElementById('modalPackage').value;
      const product = document.getElementById('modalProduct').value;
      const finalPrice = document.getElementById('modalPrice').value;

      // Get the actual values from selects to calculate breakdown
      const durationValue = document.getElementById('sizeSelect').value;
      const packageValue = document.getElementById('paperType').value;
      const productValue = document.getElementById('productSelect').value;

      // Get multipliers
      const durationMult = durationMultipliers[durationValue] || 1.0;
      const productMult = productMultipliers[productValue] || 1.0;

      // Calculate breakdown
      const activeService = getSelectedService();
      if (activeService && serviceData[activeService]) {
        // Get base prices (Basic package, 2 hours, Classic product as base)
        const baseServicePrice = serviceData[activeService].packages['basic'].price;
        const selectedPackagePrice = serviceData[activeService].packages[packageValue].price;
        
        // Calculate individual component prices
        const serviceTypePrice = baseServicePrice; // Base service price
        const packageTypePrice = selectedPackagePrice - baseServicePrice; // Additional cost for package upgrade
        
        // Duration cost (additional cost from base 2-hour duration)
        const baseDurationCost = selectedPackagePrice * (durationMult - 1.0);
        
        // Product cost (additional cost from base classic product)
        const productCost = (selectedPackagePrice * durationMult) * (productMult - 1.0);
        
        // Calculate total
        const total = selectedPackagePrice * durationMult * productMult;

        // Build invoice items with detailed breakdown
        const invoiceItems = document.getElementById('invoiceItems');
        invoiceItems.innerHTML = `
          <tr>
            <td>Service Type: ${serviceName}</td>
            <td>₱${serviceTypePrice.toFixed(2)}</td>
            <td>₱0.00</td>
            <td>₱${serviceTypePrice.toFixed(2)}</td>
          </tr>
          <tr>
            <td>Coverage Duration: ${duration}</td>
            <td>₱${baseDurationCost.toFixed(2)}</td>
            <td>₱0.00</td>
            <td>₱${baseDurationCost.toFixed(2)}</td>
          </tr>
          <tr>
            <td>Package Type: ${packageType}</td>
            <td>₱${packageTypePrice.toFixed(2)}</td>
            <td>₱0.00</td>
            <td>₱${packageTypePrice.toFixed(2)}</td>
          </tr>
          <tr>
            <td>Product: ${product}</td>
            <td>₱${productCost.toFixed(2)}</td>
            <td>₱0.00</td>
            <td>₱${productCost.toFixed(2)}</td>
          </tr>
          <tr class="subtotal-row">
            <td colspan="3" style="text-align: right; font-weight: 600;">Subtotal</td>
            <td style="font-weight: 600;">₱${total.toFixed(2)}</td>
          </tr>
        `;

        // Set total
        document.getElementById('orderTotal').textContent = `₱${total.toFixed(2)}`;
      } else {
        // Fallback if service data not available
        const invoiceItems = document.getElementById('invoiceItems');
        invoiceItems.innerHTML = `
          <tr>
            <td>${serviceName}</td>
            <td>${finalPrice}</td>
            <td>₱0.00</td>
            <td>${finalPrice}</td>
          </tr>
          <tr>
            <td class="item-detail">Duration: ${duration}</td>
            <td colspan="3"></td>
          </tr>
          <tr>
            <td class="item-detail">Package: ${packageType}</td>
            <td colspan="3"></td>
          </tr>
          <tr>
            <td class="item-detail">Product: ${product}</td>
            <td colspan="3"></td>
          </tr>
        `;
        
        // Set total
        document.getElementById('orderTotal').textContent = finalPrice;
      }

      // Show order summary
      orderSummary.style.display = 'block';
    }

    // Global variable to store booked slots
    let bookedSlots = {};
    let fullyBookedDays = [];

    // Function to fetch booked slots from server
    async function fetchBookedSlots() {
      try {
        const response = await fetch('get_booked_slots.php');
        const data = await response.json();

        if (data.success) {
          bookedSlots = data.booked_slots;
          fullyBookedDays = data.fully_booked_days || [];
          console.log('Booked slots loaded:', bookedSlots);
          console.log('Fully booked days:', fullyBookedDays);

          // Reinitialize date picker with new data
          updateDateInput();
        } else {
          console.error('Failed to load booked slots');
        }
      } catch (error) {
        console.error('Error fetching booked slots:', error);
      }
    }

    // Function to check if a date is fully booked (8+ hours)
    function isDateFullyBooked(dateString) {
      return fullyBookedDays.includes(dateString);
    }

    // Function to check if a date has any bookings
    function isDateBooked(dateString) {
      return bookedSlots.hasOwnProperty(dateString) && bookedSlots[dateString].length > 0;
    }

    // Function to get available time slots for a date and duration
    async function getAvailableSlots(dateString, duration) {
      try {
        const response = await fetch('api/check_booking_availability.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `date=${dateString}&duration=${duration}`
        });
        const data = await response.json();
        
        if (data.available) {
          return data.available_slots;
        } else {
          console.log('Availability check:', data.message);
          return [];
        }
      } catch (error) {
        console.error('Error checking availability:', error);
        return [];
      }
    }

    // Function to check if a specific time is booked on a date with duration consideration
    function isTimeBooked(dateString, timeString, duration) {
      if (!bookedSlots[dateString]) return false;

      // Extract hours from duration
      const durationMatch = duration.match(/(\d+)/);
      const requestedHours = durationMatch ? parseInt(durationMatch[1]) : 0;
      
      // Parse requested start time
      const [requestedHour, requestedMinute] = timeString.split(':').map(Number);
      const requestedEnd = requestedHour + requestedHours;

      // Check each existing booking
      for (const booking of bookedSlots[dateString]) {
        const [existingHour, existingMinute] = booking.time.split(':').map(Number);
        const durationMatch = booking.duration.match(/(\d+)/);
        const existingHours = durationMatch ? parseInt(durationMatch[1]) : 0;
        const existingEnd = existingHour + existingHours;

        // Check if there's overlap
        if (!(requestedEnd <= existingHour || requestedHour >= existingEnd)) {
          return true; // Time conflict
        }

        // Check 1-hour gap requirement
        const gapBefore = requestedEnd <= (existingHour - 1);
        const gapAfter = requestedHour >= (existingEnd + 1);
        
        if (!gapBefore && !gapAfter) {
          // Not enough gap between bookings
          if (!(requestedEnd < existingHour || requestedHour > existingEnd)) {
            return true;
          }
        }
      }

      return false;
    }

    // Global variable to store Flatpickr instance
    let datePicker = null;

    // Function to initialize/update Flatpickr date picker
    // Function to initialize/update Flatpickr date picker
    function updateDateInput() {
      const dateInput = document.getElementById('modalEventDate');
      if (!dateInput) return;

      // Get disabled dates as strings for comparison
      const fullyBookedDatesStrings = fullyBookedDays || [];
      console.log('Fully booked dates:', fullyBookedDatesStrings);

      // Get today's date
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      // Set minimum date to 3 days from today
      const minDate = new Date(today);
      minDate.setDate(minDate.getDate() + 3);

      // If Flatpickr is already initialized, update it
      if (datePicker) {
        datePicker.destroy();
      }

      // Detect if device is mobile
      const isMobile = window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

      // Initialize Flatpickr with disable function
      datePicker = flatpickr(dateInput, {
        minDate: minDate, // Require 3 days advance booking
        disable: [
          function(date) {
            // Format date to YYYY-MM-DD in local timezone (no UTC conversion)
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const dateString = `${year}-${month}-${day}`;
            
            // Only disable if fully booked (8+ hours)
            const shouldDisable = fullyBookedDatesStrings.includes(dateString);
            if (shouldDisable) {
              console.log(`Disabling date: ${dateString}`);
            }
            return shouldDisable;
          }
        ],
        dateFormat: "Y-m-d",
        defaultDate: null, // Leave blank until user selects a date
        inline: false, // Hide calendar - only show on click
        static: false, // Allow calendar to close on selection
        monthSelectorType: "dropdown", // Better month selector
        onChange: function(selectedDates, dateStr, instance) {
          if (selectedDates.length > 0) {
            updateTimeInput(dateStr);
          }
        },
        onOpen: function(selectedDates, dateStr, instance) {
          console.log('Calendar opened. Checking disabled dates...');
        },
        onDayCreate: function(dObj, dStr, fp, dayElem) {
          // Format date to YYYY-MM-DD in local timezone
          const date = dayElem.dateObj;
          const year = date.getFullYear();
          const month = String(date.getMonth() + 1).padStart(2, '0');
          const day = String(date.getDate()).padStart(2, '0');
          const dateString = `${year}-${month}-${day}`;

          // Check if this date is fully booked (8+ hours)
          if (fullyBookedDatesStrings.includes(dateString)) {
            console.log(`Adding booked-date class to: ${dateString}`);
            dayElem.classList.add('booked-date');

            // Add the prohibited icon (🚫) to the day
            const iconSpan = document.createElement('span');
            iconSpan.className = 'booked-icon';
            iconSpan.textContent = '🚫';
            iconSpan.style.cssText = `
              position: absolute;
              top: 2px;
              right: 2px;
              font-size: 12px;
              line-height: 1;
            `;
            dayElem.style.position = 'relative';
            dayElem.appendChild(iconSpan);

            // Add hover event listeners (only on desktop)
            if (!isMobile) {
              dayElem.addEventListener('mouseenter', function(e) {
                showBookingTooltip(e, 'This date is fully booked');
                // Add mouse move listener to update tooltip position
                const moveHandler = function(moveEvent) {
                  updateTooltipPosition(moveEvent);
                };
                dayElem.addEventListener('mousemove', moveHandler);

                // Store the handler to remove it later
                dayElem._moveHandler = moveHandler;
              });

              dayElem.addEventListener('mouseleave', function(e) {
                hideBookingTooltip();
                // Remove the mouse move listener
                if (dayElem._moveHandler) {
                  dayElem.removeEventListener('mousemove', dayElem._moveHandler);
                  delete dayElem._moveHandler;
                }
              });
            } else {
              // On mobile, add touch feedback for booked dates
              dayElem.addEventListener('touchstart', function(e) {
                e.preventDefault();
                // Show a simple alert for mobile users
                if (!document.querySelector('.mobile-tooltip')) {
                  const tooltip = document.createElement('div');
                  tooltip.className = 'mobile-tooltip';
                  tooltip.textContent = 'This date is already booked';
                  tooltip.style.cssText = `
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: rgba(0, 0, 0, 0.8);
                    color: white;
                    padding: 10px 20px;
                    border-radius: 5px;
                    z-index: 10000;
                    font-size: 14px;
                  `;
                  document.body.appendChild(tooltip);
                  setTimeout(() => tooltip.remove(), 2000);
                }
              });
            }
          }
        }
      });
    }

    // Function to update tooltip position during mouse movement
    function updateTooltipPosition(event) {
      const tooltip = document.querySelector('.booking-tooltip');
      if (tooltip) {
        tooltip.style.left = (event.pageX + 10) + 'px';
        tooltip.style.top = (event.pageY - 30) + 'px';
      }
    }

    // Function to show tooltip on hover
    function showBookingTooltip(event, message) {
      console.log('Showing tooltip:', message);
      // Remove any existing tooltip
      hideBookingTooltip();

      // Create tooltip element
      const tooltip = document.createElement('div');
      tooltip.className = 'booking-tooltip';
      tooltip.textContent = message;

      // Position tooltip near the mouse cursor (CSS will handle final positioning)
      tooltip.style.left = (event.pageX + 10) + 'px';
      tooltip.style.top = (event.pageY - 30) + 'px';

      document.body.appendChild(tooltip);
      console.log('Tooltip created and added to DOM');
    }

    // Function to hide tooltip
    function hideBookingTooltip() {
      const existingTooltip = document.querySelector('.booking-tooltip');
      if (existingTooltip) {
        existingTooltip.remove();
        console.log('Tooltip removed');
      }
    }

    // Function to disable booked times for selected date
    function updateTimeInput(selectedDate) {
      const timeInput = document.getElementById('modalTime');
      const durationInput = document.getElementById('modalDuration');
      if (!timeInput || !selectedDate) return;

      // Clear any previous disabled styling
      timeInput.style.borderColor = '';

      // Show available slots if date is selected
      const duration = durationInput?.value || '';
      if (duration && selectedDate) {
        getAvailableSlots(selectedDate, duration).then(slots => {
          if (slots.length > 0) {
            // Update input's placeholder to show available times
            timeInput.placeholder = `Available: ${slots.slice(0, 5).join(', ')}${slots.length > 5 ? '...' : ''}`;
            console.log('Available time slots:', slots);
          } else if (isDateFullyBooked(selectedDate)) {
            timeInput.placeholder = 'This date is fully booked';
          } else {
            timeInput.placeholder = 'No available slots for selected duration';
          }
        });
      }

      // Remove old event listeners before adding new ones
      const newTimeInput = timeInput.cloneNode(true);
      timeInput.parentNode.replaceChild(newTimeInput, timeInput);

      // Add input validation for time with duration consideration
      newTimeInput.addEventListener('input', function(e) {
        const selectedTime = e.target.value;
        const duration = durationInput?.value || '';
        if (selectedTime && selectedDate && duration && isTimeBooked(selectedDate, selectedTime, duration)) {
          // Show visual indication that this time is booked
          newTimeInput.style.borderColor = '#ef4444';
          newTimeInput.style.borderWidth = '2px';

          // Show alert to user
          Swal.fire({
            icon: 'warning',
            title: 'Time Slot Unavailable',
            text: `The selected time (${selectedTime}) is not available for ${new Date(selectedDate).toLocaleDateString()}. Please choose a different time.`,
            confirmButtonColor: '#F5276C',
            timer: 3000,
            timerProgressBar: true
          });
        } else {
          newTimeInput.style.borderColor = '';
          newTimeInput.style.borderWidth = '';
        }
      });
    }

    // Function to validate booking before submission
    function validateBookingTime() {
      const dateInput = document.getElementById('modalEventDate');
      const timeInput = document.getElementById('modalTime');
      const durationInput = document.getElementById('modalDuration');

      if (!dateInput || !timeInput || !durationInput) return true;

      const selectedDate = dateInput.value;
      const selectedTime = timeInput.value;
      const duration = durationInput.value;

      // Check if date is fully booked
      if (isDateFullyBooked(selectedDate)) {
        Swal.fire({
          icon: 'error',
          title: 'Day Fully Booked',
          text: 'This date is already fully booked with an 8-hour or longer session. Please choose a different date.',
          confirmButtonColor: '#F5276C'
        });
        return false;
      }

      // Check if time slot conflicts with existing bookings
      if (selectedDate && selectedTime && isTimeBooked(selectedDate, selectedTime, duration)) {
        Swal.fire({
          icon: 'error',
          title: 'Time Slot Unavailable',
          text: `The selected time (${selectedTime}) on ${new Date(selectedDate).toLocaleDateString()} conflicts with an existing booking or doesn't meet the 1-hour gap requirement. Please choose a different time.`,
          confirmButtonColor: '#F5276C'
        });
        return false;
      }

      return true;
    }

    // Add event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
      const modalEventDate = document.getElementById('modalEventDate');
      const modalTime = document.getElementById('modalTime');
      const durationSelect = document.getElementById('sizeSelect');

      if (modalEventDate) {
        modalEventDate.addEventListener('change', updateOrderSummary);
        modalEventDate.addEventListener('change', function(e) {
          // Update available time slots when date changes
          updateTimeInput(e.target.value);
        });
      }
      if (modalTime) {
        modalTime.addEventListener('change', updateOrderSummary);
      }
      if (durationSelect) {
        durationSelect.addEventListener('change', function(e) {
          // Update available time slots when duration changes
          const dateInput = document.getElementById('modalEventDate');
          if (dateInput && dateInput.value) {
            updateTimeInput(dateInput.value);
          }
        });
      }

      // Load booked slots when page loads
      fetchBookedSlots();
    });

    // Override the submitBooking function to add time validation
    const originalSubmitBooking = submitBooking;
    submitBooking = function(event) {
      if (!validateBookingTime()) {
        event.preventDefault();
        return false;
      }
      return originalSubmitBooking(event);
    };

    // Update barangays dropdown when city is selected
    function updateBarangays() {
      const citySelect = document.getElementById('modalCity');
      const barangaySelect = document.getElementById('modalBarangay');
      const postalInput = document.getElementById('modalPostal');
      
      if (!citySelect || !barangaySelect) {
        console.error('Modal elements not found');
        return;
      }
      
      const selectedCity = citySelect.value;
      
      // Clear barangay options
      barangaySelect.innerHTML = '<option value="">-- Select Barangay --</option>';
      if (postalInput) {
        postalInput.value = '';
      }
      
      if (selectedCity && rizalLocations[selectedCity]) {
        const barangays = rizalLocations[selectedCity].barangays;
        
        // Add barangay options
        barangays.forEach(barangay => {
          const option = document.createElement('option');
          option.value = barangay;
          option.textContent = barangay;
          barangaySelect.appendChild(option);
        });
      }
    }

    // Update postal code when barangay is selected
    function updatePostalCode() {
      const citySelect = document.getElementById('modalCity');
      const postalInput = document.getElementById('modalPostal');
      
      const selectedCity = citySelect.value;
      
      if (selectedCity && rizalLocations[selectedCity]) {
        postalInput.value = rizalLocations[selectedCity].postal_code;
      }
    }

    // Barangay coordinates verified from Google Maps lookup (City_Barangay format)
    const barangayCoordinates = {
      // Angono done
      "Angono_Bagumbayan": [14.5219, 121.1494],
      "Angono_Kalayaan": [14.5286, 121.1472],
      "Angono_Mahabang Parang": [14.5480, 121.1896],
      "Angono_Poblacion Ibaba": [14.5233, 121.1486],
      "Angono_Poblacion Itaas": [14.5240, 121.1513],
      "Angono_San Isidro": [14.5268, 121.1527],
      "Angono_San Pedro": [14.5224, 121.1512],
      "Angono_San Roque": [14.5327, 121.1729],
      "Angono_San Vicente": [14.5231, 121.1470],
      "Angono_Santo Niño": [14.5259, 121.1508],
      // Antipolo done
      "Antipolo_Bagong Nayon": [14.5910, 121.1640],
      "Antipolo_Beverly Hills": [14.5826, 121.1591],
      "Antipolo_Calawis": [14.6718, 121.2420],
      "Antipolo_Cupang": [14.6332, 121.1383],
      "Antipolo_Dalig": [14.5890, 121.1752],
      "Antipolo_Dela Paz": [14.5902, 121.1700],
      "Antipolo_Inarawan": [14.6247, 121.1804],
      "Antipolo_Mambugan": [14.6187, 121.1405],
      "Antipolo_Mayamot": [14.6296, 121.1202],
      "Antipolo_Muntindilaw": [14.5978, 121.1312],
      "Antipolo_San Isidro": [14.5919, 121.1853],
      "Antipolo_San Jose": [14.5861, 121.1822],
      "Antipolo_San Juan": [14.6451, 121.1994],
      "Antipolo_San Luis": [14.6028, 121.1974],
      "Antipolo_San Roque": [14.5760, 121.1714],
      "Antipolo_Santa Cruz": [14.6153, 121.1684],
      // Baras done
      "Baras_Concepcion": [14.5220, 121.2685],
      "Baras_Evangelista": [14.5177, 121.2763],
      "Baras_Mabini": [14.5204, 121.2677],
      "Baras_Pinugay": [14.5923, 121.2727],
      "Baras_Rizal (Poblacion)": [14.5248, 121.2651],
      "Baras_San Juan": [14.5241, 121.2677],
      "Baras_San Jose": [14.5263, 121.2677],
      "Baras_San Miguel": [14.5220, 121.2665],
      "Baras_San Salvador": [14.5226, 121.2646],
      "Baras_Santiago": [14.5143, 121.2608],
      // Binangonan done
      "Binangonan_Bangad": [14.3710, 121.2218],
      "Binangonan_Batingan": [14.4719, 121.1967],
      "Binangonan_Bilibiran": [14.4974, 121.1751],
      "Binangonan_Binitagan": [14.3121, 121.2260],
      "Binangonan_Bombong": [14.3930, 121.2228],
      "Binangonan_Buhangin": [14.3628, 121.2213],
      "Binangonan_Calumpang": [14.4774, 121.1900],
      "Binangonan_Ginoong Sanay": [14.3245, 121.2260],
      "Binangonan_Gulod": [14.3491, 121.2158],
      "Binangonan_Habagatan": [14.2932, 121.2391],
      "Binangonan_Ithan": [14.4321, 121.2140],
      "Binangonan_Janosa": [14.3538, 121.2183],
      "Binangonan_Kalawaan": [14.4903, 121.1842],
      "Binangonan_Kalinawan": [14.4263, 121.2095],
      "Binangonan_Kasile": [14.3993, 121.2221],
      "Binangonan_Kaytome": [14.3498, 121.2179],
      "Binangonan_Kinaboogan": [14.3765, 121.2218],
      "Binangonan_Kinagatan": [14.3862, 121.2217],
      "Binangonan_Layunan": [14.4680, 121.1933],
      // Cainta done
      "Cainta_San Andres": [14.5743, 121.1105],
      "Cainta_San Isidro": [14.6092, 121.1115],
      "Cainta_San Juan": [14.5755, 121.1223],
      "Cainta_San Roque": [14.5754, 121.1169],
      "Cainta_Santa Domingo": [14.5922, 121.1127],
      "Cainta_Santa Niño": [14.5818, 121.1180],
      "Cainta_Santa Rosa": [14.5790, 121.1170],
      // Cardona done
      "Cardona_Balibago": [14.3261, 121.2409],
      "Cardona_Boor": [14.3625, 121.2378],
      "Cardona_Calahan": [14.3245, 121.5312],
      "Cardona_Dalig": [14.4816, 121.2324],
      "Cardona_Del Remedio": [14.4870, 121.2297],
      "Cardona_Iglesia": [	14.4880, 121.2253],
      "Cardona_Lambac": [14.3474, 121.2417],
      "Cardona_Looc": [14.4791, 121.2242],
      "Cardona_Malanggam-Calubacan": [14.3378, 121.2444],
      "Cardona_Nagsulo": [14.4354, 121.2182],
      "Cardona_Navotas": [14.3319, 121.2367],
      "Cardona_Patunhay": [14.4864, 121.2333],
      "Cardona_Real (Poblacion)": [14.4851, 121.2311],
      "Cardona_Sampad": [14.4562, 121.2191],
      "Cardona_San Roque (Poblacion)": [14.4605, 121.2298],
      "Cardona_Subay": [14.4031, 121.2271],
      "Cardona_Ticulio": [14.4220, 121.2214],
      "Cardona_Tuna": [14.3489, 121.2398],
      // Jalajala done
      "Jalajala_Bagumbong": [14.3429, 121.3762],
      "Jalajala_Bayugo": [14.3241, 121.3075],
      "Jalajala_First (Special) District (Poblacion)": [14.3541, 121.3239],
      "Jalajala_Lubo": [14.3316, 121.3536],
      "Jalajala_Paalaman": [14.3527, 121.3392],
      "Jalajala_Pagkalinawan": [14.3170, 121.3401],
      "Jalajala_Palaypalay": [14.2964, 121.3232],
      "Jalajala_Punta": [14.2940, 121.3060],
      "Jalajala_Second District (Poblacion)": [14.3525, 121.3236],
      "Jalajala_Sipsipin": [14.3714, 121.3323],
      "Jalajala_Third District (Poblacion)": [14.3479, 121.3222],
      // Morong done
      "Morong_Bombongan": [14.5064, 121.2195],
      "Morong_Caniogan-Calero-Lanang (CCL)": [14.5222, 121.2513],
      "Morong_Lagundi": [14.5359, 121.2560],
      "Morong_Maybancal": [14.5254, 121.2415],
      "Morong_San Jose (Poblacion)": [14.5143, 121.2367],
      "Morong_San Juan (Poblacion)": [14.5167, 121.2389],
      "Morong_San Pedro (Poblacion)": [14.5072, 121.2361],
      "Morong_San Guillermo": [14.5237, 121.2118],
      // Pililla done
      "Pililla_Bagumbayan (Poblacion)": [14.4763, 121.3146],
      "Pililla_Halayhayin": [14.4637, 121.3258],
      "Pililla_Hulo (Poblacion)": [14.4875, 121.3054],
      "Pililla_Imatong (Poblacion)": [14.4824, 121.3066],
      "Pililla_Malaya": [14.3990, 121.3390],
      "Pililla_Niogan": [14.4064, 121.3409],
      "Pililla_Quisao": [14.4364, 121.3352],
      "Pililla_Takungan (Poblacion)": [14.4817, 121.3051],
      "Pililla_Wawa (Poblacion)": [14.4779, 121.3064],
      // Rodriguez done
      "Rodriguez_Balite": [14.7336, 121.1457],
      "Rodriguez_Burgos": [14.7188, 121.1411],
      "Rodriguez_Geronimo": [14.7325, 121.1494],
      "Rodriguez_Macabud": [14.7947, 121.1392],
      "Rodriguez_Manggahan": [14.7245, 121.1423],
      "Rodriguez_Mascap": [14.7630, 121.1837],
      "Rodriguez_Puray": [14.7719, 121.2048],
      "Rodriguez_Rosario": [14.7305, 121.1416],
      "Rodriguez_San Isidro": [14.7611, 121.1539],
      "Rodriguez_San Jose": [14.7462, 121.1373],
      "Rodriguez_San Rafael": [14.7353, 121.1523],
      // San Mateo done
      "San Mateo_Ampid 1": [14.6811, 121.1189],
      "San Mateo_Ampid 2": [14.6864, 121.1150],
      "San Mateo_Banaba": [14.6747, 121.1144],
      "San Mateo_Dulong Bayan 1": [14.7005, 121.1227],
      "San Mateo_Dulong Bayan 2": [14.7006, 121.1258],
      "San Mateo_Guinayang": [14.7071, 121.1314],
      "San Mateo_Guitnang Bayan 1": [14.6952, 121.1196],
      "San Mateo_Guitnang Bayan 2": [14.6979, 121.1234],
      "San Mateo_Gulod Malaya": [14.6737, 121.1326],
      "San Mateo_Malanday": [14.7049, 121.1256],
      "San Mateo_Maly": [14.3534, 121.5523],
      "San Mateo_Pintong Bukawe": [14.6755, 121.2081],
      "San Mateo_Santa Ana": [14.3823, 121.5367],
      "San Mateo_Santo Niño": [14.6690, 121.1343],
      "San Mateo_Silangan": [14.6571, 121.1519],
      // Tanay done
      "Tanay_Cayabu": [14.6617, 121.3435],
      "Tanay_Cuyambay": [14.5789, 121.3410],
      "Tanay_Daraitan": [14.6039, 121.4293],
      "Tanay_Katipunan-Bayani (Poblacion)": [14.5021, 121.2862],
      "Tanay_Kay Buto (Poblacion)": [14.4981, 121.2833],
      "Tanay_Laiban": [14.6197, 121.3893],
      "Tanay_Mag-Ampon (Poblacion)": [14.5002, 121.2875],
      "Tanay_Mamuyao": [14.6831, 121.3829],
      "Tanay_Pinagkamaligan (Poblacion)": [14.4957, 121.2852],
      "Tanay_Plaza Aldea (Poblacion)": [14.4966, 121.2874],
      "Tanay_Sampaloc": [14.5462, 121.3648],
      "Tanay_San Andres": [14.6324, 121.3479],
      "Tanay_San Isidro (Poblacion)": [14.4954, 121.2813],
      "Tanay_Santa Inez": [14.7131, 121.3268],
      "Tanay_Santo Niño": [14.4982, 121.2849],
      "Tanay_Tabing Ilog (Poblacion)": [14.4988, 121.2872],
      "Tanay_Tandang Kutyo (Poblacion)": [14.5057, 121.2899],
      "Tanay_Tinucan": [14.6806, 121.3335],
      "Tanay_Wawa (Poblacion)": [14.4925, 121.2848],
      // Taytay  done
      "Taytay_Dolores": [14.5692, 121.1352],
      "Taytay_Muzon": [14.5434, 121.1446],
      "Taytay_San Isidro": [14.5772, 121.1336],
      "Taytay_San Juan": [14.5549, 121.1378],
      "Taytay_Santa Ana": [14.5374, 121.1099],
      // Teresa done
      "Teresa_Bagumbayan": [14.5501, 121.2185],
      "Teresa_Calumpang Santo Cristo": [14.5583, 121.2063],
      "Teresa_Dalig": [14.5603, 121.2129],
      "Teresa_Dulumbayan": [14.5568, 121.2047],
      "Teresa_May-Iba": [	14.5689, 121.2101],
      "Teresa_Poblacion": [14.5594, 121.2079],
      "Teresa_Prinza": [14.5397, 121.2122],
      "Teresa_San Gabriel": [14.5558, 121.2111],
      "Teresa_San Roque": [14.5518, 121.2150]
    };

    // Business location (Amuning Tokobe Enterprise)
    const businessLocation = [14.5780, 121.1824]; // Antipolo, Rizal (Brgy. Dalig)

    // Map instance
    let locationMap = null;
    let businessMarker = null;
    let clientMarker = null;
    let distanceControl = null;

    // Calculate distance between two coordinates using Haversine formula
    function calculateDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // Earth's radius in kilometers
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLon = (lon2 - lon1) * Math.PI / 180;
      const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      const distance = R * c; // Distance in kilometers
      return distance;
    }

    // Calculate travel fee based on distance
    function calculateTravelFee(distance) {
      if (distance <= 10) {
        return 0; // Free for <= 10 km
      } else if (distance <= 30) {
        return 500; // ₱500 for 11-30 km
      } else if (distance <= 40) {
        return 1000; // ₱1000 for 31-40 km
      } else if (distance <= 50) {
        return 1500; // ₱1500 for 41-50 km
      } else {
        // ₱500 for every 10 km after 50 km
        const extraKm = Math.ceil((distance - 50) / 10);
        return 1500 + (extraKm * 500);
      }
    }

    // Update distance display
    function updateDistanceDisplay(distance) {
      // Remove old distance control if exists
      if (distanceControl) {
        locationMap.removeControl(distanceControl);
      }

      const travelFee = calculateTravelFee(distance);
      let feeText = '';
      let feeClass = '';
      if (travelFee === 0) {
        feeText = 'FREE';
        feeClass = 'travel-fee-free';
      } else {
        feeText = `₱${travelFee.toLocaleString()}`;
        feeClass = 'travel-fee-amount';
      }

      // Create custom distance control (minimal)
      const DistanceControl = L.Control.extend({
        onAdd: function(map) {
          const div = L.DomUtil.create('div', 'distance-control');
          div.innerHTML = `
            <div class="distance-info">
              <strong>Distance:</strong><br>
              <span class="distance-value">${distance.toFixed(2)} km</span>
              <span class="distance-miles">(${(distance * 0.621371).toFixed(2)} mi)</span>
            </div>
          `;
          return div;
        }
      });

      distanceControl = new DistanceControl({ position: 'topright' });
      distanceControl.addTo(locationMap);

      // Update prominent travel fee display outside map
      const travelFeeDisplay = document.getElementById('travelFeeDisplay');
      const travelFeeContent = document.getElementById('travelFeeContent');
      
      if (travelFeeDisplay && travelFeeContent) {
        travelFeeContent.innerHTML = `
          <div class="distance-breakdown">
            <div class="breakdown-item">
              <span class="breakdown-label">Distance:</span>
              <span class="breakdown-value">${distance.toFixed(2)} km</span>
            </div>
            <div class="breakdown-item">
              <span class="breakdown-label">Travel Fee:</span>
              <span class="breakdown-value ${feeClass}">${feeText}</span>
            </div>
          </div>
        `;
        travelFeeDisplay.style.display = 'block';
      }

      // Store travel fee globally so we can use it in price calculation
      window.currentTravelFee = travelFee;
    }

    // Initialize map on modal show
    function initializeLocationMap() {
      if (locationMap) return; // Already initialized

      locationMap = L.map('locationMap').setView([14.35, 121.35], 9);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
      }).addTo(locationMap);

      // Add business location marker (red)
      businessMarker = L.circleMarker(businessLocation, {
        radius: 10,
        fillColor: '#dc2626',
        color: '#dc2626',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.8
      }).addTo(locationMap);
      
      businessMarker.bindPopup('<strong>Amuning Tokobe Enterprise</strong><br>Antipolo, Rizal');
    }

    // Update map when barangay is selected
    function updateLocationMap() {
      const citySelect = document.getElementById('modalCity');
      const barangaySelect = document.getElementById('modalBarangay');
      
      const selectedCity = citySelect.value;
      const selectedBarangay = barangaySelect.value;

      if (!selectedBarangay || !selectedCity) {
        if (clientMarker) {
          locationMap.removeLayer(clientMarker);
          clientMarker = null;
        }
        if (distanceControl) {
          locationMap.removeControl(distanceControl);
          distanceControl = null;
        }
        return;
      }

      // Initialize map if not done yet
      if (!locationMap) {
        initializeLocationMap();
      }

      // Construct the key with City_Barangay format
      const coordinateKey = selectedCity + "_" + selectedBarangay;

      if (!barangayCoordinates[coordinateKey]) {
        console.error('Coordinates not found for:', coordinateKey);
        return;
      }

      const coords = barangayCoordinates[coordinateKey];
      console.log('Selected coordinates:', coords);

      // Remove old client marker if exists
      if (clientMarker) {
        locationMap.removeLayer(clientMarker);
      }

      // Add new client location marker (blue)
      clientMarker = L.circleMarker(coords, {
        radius: 10,
        fillColor: '#2563eb',
        color: '#2563eb',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.8
      }).addTo(locationMap);

      clientMarker.bindPopup(`<strong>Your Location</strong><br>${selectedBarangay}, ${selectedCity}`);

      // Calculate and display distance
      const distance = calculateDistance(businessLocation[0], businessLocation[1], coords[0], coords[1]);
      updateDistanceDisplay(distance);

      // Fit map to show both markers
      if (businessMarker && clientMarker) {
        const group = new L.featureGroup([businessMarker, clientMarker]);
        locationMap.fitBounds(group.getBounds().pad(0.1));
      } else {
        locationMap.setView(coords, 13);
      }
    }

    // Initialize map when booking modal is opened
    const originalShowBookingModal = showBookingModal;
    showBookingModal = function() {
      originalShowBookingModal();
      setTimeout(() => {
        initializeLocationMap();
      }, 100);
    };

    // Show booking policy popup on page load
    function showBookingPolicyPopup() {
      const now = Date.now();
      const lastSessionTime = sessionStorage.getItem('photoPolicySessionTime');
      const popupDisabled = sessionStorage.getItem('photoPolicyPopupDisabled');
      
      // If more than 30 minutes have passed, treat it as a new session
      // This ensures popup shows even if sessionStorage somehow persists
      const isNewSession = !lastSessionTime || (now - parseInt(lastSessionTime)) > 30 * 60 * 1000;
      
      // Only show popup if:
      // 1. This is detected as a new session OR
      // 2. Popup hasn't been disabled for this session
      if (isNewSession || popupDisabled !== 'true') {
        // Set or update the session time
        sessionStorage.setItem('photoPolicySessionTime', now.toString());
        // Clear the disabled flag for this new session
        sessionStorage.removeItem('photoPolicyPopupDisabled');
      } else if (popupDisabled === 'true') {
        // Popup is disabled for this session
        return;
      }

      Swal.fire({
        title: 'Important Booking Information',
        html: `
          <div style="text-align: left;">
            <p style="margin-bottom: 15px; color: #333; line-height: 1.6;">
              After booking, please wait for your reservation to be validated. Only once it has been approved, can you proceed with payment, and your booking will be processed officially after payment is completed.
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
                sessionStorage.setItem('photoPolicyPopupDisabled', 'true');
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

<script src="assets/js/photo_dynamic.js"></script>

<?php include 'includes/footer.php'; ?>