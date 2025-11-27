/**
 * Twilio Click-to-Call Integration for SuiteCRM
 * Adds call and SMS buttons next to all phone numbers throughout the CRM
 */
(function() {
    'use strict';
    
    // Configuration
    var CONFIG = {
        moduleUrl: 'index.php?module=TwilioIntegration',
        callAction: '&action=makecall&phone=',
        smsAction: '&action=sendsms&phone=',
        processedAttr: 'data-twilio-processed'
    };
    
    // Initialize
    function init() {
        console.log('[Twilio Click-to-Call] Initializing...');
        processPage();
        observeChanges();
    }
    
    // Process all phone fields on the page
    function processPage() {
        processInputFields();
        processDetailFields();
        processListViewCells();
        processTelLinks();
        processSpanFields();
    }
    
    // Process phone input fields
    function processInputFields() {
        var inputs = document.querySelectorAll('input');
        inputs.forEach(function(input) {
            var name = (input.name || '').toLowerCase();
            var id = (input.id || '').toLowerCase();
            var type = input.type || '';
            
            if (!input.getAttribute(CONFIG.processedAttr)) {
                var isPhoneField = name.indexOf('phone') >= 0 || 
                                   name.indexOf('mobile') >= 0 || 
                                   name.indexOf('fax') >= 0 ||
                                   id.indexOf('phone') >= 0 || 
                                   id.indexOf('mobile') >= 0 ||
                                   type === 'tel';
                
                if (isPhoneField && input.value && isValidPhone(input.value)) {
                    addButtons(input, input.value, 'afterend');
                    input.setAttribute(CONFIG.processedAttr, 'true');
                }
            }
        });
    }
    
    // Process SuiteCRM span fields in detail views
    function processSpanFields() {
        // Target SuiteCRM detail view phone field spans
        var selectors = [
            'span[field="phone_work"]',
            'span[field="phone_mobile"]',
            'span[field="phone_office"]',
            'span[field="phone_home"]',
            'span[field="phone_other"]',
            'span[field="phone_fax"]',
            'span[data-field="phone_work"]',
            'span[data-field="phone_mobile"]',
            'span[data-field="phone_office"]'
        ];
        
        selectors.forEach(function(selector) {
            document.querySelectorAll(selector).forEach(function(elem) {
                if (!elem.getAttribute(CONFIG.processedAttr)) {
                    var phone = elem.textContent.trim();
                    if (phone && isValidPhone(phone)) {
                        addButtons(elem, phone, 'afterend');
                        elem.setAttribute(CONFIG.processedAttr, 'true');
                    }
                }
            });
        });
    }
    
    // Process detail view display fields
    function processDetailFields() {
        var elements = document.querySelectorAll('span, div, td');
        elements.forEach(function(elem) {
            if (elem.getAttribute(CONFIG.processedAttr)) return;
            if (elem.querySelectorAll('.twilio-buttons').length > 0) return;
            
            var id = (elem.id || '').toLowerCase();
            var className = (elem.className || '').toLowerCase();
            
            var isPhoneField = id.indexOf('phone') >= 0 || 
                               id.indexOf('mobile') >= 0 ||
                               className.indexOf('phone') >= 0;
            
            if (isPhoneField && elem.childNodes.length <= 2) {
                var phone = elem.textContent.trim();
                if (phone && isValidPhone(phone)) {
                    addButtons(elem, phone, 'beforeend');
                    elem.setAttribute(CONFIG.processedAttr, 'true');
                }
            }
        });
    }
    
    // Process list view table cells
    function processListViewCells() {
        var phoneColumnIndexes = [];
        var headers = document.querySelectorAll('th, th a');
        headers.forEach(function(th, index) {
            var text = (th.textContent || '').toLowerCase();
            if (text.indexOf('phone') >= 0 || text.indexOf('mobile') >= 0 || text.indexOf('fax') >= 0) {
                var thElem = th.tagName === 'TH' ? th : th.closest('th');
                if (thElem) {
                    var thIndex = Array.from(thElem.parentNode.children).indexOf(thElem);
                    if (phoneColumnIndexes.indexOf(thIndex) === -1) {
                        phoneColumnIndexes.push(thIndex);
                    }
                }
            }
        });
        
        if (phoneColumnIndexes.length > 0) {
            var rows = document.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                var cells = row.querySelectorAll('td');
                phoneColumnIndexes.forEach(function(colIndex) {
                    var cell = cells[colIndex];
                    if (cell && !cell.getAttribute(CONFIG.processedAttr)) {
                        var phone = cell.textContent.trim();
                        if (phone && isValidPhone(phone)) {
                            addButtons(cell, phone, 'beforeend');
                            cell.setAttribute(CONFIG.processedAttr, 'true');
                        }
                    }
                });
            });
        }
    }
    
    // Process tel: links
    function processTelLinks() {
        var links = document.querySelectorAll('a');
        links.forEach(function(link) {
            if (link.href && link.href.indexOf('tel:') === 0 && !link.getAttribute(CONFIG.processedAttr)) {
                var phone = link.href.replace('tel:', '').trim();
                if (phone && isValidPhone(phone)) {
                    addButtons(link, phone, 'afterend');
                    link.setAttribute(CONFIG.processedAttr, 'true');
                }
            }
        });
    }
    
    // Validate phone number
    function isValidPhone(str) {
        if (!str || typeof str !== 'string') return false;
        var cleaned = str.replace(/[\s\-().]/g, '');
        return cleaned.length >= 7 && cleaned.length <= 20 && /^\+?\d+$/.test(cleaned);
    }
    
    // Add call and SMS buttons
    function addButtons(element, phone, position) {
        var container = document.createElement('span');
        container.className = 'twilio-buttons';
        container.style.cssText = 'display:inline-flex;gap:3px;margin-left:8px;vertical-align:middle;';
        
        // Call button
        var callBtn = document.createElement('button');
        callBtn.type = 'button';
        callBtn.className = 'btn btn-xs btn-success twilio-call-btn';
        callBtn.title = 'Call ' + phone;
        callBtn.innerHTML = '&#x1F4DE; Call';
        callBtn.style.cssText = 'cursor:pointer;padding:2px 8px;font-size:11px;';
        callBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            openWindow(CONFIG.moduleUrl + CONFIG.callAction + encodeURIComponent(phone), 'TwilioCall', 500, 400);
        };
        
        // SMS button  
        var smsBtn = document.createElement('button');
        smsBtn.type = 'button';
        smsBtn.className = 'btn btn-xs btn-primary twilio-sms-btn';
        smsBtn.title = 'SMS ' + phone;
        smsBtn.innerHTML = '&#x1F4AC; SMS';
        smsBtn.style.cssText = 'cursor:pointer;padding:2px 8px;font-size:11px;';
        smsBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            openWindow(CONFIG.moduleUrl + CONFIG.smsAction + encodeURIComponent(phone), 'TwilioSMS', 500, 500);
        };
        
        container.appendChild(callBtn);
        container.appendChild(smsBtn);
        
        if (position === 'beforeend') {
            element.appendChild(container);
        } else {
            element.insertAdjacentElement(position, container);
        }
    }
    
    // Open popup window
    function openWindow(url, name, width, height) {
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;
        window.open(url, name, 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',scrollbars=yes,resizable=yes');
    }
    
    // Watch for DOM changes (AJAX content)
    function observeChanges() {
        if (typeof MutationObserver === 'undefined') return;
        
        var debounceTimer;
        var observer = new MutationObserver(function(mutations) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(processPage, 200);
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Also run after delays to catch late-loading content
    setTimeout(processPage, 1000);
    setTimeout(processPage, 3000);
})();
