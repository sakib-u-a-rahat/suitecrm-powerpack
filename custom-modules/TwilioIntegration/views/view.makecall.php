<?php
require_once("include/MVC/View/SugarView.php");

class TwilioIntegrationViewMakecall extends SugarView {
    
    public function display() {
        global $current_user, $sugar_config;
        
        $phone = isset($_REQUEST["phone"]) ? htmlspecialchars($_REQUEST["phone"]) : "";
        $from = isset($_REQUEST["from"]) ? htmlspecialchars($_REQUEST["from"]) : "";
        
        // Handle POST - initiate call
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action_type"]) && $_POST["action_type"] === "call") {
            $this->initiateCall();
            return;
        }
        
        // Display call form
        $this->displayCallForm($phone, $from);
    }
    
    private function displayCallForm($phone, $from) {
        global $current_user, $sugar_config;
        
        // Get configured Twilio phone number
        $twilioFrom = isset($sugar_config["twilio_phone_number"]) ? $sugar_config["twilio_phone_number"] : "";
        
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Make Call</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; margin: 0; }
        .call-container { max-width: 400px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin: 0 0 20px 0; color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input[type="tel"], input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        .btn { display: inline-block; padding: 12px 25px; font-size: 16px; border: none; border-radius: 4px; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-call { background: #28a745; color: white; }
        .btn-call:hover { background: #218838; }
        .btn-cancel { background: #6c757d; color: white; }
        .btn-cancel:hover { background: #5a6268; }
        .phone-icon { font-size: 48px; text-align: center; margin-bottom: 15px; }
        .status { padding: 10px; border-radius: 4px; margin-top: 15px; display: none; }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="call-container">
        <div class="phone-icon">📞</div>
        <h2>Make a Call</h2>
        
        <form id="callForm" method="POST">
            <input type="hidden" name="action_type" value="call">
            
            <div class="form-group">
                <label>Call To:</label>
                <input type="tel" name="to" id="toNumber" value="{$phone}" required placeholder="+1234567890">
            </div>
            
            <div class="form-group">
                <label>From (Twilio Number):</label>
                <input type="tel" name="from" id="fromNumber" value="{$twilioFrom}" required placeholder="+1234567890">
            </div>
            
            <button type="submit" class="btn btn-call">📞 Initiate Call</button>
            <button type="button" class="btn btn-cancel" onclick="window.close()">Cancel</button>
        </form>
        
        <div id="status" class="status"></div>
    </div>
    
    <script>
    document.getElementById("callForm").addEventListener("submit", function(e) {
        e.preventDefault();
        var statusDiv = document.getElementById("status");
        statusDiv.className = "status info";
        statusDiv.style.display = "block";
        statusDiv.textContent = "Initiating call...";
        
        var formData = new FormData(this);
        fetch("index.php?module=TwilioIntegration&action=makecall", {
            method: "POST",
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                statusDiv.className = "status success";
                statusDiv.textContent = "Call initiated successfully!";
            } else {
                statusDiv.className = "status error";
                statusDiv.textContent = "Error: " + (data.error || "Failed to initiate call");
            }
        })
        .catch(function(err) {
            statusDiv.className = "status error";
            statusDiv.textContent = "Error: " + err.message;
        });
    });
    </script>
</body>
</html>
HTML;
    }
    
    private function initiateCall() {
        global $sugar_config;
        header("Content-Type: application/json");
        
        $to = isset($_POST["to"]) ? $_POST["to"] : "";
        $from = isset($_POST["from"]) ? $_POST["from"] : "";
        
        if (empty($to) || empty($from)) {
            echo json_encode(["success" => false, "error" => "Both To and From numbers are required"]);
            return;
        }
        
        // Check if Twilio is configured
        $twilioSid = isset($sugar_config["twilio_account_sid"]) ? $sugar_config["twilio_account_sid"] : "";
        $twilioToken = isset($sugar_config["twilio_auth_token"]) ? $sugar_config["twilio_auth_token"] : "";
        
        if (empty($twilioSid) || empty($twilioToken)) {
            echo json_encode(["success" => false, "error" => "Twilio is not configured. Please go to Twilio Integration > Configuration."]);
            return;
        }
        
        // Make the call via Twilio API
        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Calls.json";
            
            $data = array(
                "To" => $to,
                "From" => $from,
                "Url" => "http://demo.twilio.com/docs/voice.xml"  // Default TwiML for demo
            );
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "{$twilioSid}:{$twilioToken}");
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode(["success" => true, "call_sid" => $result["sid"]]);
            } else {
                $errorMsg = isset($result["message"]) ? $result["message"] : "Failed to initiate call";
                echo json_encode(["success" => false, "error" => $errorMsg]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
    }
}
