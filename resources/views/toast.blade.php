
<script>
// 🔹 Inject CSS into 
if (!document.getElementById("toast-styles")) {
    const style = document.createElement("style");
    style.id = "toast-styles";
    style.innerHTML = `
        #custom-toast-container {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 99999;
        }
        .custom-toast {
            background: #fff;
            border-radius: 16px;
            padding: 24px 32px;
            min-width: 280px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            font-family: "Segoe UI", sans-serif;
            font-size: 14px;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
            pointer-events: auto;
        }
        .custom-toast.show {
            opacity: 1;
            transform: scale(1);
        }
        .custom-toast h4 {
            margin: 12px 0 6px;
            font-size: 18px;
        }
        .custom-toast p {
            margin: 0;
            color: #555;
            font-size: 14px;
        }
        .toast-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            margin: 0 auto 12px auto;
            font-size: 36px;
            font-weight: bold;
        }
        .toast-icon.success { background: rgba(76,175,80,0.15); color: #4caf50; }
        .toast-icon.error   { background: rgba(244,67,54,0.15); color: #f44336; }
        .toast-icon.info    { background: rgba(33,150,243,0.15); color: #2196f3; }
        .custom-toast .close-btn {
            position: absolute;
            top: 8px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 4px;
            font-size: 14px;
            line-height: 1;
            cursor: pointer;
            color: rgba(0,0,0,0.7);
            padding: 2px 6px;
            z-index: 9999;
        }
    `;
    document.head.appendChild(style);
}

// 🔹 Ensure container exists
if (!document.getElementById('custom-toast-container')) {
    const container = document.createElement('div');
    container.id = 'custom-toast-container';
    document.body.appendChild(container);
}

/**
 * Show a centered toast
 * @param {string} title - Heading text
 * @param {string} message - Body text
 * @param {'success'|'error'|'info'} type
 * @param {number} duration - Auto close in ms
 */
function showToast(title, message, type = 'info', duration = 2500) {
    const container = document.getElementById('custom-toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `custom-toast ${type}`;

    let iconHtml = '';
    if (type === 'success') iconHtml = `<div class="toast-icon success">✔</div>`;
    if (type === 'error')   iconHtml = `<div class="toast-icon error">✖</div>`;
    if (type === 'info')    iconHtml = `<div class="toast-icon info">ℹ</div>`;

    toast.innerHTML = `
        ${iconHtml}
        <h4>${title}</h4>
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
