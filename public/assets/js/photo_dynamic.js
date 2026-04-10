// Global variables for photo services
let photoServicesData = [];

// Load photo services from database
async function loadPhotoServices() {
  const servicesGrid = document.querySelector('.services-grid');
  if (servicesGrid) {
    // Show loading animation
    servicesGrid.innerHTML = `
      <div class="loading-container" style="
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
        text-align: center;
      ">
        <div class="loading-spinner" style="
          border: 4px solid #f3f3f3;
          border-top: 4px solid #F5276C;
          border-radius: 50%;
          width: 40px;
          height: 40px;
          animation: spin 1s linear infinite;
          margin-bottom: 20px;
        "></div>
        <p style="color: #666; font-size: 16px;">Loading photo services...</p>
      </div>
      <style>
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      </style>
    `;
  }

  try {
    const response = await fetch('get_photo_services.php');
    const data = await response.json();

    if (data.success) {
      // Store ALL services including unavailable ones
      photoServicesData = data.services;
      console.log('Loaded services:', photoServicesData); // Debug log
      renderPhotoServices();
    } else {
      console.error('Failed to load photo services:', data.message);
      if (servicesGrid) {
        servicesGrid.innerHTML = `
          <div style="text-align: center; padding: 40px; color: #666;">
            <p>Failed to load services. Please try again.</p>
          </div>
        `;
      }
    }
  } catch (error) {
    console.error('Error loading photo services:', error);
    if (servicesGrid) {
      servicesGrid.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #666;">
          <p>Error loading services. Please refresh the page.</p>
        </div>
      `;
    }
  }
}

// Render photo services dynamically
function renderPhotoServices() {
  const servicesGrid = document.querySelector('.services-grid');
  if (!servicesGrid) return;

  servicesGrid.innerHTML = '';

  // Check if we have any services
  if (!photoServicesData || photoServicesData.length === 0) {
    servicesGrid.innerHTML = `
      <div style="text-align: center; padding: 40px; color: #666;">
        <p>No services available at the moment.</p>
      </div>
    `;
    return;
  }

  photoServicesData.forEach(service => {
    const serviceItem = document.createElement('div');
    serviceItem.className = 'service-item';
    
    // Add position relative for overlay positioning
    serviceItem.style.position = 'relative';

    // Convert is_available to boolean if it's a string or number
    const isAvailable = service.is_available === true || 
                       service.is_available === 1 || 
                       service.is_available === '1' || 
                       service.is_available === 'true';

    // Only allow selection if service is available
    if (isAvailable) {
      serviceItem.onclick = () => selectPhotoService(service.service_name);
      serviceItem.style.cursor = 'pointer';
    } else {
      serviceItem.style.cursor = 'not-allowed';
    }

    // Create unavailable overlay if service is not available
    const unavailableOverlay = !isAvailable ? `
      <div class="unavailable-overlay" style="
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.75);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: inherit;
      ">
        <div style="
          color: white;
          font-size: 20px;
          font-weight: bold;
          text-align: center;
          text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
          padding: 20px;
        ">
          NOT AVAILABLE
        </div>
      </div>
    ` : '';

    // Use basic_price as the displayed base price
    const base = parseFloat(service.basic_price || 0);
    const formattedBasePrice = base % 1 === 0 ? base.toString() : base.toFixed(2);

    serviceItem.innerHTML = `
      <div style="position: relative; height: 100%;">
        <div class="service-image">
          <img src="assets/${service.image_path}" alt="${service.service_name} Photobooth">
          <div class="service-overlay">
            <div class="service-overlay-content">
              <h3>${service.service_name} Photobooth</h3>
              <p>${service.description}</p>
              <div class="service-overlay-btn">${isAvailable ? 'Select Service' : 'Unavailable'}</div>
            </div>
          </div>
        </div>
        <div class="service-content">
          <h3>${service.service_name} Photobooth</h3>
          <p>${service.description}</p>
          <div class="service-price">From ₱${formattedBasePrice}</div>
        </div>
        ${unavailableOverlay}
      </div>
    `;

    servicesGrid.appendChild(serviceItem);
  });

  // Add event listeners after services are rendered
  addEventListenersAfterLoad();
}

// Select a photo service
function selectPhotoService(serviceName) {
  const service = photoServicesData.find(s => s.service_name === serviceName);
  if (!service) return;

  // Check if service is available
  const isAvailable = service.is_available === true || 
                     service.is_available === 1 || 
                     service.is_available === '1' || 
                     service.is_available === 'true';

  if (!isAvailable) {
    Swal.fire({
      icon: 'warning',
      title: 'Service Unavailable',
      text: 'This service is currently not available. Please select another service.',
      confirmButtonText: 'OK'
    });
    return;
  }

  // Set the global selectedService variable that photo.js expects
  selectedService = serviceName;

  // Populate serviceData object that photo.js expects
  serviceData[serviceName] = {
    id: service.id,
    description: service.description,
    image_path: service.image_path,
    basePrice: parseFloat(service.basic_price || 0),
    packages: {
      basic: { price: parseFloat(service.basic_price || 0) },
      standard: { price: parseFloat(service.standard_price || 0) }
    }
  };

  // Update form inputs
  const serviceInput = document.getElementById('serviceInput');
  if (serviceInput) {
    serviceInput.value = serviceName;
  }
  
  const descriptionInput = document.getElementById('descriptionInput');
  if (descriptionInput) {
    descriptionInput.value = service.description;
  }

  // Update preview
  const selectedServiceEl = document.getElementById('selectedService');
  if (selectedServiceEl) {
    selectedServiceEl.textContent = serviceName + ' Photobooth';
  }
  
  const serviceDescEl = document.getElementById('serviceDescription');
  if (serviceDescEl) {
    serviceDescEl.textContent = service.description;
  }

  // Update preview image
  const previewImg = document.getElementById('previewImage');
  if (previewImg) {
    previewImg.src = `assets/${service.image_path}`;
  }

  // Update hidden field for modal
  const modalEventType = document.getElementById('modalEventType');
  if (modalEventType) {
    modalEventType.value = serviceName + ' Photobooth';
  }

  // Update price display with basic package price
  const basicPrice = service.packages.basic.price;
  // Format price - only show decimals if not whole number
  const formattedPrice = basicPrice % 1 === 0 ? basicPrice.toString() : basicPrice.toFixed(2);
  
  const priceDisplay = document.getElementById('priceDisplay');
  if (priceDisplay) {
    priceDisplay.textContent = `₱${formattedPrice}`;
  }
  
  const priceInput = document.getElementById('priceInput');
  if (priceInput) {
    priceInput.value = basicPrice.toString(); // Store numeric value
  }

  // Call the existing selectService function to maintain compatibility
  if (typeof selectService === 'function') {
    selectService(serviceName);
  }
}

// Initialize photo services
document.addEventListener('DOMContentLoaded', function() {
  loadPhotoServices();
});

// Function to add event listeners after services are loaded
function addEventListenersAfterLoad() {
  // Add event listeners for form elements
  const productSelect = document.getElementById('productSelect');
  const durationSelect = document.getElementById('sizeSelect');
  const packageSelect = document.getElementById('paperType');

  if (productSelect) {
    productSelect.addEventListener('change', () => {
      if (typeof updatePrice === 'function') updatePrice();
    });
  }

  if (durationSelect) {
    durationSelect.addEventListener('change', () => {
      if (typeof updatePrice === 'function') updatePrice();
    });
  }

  if (packageSelect) {
    packageSelect.addEventListener('change', () => {
      if (typeof updatePrice === 'function') updatePrice();
    });
  }
}