const serviceData = {};

// Global variables
var selectedService = null;

// Initialize the page
// Event listeners are now added dynamically after services load

// Select a service
function selectService(serviceName) {
  // Remove active class from all services
  const serviceItems = document.querySelectorAll('.service-item');
  serviceItems.forEach(item => {
    item.classList.remove('active');
  });

  // Add active class to selected service
  const selectedItem = document.querySelector(`[data-service="${serviceName}"]`);
  selectedItem.classList.add('active');

  // Update selected service
  selectedService = serviceName;

  // Get service description
  const description = serviceData[serviceName].description;

  // Update preview
  document.getElementById('selectedService').textContent = serviceName + ' Photography';
  document.getElementById('serviceDescription').textContent = description;

  // Update preview image
  const serviceImage = serviceData[serviceName].image_path;
  document.getElementById('previewImage').src = `assets/${serviceImage}`;

  // Update hidden field for modal
  if (document.getElementById('modalEventType')) {
    document.getElementById('modalEventType').value = serviceName + ' Photography';
  }

  // Update price
  updatePrice();
}

// Clear selection
function clearSelection() {
  // Remove active class from all services
  const serviceItems = document.querySelectorAll('.service-item');
  serviceItems.forEach(item => {
    item.classList.remove('active');
  });
  
  // Reset selected service
  selectedService = null;
  
  // Reset preview
  document.getElementById('selectedService').textContent = 'No Service Selected';
  document.getElementById('serviceDescription').textContent = 'Select a service to see details';
  document.getElementById('previewImage').src = 'https://via.placeholder.com/250x200?text=Select+a+Service';
  
  // Reset price
  document.getElementById('priceDisplay').textContent = '₱0';
}

// Update price based on selections
function updatePrice() {
  if (!selectedService) return;
  
  const duration = document.getElementById('sizeSelect').value;
  const packageType = document.getElementById('paperType').value;
  const product = document.getElementById('productSelect').value;
  
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
  
  // Get base price from service data
  let basePrice = serviceData[selectedService].packages[packageType].price;
  
  // Apply duration multiplier
  let durationPrice = basePrice * durationMultipliers[duration];
  
  // Apply product multiplier
  let totalPrice = durationPrice * productMultipliers[product];

  // Format price - only show decimals if not whole number
  const formattedPrice = totalPrice % 1 === 0 ? totalPrice.toString() : totalPrice.toFixed(2);
  const formatted = `₱${formattedPrice}`;

  // Update displayed price
  document.getElementById('priceDisplay').textContent = formatted;
  
  // Update hidden input if it exists
  const priceInput = document.getElementById('priceInput');
  if (priceInput) {
    priceInput.value = formattedPrice; // Store numeric value for backend
  }
}

// Show booking modal
function showBookingModal() {
  if (!selectedService) {
    Swal.fire({
      icon: 'warning',
      title: 'Select a service',
      text: 'Please choose a service before filling out the form.'
    });
    return;
  }

  const durationLabels = {
    '2-hours': '2 Hours',
    '4-hours': '4 Hours',
    '6-hours': '6 Hours',
    '8-hours': '8 Hours (Full Day)',
    '12-hours': '12 Hours'
  };

  const packageLabels = {
    'basic': 'Basic (Photos Only)',
    'standard': 'Standard (Photos + Editing)'
  };

  const durationSelect = document.getElementById('sizeSelect');
  const packageSelect = document.getElementById('paperType');

  const modal = document.getElementById('bookingModal');
  const modalEventType = document.getElementById('modalEventType');
  const modalDuration = document.getElementById('modalDuration');
  const modalPackage = document.getElementById('modalPackage');
  const modalPrice = document.getElementById('modalPrice');

  if (modalEventType) {
    modalEventType.value = selectedService + ' Photography';
  }

  if (modalDuration && durationSelect) {
    modalDuration.value = durationLabels[durationSelect.value] || '';
  }

  if (modalPackage && packageSelect) {
    modalPackage.value = packageLabels[packageSelect.value] || '';
  }

  if (modalPrice) {
    modalPrice.value = document.getElementById('priceDisplay').textContent;
  }

  modal.style.display = 'block';
}

// Close modal
function closeModal() {
  const modal = document.getElementById('bookingModal');
  modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('bookingModal');
  if (event.target == modal) {
    modal.style.display = 'none';
  }
}

// Submit booking
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
  
  // Get form values
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
    region: document.getElementById('modalRegion').value,
    postal: document.getElementById('modalPostal').value,
    country: document.getElementById('modalCountry').value,
    remarks: document.getElementById('modalRemarks').value,
    packageType: document.getElementById('paperType').value,
    duration: document.getElementById('sizeSelect').value,
    price: document.getElementById('priceInput').value
  };
  
  // Send data to server
  console.log('Submitting booking data:', formData);
  
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
          html: `Thank you for your booking, <strong>${formData.name}</strong>!<br>We will contact you shortly at <strong>${formData.email}</strong>.<br><br>Booking ID: #${data.booking_id || 'N/A'}`,
          confirmButtonColor: '#F5276C',
          confirmButtonText: 'OK'
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
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'Connection Error',
        text: 'Could not connect to server. Please check your connection and try again.',
        confirmButtonColor: '#F5276C'
      });
    } else {
      alert('Connection error: Could not connect to server');
    }
  });
  
  return false;
}

// Scroll to services section
function scrollToServices() {
  document.querySelector('.printing-layout').scrollIntoView({ 
    behavior: 'smooth' 
  });
}

function showUploadModal() {
  showBookingModal();
}
// Rizal locations data
const rizalLocations = {
  "Angono": {
    "postal_code": "1930",
    "barangays": ["Bagumbayan", "Kalayaan", "Mahabang Parang", "Poblacion Ibaba", "Poblacion Itaas", "San Isidro", "San Pedro", "San Roque", "San Vicente", "Santo Niño"]
  },
  "Antipolo": {
    "postal_code": "1870",
    "barangays": ["Bagong Nayon", "Beverly Hills", "Calawis", "Cupang", "Dalig", "Dela Paz", "Inarawan", "Mambugan", "Mayamot", "Muntindilaw", "San Isidro", "San Jose", "San Juan", "San Luis", "San Roque", "Santa Cruz"]
  },
  "Baras": {
    "postal_code": "1970",
    "barangays": ["Concepcion", "Evangelista", "Mabini", "Pinugay", "Rizal (Poblacion)", "San Juan", "San Jose", "San Miguel", "San Salvador", "Santiago"]
  },
  "Binangonan": {
    "postal_code": "1940",
    "barangays": ["Bangad", "Batingan", "Bilibiran", "Binitagan", "Bombong", "Buhangin", "Calumpang", "Ginoong Sanay", "Gulod", "Habagatan", "Ithan", "Janosa", "Kalawaan", "Kalinawan", "Kasile", "Kaytome", "Kinaboogan", "Kinagatan", "Layunan", "Libid", "Libis", "Limbon-limbon", "Lunsad", "Macamot", "Mahabang Parang", "Malakaban", "Mambog", "Pag-asa", "Palangoy", "Pantok", "Pila-Pila", "Pinagdilawan", "Pipindan", "Rayap", "San Carlos", "Sapang", "Tabon", "Tagpos", "Tatala", "Tayuman"]
  },
  "Cainta": {
    "postal_code": "1900",
    "barangays": ["San Andres", "San Isidro", "San Juan", "San Roque", "Santa Domingo", "Santa Niño", "Santa Rosa"]
  },
  "Cardona": {
    "postal_code": "1950",
    "barangays": ["Balibago", "Boor", "Calahan", "Dalig", "Del Remedio", "Iglesia", "Lambac", "Looc", "Malanggam-Calubacan", "Nagsulo", "Navotas", "Patunhay", "Real (Poblacion)", "Sampad", "San Roque (Poblacion)", "Subay", "Ticulio", "Tuna"]
  },
  "Jalajala": {
    "postal_code": "1990",
    "barangays": ["Bagumbong", "Bayugo", "First (Special) District (Poblacion)", "Lubo", "Paalaman", "Pagkalinawan", "Palaypalay", "Punta", "Second District (Poblacion)", "Sipsipin", "Third District (Poblacion)"]
  },
  "Morong": {
    "postal_code": "1960",
    "barangays": ["Bombongan", "Caniogan-Calero-Lanang (CCL)", "Lagundi", "Maybancal", "San Jose (Poblacion)", "San Juan (Poblacion)", "San Pedro (Poblacion)", "San Guillermo"]
  },
  "Pililla": {
    "postal_code": "1910",
    "barangays": ["Bagumbayan (Poblacion)", "Halayhayin", "Hulo (Poblacion)", "Imatong (Poblacion)", "Malaya", "Niogan", "Quisao", "Takungan (Poblacion)", "Wawa (Poblacion)"]
  },
  "Rodriguez": {
    "postal_code": "1860",
    "barangays": ["Balite", "Burgos", "Geronimo", "Macabud", "Manggahan", "Mascap", "Puray", "Rosario", "San Isidro", "San Jose", "San Rafael"]
  },
  "San Mateo": {
    "postal_code": "1850",
    "barangays": ["Ampid 1", "Ampid 2", "Banaba", "Dulong Bayan 1", "Dulong Bayan 2", "Guinayang", "Guitnang Bayan 1", "Guitnang Bayan 2", "Gulod Malaya", "Malanday", "Maly", "Pintong Bukawe", "Santa Ana", "Santo Niño", "Silangan"]
  },
  "Tanay": {
    "postal_code": "1980",
    "barangays": ["Cayabu", "Cuyambay", "Daraitan", "Katipunan-Bayani (Poblacion)", "Kay Buto (Poblacion)", "Laiban", "Madilay-dilay", "Mag-Ampon (Poblacion)", "Mamuyao", "Pinagkamaligan (Poblacion)", "Plaza Aldea (Poblacion)", "Sampaloc", "San Andres", "San Isidro (Poblacion)", "Santa Inez", "Santo Niño", "Tabing Ilog (Poblacion)", "Tandang Kutyo (Poblacion)", "Tinucan", "Wawa (Poblacion)"]
  },
  "Taytay": {
    "postal_code": "1920",
    "barangays": ["Dolores", "Muzon", "San Isidro", "San Juan", "Santa Ana"]
  },
  "Teresa": {
    "postal_code": "1880",
    "barangays": ["Bagumbayan", "Calumpang Santo Cristo", "Dalig", "Dulumbayan", "May-Iba", "Poblacion", "Prinza", "San Gabriel", "San Roque"]
  }
};

// Update barangays dropdown when city is selected
function updateBarangays() {
  console.log('updateBarangays() called');
  
  const citySelect = document.getElementById('modalCity');
  const barangaySelect = document.getElementById('modalBarangay');
  const postalInput = document.getElementById('modalPostal');
  
  console.log('citySelect:', citySelect);
  console.log('barangaySelect:', barangaySelect);
  console.log('rizalLocations:', rizalLocations);
  
  if (!citySelect || !barangaySelect) {
    console.error('Modal elements not found');
    return;
  }
  
  const selectedCity = citySelect.value;
  console.log('selectedCity:', selectedCity);
  
  // Clear barangay options
  barangaySelect.innerHTML = '<option value="">-- Select Barangay --</option>';
  if (postalInput) {
    postalInput.value = '';
  }
  
  if (selectedCity && rizalLocations[selectedCity]) {
    console.log('Found city in rizalLocations:', selectedCity);
    const barangays = rizalLocations[selectedCity].barangays;
    console.log('Barangays:', barangays);
    
    // Add barangay options
    barangays.forEach(barangay => {
      const option = document.createElement('option');
      option.value = barangay;
      option.textContent = barangay;
      barangaySelect.appendChild(option);
    });
    console.log('Barangay options added:', barangays.length);
  } else {
    console.warn('City not found in rizalLocations or city is empty');
  }
}

// Update postal code when barangay is selected
function updatePostalCode() {
  console.log('updatePostalCode() called');
  
  const citySelect = document.getElementById('modalCity');
  const postalInput = document.getElementById('modalPostal');
  
  const selectedCity = citySelect.value;
  console.log('selectedCity for postal:', selectedCity);
  
  if (selectedCity && rizalLocations[selectedCity]) {
    const postalCode = rizalLocations[selectedCity].postal_code;
    console.log('Setting postal code:', postalCode);
    postalInput.value = postalCode;
  } else {
    console.warn('City not found or empty for postal code update');
  }
}