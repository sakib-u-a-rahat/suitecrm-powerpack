<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class TwilioHooks {
    
    /**
     * Inject Click-to-Call JavaScript into DetailView pages
     */
    public static function injectClickToCallJS() {
        global $sugar_config;
        
        // Only inject on DetailView pages for Contacts and Leads
        $module = $_REQUEST['module'] ?? '';
        $action = $_REQUEST['action'] ?? '';
        
        if (($module === 'Contacts' || $module === 'Leads') && $action === 'DetailView') {
            // Check if Twilio is configured and enabled
            $twilioEnabled = $sugar_config['twilio_enable_click_to_call'] ?? false;
            
            if ($twilioEnabled) {
                echo '<script src="modules/TwilioIntegration/click-to-call.js"></script>';
            }
        }
    }
}
