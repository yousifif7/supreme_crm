
	/**
 * Show a dashboard alert toast
 * @param {string} type - Type of alert (e.g., "guard_missed_shift", "panic", etc.)
 * @param {string} message - Alert message
 * @param {string} route - Route to go when clicked
 * @param {number} duration - Auto close in ms (null = persistent)
 */
function showDashboardAlert(type, message, route) {
    // Create container if not exists
    let container = document.getElementById('dashboard-alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'dashboard-alert-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `dashboard-alert alert-${type}`;

    toast.innerHTML = `
        <div class="alert-content">
            <p>${message}</p>
            <button class="alert-close">&times;</button>
        </div>
    `;

    // Click on toast navigates to route
    toast.addEventListener('click', e => {
        if (!e.target.classList.contains('alert-close')) {
            window.location.href = route;
        }
    });

    // Close button
    toast.querySelector('.alert-close').addEventListener('click', e => {
        e.stopPropagation();
        toast.remove();
    });

    container.appendChild(toast);

    // Animate in
    setTimeout(() => toast.classList.add('show'), 50);

    // Optional auto-remove after 30s
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 30000);
}


// app.js (loaded on every page)

async function fetchDashboardAlerts() {
    try {
        const response = await fetch(`${baseUrl}/api/dashboard-alerts`, {
            headers: {
                'Accept': 'application/json',
                // 'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        // if (!response.ok) throw new Error('Failed to fetch alerts');

        const data = await response.json();

        if (data.alerts && data.alerts.length > 0) {
            data.alerts.forEach(alert => {
                let route = '/dashboard';
                switch (alert.type) {
                    case 'patrol_missed':
                        route = `/shift-dates/${alert.shift_id}/view`;
                        break;
                    case 'checkcall_missed':
                        route = `/shift-dates/${alert.shift_id}/view`;
                        break;
                    case 'document_expiry':
                        route = `/documents/${alert.document_id}`;
                        break;
                    case 'shift_unassigned':
                        route = `/shift-dates/${alert.shift_date_id}/view`;
                        break;
                    case 'panic_button':
                        route = `/panic/${alert.user_id}`;
                        break;
                }
                showDashboardAlert(alert.type, alert.message, route, null);
                playBeep();
            });
        }
    } catch (err) {
        console.error('Error fetching dashboard alerts:', err);
    }
}

// Load preference from localStorage
let soundEnabled = localStorage.getItem('alertSoundEnabled') === 'true';

// Update bell icon based on state
const bell = document.getElementById('alert-bell');
function updateBellIcon() {
    bell.textContent = soundEnabled ? '🔔' : '🔕';
}
updateBellIcon();

// Click to toggle sound
bell.addEventListener('click', () => {
    soundEnabled = !soundEnabled;
    localStorage.setItem('alertSoundEnabled', soundEnabled);
    updateBellIcon();
});

// Web Audio API beep
let audioCtx;
function playBeep(duration = 100, frequency = 440, volume = 0.2) {
    if (!soundEnabled) return; // respect toggle

    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }

    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();

    oscillator.type = 'sine';
    oscillator.frequency.value = frequency;
    gainNode.gain.value = volume;

    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);

    oscillator.start();
    setTimeout(() => oscillator.stop(), duration);
}


function playAlertSound() {
    const sound = document.getElementById('alert-sound');
    if (sound) {
        sound.currentTime = 0; // rewind
        sound.play().catch(e => console.warn('Sound blocked:', e));
    }
}

// Run immediately, then repeat every 60 seconds
fetchDashboardAlerts();
setInterval(fetchDashboardAlerts, 60000);
