<?php
/**
 * Twilio Configuration View
 * Admin interface for configuring Twilio integration
 */

require_once("include/MVC/View/SugarView.php");

class TwilioIntegrationViewConfig extends SugarView {
    
    private $configSaved = false;
    private $testResult = null;
    
    public function display() {
        global $current_user, $sugar_config;
        
        // Check admin access
        if (!is_admin($current_user)) {
            echo "<div style=\"padding:20px;color:red;\">Access denied. Admin privileges required.</div>";
            return;
        }
        
        // Handle POST - save config
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (isset($_POST["save_config"])) {
                $this->saveConfig();
            } elseif (isset($_POST["test_connection"])) {
                $this->testConnection();
            }
        }
        
        // Display config form
        $this->displayConfigForm();
    }
    
    private function saveConfig() {
        global $sugar_config;
        
        $accountSid = isset($_POST["twilio_account_sid"]) ? trim($_POST["twilio_account_sid"]) : "";
        $authToken = isset($_POST["twilio_auth_token"]) ? trim($_POST["twilio_auth_token"]) : "";
        $phoneNumber = isset($_POST["twilio_phone_number"]) ? trim($_POST["twilio_phone_number"]) : "";
        $fallbackPhone = isset($_POST["twilio_fallback_phone"]) ? trim($_POST["twilio_fallback_phone"]) : "";
        $autoCreateLead = isset($_POST["twilio_auto_create_lead"]) ? 1 : 0;
        
        // Read existing config_override
        $override_file = "config_override.php";
        $existing_config = "";
        if (file_exists($override_file)) {
            $existing_config = file_get_contents($override_file);
            // Remove PHP tag and existing Twilio entries
            $existing_config = preg_replace("/<\?php\s*/", "", $existing_config);
            $existing_config = preg_replace("/\\$sugar_config\[.twilio_[^'\"]+.\]\s*=\s*[^;]+;\s*/", "", $existing_config);
        }
        
        // Build new config
        $config_content = "<?php\n\n";
        $config_content .= "// Twilio Configuration\n";
        $config_content .= "\$sugar_config['twilio_account_sid'] = '" . addslashes($accountSid) . "';\n";
        $config_content .= "\$sugar_config['twilio_auth_token'] = '" . addslashes($authToken) . "';\n";
        $config_content .= "\$sugar_config['twilio_phone_number'] = '" . addslashes($phoneNumber) . "';\n";
        $config_content .= "\$sugar_config['twilio_fallback_phone'] = '" . addslashes($fallbackPhone) . "';\n";
        $config_content .= "\$sugar_config['twilio_auto_create_lead'] = " . $autoCreateLead . ";\n\n";
        
        // Append existing non-Twilio config
        if (!empty(trim($existing_config))) {
            $config_content .= $existing_config;
        }
        
        file_put_contents($override_file, $config_content);
        
        // Update runtime config
        $sugar_config['twilio_account_sid'] = $accountSid;
        $sugar_config['twilio_auth_token'] = $authToken;
        $sugar_config['twilio_phone_number'] = $phoneNumber;
        $sugar_config['twilio_fallback_phone'] = $fallbackPhone;
        $sugar_config['twilio_auto_create_lead'] = $autoCreateLead;
        
        $this->configSaved = true;
    }
    
    private function testConnection() {
        global $sugar_config;
        
        $accountSid = $sugar_config['twilio_account_sid'] ?? '';
        $authToken = $sugar_config['twilio_auth_token'] ?? '';
        
        // Also check environment variables
        if (empty($accountSid)) {
            $accountSid = getenv('TWILIO_ACCOUNT_SID');
        }
        if (empty($authToken)) {
            $authToken = getenv('TWILIO_AUTH_TOKEN');
        }
        
        if (empty($accountSid) || empty($authToken)) {
            $this->testResult = ['success' => false, 'message' => 'Account SID and Auth Token are required'];
            return;
        }
        
        // Test API connection
        $url = "https://api.twilio.com/2010-04-01/Accounts/$accountSid.json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!empty($error)) {
            $this->testResult = ['success' => false, 'message' => "cURL Error: $error"];
            return;
        }
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $friendlyName = $data['friendly_name'] ?? 'Unknown';
            $status = $data['status'] ?? 'Unknown';
            $this->testResult = ['success' => true, 'message' => "Connected! Account: $friendlyName, Status: $status"];
        } elseif ($httpCode === 401) {
            $this->testResult = ['success' => false, 'message' => 'Authentication failed. Check your Account SID and Auth Token.'];
        } else {
            $this->testResult = ['success' => false, 'message' => "API Error (HTTP $httpCode): $response"];
        }
    }
    
    private function displayConfigForm() {
        global $sugar_config;
        
        // Get values from config or env vars
        $accountSid = $sugar_config['twilio_account_sid'] ?? getenv('TWILIO_ACCOUNT_SID') ?: '';
        $authToken = $sugar_config['twilio_auth_token'] ?? getenv('TWILIO_AUTH_TOKEN') ?: '';
        $phoneNumber = $sugar_config['twilio_phone_number'] ?? getenv('TWILIO_PHONE_NUMBER') ?: '';
        $fallbackPhone = $sugar_config['twilio_fallback_phone'] ?? '';
        $autoCreateLead = $sugar_config['twilio_auto_create_lead'] ?? 0;
        
        // Escape for HTML
        $accountSid = htmlspecialchars($accountSid);
        $authToken = htmlspecialchars($authToken);
        $phoneNumber = htmlspecialchars($phoneNumber);
        $fallbackPhone = htmlspecialchars($fallbackPhone);
        $autoCreateChecked = $autoCreateLead ? 'checked' : '';
        
        // Generate webhook URLs
        $siteUrl = $sugar_config['site_url'] ?? '';
        $voiceWebhook = $siteUrl . '/index.php?module=TwilioIntegration&action=webhook&webhook_action=voice';
        $voiceStatus = $siteUrl . '/index.php?module=TwilioIntegration&action=webhook&webhook_action=status';
        $voiceFallback = $siteUrl . '/index.php?module=TwilioIntegration&action=webhook&webhook_action=fallback';
        $smsWebhook = $siteUrl . '/index.php?module=TwilioIntegration&action=sms_webhook&sms_action=inbound';
        $smsStatus = $siteUrl . '/index.php?module=TwilioIntegration&action=sms_webhook&sms_action=status';
        
        // Messages
        $savedMessage = '';
        if ($this->configSaved) {
            $savedMessage = '<div class="alert alert-success">✅ Configuration saved successfully!</div>';
        }
        
        $testMessage = '';
        if ($this->testResult) {
            $class = $this->testResult['success'] ? 'alert-success' : 'alert-danger';
            $icon = $this->testResult['success'] ? '✅' : '❌';
            $testMessage = '<div class="alert ' . $class . '">' . $icon . ' ' . htmlspecialchars($this->testResult['message']) . '</div>';
        }
        
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Twilio Configuration</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; background: #1a1a2e; margin: 0; color: #e0e0e0; }
        .config-container { max-width: 900px; margin: 0 auto; }
        .card { background: #16213e; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        h1 { margin: 0 0 5px 0; color: #fff; font-size: 24px; }
        .subtitle { color: #8b8b8b; margin-bottom: 20px; }
        h3 { margin: 0 0 15px 0; color: #4ecca3; font-size: 16px; border-bottom: 1px solid #2a3f5f; padding-bottom: 10px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: 500; margin-bottom: 6px; color: #b0b0b0; }
        input[type="text"], input[type="password"] { 
            width: 100%; padding: 12px; border: 1px solid #2a3f5f; border-radius: 6px; 
            font-size: 14px; background: #0f0f23; color: #fff;
        }
        input[type="text"]:focus, input[type="password"]:focus { border-color: #4ecca3; outline: none; }
        .help-text { font-size: 12px; color: #666; margin-top: 5px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; }
        .btn { 
            display: inline-block; padding: 12px 24px; font-size: 14px; border: none; 
            border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;
        }
        .btn-primary { background: #4ecca3; color: #1a1a2e; }
        .btn-primary:hover { background: #3db892; }
        .btn-secondary { background: #2a3f5f; color: #fff; margin-left: 10px; }
        .btn-secondary:hover { background: #3a5080; }
        .btn-test { background: #f39c12; color: #1a1a2e; margin-left: 10px; }
        .btn-test:hover { background: #e67e22; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: rgba(78, 204, 163, 0.2); color: #4ecca3; border: 1px solid #4ecca3; }
        .alert-danger { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        .webhook-box { background: #0f0f23; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .webhook-label { font-size: 12px; color: #4ecca3; margin-bottom: 5px; font-weight: 500; }
        .webhook-url { 
            font-family: monospace; font-size: 12px; color: #8b8b8b; word-break: break-all;
            background: #1a1a2e; padding: 8px; border-radius: 4px; display: block;
        }
        .copy-btn { 
            font-size: 11px; padding: 4px 8px; background: #2a3f5f; color: #fff; 
            border: none; border-radius: 4px; cursor: pointer; margin-top: 5px;
        }
        .copy-btn:hover { background: #3a5080; }
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }
        .env-note { background: rgba(241, 196, 15, 0.1); border: 1px solid #f1c40f; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .env-note strong { color: #f1c40f; }
        .status-indicator { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 8px; }
        .status-ok { background: #4ecca3; }
        .status-error { background: #e74c3c; }
        .status-warning { background: #f39c12; }
    </style>
</head>
<body>
    <div class="config-container">
        <div class="card">
            <h1>📞 Twilio Integration Settings</h1>
            <p class="subtitle">Configure your Twilio account for click-to-call, SMS, and inbound call handling.</p>
            
            {$savedMessage}
            {$testMessage}
            
            <div class="env-note">
                <strong>💡 Environment Variables:</strong> You can also set credentials via environment variables: 
                <code>TWILIO_ACCOUNT_SID</code>, <code>TWILIO_AUTH_TOKEN</code>, <code>TWILIO_PHONE_NUMBER</code>
            </div>
            
            <form method="POST">
                <h3>API Credentials</h3>
                
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Account SID</label>
                            <input type="text" name="twilio_account_sid" value="{$accountSid}" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            <div class="help-text">Found on your Twilio Console Dashboard</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Auth Token</label>
                            <input type="password" name="twilio_auth_token" value="{$authToken}" placeholder="Your auth token">
                            <div class="help-text">Found on your Twilio Console Dashboard</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Twilio Phone Number</label>
                            <input type="text" name="twilio_phone_number" value="{$phoneNumber}" placeholder="+1234567890">
                            <div class="help-text">Your Twilio phone number for outbound calls and SMS</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Fallback Phone Number</label>
                            <input type="text" name="twilio_fallback_phone" value="{$fallbackPhone}" placeholder="+1234567890">
                            <div class="help-text">Number to forward calls when no BDM assigned</div>
                        </div>
                    </div>
                </div>
                
                <h3>Automation Settings</h3>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="twilio_auto_create_lead" id="auto_create" {$autoCreateChecked}>
                    <label for="auto_create" style="margin: 0;">Auto-create leads from unknown SMS numbers</label>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="save_config" value="1" class="btn btn-primary">💾 Save Configuration</button>
                    <button type="submit" name="test_connection" value="1" class="btn btn-test">🔌 Test Connection</button>
                    <a href="index.php?module=TwilioIntegration&action=index" class="btn btn-secondary">← Back</a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h3>📡 Webhook URLs (Configure in Twilio Console)</h3>
            <p style="color: #8b8b8b; margin-bottom: 20px; font-size: 13px;">
                Copy these URLs to your Twilio Console → Phone Numbers → Configure for your phone number.
            </p>
            
            <div class="row">
                <div class="col">
                    <div class="webhook-box">
                        <div class="webhook-label">Voice Webhook (When a call comes in)</div>
                        <code class="webhook-url" id="voice-webhook">{$voiceWebhook}</code>
                        <button type="button" class="copy-btn" onclick="copyToClipboard('voice-webhook')">📋 Copy</button>
                    </div>
                    
                    <div class="webhook-box">
                        <div class="webhook-label">Voice Status Callback</div>
                        <code class="webhook-url" id="voice-status">{$voiceStatus}</code>
                        <button type="button" class="copy-btn" onclick="copyToClipboard('voice-status')">📋 Copy</button>
                    </div>
                    
                    <div class="webhook-box">
                        <div class="webhook-label">Voice Fallback URL</div>
                        <code class="webhook-url" id="voice-fallback">{$voiceFallback}</code>
                        <button type="button" class="copy-btn" onclick="copyToClipboard('voice-fallback')">📋 Copy</button>
                    </div>
                </div>
                <div class="col">
                    <div class="webhook-box">
                        <div class="webhook-label">SMS Webhook (When a message comes in)</div>
                        <code class="webhook-url" id="sms-webhook">{$smsWebhook}</code>
                        <button type="button" class="copy-btn" onclick="copyToClipboard('sms-webhook')">📋 Copy</button>
                    </div>
                    
                    <div class="webhook-box">
                        <div class="webhook-label">SMS Status Callback</div>
                        <code class="webhook-url" id="sms-status">{$smsStatus}</code>
                        <button type="button" class="copy-btn" onclick="copyToClipboard('sms-status')">📋 Copy</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>📊 Quick Status Check</h3>
            <div id="status-check">
                <p><span class="status-indicator status-warning"></span> Click "Test Connection" to verify your Twilio credentials</p>
            </div>
        </div>
    </div>
    
    <script>
    function copyToClipboard(elementId) {
        const text = document.getElementById(elementId).textContent;
        navigator.clipboard.writeText(text).then(function() {
            alert('Copied to clipboard!');
        }).catch(function(err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Copied to clipboard!');
        });
    }
    </script>
</body>
</html>
HTML;
    }
}
