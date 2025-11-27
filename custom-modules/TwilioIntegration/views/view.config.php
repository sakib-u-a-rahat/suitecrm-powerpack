<?php
require_once("include/MVC/View/SugarView.php");

class TwilioIntegrationViewConfig extends SugarView {
    
    public function display() {
        global $current_user, $sugar_config;
        
        // Check admin access
        if (!is_admin($current_user)) {
            echo "<div style=\"padding:20px;color:red;\">Access denied. Admin privileges required.</div>";
            return;
        }
        
        // Handle POST - save config
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_config"])) {
            $this->saveConfig();
        }
        
        // Display config form
        $this->displayConfigForm();
    }
    
    private function saveConfig() {
        global $sugar_config;
        
        $accountSid = isset($_POST["twilio_account_sid"]) ? trim($_POST["twilio_account_sid"]) : "";
        $authToken = isset($_POST["twilio_auth_token"]) ? trim($_POST["twilio_auth_token"]) : "";
        $phoneNumber = isset($_POST["twilio_phone_number"]) ? trim($_POST["twilio_phone_number"]) : "";
        
        // Update config_override.php
        $config_override = array(
            "twilio_account_sid" => $accountSid,
            "twilio_auth_token" => $authToken,
            "twilio_phone_number" => $phoneNumber,
        );
        
        // Read existing config_override
        $override_file = "config_override.php";
        if (file_exists($override_file)) {
            include($override_file);
        }
        
        // Merge with new values
        foreach ($config_override as $key => $value) {
            $sugar_config[$key] = $value;
        }
        
        // Write config_override.php
        $config_content = "<?php\n\n";
        $config_content .= "\$sugar_config[\"twilio_account_sid\"] = \"" . addslashes($accountSid) . "\";\n";
        $config_content .= "\$sugar_config[\"twilio_auth_token\"] = \"" . addslashes($authToken) . "\";\n";
        $config_content .= "\$sugar_config[\"twilio_phone_number\"] = \"" . addslashes($phoneNumber) . "\";\n";
        
        // Preserve existing config_override entries
        if (file_exists($override_file)) {
            $existing = file_get_contents($override_file);
            // Remove old Twilio entries if they exist
            $existing = preg_replace("/\\$sugar_config\[\"twilio_[^\"]+\"\]\s*=\s*[^;]+;\n?/", "", $existing);
            $existing = preg_replace("/<\?php\n*/", "", $existing);
            $config_content .= $existing;
        }
        
        file_put_contents($override_file, $config_content);
        
        $this->configSaved = true;
    }
    
    private function displayConfigForm() {
        global $sugar_config;
        
        $accountSid = isset($sugar_config["twilio_account_sid"]) ? htmlspecialchars($sugar_config["twilio_account_sid"]) : "";
        $authToken = isset($sugar_config["twilio_auth_token"]) ? htmlspecialchars($sugar_config["twilio_auth_token"]) : "";
        $phoneNumber = isset($sugar_config["twilio_phone_number"]) ? htmlspecialchars($sugar_config["twilio_phone_number"]) : "";
        
        $savedMessage = "";
        if (isset($this->configSaved) && $this->configSaved) {
            $savedMessage = "<div class=\"alert alert-success\">Configuration saved successfully!</div>";
        }
        
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Twilio Configuration</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; margin: 0; }
        .config-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin: 0 0 10px 0; color: #333; }
        .subtitle { color: #666; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        .help-text { font-size: 12px; color: #888; margin-top: 5px; }
        .btn { display: inline-block; padding: 12px 30px; font-size: 14px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; color: white; margin-left: 10px; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .info-box { background: #e7f3ff; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #007bff; }
        .info-box h4 { margin: 0 0 10px 0; color: #0056b3; }
        .info-box p { margin: 0; font-size: 13px; color: #555; }
    </style>
</head>
<body>
    <div class="config-container">
        <h2>🔧 Twilio Configuration</h2>
        <p class="subtitle">Configure your Twilio account settings for click-to-call and SMS functionality.</p>
        
        {$savedMessage}
        
        <div class="info-box">
            <h4>How to get your Twilio credentials:</h4>
            <p>1. Sign up at <a href="https://www.twilio.com" target="_blank">twilio.com</a><br>
            2. Go to your Console Dashboard<br>
            3. Copy your Account SID and Auth Token<br>
            4. Get a Twilio phone number from the Phone Numbers section</p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="save_config" value="1">
            
            <div class="form-group">
                <label>Account SID</label>
                <input type="text" name="twilio_account_sid" value="{$accountSid}" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                <div class="help-text">Found on your Twilio Console Dashboard</div>
            </div>
            
            <div class="form-group">
                <label>Auth Token</label>
                <input type="password" name="twilio_auth_token" value="{$authToken}" placeholder="Your auth token">
                <div class="help-text">Found on your Twilio Console Dashboard (click to reveal)</div>
            </div>
            
            <div class="form-group">
                <label>Twilio Phone Number</label>
                <input type="text" name="twilio_phone_number" value="{$phoneNumber}" placeholder="+1234567890">
                <div class="help-text">Your Twilio phone number for outbound calls and SMS</div>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Configuration</button>
            <a href="index.php?module=TwilioIntegration&action=index" class="btn btn-secondary">Back to Module</a>
        </form>
    </div>
</body>
</html>
HTML;
    }
}
