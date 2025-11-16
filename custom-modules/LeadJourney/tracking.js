/**
 * Lead Journey Timeline - Site Visit Tracking
 */
(function() {
    'use strict';
    
    // Configuration
    const TRACKING_ENDPOINT = '/index.php?module=LeadJourney&action=trackVisit';
    const SESSION_KEY = 'leadJourneySession';
    
    // Get or create session
    function getSession() {
        let session = sessionStorage.getItem(SESSION_KEY);
        if (!session) {
            session = {
                id: generateSessionId(),
                startTime: Date.now(),
                pageViews: []
            };
            sessionStorage.setItem(SESSION_KEY, JSON.stringify(session));
        } else {
            session = JSON.parse(session);
        }
        return session;
    }
    
    // Generate unique session ID
    function generateSessionId() {
        return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    // Track page view
    function trackPageView() {
        const session = getSession();
        const pageView = {
            url: window.location.href,
            title: document.title,
            timestamp: Date.now(),
            referrer: document.referrer
        };
        
        session.pageViews.push(pageView);
        sessionStorage.setItem(SESSION_KEY, JSON.stringify(session));
        
        // Send to server
        sendToServer(pageView);
    }
    
    // Send tracking data to server
    function sendToServer(data) {
        // Get lead/contact ID from URL or cookie
        const recordId = getRecordId();
        const parentType = getParentType();
        
        if (!recordId || !parentType) {
            return; // Not tracking non-lead/contact pages
        }
        
        fetch(TRACKING_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                parent_type: parentType,
                parent_id: recordId,
                touchpoint_type: 'site_visit',
                data: data
            })
        }).catch(error => {
            console.error('Failed to track page view:', error);
        });
    }
    
    // Get record ID from URL or cookie
    function getRecordId() {
        // Check URL
        const match = window.location.search.match(/record=([^&]+)/);
        if (match) return match[1];
        
        // Check cookie
        const cookie = document.cookie.split('; ').find(row => row.startsWith('lead_id='));
        if (cookie) return cookie.split('=')[1];
        
        return null;
    }
    
    // Get parent type from URL
    function getParentType() {
        const match = window.location.search.match(/module=([^&]+)/);
        if (match && (match[1] === 'Leads' || match[1] === 'Contacts')) {
            return match[1];
        }
        return null;
    }
    
    // Track time on page
    let pageStartTime = Date.now();
    window.addEventListener('beforeunload', function() {
        const duration = Math.floor((Date.now() - pageStartTime) / 1000);
        if (duration > 5) { // Only track if stayed more than 5 seconds
            sendToServer({
                event: 'page_exit',
                duration: duration
            });
        }
    });
    
    // Initialize tracking
    trackPageView();
    
})();
