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
        <button class="close-btn" aria-label="Close">&times;</button>
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

    // Close button handler
    const closeBtn = toast.querySelector('.close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            toast.classList.remove('show');
            setTimeout(() => { if (toast.parentNode) container.removeChild(toast); }, 300);
        });
    }
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
        <button class="close-btn" aria-label="Close">&times;</button><br>
        <div class="toast-icon">⚠</div>
        <div class="toast-content">
            <p>${message}</p>
            <div class="toast-actions">
                <button class="override-btn">Override Restriction</button>
            </div>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 50);

    // Step 1: Override clicked
    toast.querySelector('.override-btn').addEventListener('click', function () {
        // Replace actions with confirmation buttons
        const actions = toast.querySelector('.toast-actions');
        actions.innerHTML = `
            <button class="confirm-btn">Yes, Override</button>
            <button class="cancel-btn">Cancel</button>
        `;

        // Step 2: Confirm override
        actions.querySelector('.confirm-btn').addEventListener('click', function () {
            if (typeof onOverride === 'function') {
                onOverride();
            }
            closeToast();
        });

        // Step 2: Cancel override
        actions.querySelector('.cancel-btn').addEventListener('click', function () {
            closeToast();
        });
    });

    function closeToast() {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) container.removeChild(toast);
        }, 300);
    }

    // Close button handler (visible X)
    const closeBtn = toast.querySelector('.close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeToast();
        });
    }

    // Prevent clicks inside the toast from propagating to underlying page
    toast.addEventListener('click', function (e) {
        e.stopPropagation();
    });
}




