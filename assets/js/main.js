/**
 * MyEduConnect - Main JavaScript
 * Learning Management System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initNavbar();
    initForms();
    initModals();
    initTooltips();
    initSearch();
    initFileUpload();
});

/**
 * Initialize Navbar
 */
function initNavbar() {
    // Mobile menu toggle
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function() {
            navbarCollapse.classList.toggle('show');
        });
    }
    
    // Close mobile menu on link click
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                navbarCollapse.classList.remove('show');
            }
        });
    });
}

/**
 * Initialize Forms
 */
function initForms() {
    // Form validation
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
    
    // Password strength indicator
    const passwordInputs = document.querySelectorAll('input[type="password"][data-strength="true"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    });
}

/**
 * Validate Form
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        const value = input.value.trim();
        const fieldName = input.getAttribute('data-field') || input.name;
        
        if (value === '') {
            showInputError(input, `${fieldName} is required`);
            isValid = false;
        } else {
            clearInputError(input);
            
            // Email validation
            if (input.type === 'email' && !isValidEmail(value)) {
                showInputError(input, 'Please enter a valid email address');
                isValid = false;
            }
            
            // Password confirmation
            if (input.type === 'password' && input.hasAttribute('data-confirm')) {
                const confirmPassword = document.querySelector(`input[name="${input.getAttribute('data-confirm')}"]`);
                if (confirmPassword && value !== confirmPassword.value) {
                    showInputError(input, 'Passwords do not match');
                    isValid = false;
                }
            }
        }
    });
    
    return isValid;
}

/**
 * Show Input Error
 */
function showInputError(input, message) {
    input.classList.add('is-invalid');
    
    let errorElement = input.parentNode.querySelector('.invalid-feedback');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        input.parentNode.appendChild(errorElement);
    }
    errorElement.textContent = message;
}

/**
 * Clear Input Error
 */
function clearInputError(input) {
    input.classList.remove('is-invalid');
    const errorElement = input.parentNode.querySelector('.invalid-feedback');
    if (errorElement) {
        errorElement.remove();
    }
}

/**
 * Check if email is valid
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Check Password Strength
 */
function checkPasswordStrength(password) {
    const strengthIndicator = document.querySelector('.password-strength');
    if (!strengthIndicator) return;
    
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/\d/)) strength++;
    if (password.match(/[^a-zA-Z\d]/)) strength++;
    
    const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#28a745'];
    
    strengthIndicator.textContent = levels[strength];
    strengthIndicator.style.color = colors[strength];
}

/**
 * Initialize Modals
 */
function initModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            document.body.style.overflow = 'hidden';
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.style.overflow = '';
        });
    });
}

/**
 * Initialize Tooltips
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

/**
 * Initialize Search
 */
function initSearch() {
    const searchInput = document.querySelector('input[data-search="true"]');
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        }
    });
}

/**
 * Perform Search
 */
function performSearch(query) {
    // This would typically make an AJAX call
    console.log('Searching for:', query);
}

/**
 * Initialize File Upload
 */
function initFileUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"][data-upload="true"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                validateFile(file);
            }
        });
    });
}

/**
 * Validate File
 */
function validateFile(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    if (file.size > maxSize) {
        alert('File size exceeds 5MB limit');
        return false;
    }
    
    if (!allowedTypes.includes(file.type)) {
        alert('Invalid file type. Please upload PDF or Word documents.');
        return false;
    }
    
    return true;
}

/**
 * Show Alert Message
 */
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.alert-container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

/**
 * Show Loading Spinner
 */
function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    document.body.appendChild(spinner);
}

/**
 * Hide Loading Spinner
 */
function hideLoading() {
    const spinner = document.querySelector('.spinner-overlay');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Confirm Action
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Format Currency
 */
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

/**
 * Format Date
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

/**
 * Copy to Clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copied to clipboard!', 'success');
    }).catch(err => {
        showAlert('Failed to copy to clipboard', 'error');
    });
}

/**
 * AJAX Request Helper
 */
function ajaxRequest(url, method = 'GET', data = null) {
    showLoading();
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    return fetch(url, options)
        .then(response => {
            hideLoading();
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            throw error;
        });
}

/**
 * Enrollment Handler
 */
function enrollInCourse(courseId) {
    confirmAction('Are you sure you want to enroll in this course?', function() {
        ajaxRequest('/api/enroll.php', 'POST', { course_id: courseId })
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Enrollment failed. Please try again.', 'error');
            });
    });
}

/**
 * Course Search Handler
 */
function searchCourses(keyword) {
    const searchResults = document.querySelector('.search-results');
    
    if (keyword.length < 2) {
        searchResults.innerHTML = '';
        return;
    }
    
    ajaxRequest(`/api/search.php?q=${encodeURIComponent(keyword)}`)
        .then(data => {
            if (data.courses && data.courses.length > 0) {
                let html = '<div class="list-group">';
                data.courses.forEach(course => {
                    html += `
                        <a href="/course.php?id=${course.course_id}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${course.title}</h6>
                                <small>${formatCurrency(course.price)}</small>
                            </div>
                            <p class="mb-1">${course.description.substring(0, 100)}...</p>
                            <small>${course.instructor_name}</small>
                        </a>
                    `;
                });
                html += '</div>';
                searchResults.innerHTML = html;
            } else {
                searchResults.innerHTML = '<p class="text-muted">No courses found.</p>';
            }
        })
        .catch(error => {
            searchResults.innerHTML = '<p class="text-danger">Search failed. Please try again.</p>';
        });
}

/**
 * Payment Handler
 */
function processPayment(courseId, amount) {
    confirmAction(`Confirm payment of ${formatCurrency(amount)}?`, function() {
        showLoading();
        
        // Simulate payment processing
        setTimeout(() => {
            hideLoading();
            showAlert('Payment successful! You are now enrolled.', 'success');
            setTimeout(() => {
                window.location.href = '/student/dashboard.php';
            }, 2000);
        }, 2000);
    });
}

/**
 * Material Download Handler
 */
function downloadMaterial(materialId) {
    window.location.href = `/api/download.php?id=${materialId}`;
}

/**
 * Dynamic Content Loader
 */
function loadContent(url, targetElement) {
    showLoading();
    
    fetch(url)
        .then(response => response.text())
        .then(html => {
            hideLoading();
            document.querySelector(targetElement).innerHTML = html;
        })
        .catch(error => {
            hideLoading();
            showAlert('Failed to load content', 'error');
        });
}

/**
 * Real-time Notification Checker
 */
function checkNotifications() {
    if (!isLoggedIn()) return;
    
    ajaxRequest('/api/notifications.php')
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                updateNotificationBadge(data.notifications.length);
            }
        })
        .catch(error => {
            console.error('Failed to check notifications');
        });
}

/**
 * Update Notification Badge
 */
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
    }
}

/**
 * Auto-save Form
 */
function autoSaveForm(form, interval = 30000) {
    let autoSaveInterval;
    
    form.addEventListener('input', function() {
        clearTimeout(autoSaveInterval);
        autoSaveInterval = setTimeout(() => {
            const formData = new FormData(form);
            ajaxRequest('/api/autosave.php', 'POST', Object.fromEntries(formData))
                .then(data => {
                    if (data.success) {
                        console.log('Auto-saved');
                    }
                });
        }, interval);
    });
}

/**
 * Export to CSV
 */
function exportToCSV(data, filename) {
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

/**
 * Convert to CSV
 */
function convertToCSV(data) {
    const headers = Object.keys(data[0]);
    const csv = [
        headers.join(','),
        ...data.map(row => headers.map(header => JSON.stringify(row[header])).join(','))
    ];
    return csv.join('\n');
}

// Check for session timeout
setInterval(checkSessionTimeout, 60000); // Check every minute

function checkSessionTimeout() {
    const loginTime = localStorage.getItem('loginTime');
    if (loginTime) {
        const elapsed = Date.now() - parseInt(loginTime);
        const timeout = 3600000; // 1 hour
        
        if (elapsed > timeout) {
            window.location.href = '/logout.php';
        }
    }
}
