<?php

class TwilioClient {
    private $accountSid;
    private $authToken;
    private $phoneNumber;
    
    public function __construct() {
        $config = TwilioIntegration::getConfig();
        $this->accountSid = $config['account_sid'];
        $this->authToken = $config['auth_token'];
        $this->phoneNumber = $config['phone_number'];
    }
    
    /**
     * Initiate a call via Twilio API
     */
    public function initiateCall($to, $from = null, $recordingEnabled = true) {
        if (!$from) {
            $from = $this->phoneNumber;
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Calls.json";
        
        $data = array(
            'From' => $from,
            'To' => $to,
            'Url' => $this->getTwiMLUrl(),
            'Record' => $recordingEnabled ? 'true' : 'false',
            'StatusCallback' => $this->getWebhookUrl(),
            'StatusCallbackEvent' => array('initiated', 'ringing', 'answered', 'completed'),
        );
        
        return $this->makeRequest($url, $data);
    }
    
    /**
     * Get call details
     */
    public function getCallDetails($callSid) {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Calls/{$callSid}.json";
        return $this->makeRequest($url, null, 'GET');
    }
    
    /**
     * Get call recordings
     */
    public function getRecordings($callSid) {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Recordings.json?CallSid={$callSid}";
        return $this->makeRequest($url, null, 'GET');
    }
    
    /**
     * Download recording
     */
    public function downloadRecording($recordingSid, $savePath) {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Recordings/{$recordingSid}.mp3";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->accountSid . ':' . $this->authToken);
        
        $recording = curl_exec($ch);
        curl_close($ch);
        
        file_put_contents($savePath, $recording);
        return $savePath;
    }
    
    /**
     * Make HTTP request to Twilio API
     */
    private function makeRequest($url, $data = null, $method = 'POST') {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->accountSid . ':' . $this->authToken);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Get TwiML URL for call handling
     */
    private function getTwiMLUrl() {
        global $sugar_config;
        $siteUrl = $sugar_config['site_url'];
        return $siteUrl . '/index.php?module=TwilioIntegration&action=twiml';
    }
    
    /**
     * Get webhook URL for call status updates
     */
    private function getWebhookUrl() {
        global $sugar_config;
        $siteUrl = $sugar_config['site_url'];
        return $siteUrl . '/index.php?module=TwilioIntegration&action=webhook';
    }
}
