/**
 * Real-time Clock Component for Philippines Time (GMT+8)
 * Updates every second with live time display
 */

class PhilippinesRealTimeClock {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            showSeconds: true,
            showDate: true,
            showTimezone: true,
            updateInterval: 1000, // 1 second
            format: '12', // 12 or 24 hour format
            ...options
        };
        
        this.isRunning = false;
        this.serverTimeOffset = 0;
        this.lastServerSync = 0;
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Container element not found');
            return;
        }
        
        this.syncWithServer();
        this.startClock();
        this.setupEventListeners();
    }
    
    /**
     * Sync with server time to ensure accuracy
     */
    async syncWithServer() {
        try {
            const response = await fetch('philippines_time.php?action=get_time');
            const serverData = await response.json();
            
            const clientTime = new Date().getTime();
            const serverTime = serverData.timestamp * 1000;
            
            this.serverTimeOffset = serverTime - clientTime;
            this.lastServerSync = Date.now();
        } catch (error) {
            // Silent fail - server time sync error
        }
    }
    
    /**
     * Start the real-time clock
     */
    startClock() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.updateDisplay();
        
        this.intervalId = setInterval(() => {
            this.updateDisplay();
            
            // Re-sync with server every 5 minutes
            if (Date.now() - this.lastServerSync > 300000) {
                this.syncWithServer();
            }
        }, this.options.updateInterval);
    }
    
    /**
     * Stop the real-time clock
     */
    stopClock() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.isRunning = false;
        }
    }
    
    /**
     * Update the clock display
     */
    updateDisplay() {
        const now = new Date(Date.now() + this.serverTimeOffset);
        
        const timeString = this.formatTime(now);
        const dateString = this.formatDate(now);
        
        let displayHTML = `
            <div class="realtime-clock">
                <div class="clock-time">${timeString}</div>
                ${this.options.showDate ? `<div class="clock-date">${dateString}</div>` : ''}
                ${this.options.showTimezone ? '<div class="clock-timezone">Philippines Standard Time (GMT+8)</div>' : ''}
            </div>
        `;
        
        this.container.innerHTML = displayHTML;
    }
    
    /**
     * Convert UTC time to Philippines time
     */
    convertToPhilippinesTime(date) {
        // Create a new date object with Philippines timezone
        const philippinesTime = new Date(date.toLocaleString("en-US", {timeZone: "Asia/Manila"}));
        return philippinesTime;
    }
    
    /**
     * Format time based on options
     */
    formatTime(date) {
        const options = {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Manila'
        };
        
        if (this.options.showSeconds) {
            options.second = '2-digit';
        }
        
        if (this.options.format === '12') {
            options.hour12 = true;
        } else {
            options.hour12 = false;
        }
        
        return date.toLocaleTimeString('en-US', options);
    }
    
    /**
     * Format date
     */
    formatDate(date) {
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            timeZone: 'Asia/Manila'
        };
        
        return date.toLocaleDateString('en-US', options);
    }
    
    /**
     * Get current Philippines time as object
     */
    getCurrentTime() {
        const now = new Date(Date.now() + this.serverTimeOffset);
        
        return {
            timestamp: now.getTime(),
            formatted: this.formatTime(now),
            date: this.formatDate(now),
            iso: now.toISOString(),
            timezone: 'Asia/Manila'
        };
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopClock();
            } else {
                this.startClock();
                this.syncWithServer();
            }
        });
        
        // Handle window focus
        window.addEventListener('focus', () => {
            this.syncWithServer();
        });
    }
    
    /**
     * Update clock options
     */
    updateOptions(newOptions) {
        this.options = { ...this.options, ...newOptions };
        this.updateDisplay();
    }
}

// CSS styles for the clock
const clockStyles = `
<style>
.realtime-clock {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    margin: 10px 0;
}

.clock-time {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.clock-date {
    font-size: 1.2em;
    margin-bottom: 5px;
    opacity: 0.9;
}

.clock-timezone {
    font-size: 0.9em;
    opacity: 0.8;
    font-style: italic;
}

/* Responsive design */
@media (max-width: 768px) {
    .clock-time {
        font-size: 2em;
    }
    
    .clock-date {
        font-size: 1em;
    }
}
</style>
`;

// Add styles to document head
if (typeof document !== 'undefined') {
    document.head.insertAdjacentHTML('beforeend', clockStyles);
}

// Auto-initialize if container exists
document.addEventListener('DOMContentLoaded', function() {
    const autoClock = document.getElementById('philippines-clock');
    if (autoClock && !window.philippinesClock) {
        window.philippinesClock = new PhilippinesRealTimeClock('philippines-clock');
    }
});
