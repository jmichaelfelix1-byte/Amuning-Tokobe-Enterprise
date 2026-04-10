// Global variables for print services
let printServicesData = [];

// Load print services from database
async function loadPrintServices() {
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
        <p style="color: #666; font-size: 16px;">Loading print services...</p>
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
    const response = await fetch('get_print_services.php');
    const data = await response.json();

    if (data.success) {
      printServicesData = data.services;
      renderPrintServices();
    } else {
      console.error('Failed to load print services:', data.message);
      if (servicesGrid) {
        servicesGrid.innerHTML = `
          <div style="text-align: center; padding: 40px; color: #666;">
            <p>Failed to load services. Please try again.</p>
          </div>
        `;
      }
    }
  } catch (error) {
    console.error('Error loading print services:', error);
    if (servicesGrid) {
      servicesGrid.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #666;">
          <p>Error loading services. Please refresh the page.</p>
        </div>
      `;
    }
  }
}

// Render print services dynamically
function renderPrintServices() {
  const servicesGrid = document.querySelector('.services-grid');
  if (!servicesGrid) return;

  servicesGrid.innerHTML = '';

  printServicesData.forEach(service => {
    const serviceItem = document.createElement('div');
    serviceItem.className = 'service-item';

    // Only allow selection if service is available
    if (service.is_available) {
      serviceItem.onclick = () => selectPrintService(service);
    } else {
      serviceItem.style.cursor = 'not-allowed';
      serviceItem.style.opacity = '0.6';
    }

    const unavailableOverlay = !service.is_available ? `
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
          color: white !important;
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

    const formattedBasePrice = service.base_price % 1 === 0 ? service.base_price.toString() : service.base_price.toFixed(2);

    serviceItem.innerHTML = `
      <div style="position: relative;">
        <div class="service-image">
          <img src="assets/${service.image_path}" alt="${service.service_name}">
          <div class="service-overlay">
            <div class="service-overlay-content">
              <h3>${service.service_name}</h3>
              <p>${service.description}</p>
              <div class="service-overlay-btn">${service.is_available ? 'Select Service' : 'Unavailable'}</div>
            </div>
          </div>
        </div>
        <div class="service-content">
          <h3>${service.service_name}</h3>
          <p>${service.description}</p>
          <div class="service-price">From ₱${formattedBasePrice}</div>
        </div>
        ${unavailableOverlay}
      </div>
    `;

    servicesGrid.appendChild(serviceItem);
  });
}

// Debug function to check service data
function debugServiceData(service) {
  console.log('=== DEBUG SERVICE DATA ===');
  console.log('Service name:', service.service_name);
  console.log('Base price:', service.base_price);
  console.log('Paper types (raw):', service.paper_types);
  console.log('Paper types (type):', typeof service.paper_types);
  console.log('Sizes (raw):', service.sizes);
  console.log('Sizes (type):', typeof service.sizes);
  
  // Check if paper_types is a string that needs parsing
  if (typeof service.paper_types === 'string') {
    try {
      const parsed = JSON.parse(service.paper_types);
      console.log('Parsed paper types:', parsed);
    } catch (e) {
      console.log('Could not parse paper types as JSON');
    }
  }
  console.log('==========================');
}

// Select a print service
function selectPrintService(service) {
  if (!service) return;

  // Enhanced debugging
  console.log('=== SELECT PRINT SERVICE DEBUG ===');
  console.log('Full service object:', service);
  debugServiceData(service);

  // Update form inputs
  document.getElementById('serviceInput').value = service.service_name;
  document.getElementById('descriptionInput').value = service.description;

  // Update preview
  document.getElementById('selectedService').textContent = service.service_name;
  document.getElementById('serviceDescription').textContent = service.description;

  // Update preview image
  const previewImage = document.getElementById('previewImage');
  if (previewImage) {
    previewImage.src = `assets/${service.image_path}`;
  }

  // Update paper types dropdown - ENHANCED DEBUGGING
  const paperTypeSelect = document.getElementById('paperType');
  paperTypeSelect.innerHTML = '<option value="">Select Paper Type</option>';
  
  console.log('=== PAPER TYPES PROCESSING ===');
  let paperTypesArray = service.paper_types;
  
  // Enhanced parsing with better debugging
  if (typeof service.paper_types === 'string') {
    console.log('Paper types is a string, attempting to parse JSON...');
    try {
      paperTypesArray = JSON.parse(service.paper_types);
      console.log('Successfully parsed paper types:', paperTypesArray);
    } catch (e) {
      console.error('Failed to parse paper_types as JSON:', e);
      console.log('Raw paper_types string:', service.paper_types);
      
      // Try alternative parsing - maybe it's comma-separated?
      if (service.paper_types.includes(',')) {
        paperTypesArray = service.paper_types.split(',').map(item => item.trim());
        console.log('Parsed as comma-separated:', paperTypesArray);
      } else {
        // Fallback to a default array
        paperTypesArray = ['Standard Paper', 'Glossy Paper', 'Matte Paper'];
        console.log('Using fallback paper types:', paperTypesArray);
      }
    }
  }
  
  console.log('Final paper types array:', paperTypesArray);
  console.log('Array type:', typeof paperTypesArray);
  console.log('Is array?', Array.isArray(paperTypesArray));
  
  if (paperTypesArray && Array.isArray(paperTypesArray) && paperTypesArray.length > 0) {
    paperTypesArray.forEach((type, index) => {
      // Enhanced validation
      if (!type || typeof type !== 'string') {
        console.warn(`Invalid paper type at index ${index}:`, type);
        return;
      }
      
      const option = document.createElement('option');
      const value = type.trim().toLowerCase().replace(/\s+/g, '_');
      const displayText = type.trim().charAt(0).toUpperCase() + type.trim().slice(1).replace(/_/g, ' ');
      
      option.value = value;
      option.textContent = displayText;
      paperTypeSelect.appendChild(option);
      
      console.log(`Added paper type option: value="${value}", text="${displayText}"`);
    });
  } else {
    console.error('Paper types not found or not an array:', paperTypesArray);
    // Enhanced fallback options
    const defaultTypes = ['Standard Paper', 'Glossy Paper', 'Matte Paper', 'Cardstock'];
    defaultTypes.forEach(type => {
      const option = document.createElement('option');
      const value = type.toLowerCase().replace(/\s+/g, '_');
      option.value = value;
      option.textContent = type;
      paperTypeSelect.appendChild(option);
      console.log(`Added fallback paper type: ${type}`);
    });
  }

  // Update sizes dropdown (similar enhanced debugging)
  const sizeSelect = document.getElementById('sizeSelect');
  sizeSelect.innerHTML = '<option value="">Select Size</option>';
  
  let sizesArray = service.sizes;
  if (typeof service.sizes === 'string') {
    try {
      sizesArray = JSON.parse(service.sizes);
    } catch (e) {
      console.error('Failed to parse sizes as JSON:', e);
      if (service.sizes.includes(',')) {
        sizesArray = service.sizes.split(',').map(item => item.trim());
      } else {
        sizesArray = ['A4', 'Letter', 'Legal'];
      }
    }
  }
  
  if (sizesArray && Array.isArray(sizesArray) && sizesArray.length > 0) {
    sizesArray.forEach(size => {
      const option = document.createElement('option');
      option.value = size;
      option.textContent = size;
      sizeSelect.appendChild(option);
    });
  }

  // Auto-select first available options with enhanced debugging
  console.log('=== AUTO-SELECTION DEBUG ===');
  if (paperTypesArray && paperTypesArray.length > 0) {
    const firstPaperType = paperTypesArray[0];
    const firstPaperValue = typeof firstPaperType === 'string' ? firstPaperType.trim().toLowerCase().replace(/\s+/g, '_') : 'standard';
    paperTypeSelect.value = firstPaperValue;
    console.log('Auto-selected paper type:', {
      'raw value': firstPaperType,
      'processed value': firstPaperValue,
      'select value': paperTypeSelect.value
    });
  } else {
    console.warn('No paper types available for auto-selection');
  }
  
  if (sizesArray && sizesArray.length > 0) {
    sizeSelect.value = sizesArray[0];
    console.log('Auto-selected size:', sizeSelect.value);
  }

  // Debug the final state of the paper type select
  console.log('=== FINAL PAPER TYPE SELECT STATE ===');
  console.log('Selected value:', paperTypeSelect.value);
  console.log('Number of options:', paperTypeSelect.options.length);
  for (let i = 0; i < paperTypeSelect.options.length; i++) {
    console.log(`Option ${i}: value="${paperTypeSelect.options[i].value}", text="${paperTypeSelect.options[i].text}"`);
  }

  // Set current service for price calculations
  window.currentService = {
    service_name: service.service_name,
    price: parseFloat(service.base_price),
    base_price: parseFloat(service.base_price),
    stock_quantity: service.stock_quantity || 1000
  };

  console.log('=== SERVICE SETUP COMPLETE ===');
  console.log('Current service:', window.currentService);

  // Update price calculation
  calculateTotalPrice();

  // Add event listeners for dynamic price updates
  paperTypeSelect.addEventListener('change', calculateTotalPrice);
  sizeSelect.addEventListener('change', calculateTotalPrice);
  document.getElementById('quantity').addEventListener('input', calculateTotalPrice);
  document.getElementById('colorType').addEventListener('change', calculateTotalPrice);

  // Show the order form modal
  openOrderModal();
}

// Open the order form modal
function openOrderModal() {
  const modal = document.getElementById('orderFormModal');
  if (modal) {
    modal.style.display = 'flex';
    // Scroll to top of modal
    modal.scrollTop = 0;
    // Add animation
    modal.classList.add('modal-open');
  }
}

// Close the order form modal
function closeOrderModal() {
  const modal = document.getElementById('orderFormModal');
  if (modal) {
    modal.style.display = 'none';
    modal.classList.remove('modal-open');
  }
}

// Close modal when clicking outside of it and initialize print services
document.addEventListener('DOMContentLoaded', function() {
  // Setup modal close on outside click
  const modal = document.getElementById('orderFormModal');
  if (modal) {
    modal.addEventListener('click', function(event) {
      if (event.target === modal) {
        closeOrderModal();
      }
    });
  }

  // Load print services
  loadPrintServices();
  
  // Add global quantity listener
  const quantityInput = document.getElementById('quantity');
  if (quantityInput) {
    quantityInput.addEventListener('input', function() {
      if (window.currentService && uploadedFiles.length > 0) {
        calculateTotalPrice();
      }
    });
  }
});