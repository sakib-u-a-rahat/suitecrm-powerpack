<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/MVC/Controller/SugarController.php');

class TwilioIntegrationController extends SugarController {
    
    public function __construct() {
        parent::__construct();
        // Register custom actions
        $this->action_remap['makecall'] = 'makecall';
        $this->action_remap['sendsms'] = 'sendsms';
        $this->action_remap['webhook'] = 'webhook';
        $this->action_remap['sms_webhook'] = 'sms_webhook';
        $this->action_remap['twiml'] = 'twiml';
        $this->action_remap['config'] = 'config';
    }
    
    public function action_makecall() {
        $this->view = 'makecall';
    }
    
    public function action_sendsms() {
        $this->view = 'sendsms';
    }
    
    public function action_webhook() {
        $this->view = 'webhook';
    }
    
    public function action_sms_webhook() {
        $this->view = 'sms_webhook';
    }
    
    public function action_twiml() {
        $this->view = 'twiml';
    }
    
    public function action_config() {
        $this->view = 'config';
    }
}
