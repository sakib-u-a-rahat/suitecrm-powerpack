/**
 * Click-to-call functionality for SuiteCRM
 */
(function() {
    'use strict';
    
    // Initialize click-to-call on phone fields
    function initClickToCall() {
        const phoneFields = document.querySelectorAll('span[field="phone_work"], span[field="phone_mobile"], span[field="phone_office"]');
        
        phoneFields.forEach(function(field) {
            const phoneNumber = field.textContent.trim();
            if (phoneNumber && phoneNumber !== '') {
                field.style.cursor = 'pointer';
                field.style.color = '#0070d2';
                field.title = 'Click to call ' + phoneNumber;
                
                field.addEventListener('click', function(e) {
                    e.preventDefault();
                    makeCall(phoneNumber);
                });
            }
        });
    }
    
    // Make call via Twilio
    function makeCall(phoneNumber) {
        if (!confirm('Call ' + phoneNumber + '?')) {
            return;
        }
        
        const recordId = getRecordId();
        const moduleName = getModuleName();
        
        fetch('index.php?module=TwilioIntegration&action=makeCall', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                to: phoneNumber,
                record_id: recordId,
                module: moduleName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Call initiated successfully!', 'success');
            } else {
                showNotification('Failed to initiate call: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showNotification('Error: ' + error.message, 'error');
        });
    }
    
    // Get current record ID
    function getRecordId() {
        const match = window.location.search.match(/record=([^&]+)/);
        return match ? match[1] : null;
    }
    
    // Get current module name
    function getModuleName() {
        const match = window.location.search.match(/module=([^&]+)/);
        return match ? match[1] : null;
    }
    
    // Show notification
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = 'twilio-notification twilio-notification-' + type;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#4caf50' : '#f44336'};
            color: white;
            border-radius: 4px;
            z-index: 10000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(function() {
            notification.remove();
        }, 3000);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClickToCall);
    } else {
        initClickToCall();
    }
    
    // Re-initialize on AJAX content updates
    const observer = new MutationObserver(function(mutations) {
        initClickToCall();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
})();
