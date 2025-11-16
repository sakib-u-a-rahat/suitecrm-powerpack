<?php
require_once('include/MVC/View/SugarView.php');

class TwilioIntegrationViewConfig extends SugarView {
    public function display() {
        global $mod_strings, $app_strings, $sugar_config;
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveConfig();
        }
        
        $config = TwilioIntegration::getConfig();
        
        echo $this->renderConfigForm($config);
    }
    
    private function saveConfig() {
        require_once('modules/Administration/Administration.php');
        $admin = new Administration();
        
        $admin->saveSetting('twilio', 'account_sid', $_POST['account_sid']);
        $admin->saveSetting('twilio', 'auth_token', $_POST['auth_token']);
        $admin->saveSetting('twilio', 'phone_number', $_POST['phone_number']);
        $admin->saveSetting('twilio', 'enable_click_to_call', isset($_POST['enable_click_to_call']) ? '1' : '0');
        $admin->saveSetting('twilio', 'enable_auto_logging', isset($_POST['enable_auto_logging']) ? '1' : '0');
        $admin->saveSetting('twilio', 'enable_recordings', isset($_POST['enable_recordings']) ? '1' : '0');
        
        echo '<div class="alert alert-success">Configuration saved successfully!</div>';
    }
    
    private function renderConfigForm($config) {
        ob_start();
        ?>
        <style>
            .config-container {
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .form-group input[type="text"],
            .form-group input[type="password"] {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .form-group input[type="checkbox"] {
                margin-right: 10px;
            }
            .btn-save {
                background: #0070d2;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .btn-save:hover {
                background: #005fb2;
            }
        </style>
        
        <div class="config-container">
            <h2>Twilio Integration Configuration</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Twilio Account SID</label>
                    <input type="text" name="account_sid" value="<?php echo htmlspecialchars($config['account_sid']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Twilio Auth Token</label>
                    <input type="password" name="auth_token" value="<?php echo htmlspecialchars($config['auth_token']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Twilio Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($config['phone_number']); ?>" placeholder="+1234567890" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="enable_click_to_call" <?php echo $config['enable_click_to_call'] ? 'checked' : ''; ?>>
                        Enable Click-to-Call
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="enable_auto_logging" <?php echo $config['enable_auto_logging'] ? 'checked' : ''; ?>>
                        Enable Automatic Call Logging
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="enable_recordings" <?php echo $config['enable_recordings'] ? 'checked' : ''; ?>>
                        Enable Call Recordings
                    </label>
                </div>
                
                <button type="submit" class="btn-save">Save Configuration</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}
