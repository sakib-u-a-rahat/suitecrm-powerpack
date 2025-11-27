<?php
require_once("include/MVC/View/SugarView.php");

class TwilioIntegrationViewMakecall extends SugarView {
    
    public function display() {
        global $current_user, $sugar_config;
        
        $phone = isset($_REQUEST["phone"]) ? htmlspecialchars($_REQUEST["phone"]) : "";
        $action_type = isset($_REQUEST["action_type"]) ? $_REQUEST["action_type"] : "";
        
        // Handle API calls
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if ($action_type === "call" || (isset($_POST["action_type"]) && $_POST["action_type"] === "call")) {
                $this->initiateCall();
                return;
            }
            if ($action_type === "end_call" || (isset($_POST["action_type"]) && $_POST["action_type"] === "end_call")) {
                $this->endCall();
                return;
            }
            if ($action_type === "call_status" || (isset($_POST["action_type"]) && $_POST["action_type"] === "call_status")) {
                $this->getCallStatus();
                return;
            }
        }
        
        // Display call form
        $this->displayCallUI($phone);
    }
    
    private function displayCallUI($phone) {
        global $sugar_config;
        
        $twilioFrom = isset($sugar_config["twilio_phone_number"]) ? $sugar_config["twilio_phone_number"] : "";
        
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Twilio Call</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 0; margin: 0; background: #1a1a2e; min-height: 100vh; }
        
        .call-container { max-width: 380px; margin: 20px auto; background: #16213e; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        
        /* Dial Screen */
        #dialScreen { text-align: center; }
        .dial-header { color: #eee; font-size: 14px; margin-bottom: 20px; }
        .phone-display { background: #0f3460; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .phone-number { color: #fff; font-size: 24px; font-weight: 600; word-break: break-all; }
        .from-number { color: #888; font-size: 12px; margin-top: 8px; }
        
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { color: #aaa; font-size: 12px; display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 12px 15px; border: none; border-radius: 8px; background: #0f3460; color: #fff; font-size: 16px; }
        .form-group input:focus { outline: 2px solid #4a90d9; }
        
        .btn { padding: 15px 30px; font-size: 16px; border: none; border-radius: 50px; cursor: pointer; width: 100%; margin-top: 10px; font-weight: 600; transition: all 0.2s; }
        .btn-call { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .btn-call:hover { transform: scale(1.02); box-shadow: 0 5px 20px rgba(40,167,69,0.4); }
        .btn-cancel { background: #333; color: #aaa; }
        .btn-cancel:hover { background: #444; }
        
        /* Active Call Screen */
        #callScreen { display: none; text-align: center; }
        .call-status { color: #4a90d9; font-size: 14px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 2px; }
        .call-status.ringing { color: #ffc107; }
        .call-status.connected { color: #28a745; }
        .call-status.ended { color: #dc3545; }
        
        .caller-info { margin: 30px 0; }
        .caller-avatar { width: 100px; height: 100px; background: linear-gradient(135deg, #4a90d9, #357abd); border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 40px; }
        .caller-name { color: #fff; font-size: 24px; font-weight: 600; margin-bottom: 5px; }
        .caller-number { color: #888; font-size: 14px; }
        
        .call-timer { font-size: 48px; color: #fff; font-weight: 300; margin: 30px 0; font-family: 'SF Mono', monospace; }
        
        .call-actions { display: flex; justify-content: center; gap: 20px; margin-top: 30px; }
        .action-btn { width: 70px; height: 70px; border-radius: 50%; border: none; cursor: pointer; font-size: 24px; transition: all 0.2s; }
        .action-btn:hover { transform: scale(1.1); }
        
        .btn-mute { background: #333; color: #fff; }
        .btn-mute.active { background: #4a90d9; }
        .btn-speaker { background: #333; color: #fff; }
        .btn-speaker.active { background: #4a90d9; }
        .btn-end { background: linear-gradient(135deg, #dc3545, #c82333); color: white; width: 80px; height: 80px; font-size: 28px; }
        .btn-end:hover { box-shadow: 0 5px 20px rgba(220,53,69,0.5); }
        
        .call-quality { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .quality-bar { width: 4px; height: 20px; background: #333; border-radius: 2px; }
        .quality-bar.active { background: #28a745; }
        
        /* Status messages */
        .status-msg { padding: 12px; border-radius: 8px; margin-top: 15px; font-size: 14px; }
        .status-msg.error { background: rgba(220,53,69,0.2); color: #ff6b6b; }
        .status-msg.success { background: rgba(40,167,69,0.2); color: #51cf66; }
        .status-msg.info { background: rgba(74,144,217,0.2); color: #74c0fc; }
    </style>
</head>
<body>
    <div class="call-container">
        <!-- Dial Screen -->
        <div id="dialScreen">
            <div class="dial-header">TWILIO CALL</div>
            
            <div class="phone-display">
                <div class="phone-number" id="displayNumber">{$phone}</div>
                <div class="from-number">From: <span id="displayFrom">{$twilioFrom}</span></div>
            </div>
            
            <form id="callForm">
                <div class="form-group">
                    <label>Call To</label>
                    <input type="tel" name="to" id="toNumber" value="{$phone}" required placeholder="+1 (234) 567-8900">
                </div>
                
                <div class="form-group">
                    <label>From (Your Twilio Number)</label>
                    <input type="tel" name="from" id="fromNumber" value="{$twilioFrom}" required placeholder="+1 (234) 567-8900">
                </div>
                
                <button type="submit" class="btn btn-call">📞 Start Call</button>
                <button type="button" class="btn btn-cancel" onclick="window.close()">Cancel</button>
            </form>
            
            <div id="dialStatus" class="status-msg" style="display:none;"></div>
        </div>
        
        <!-- Active Call Screen -->
        <div id="callScreen">
            <div class="call-status" id="callStatus">Connecting...</div>
            
            <div class="caller-info">
                <div class="caller-avatar">👤</div>
                <div class="caller-name" id="callerName">Calling...</div>
                <div class="caller-number" id="callerNumber"></div>
            </div>
            
            <div class="call-timer" id="callTimer">00:00</div>
            
            <div class="call-quality">
                <div class="quality-bar active"></div>
                <div class="quality-bar active"></div>
                <div class="quality-bar active"></div>
                <div class="quality-bar active"></div>
                <div class="quality-bar"></div>
            </div>
            
            <div class="call-actions">
                <button class="action-btn btn-mute" id="muteBtn" title="Mute">🔇</button>
                <button class="action-btn btn-end" id="endBtn" title="End Call">📞</button>
                <button class="action-btn btn-speaker" id="speakerBtn" title="Speaker">🔊</button>
            </div>
            
            <div id="callScreenStatus" class="status-msg" style="display:none;"></div>
        </div>
    </div>
    
    <script>
    (function() {
        var currentCallSid = null;
        var callStartTime = null;
        var timerInterval = null;
        var statusInterval = null;
        
        var dialScreen = document.getElementById("dialScreen");
        var callScreen = document.getElementById("callScreen");
        var callTimer = document.getElementById("callTimer");
        var callStatus = document.getElementById("callStatus");
        var callerName = document.getElementById("callerName");
        var callerNumber = document.getElementById("callerNumber");
        
        // Update display when input changes
        document.getElementById("toNumber").addEventListener("input", function() {
            document.getElementById("displayNumber").textContent = this.value || "Enter number";
        });
        document.getElementById("fromNumber").addEventListener("input", function() {
            document.getElementById("displayFrom").textContent = this.value;
        });
        
        // Handle call form submission
        document.getElementById("callForm").addEventListener("submit", function(e) {
            e.preventDefault();
            initiateCall();
        });
        
        // End call button
        document.getElementById("endBtn").addEventListener("click", function() {
            endCall();
        });
        
        // Mute button (visual only for now)
        document.getElementById("muteBtn").addEventListener("click", function() {
            this.classList.toggle("active");
            this.textContent = this.classList.contains("active") ? "🔈" : "🔇";
        });
        
        // Speaker button (visual only)
        document.getElementById("speakerBtn").addEventListener("click", function() {
            this.classList.toggle("active");
        });
        
        function initiateCall() {
            var to = document.getElementById("toNumber").value;
            var from = document.getElementById("fromNumber").value;
            
            showDialStatus("info", "Initiating call...");
            
            var formData = new FormData();
            formData.append("action_type", "call");
            formData.append("to", to);
            formData.append("from", from);
            
            fetch("legacy/index.php?module=TwilioIntegration&action=makecall", {
                method: "POST",
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    currentCallSid = data.call_sid;
                    showCallScreen(to);
                    startCallTimer();
                    startStatusPolling();
                } else {
                    showDialStatus("error", data.error || "Failed to initiate call");
                }
            })
            .catch(function(err) {
                showDialStatus("error", "Error: " + err.message);
            });
        }
        
        function showCallScreen(phoneNumber) {
            dialScreen.style.display = "none";
            callScreen.style.display = "block";
            callerNumber.textContent = phoneNumber;
            callerName.textContent = "Calling...";
            callStatus.textContent = "Connecting...";
            callStatus.className = "call-status ringing";
        }
        
        function showDialScreen() {
            callScreen.style.display = "none";
            dialScreen.style.display = "block";
            stopCallTimer();
            stopStatusPolling();
            currentCallSid = null;
        }
        
        function startCallTimer() {
            callStartTime = Date.now();
            timerInterval = setInterval(updateTimer, 1000);
        }
        
        function stopCallTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
        }
        
        function updateTimer() {
            var elapsed = Math.floor((Date.now() - callStartTime) / 1000);
            var minutes = Math.floor(elapsed / 60);
            var seconds = elapsed % 60;
            callTimer.textContent = pad(minutes) + ":" + pad(seconds);
        }
        
        function pad(num) {
            return num < 10 ? "0" + num : num;
        }
        
        function startStatusPolling() {
            statusInterval = setInterval(checkCallStatus, 3000);
        }
        
        function stopStatusPolling() {
            if (statusInterval) {
                clearInterval(statusInterval);
                statusInterval = null;
            }
        }
        
        function checkCallStatus() {
            if (!currentCallSid) return;
            
            var formData = new FormData();
            formData.append("action_type", "call_status");
            formData.append("call_sid", currentCallSid);
            
            fetch("legacy/index.php?module=TwilioIntegration&action=makecall", {
                method: "POST",
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    updateCallStatus(data.status);
                }
            })
            .catch(function() {});
        }
        
        function updateCallStatus(status) {
            var statusText = status.charAt(0).toUpperCase() + status.slice(1).replace(/-/g, " ");
            callStatus.textContent = statusText;
            
            if (status === "ringing" || status === "queued" || status === "initiated") {
                callStatus.className = "call-status ringing";
                callerName.textContent = "Ringing...";
            } else if (status === "in-progress") {
                callStatus.className = "call-status connected";
                callerName.textContent = "Connected";
            } else if (status === "completed" || status === "busy" || status === "no-answer" || status === "canceled" || status === "failed") {
                callStatus.className = "call-status ended";
                callerName.textContent = "Call Ended";
                stopCallTimer();
                stopStatusPolling();
                
                setTimeout(function() {
                    showDialScreen();
                    showDialStatus("info", "Call ended: " + statusText);
                }, 2000);
            }
        }
        
        function endCall() {
            if (!currentCallSid) {
                showDialScreen();
                return;
            }
            
            callStatus.textContent = "Ending call...";
            
            var formData = new FormData();
            formData.append("action_type", "end_call");
            formData.append("call_sid", currentCallSid);
            
            fetch("legacy/index.php?module=TwilioIntegration&action=makecall", {
                method: "POST",
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                callStatus.className = "call-status ended";
                callStatus.textContent = "Call Ended";
                callerName.textContent = "Disconnected";
                stopCallTimer();
                stopStatusPolling();
                
                setTimeout(function() {
                    showDialScreen();
                    if (data.success) {
                        showDialStatus("success", "Call ended successfully");
                    } else {
                        showDialStatus("error", data.error || "Error ending call");
                    }
                }, 1500);
            })
            .catch(function(err) {
                showDialScreen();
                showDialStatus("error", "Error: " + err.message);
            });
        }
        
        function showDialStatus(type, message) {
            var el = document.getElementById("dialStatus");
            el.className = "status-msg " + type;
            el.textContent = message;
            el.style.display = "block";
            
            if (type !== "error") {
                setTimeout(function() { el.style.display = "none"; }, 5000);
            }
        }
    })();
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
        
        $twilioSid = isset($sugar_config["twilio_account_sid"]) ? $sugar_config["twilio_account_sid"] : "";
        $twilioToken = isset($sugar_config["twilio_auth_token"]) ? $sugar_config["twilio_auth_token"] : "";
        
        if (empty($twilioSid) || empty($twilioToken)) {
            echo json_encode(["success" => false, "error" => "Twilio is not configured. Go to Admin > Twilio Integration > Configuration"]);
            return;
        }
        
        try {
            // Get the TwiML URL for the call
            $siteUrl = rtrim($sugar_config["site_url"], "/");
            $twimlUrl = $siteUrl . "/legacy/index.php?module=TwilioIntegration&action=twiml&to=" . urlencode($to);
            
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Calls.json";
            
            $data = array(
                "To" => $to,
                "From" => $from,
                "Url" => $twimlUrl,
                "StatusCallback" => $siteUrl . "/legacy/index.php?module=TwilioIntegration&action=webhook",
                "StatusCallbackEvent" => "initiated ringing answered completed",
                "Record" => "true"
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
                echo json_encode(["success" => true, "call_sid" => $result["sid"], "status" => $result["status"]]);
            } else {
                $errorMsg = isset($result["message"]) ? $result["message"] : "Failed to initiate call";
                echo json_encode(["success" => false, "error" => $errorMsg]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
    }
    
    private function endCall() {
        global $sugar_config;
        header("Content-Type: application/json");
        
        $callSid = isset($_POST["call_sid"]) ? $_POST["call_sid"] : "";
        
        if (empty($callSid)) {
            echo json_encode(["success" => false, "error" => "Call SID is required"]);
            return;
        }
        
        $twilioSid = isset($sugar_config["twilio_account_sid"]) ? $sugar_config["twilio_account_sid"] : "";
        $twilioToken = isset($sugar_config["twilio_auth_token"]) ? $sugar_config["twilio_auth_token"] : "";
        
        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Calls/{$callSid}.json";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["Status" => "completed"]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "{$twilioSid}:{$twilioToken}");
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode(["success" => true]);
            } else {
                $result = json_decode($response, true);
                echo json_encode(["success" => false, "error" => $result["message"] ?? "Failed to end call"]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
    }
    
    private function getCallStatus() {
        global $sugar_config;
        header("Content-Type: application/json");
        
        $callSid = isset($_POST["call_sid"]) ? $_POST["call_sid"] : "";
        
        if (empty($callSid)) {
            echo json_encode(["success" => false, "error" => "Call SID is required"]);
            return;
        }
        
        $twilioSid = isset($sugar_config["twilio_account_sid"]) ? $sugar_config["twilio_account_sid"] : "";
        $twilioToken = isset($sugar_config["twilio_auth_token"]) ? $sugar_config["twilio_auth_token"] : "";
        
        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Calls/{$callSid}.json";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "{$twilioSid}:{$twilioToken}");
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode([
                    "success" => true, 
                    "status" => $result["status"],
                    "duration" => $result["duration"] ?? 0,
                    "direction" => $result["direction"] ?? "outbound"
                ]);
            } else {
                echo json_encode(["success" => false, "error" => "Failed to get call status"]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
    }
}
