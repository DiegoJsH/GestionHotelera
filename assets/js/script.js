// Modal functionality
function openModal(modalId) {
  document.getElementById(modalId).style.display = "block"
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none"
}

// Close modal when clicking outside
window.onclick = (event) => {
  if (event.target.classList.contains("modal")) {
    event.target.style.display = "none"
  }
}

// Form submissions (you can customize these)
document.addEventListener("DOMContentLoaded", () => {
  // Handle all form submissions
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      e.preventDefault()
      // Add your form handling logic here
      console.log("Form submitted:", this)

      // Close modal if form is in a modal
      const modal = this.closest(".modal")
      if (modal) {
        modal.style.display = "none"
      }

      // Show success message (you can customize this)
      alert("Operación realizada con éxito")
    })
  })

  // Handle button clicks
  const buttons = document.querySelectorAll("button[onclick]")
  buttons.forEach((button) => {
    if (!button.onclick) {
      button.addEventListener("click", function () {
        // Add your button logic here
        console.log("Button clicked:", this.textContent)
      })
    }
  })
})

// Navigation active state
document.addEventListener("DOMContentLoaded", () => {
  const currentPage = window.location.pathname.split("/").pop()
  const navLinks = document.querySelectorAll(".nav-link")

  navLinks.forEach((link) => {
    link.classList.remove("active")
    if (link.getAttribute("href") === currentPage) {
      link.classList.add("active")
    }
  })
})

// Sample functions for room management
function changeRoomStatus(roomId, status) {
  console.log(`Changing room ${roomId} to ${status}`)
  // Add your room status change logic here
}

function checkInGuest(roomId) {
  console.log(`Check-in for room ${roomId}`)
  // Add your check-in logic here
}

function checkOutGuest(roomId) {
  console.log(`Check-out for room ${roomId}`)
  // Add your check-out logic here
}

// Sample functions for reservations
function confirmReservation(reservationId) {
  console.log(`Confirming reservation ${reservationId}`)
  // Add your reservation confirmation logic here
}

function cancelReservation(reservationId) {
  console.log(`Cancelling reservation ${reservationId}`)
  // Add your reservation cancellation logic here
}

// Sample functions for guests
function viewGuestHistory(guestId) {
  console.log(`Viewing history for guest ${guestId}`)
  // Add your guest history logic here
}

function editGuest(guestId) {
  console.log(`Editing guest ${guestId}`)
  // Add your guest editing logic here
}

// Export functions
function exportToPDF() {
  console.log("Exporting to PDF")
  // Add your PDF export logic here
  alert("Función de exportación a PDF - Implementar según necesidades")
}

// Search functionality
function setupSearch() {
  const searchInputs = document.querySelectorAll('input[placeholder*="Buscar"]')
  searchInputs.forEach((input) => {
    input.addEventListener("input", function () {
      const searchTerm = this.value.toLowerCase()
      // Add your search logic here
      console.log("Searching for:", searchTerm)
    })
  })
}

// Initialize search on page load
document.addEventListener("DOMContentLoaded", setupSearch)
