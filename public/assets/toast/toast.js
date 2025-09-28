// Ensure container exists
if (!document.getElementById('custom-toast-container')) {
    const container = document.createElement('div');
    container.id = 'custom-toast-container';
    document.body.appendChild(container);
}

/**
 * Show a centered toast
 * @param {string} message - Body text
 * @param {'success'|'error'|'info'} type
 * @param {number} duration - Auto close in ms
 */
function showToast(message, type = 'info', duration = 2500) {
    // Ensure container exists and append to body at the end
    let container = document.getElementById('custom-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'custom-toast-container';
        document.body.appendChild(container); // append at the very end
    } else {
        // Move it to the end of body to ensure it's above all modals
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'custom-toast';

    let iconHtml = '';
    if (type === 'success') iconHtml = `<div class="toast-icon success">✔</div>`;
    if (type === 'error') iconHtml = `<div class="toast-icon error">✖</div>`;
    if (type === 'info') iconHtml = `<div class="toast-icon info">ℹ</div>`;

    toast.innerHTML = `
        ${iconHtml}
        <p>${message}</p>
    `;

    container.appendChild(toast);

    // Animate in
    setTimeout(() => toast.classList.add('show'), 50);

    // Auto remove after duration
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) container.removeChild(toast);
        }, 300);
    }, duration);
}

/**
 * Show a restriction error toast with override button
 * @param {string} message - Restriction message
 * @param {Function} onOverride - Callback when override is clicked
 * @param {number} duration - Auto close in ms (optional, null = persistent)
 */
function showRestrictionToast(message, onOverride) {
    let container = document.getElementById('custom-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'custom-toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'custom-toast';

    toast.innerHTML = `
        <div class="toast-icon">⚠</div>
        <div class="toast-content">
            <p>${message}</p>
            <button class="override-btn">Override Restriction</button>
        </div>
    `;

    container.appendChild(toast);

    // Animate in
    setTimeout(() => toast.classList.add('show'), 50);

    // Bind override button
    toast.querySelector('.override-btn').addEventListener('click', function () {
        if (typeof onOverride === 'function') {
            onOverride();
        }
        // remove toast immediately
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) container.removeChild(toast);
        }, 300);
    });

    // Auto remove if no action
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) container.removeChild(toast);
        }, 300);
    }, 5000);
}




