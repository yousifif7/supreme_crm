
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
            headers: { 'Accept': 'application/json' },
            credentials: 'include' // needed if using Laravel Sanctum
        });

        if (!response.ok) {
            throw new Error(`Failed to fetch alerts: ${response.status}`);
        }

        const data = await response.json();

        if (data.alerts && data.alerts.length > 0) {
            data.alerts.forEach(alert => {
                let route = '/dashboard';
                switch (alert.type) {
                    case 'patrol_missed':
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

                // Show toast alert
                showDashboardAlert(alert.type, alert.message, route);

                // Play sound automatically
                playAlertSound();
            });
        }
    } catch (err) {
        console.error('Error fetching dashboard alerts:', err);
    }
}

function playAlertSound() {
    const sound = document.getElementById('alert-sound');
    if (!sound) return;

    // Reset and play
    sound.currentTime = 0;
    const playPromise = sound.play();

    // Catch browser autoplay block
    if (playPromise !== undefined) {
        playPromise.catch(err => {
            console.warn('Alert sound blocked:', err);
        });
    }
}

// Fetch immediately, then repeat every 60 seconds
fetchDashboardAlerts();
setInterval(fetchDashboardAlerts, 60000);
