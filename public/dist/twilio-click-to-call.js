/**
 * Twilio Click-to-Call for SuiteCRM 8 Angular UI
 * Adds call and SMS buttons next to phone numbers
 * v2.4.0 - Fixed for SuiteCRM 8 phone field components
 */
(function() {
    "use strict";
    
    // Prevent multiple initializations
    if (window.TWILIO_CTC_INIT) return;
    window.TWILIO_CTC_INIT = true;
    
    var CONFIG = {
        callUrl: "legacy/index.php?module=TwilioIntegration&action=makecall&phone=",
        smsUrl: "legacy/index.php?module=TwilioIntegration&action=sendsms&phone="
    };
    
    console.log("[Twilio CTC] Script loaded v2.4.0");
    
    function scanForPhoneNumbers() {
        console.log("[Twilio CTC] Scanning for phone numbers...");
        var found = 0;
        
        // Method 1: SuiteCRM 8 phone field components (PRIMARY)
        document.querySelectorAll("scrm-phone-detail a[href^='tel:']").forEach(function(link) {
            if (processPhoneLink(link)) found++;
        });
        
        // Method 2: Any tel: links anywhere
        document.querySelectorAll("a[href^='tel:']").forEach(function(link) {
            if (processPhoneLink(link)) found++;
        });
        
        // Method 3: Links in table cells
        document.querySelectorAll("table tbody td a").forEach(function(link) {
            if (processPhoneLink(link)) found++;
        });
        
        // Method 4: scrm-field components
        document.querySelectorAll("scrm-field a").forEach(function(link) {
            if (processPhoneLink(link)) found++;
        });
        
        // Method 5: Plain text phone numbers in cells
        document.querySelectorAll("table tbody td").forEach(function(cell) {
            if (cell.querySelector(".twilio-btns")) return;
            if (cell.getAttribute("data-twilio-done")) return;
            if (!cell.querySelector("a")) {
                var text = cell.textContent.trim();
                if (isPhoneNumber(text)) {
                    console.log("[Twilio CTC] Found phone in text:", text);
                    addButtonsToElement(cell, text);
                    found++;
                }
            }
        });
        
        // Method 6: Detail view - look for any element with phone-like content
        document.querySelectorAll("[class*='field'] span, [class*='value'] span, .form-group span").forEach(function(span) {
            if (span.closest(".twilio-btns")) return;
            if (span.parentElement && span.parentElement.querySelector(".twilio-btns")) return;
            var text = span.textContent.trim();
            if (isPhoneNumber(text)) {
                console.log("[Twilio CTC] Found phone in span:", text);
                addButtonsAfterElement(span, text);
                found++;
            }
        });
        
        console.log("[Twilio CTC] Found " + found + " phone numbers");
    }
    
    function processPhoneLink(link) {
        // Skip if already processed
        var parent = link.closest("scrm-phone-detail") || link.closest("td") || link.parentElement;
        if (!parent) return false;
        if (parent.querySelector(".twilio-btns")) return false;
        if (link.getAttribute("data-twilio-done")) return false;
        
        var phone = "";
        
        // Extract phone from tel: href
        if (link.href && link.href.indexOf("tel:") === 0) {
            phone = link.href.replace("tel:", "");
        } else {
            phone = link.textContent.trim();
        }
        
        if (!phone || !isPhoneNumber(phone)) return false;
        
        console.log("[Twilio CTC] Processing phone:", phone);
        addButtonsAfterElement(link, phone);
        link.setAttribute("data-twilio-done", "1");
        return true;
    }
    
    function isPhoneNumber(text) {
        if (!text || typeof text !== "string") return false;
        text = text.trim();
        var digits = text.replace(/\D/g, "");
        if (digits.length < 7 || digits.length > 15) return false;
        
        // Various phone formats
        return /^\+?\d[\d\s\-\.\(\)]{6,18}\d$/.test(text) ||
               /^\(\d{3}\)\s*\d{3}[-.]?\d{4}$/.test(text) ||
               /^\d{3}[-.\s]\d{3}[-.\s]\d{4}$/.test(text) ||
               /^\+\d{1,4}[-.\s]?\(?\d+\)?[-.\s\d]{5,}$/.test(text);
    }
    
    function addButtonsToElement(element, phone) {
        element.appendChild(createButtons(phone));
    }
    
    function addButtonsAfterElement(element, phone) {
        var btns = createButtons(phone);
        if (element.nextSibling) {
            element.parentNode.insertBefore(btns, element.nextSibling);
        } else {
            element.parentNode.appendChild(btns);
        }
    }
    
    function createButtons(phone) {
        var container = document.createElement("span");
        container.className = "twilio-btns";
        container.style.cssText = "display:inline-flex;gap:4px;margin-left:8px;vertical-align:middle;";
        
        var callBtn = document.createElement("button");
        callBtn.type = "button";
        callBtn.title = "Call " + phone;
        callBtn.innerHTML = "&#128222;"; // 📞
        callBtn.style.cssText = "cursor:pointer;padding:2px 6px;background:#28a745;color:#fff;border:none;border-radius:4px;font-size:14px;line-height:1;";
        callBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.open(CONFIG.callUrl + encodeURIComponent(phone), "TwilioCall", "width=500,height=400");
        };
        
        var smsBtn = document.createElement("button");
        smsBtn.type = "button";
        smsBtn.title = "SMS " + phone;
        smsBtn.innerHTML = "&#128172;"; // 💬
        smsBtn.style.cssText = "cursor:pointer;padding:2px 6px;background:#007bff;color:#fff;border:none;border-radius:4px;font-size:14px;line-height:1;";
        smsBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.open(CONFIG.smsUrl + encodeURIComponent(phone), "TwilioSMS", "width=500,height=450");
        };
        
        container.appendChild(callBtn);
        container.appendChild(smsBtn);
        return container;
    }
    
    function startObserver() {
        var timeout = null;
        var observer = new MutationObserver(function() {
            clearTimeout(timeout);
            timeout = setTimeout(scanForPhoneNumbers, 300);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
    
    function init() {
        console.log("[Twilio CTC] Initializing...");
        setTimeout(scanForPhoneNumbers, 1000);
        setTimeout(scanForPhoneNumbers, 2000);
        setTimeout(scanForPhoneNumbers, 4000);
        startObserver();
    }
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
