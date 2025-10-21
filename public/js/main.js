/**
 * Blacklist Manager - Main JavaScript
 */

// Toggle all checkboxes
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('input[name="selected_ips[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = source.checked);
}

// Confirm delete
function confirmDelete(message = 'Are you sure you want to delete this?') {
    return confirm(message);
}

// Auto-submit form on change
function autoSubmit(form) {
    form.submit();
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Show loading spinner
function showLoading(message = 'Loading...') {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    overlay.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 8px; text-align: center;">
            <i class="fas fa-spinner fa-spin" style="font-size: 3em; color: #3498db;"></i>
            <p style="margin-top: 15px; font-size: 1.2em;">${message}</p>
        </div>
    `;
    document.body.appendChild(overlay);
}

// Hide loading spinner
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// Validate IP address
function validateIP(ip) {
    const ipPattern = /^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$/;
    if (!ipPattern.test(ip)) {
        return false;
    }

    const parts = ip.split('/')[0].split('.');
    return parts.every(part => {
        const num = parseInt(part);
        return num >= 0 && num <= 255;
    });
}

// Real-time IP validation
document.addEventListener('DOMContentLoaded', function() {
    const ipInputs = document.querySelectorAll('input[name="ip_address"]');

    ipInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && !validateIP(value)) {
                this.style.borderColor = '#e74c3c';
                alert('Invalid IP address format');
            } else {
                this.style.borderColor = '';
            }
        });
    });

    // Auto-close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Tab switching
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Show selected tab content
    document.getElementById(tabName).classList.add('active');

    // Add active class to clicked tab
    event.target.classList.add('active');
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Live search (if needed)
const liveSearch = debounce(function(searchTerm) {
    console.log('Searching for:', searchTerm);
    // Implement live search logic here
}, 300);

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const required = form.querySelectorAll('[required]');
    let valid = true;

    required.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#e74c3c';
            valid = false;
        } else {
            field.style.borderColor = '';
        }
    });

    if (!valid) {
        alert('Please fill in all required fields');
    }

    return valid;
}
