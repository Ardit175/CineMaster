/**
 * ============================================
 * CineMaster - Main JavaScript File
 * ============================================
 * Client-side functionality for the cinema booking platform
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize all components
    initializeFlashMessages();
    initializeTooltips();
    initializeSeatSelection();
    initializeFormValidation();
    initializeSearchAutocomplete();
    initializeSessionTimer();
    
});

/**
 * ===========================================
 * Flash Messages - Auto-dismiss alerts
 * ===========================================
 */
function initializeFlashMessages() {
    // Auto-dismiss success messages after 5 seconds
    const alerts = document.querySelectorAll('.alert-success, .alert-info');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * ===========================================
 * Bootstrap Tooltips Initialization
 * ===========================================
 */
function initializeTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * ===========================================
 * Seat Selection System
 * ===========================================
 * Handles interactive seat selection for booking
 */
function initializeSeatSelection() {
    const seatContainer = document.getElementById('seat-container');
    if (!seatContainer) return;
    
    const selectedSeatsInput = document.getElementById('selected-seats');
    const totalAmountDisplay = document.getElementById('total-amount');
    const selectedCountDisplay = document.getElementById('selected-count');
    const proceedBtn = document.getElementById('proceed-btn');
    const ticketPrice = parseFloat(seatContainer.dataset.price) || 12.99;
    const maxSeats = parseInt(seatContainer.dataset.maxSeats) || 10;
    
    let selectedSeats = [];
    
    // Handle seat click
    seatContainer.addEventListener('click', function(e) {
        const seat = e.target.closest('.seat.available');
        if (!seat) return;
        
        const seatId = seat.dataset.seat;
        const index = selectedSeats.indexOf(seatId);
        
        if (index > -1) {
            // Deselect seat
            selectedSeats.splice(index, 1);
            seat.classList.remove('selected');
        } else {
            // Check max seats limit
            if (selectedSeats.length >= maxSeats) {
                showToast('warning', `Maximum ${maxSeats} seats allowed per booking.`);
                return;
            }
            
            // Select seat
            selectedSeats.push(seatId);
            seat.classList.add('selected');
        }
        
        // Update UI
        updateSeatSelection();
    });
    
    function updateSeatSelection() {
        const total = selectedSeats.length * ticketPrice;
        
        if (selectedSeatsInput) {
            selectedSeatsInput.value = selectedSeats.join(',');
        }
        
        if (totalAmountDisplay) {
            totalAmountDisplay.textContent = '$' + total.toFixed(2);
        }
        
        if (selectedCountDisplay) {
            selectedCountDisplay.textContent = selectedSeats.length;
        }
        
        if (proceedBtn) {
            proceedBtn.disabled = selectedSeats.length === 0;
        }
    }
}

/**
 * ===========================================
 * Form Validation Enhancement
 * ===========================================
 */
function initializeFormValidation() {
    // Bootstrap form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthIndicator = document.getElementById('password-strength');
    
    if (passwordInput && strengthIndicator) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            
            strengthIndicator.className = 'progress-bar';
            strengthIndicator.style.width = strength.percent + '%';
            strengthIndicator.classList.add(strength.class);
        });
    }
    
    // Password confirmation matching
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword && passwordInput) {
        confirmPassword.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
}

/**
 * Check password strength
 * @param {string} password 
 * @returns {object}
 */
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 25;
    if (password.match(/[a-z]/)) strength += 25;
    if (password.match(/[A-Z]/)) strength += 25;
    if (password.match(/[0-9]/)) strength += 12.5;
    if (password.match(/[^a-zA-Z0-9]/)) strength += 12.5;
    
    let colorClass = 'bg-danger';
    if (strength >= 50) colorClass = 'bg-warning';
    if (strength >= 75) colorClass = 'bg-success';
    
    return {
        percent: strength,
        class: colorClass
    };
}

/**
 * ===========================================
 * Search Autocomplete
 * ===========================================
 */
function initializeSearchAutocomplete() {
    const searchInput = document.getElementById('search-input');
    const suggestionsContainer = document.getElementById('search-suggestions');
    
    if (!searchInput || !suggestionsContainer) return;
    
    let debounceTimer;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(function() {
            fetchSuggestions(query);
        }, 300);
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
    
    async function fetchSuggestions(query) {
        try {
            const response = await fetch(`/search.php?ajax=1&q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.length > 0) {
                suggestionsContainer.innerHTML = data.map(movie => `
                    <a href="/movie.php?id=${movie.id}" class="list-group-item list-group-item-action bg-dark text-light">
                        <strong>${escapeHtml(movie.title)}</strong>
                        <small class="text-muted d-block">${movie.genres || 'No genres'}</small>
                    </a>
                `).join('');
                suggestionsContainer.style.display = 'block';
            } else {
                suggestionsContainer.innerHTML = '<div class="p-3 text-muted">No results found</div>';
                suggestionsContainer.style.display = 'block';
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }
}

/**
 * ===========================================
 * Session Timer
 * ===========================================
 * Warns user before session expires
 */
function initializeSessionTimer() {
    // Check if user is logged in
    const sessionTimeout = document.body.dataset.sessionTimeout;
    if (!sessionTimeout) return;
    
    const timeoutMs = parseInt(sessionTimeout) * 60 * 1000;
    const warningTime = timeoutMs - (2 * 60 * 1000); // Warn 2 minutes before
    
    setTimeout(function() {
        showToast('warning', 'Your session will expire in 2 minutes. Please save your work.');
    }, warningTime);
    
    setTimeout(function() {
        window.location.href = '/login.php?timeout=1';
    }, timeoutMs);
}

/**
 * ===========================================
 * Toast Notifications
 * ===========================================
 */
function showToast(type, message) {
    // Create toast container if it doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }
    
    const toastId = 'toast-' + Date.now();
    const bgClass = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'warning': 'bg-warning text-dark',
        'info': 'bg-info text-dark'
    }[type] || 'bg-secondary';
    
    const toastHtml = `
        <div id="${toastId}" class="toast ${bgClass}" role="alert">
            <div class="d-flex">
                <div class="toast-body">${escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    
    // Remove from DOM after hiding
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

/**
 * ===========================================
 * Utility Functions
 * ===========================================
 */

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Format currency
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

// Format date
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

// Loading overlay
function showLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = '<div class="cinema-loader"></div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Confirm dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// AJAX form submission
async function submitFormAjax(form, options = {}) {
    const formData = new FormData(form);
    
    showLoading();
    
    try {
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        hideLoading();
        
        if (data.success) {
            if (options.onSuccess) options.onSuccess(data);
            if (data.redirect) window.location.href = data.redirect;
        } else {
            if (options.onError) options.onError(data);
            showToast('error', data.message || 'An error occurred.');
        }
        
    } catch (error) {
        hideLoading();
        showToast('error', 'Network error. Please try again.');
        console.error('Form submission error:', error);
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('success', 'Copied to clipboard!');
    }).catch(function(err) {
        console.error('Copy failed:', err);
    });
}

// Print ticket
function printTicket() {
    window.print();
}

/**
 * ===========================================
 * Movie Filter Functions
 * ===========================================
 */
function filterMovies(filter) {
    const cards = document.querySelectorAll('.movie-item');
    
    cards.forEach(function(card) {
        const status = card.dataset.status;
        
        if (filter === 'all' || status === filter) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(function(btn) {
        btn.classList.remove('active');
        if (btn.dataset.filter === filter) {
            btn.classList.add('active');
        }
    });
}

/**
 * ===========================================
 * Admin Dashboard Charts (if Chart.js available)
 * ===========================================
 */
function initializeCharts(data) {
    if (typeof Chart === 'undefined') return;
    
    // Revenue chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Revenue',
                    data: data.revenue,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// Export functions for global use
window.CineMaster = {
    showToast,
    showLoading,
    hideLoading,
    confirmAction,
    submitFormAjax,
    copyToClipboard,
    printTicket,
    filterMovies
};
