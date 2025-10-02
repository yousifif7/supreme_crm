/**
 * Show a dashboard alert toast
 * @param {string} type - Type of alert (e.g., "guard_missed_shift", "panic_button", etc.)
 * @param {string} message - Alert message
 * @param {string|null} route - Route to go when clicked (null = no navigation)
 * @param {string|number|null} alertId - Alert ID (required for panic/emergency)
 */
function showDashboardAlert(type, message, route = null, alertId = null) {
    // Create container if not exists
    let container = document.getElementById('dashboard-alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'dashboard-alert-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `dashboard-alert alert-${type}`;
    if (alertId) toast.dataset.alertId = alertId; // 🔥 set ID here

    toast.innerHTML = `
        <div class="alert-content">
            <p>${message}</p>
            <button class="alert-close">&times;</button>
        </div>
    `;

    // Click on toast
    toast.addEventListener('click', e => {
        if (e.target.classList.contains('alert-close')) {
            e.stopPropagation();
            toast.remove();
            return;
        }

        // Panic or emergency alerts: trigger AJAX
        if ((type === 'panic_button' || type === 'emergency_alert') && alertId) {
            $.ajax({
                url: `/api/emergency-alerts/${alertId}/acknowledge`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.success) {
                        toast_success('Emergency alert acknowledged');
                        stopAlertSound();
                        toast.remove();
                    } else {
                        toast_danger(response.message || 'Error acknowledging alert.');
                    }
                },
                error: function (err) {
                    console.error(err);
                    toast_danger('Error acknowledging alert.');
                }
            });
        } else {
            // Other alerts: navigate to route
            if (route) window.location.href = route;
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

    // Auto-remove after 30s
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
                'Accept': 'application/json'
            },
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
                    case 'patrol_warning':
                        route = `/shift-dates/${alert.shift_id}/view`;
                        break;
                    case 'patrol_missed':
                        route = `/shift-dates/${alert.shift_id}/view`;
                        break;
                    case 'checkcall_warning':
                        route = `/shift-dates/${alert.shift_id}/view`;
                        break;
                    case 'checkcall_missed':
                        route = `/shift-dates/${alert.shift_id}/view`;
                        break;
                    case 'shift_unassigned':
                        route = `/shift-dates/${alert.shift_date_id}/view`;
                        break;
                    case 'panic_button':
                        route = ``;
                        break;
                    case 'idle_control':
                        route = ``;
                        break;
                }

                // Show toast alert
                showDashboardAlert(alert.type, alert.message, route, alert.alert_id || null);

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

$(document).on('click', '.dashboard-alert', function (e) {
    e.preventDefault();

    const alertId = $(this).data('alert-id');
    const type = $(this).attr('class').match(/alert-([a-z_]+)/)?.[1]; // extract type from class

    if (!alertId || (type !== 'panic_button' && type !== 'emergency_alert')) {
        // Do nothing for other types
        return;
    }

    $('#alert_id').val(alertId); // optional

    $.ajax({
        url: `/api/emergency-alerts/${alertId}/acknowledge`,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function (response) {
            if (response.success) {
                toast_success('Emergency alert acknowledged');
                stopAlertSound();
                $(`#alert-${alertId}`).remove();
            } else {
                toast_danger(response.message || 'Error acknowledging alert.');
            }
        },
        error: function (err) {
            console.error(err);
            toast_danger('Error acknowledging alert.');
        }
    });
});
