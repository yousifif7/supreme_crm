/**
 * Performance Utilities
 * Helper functions to optimize JavaScript performance and reduce CPU usage
 */

/**
 * Debounce function - delays execution until after a pause in calls
 * Good for: resize, scroll, input events
 */
export function debounce(func, wait = 150) {
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

/**
 * Throttle function - limits execution to once per interval
 * Good for: mousemove, scroll events where you need regular updates
 */
export function throttle(func, limit = 100) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Request animation frame throttle - syncs with browser rendering
 * Good for: animations, visual updates
 */
export function rafThrottle(callback) {
    let requestId = null;
    let lastArgs;

    const later = (context) => () => {
        requestId = null;
        callback.apply(context, lastArgs);
    };

    const throttled = function(...args) {
        lastArgs = args;
        if (requestId === null) {
            requestId = requestAnimationFrame(later(this));
        }
    };

    throttled.cancel = () => {
        cancelAnimationFrame(requestId);
        requestId = null;
    };

    return throttled;
}

/**
 * Visibility-aware polling
 * Pauses polling when tab is hidden to save CPU
 */
export function createVisibilityAwarePoller(callback, interval) {
    let intervalId = null;
    
    const poll = () => {
        if (!document.hidden) {
            callback();
        }
    };
    
    const start = () => {
        if (intervalId) return;
        poll(); // Initial call
        intervalId = setInterval(poll, interval);
    };
    
    const stop = () => {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
    };
    
    // Auto-pause when tab is hidden
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            console.debug('Polling paused: tab hidden');
        } else {
            console.debug('Polling resumed: tab visible');
            poll(); // Immediate update when tab becomes visible
        }
    });
    
    return { start, stop, poll };
}

/**
 * Batch DOM updates using DocumentFragment
 * Reduces reflows and repaints
 */
export function batchDOMUpdates(container, elements) {
    const fragment = document.createDocumentFragment();
    elements.forEach(el => fragment.appendChild(el));
    container.innerHTML = '';
    container.appendChild(fragment);
}

/**
 * Lazy load images when they enter viewport
 */
export function lazyLoadImages(selector = 'img[data-src]') {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll(selector).forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for older browsers
        document.querySelectorAll(selector).forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
}

/**
 * Prevent memory leaks by cleaning up event listeners
 */
export function createScopedEventListener(element, event, handler, options) {
    element.addEventListener(event, handler, options);
    
    return {
        remove: () => element.removeEventListener(event, handler, options)
    };
}

/**
 * Monitor performance metrics
 */
export function logPerformanceMetrics() {
    if (window.performance && window.performance.timing) {
        const timing = window.performance.timing;
        const pageLoadTime = timing.loadEventEnd - timing.navigationStart;
        const connectTime = timing.responseEnd - timing.requestStart;
        const renderTime = timing.domComplete - timing.domLoading;
        
        console.group('Performance Metrics');
        console.log(`Page Load Time: ${pageLoadTime}ms`);
        console.log(`Server Response Time: ${connectTime}ms`);
        console.log(`DOM Render Time: ${renderTime}ms`);
        console.groupEnd();
    }
}
