/**
 * Twilio Click-to-Call for SuiteCRM 8 Angular UI
 * Adds call and SMS buttons next to phone numbers
 */
(function() {
    "use strict";
    
    // Prevent multiple initializations
    if (window.TwilioSuite8Initialized) return;
    window.TwilioSuite8Initialized = true;
    
    var CONFIG = {
        legacyUrl: "legacy/index.php?module=TwilioIntegration",
        callAction: "&action=makecall&phone=",
        smsAction: "&action=sendsms&phone=",
        processedAttr: "data-twilio-processed"
    };
    
    function init() {
        console.log("[Twilio Suite8] Initializing click-to-call...");
        setTimeout(processPage, 1500);
        observeChanges();
    }
    
    function processPage() {
        // First, remove any duplicate buttons
        cleanupDuplicates();
        
        // Process table cells with phone data
        processTableCells();
    }
    
    function cleanupDuplicates() {
        // Remove duplicate button sets - keep only the first one per parent
        document.querySelectorAll("td, span, div").forEach(function(container) {
            var buttons = container.querySelectorAll(".twilio-btn-container");
            if (buttons.length > 1) {
                // Keep only the first set, remove the rest
                for (var i = 1; i < buttons.length; i++) {
                    buttons[i].remove();
                }
            }
        });
    }
    
    function processTableCells() {
        // Find table headers with phone in the name
        var tables = document.querySelectorAll("table");
        tables.forEach(function(table) {
            var phoneColIndexes = [];
            var headers = table.querySelectorAll("th");
            
            headers.forEach(function(th, index) {
                var text = (th.textContent || "").toLowerCase();
                if (text.indexOf("phone") >= 0 || text.indexOf("mobile") >= 0) {
                    phoneColIndexes.push(index);
                }
            });
            
            if (phoneColIndexes.length > 0) {
                var rows = table.querySelectorAll("tbody tr");
                rows.forEach(function(row) {
                    var cells = row.querySelectorAll("td");
                    phoneColIndexes.forEach(function(colIndex) {
                        var cell = cells[colIndex];
                        if (cell && !cell.getAttribute(CONFIG.processedAttr)) {
                            // Get the phone number - look for a link or direct text
                            var phoneLink = cell.querySelector("a");
                            var phone = null;
                            
                            if (phoneLink && phoneLink.textContent) {
                                phone = phoneLink.textContent.trim();
                            } else {
                                // Get text content but exclude any existing buttons
                                var clone = cell.cloneNode(true);
                                var existingBtns = clone.querySelectorAll(".twilio-btn-container, .twilio-btn");
                                existingBtns.forEach(function(b) { b.remove(); });
                                phone = clone.textContent.trim();
                            }
                            
                            if (phone && isValidPhone(phone)) {
                                // Check if buttons already exist
                                if (!cell.querySelector(".twilio-btn-container")) {
                                    addButtons(cell, phone);
                                    cell.setAttribute(CONFIG.processedAttr, "true");
                                }
                            }
                        }
                    });
                });
            }
        });
    }
    
    function isValidPhone(str) {
        if (!str || typeof str !== "string") return false;
        var cleaned = str.replace(/[\s\-().]/g, "");
        return cleaned.length >= 7 && cleaned.length <= 20 && /^\+?\d+$/.test(cleaned);
    }
    
    function addButtons(element, phone) {
        // Double check no buttons exist
        if (element.querySelector(".twilio-btn-container")) return;
        
        // Create container for buttons
        var container = document.createElement("span");
        container.className = "twilio-btn-container";
        container.style.cssText = "display:inline-flex;gap:3px;margin-left:8px;vertical-align:middle;white-space:nowrap;";
        
        // Create call button
        var callBtn = document.createElement("button");
        callBtn.type = "button";
        callBtn.className = "twilio-btn twilio-call-btn";
        callBtn.title = "Call " + phone;
        callBtn.innerHTML = "📞";
        callBtn.style.cssText = "cursor:pointer;padding:4px 8px;background:#4a90d9;color:#fff;border:none;border-radius:4px;font-size:14px;";
        callBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            openWindow(CONFIG.legacyUrl + CONFIG.callAction + encodeURIComponent(phone), "TwilioCall", 500, 400);
        };
        
        // Create SMS button
        var smsBtn = document.createElement("button");
        smsBtn.type = "button";
        smsBtn.className = "twilio-btn twilio-sms-btn";
        smsBtn.title = "SMS " + phone;
        smsBtn.innerHTML = "💬";
        smsBtn.style.cssText = "cursor:pointer;padding:4px 8px;background:#4a90d9;color:#fff;border:none;border-radius:4px;font-size:14px;";
        smsBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            openWindow(CONFIG.legacyUrl + CONFIG.smsAction + encodeURIComponent(phone), "TwilioSMS", 500, 500);
        };
        
        container.appendChild(callBtn);
        container.appendChild(smsBtn);
        element.appendChild(container);
    }
    
    function openWindow(url, name, width, height) {
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;
        window.open(url, name, "width=" + width + ",height=" + height + ",left=" + left + ",top=" + top + ",scrollbars=yes,resizable=yes");
    }
    
    function observeChanges() {
        if (typeof MutationObserver === "undefined") return;
        
        var debounceTimer;
        var observer = new MutationObserver(function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(processPage, 800);
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Initialize
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        setTimeout(init, 800);
    }
})();
