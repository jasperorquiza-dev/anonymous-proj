// forum.js - JavaScript functionality

// Toast Notification System
function showToast(message, type = 'success', duration = 3000) {
    // Create toast container if it doesn't exist
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Icons for different types
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    // Titles for different types
    const titles = {
        success: 'Success!',
        error: 'Error!',
        warning: 'Warning!',
        info: 'Info'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || icons.success}</div>
        <div class="toast-content">
            <div class="toast-title">${titles[type] || titles.success}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        <div class="toast-progress"></div>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto remove
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 400);
    }, duration);
}

// New Theme Toggle (SVG, single button)
const storageKey = 'theme-preference';

const onClick = () => {
  theme.value = theme.value === 'light' ? 'dark' : 'light';
  setPreference();
};

const getColorPreference = () => {
  const stored = localStorage.getItem(storageKey);
  if (stored) return stored;
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const setPreference = () => {
  localStorage.setItem(storageKey, theme.value);
  reflectPreference();
};

const reflectPreference = () => {
  // Apply to <html> so variables override :root reliably
  document.documentElement.setAttribute('data-theme', theme.value);
  document.querySelector('#theme-toggle')?.setAttribute('aria-label', theme.value);
};

const theme = { value: getColorPreference() };

// set early so no page flashes / CSS is made aware
reflectPreference();

window.onload = () => {
  reflectPreference();
  const toggle = document.querySelector('#theme-toggle');
  if (toggle) toggle.addEventListener('click', onClick);
};

// sync with system changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', ({ matches: isDark }) => {
  theme.value = isDark ? 'dark' : 'light';
  setPreference();
});

// Character counter
function initCharacterCounter() {
    const messageInput = document.getElementById('message-input');
    const charCount = document.getElementById('char-count');

    if (messageInput && charCount) {
        // Get max length from data attribute or default to 500
        const maxLength = parseInt(messageInput.getAttribute('maxlength')) || 500;

        messageInput.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;

            if (length > maxLength) {
                this.value = this.value.substring(0, maxLength);
                charCount.textContent = maxLength;
            }

            // Dynamic color thresholds based on max length
            const warningThreshold = maxLength * 0.9; // 90% of max length
            const cautionThreshold = maxLength * 0.8; // 80% of max length

            if (length >= warningThreshold) {
                charCount.style.color = '#DC2626'; // Red for warning
            } else if (length >= cautionThreshold) {
                charCount.style.color = '#2563EB'; // Blue for caution
            } else {
                charCount.style.color = '#6B7280'; // Gray for normal
            }
        });
    }
}

// Form submission
function initMessageForm() {
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const charCount = document.getElementById('char-count');
    const submitBtn = document.getElementById('submit-btn');

    if (messageForm) {
        messageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!messageInput) return;
            
            const message = messageInput.value.trim();
            if (!message) return;
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Posting...';
            }
            
            try {
                const formData = new FormData(this);
                const response = await fetch('ajax/save_message.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    messageInput.value = '';
                    if (charCount) {
                        charCount.textContent = '0';
                        charCount.style.color = '#6B7280';
                    }
                    
                    showTempMessage('Message posted successfully!', 'success');
                    loadMessages();
                    
                } else {
                    showTempMessage('Error posting message: ' + result.message, 'error');
                }
            } catch (error) {
                showTempMessage('Error posting message', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Post Anonymously';
                }
            }
        });
    }
}

// Load messages function
async function loadMessages() {
    const messagesContainer = document.getElementById('messages-container');
    if (!messagesContainer) return;
    
    try {
        const response = await fetch('ajax/load_messages.php');
        const data = await response.json();
        
        if (data.status === 'success' && data.messages && data.messages.length > 0) {
            messagesContainer.innerHTML = '';
            data.messages.forEach(message => {
                const messageElement = createMessageElement(message);
                messagesContainer.appendChild(messageElement);
            });
            scrollToBottom();
        } else {
            messagesContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <h3>No messages yet</h3>
                    <p>Be the first to start a conversation!</p>
                </div>
            `;
        }
    } catch (error) {
        messagesContainer.innerHTML = '<div class="loading">Error loading messages. Please refresh.</div>';
    }
}

function createMessageElement(message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message-item';
    
    // Use EXACT same logic as time_test.php
    let timeString = 'Just now';
    if (message.created_at) {
        try {
            const messageTime = new Date(message.created_at);
            timeString = messageTime.toLocaleString('en-US', {
                timeZone: 'Asia/Manila',
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        } catch (e) {}
    }
    
    messageDiv.innerHTML = `
        <div class="message-header">
            <span class="message-username">${message.username || 'Anonymous'}</span>
            <span class="message-time">${timeString}</span>
        </div>
        <div class="message-content">${message.message || ''}</div>
    `;
    
    return messageDiv;
}

function showTempMessage(text, type) {
    // Use the new toast notification system
    showToast(text, type, 3000);
}

function initRefreshButton() {
    const refreshBtn = document.getElementById('refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', loadMessages);
    }
}

// Online users system
async function updateOnlineUsers() {
    const onlineUsersElement = document.getElementById('online-users');
    if (!onlineUsersElement) return;
    
    try {
        const response = await fetch('ajax/get_online_users.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            onlineUsersElement.textContent = data.online_count;
        }
    } catch (error) {}
}

async function updateUserOnlineStatus() {
    try {
        await fetch('ajax/update_online_status.php');
    } catch (error) {}
}

function startOnlineTracking() {
    updateUserOnlineStatus();
    updateOnlineUsers();
    setInterval(updateUserOnlineStatus, 30000);
    setInterval(updateOnlineUsers, 15000);
}

function scrollToBottom() {
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        messagesContainer.scrollTo({
            top: messagesContainer.scrollHeight,
            behavior: 'smooth'
        });
    }
}

// Initialize everything safely
document.addEventListener('DOMContentLoaded', function() {
    // Initialize other components
    initCharacterCounter();
    initMessageForm();
    initRefreshButton();
    
    // Load messages after a short delay
    setTimeout(() => {
        loadMessages();
        
        // Focus on message input if it exists
        const messageInput = document.getElementById('message-input');
        if (messageInput) messageInput.focus();
        
        // Scroll to bottom after messages load
        setTimeout(scrollToBottom, 500);
    }, 100);
    
    // Start online tracking
    startOnlineTracking();
    
    // Auto-refresh messages every 30 seconds
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        setInterval(loadMessages, 30000);
    }
});
