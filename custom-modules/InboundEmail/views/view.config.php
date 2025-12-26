<?php
/**
 * InboundEmail Configuration View
 *
 * UI for managing inbound email account configurations
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/MVC/View/SugarView.php');

class InboundEmailViewConfig extends SugarView
{
    public function display()
    {
        global $current_user, $mod_strings;

        // Admin only
        if (!$current_user->isAdmin()) {
            echo '<div class="alert alert-danger">Access denied. Administrator privileges required.</div>';
            return;
        }

        // Check for IMAP extension
        $imapAvailable = function_exists('imap_open');

        // Handle form submission
        $message = '';
        $messageType = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->handleFormSubmission();
            $message = $result['message'];
            $messageType = $result['type'];
        }

        // Get existing configurations
        $configs = $this->getConfigurations();

        // Get edit record if specified
        $editId = $_GET['edit_id'] ?? '';
        $editRecord = null;
        if ($editId) {
            $editRecord = BeanFactory::getBean('InboundEmail', $editId);
        }

        echo $this->renderConfigPage($configs, $editRecord, $imapAvailable, $message, $messageType);
    }

    private function handleFormSubmission()
    {
        $action = $_POST['form_action'] ?? '';

        switch ($action) {
            case 'save':
                return $this->saveConfiguration();
            case 'delete':
                return $this->deleteConfiguration();
            case 'test':
                return $this->testConfiguration();
            default:
                return ['message' => '', 'type' => ''];
        }
    }

    private function saveConfiguration()
    {
        $id = $_POST['record_id'] ?? '';

        if ($id) {
            $config = BeanFactory::getBean('InboundEmail', $id);
        } else {
            $config = BeanFactory::newBean('InboundEmail');
        }

        if (!$config) {
            return ['message' => 'Failed to create/load record', 'type' => 'danger'];
        }

        $config->name = $_POST['name'] ?? '';
        $config->server = $_POST['server'] ?? '';
        $config->port = intval($_POST['port'] ?? 993);
        $config->protocol = $_POST['protocol'] ?? 'imap';
        $config->username = $_POST['username'] ?? '';
        $config->ssl = isset($_POST['ssl']) ? 1 : 0;
        $config->folder = $_POST['folder'] ?? 'INBOX';
        $config->polling_interval = intval($_POST['polling_interval'] ?? 300);
        $config->status = $_POST['status'] ?? 'active';
        $config->auto_import = isset($_POST['auto_import']) ? 1 : 0;
        $config->delete_after_import = isset($_POST['delete_after_import']) ? 1 : 0;
        $config->assigned_user_id = $GLOBALS['current_user']->id;

        // Handle password
        $password = $_POST['password'] ?? '';
        if (!empty($password)) {
            $config->setPassword($password);
        }

        $config->save();

        $GLOBALS['log']->info("InboundEmail: Configuration saved - ID: " . $config->id);

        return ['message' => 'Email account saved successfully', 'type' => 'success'];
    }

    private function deleteConfiguration()
    {
        $id = $_POST['record_id'] ?? '';

        if (!$id) {
            return ['message' => 'No record ID specified', 'type' => 'danger'];
        }

        $config = BeanFactory::getBean('InboundEmail', $id);
        if ($config) {
            $config->mark_deleted($id);
            $GLOBALS['log']->info("InboundEmail: Configuration deleted - ID: " . $id);
            return ['message' => 'Email account deleted', 'type' => 'success'];
        }

        return ['message' => 'Record not found', 'type' => 'danger'];
    }

    private function testConfiguration()
    {
        require_once('modules/InboundEmail/InboundEmailClient.php');

        $config = BeanFactory::newBean('InboundEmail');
        $config->server = $_POST['server'] ?? '';
        $config->port = intval($_POST['port'] ?? 993);
        $config->protocol = $_POST['protocol'] ?? 'imap';
        $config->username = $_POST['username'] ?? '';
        $config->ssl = isset($_POST['ssl']) ? 1 : 0;
        $config->folder = $_POST['folder'] ?? 'INBOX';

        $password = $_POST['password'] ?? '';
        if (empty($password) && !empty($_POST['record_id'])) {
            // Use existing password
            $existing = BeanFactory::getBean('InboundEmail', $_POST['record_id']);
            if ($existing) {
                $config->password_enc = $existing->password_enc;
            }
        } else {
            $config->setPassword($password);
        }

        $client = new InboundEmailClient($config);
        $result = $client->testConnection();

        if ($result['success']) {
            $details = $result['details'];
            $msg = "Connection successful! Found {$details['total_messages']} total emails, {$details['unseen_messages']} unread.";
            return ['message' => $msg, 'type' => 'success'];
        }

        return ['message' => 'Connection failed: ' . $result['message'], 'type' => 'danger'];
    }

    private function getConfigurations()
    {
        $db = DBManagerFactory::getInstance();
        $configs = [];

        $sql = "SELECT id, name, server, status, last_poll_date FROM inbound_email_config WHERE deleted = 0 ORDER BY name";
        $result = $db->query($sql);

        while ($row = $db->fetchByAssoc($result)) {
            $configs[] = $row;
        }

        return $configs;
    }

    private function renderConfigPage($configs, $editRecord, $imapAvailable, $message, $messageType)
    {
        ob_start();
        ?>
        <style>
            .inbound-email-config {
                max-width: 1200px;
                margin: 20px auto;
                padding: 20px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .config-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #0070d2;
            }
            .config-header h2 {
                margin: 0;
                color: #333;
            }
            .alert {
                padding: 12px 20px;
                border-radius: 4px;
                margin-bottom: 20px;
            }
            .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
            .config-grid {
                display: grid;
                grid-template-columns: 1fr 2fr;
                gap: 30px;
            }
            .accounts-list {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
            }
            .accounts-list h3 {
                margin: 0 0 15px 0;
                font-size: 16px;
                color: #333;
            }
            .account-item {
                background: white;
                padding: 12px 15px;
                border-radius: 4px;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                cursor: pointer;
                transition: all 0.2s;
            }
            .account-item:hover {
                border-color: #0070d2;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .account-item.active {
                border-color: #0070d2;
                background: #e8f4ff;
            }
            .account-name {
                font-weight: 600;
                color: #333;
            }
            .account-server {
                font-size: 12px;
                color: #666;
                margin-top: 4px;
            }
            .account-status {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
                margin-top: 6px;
            }
            .status-active { background: #d4edda; color: #155724; }
            .status-inactive { background: #e2e3e5; color: #383d41; }
            .status-error { background: #f8d7da; color: #721c24; }
            .config-form {
                background: white;
                border-radius: 8px;
                padding: 25px;
                border: 1px solid #ddd;
            }
            .form-section {
                margin-bottom: 25px;
            }
            .form-section h4 {
                margin: 0 0 15px 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
                color: #333;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                color: #333;
            }
            .form-group input[type="text"],
            .form-group input[type="password"],
            .form-group input[type="number"],
            .form-group select {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 14px;
                box-sizing: border-box;
            }
            .form-group input:focus,
            .form-group select:focus {
                border-color: #0070d2;
                outline: none;
                box-shadow: 0 0 0 2px rgba(0,112,210,0.2);
            }
            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
            .form-check {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .form-check input[type="checkbox"] {
                width: 18px;
                height: 18px;
            }
            .help-text {
                font-size: 12px;
                color: #666;
                margin-top: 4px;
            }
            .btn-group {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.2s;
            }
            .btn-primary {
                background: #0070d2;
                color: white;
            }
            .btn-primary:hover {
                background: #005bb5;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            .btn-secondary:hover {
                background: #545b62;
            }
            .btn-danger {
                background: #dc3545;
                color: white;
            }
            .btn-danger:hover {
                background: #c82333;
            }
            .btn-success {
                background: #28a745;
                color: white;
            }
            .btn-success:hover {
                background: #218838;
            }
            .add-account-btn {
                width: 100%;
                padding: 12px;
                margin-top: 15px;
            }
        </style>

        <div class="inbound-email-config">
            <div class="config-header">
                <h2>Inbound Email Configuration</h2>
            </div>

            <?php if (!$imapAvailable): ?>
            <div class="alert alert-warning">
                <strong>Warning:</strong> PHP IMAP extension is not installed. Email fetching will not work.
                Please install php-imap extension.
            </div>
            <?php endif; ?>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="config-grid">
                <div class="accounts-list">
                    <h3>Email Accounts</h3>
                    <?php foreach ($configs as $config): ?>
                    <div class="account-item <?php echo ($editRecord && $editRecord->id === $config['id']) ? 'active' : ''; ?>"
                         onclick="window.location='?module=InboundEmail&action=config&edit_id=<?php echo $config['id']; ?>'">
                        <div class="account-name"><?php echo htmlspecialchars($config['name']); ?></div>
                        <div class="account-server"><?php echo htmlspecialchars($config['server']); ?></div>
                        <span class="account-status status-<?php echo $config['status']; ?>">
                            <?php echo ucfirst($config['status']); ?>
                        </span>
                        <?php if ($config['last_poll_date']): ?>
                        <div class="account-server">Last poll: <?php echo $config['last_poll_date']; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($configs)): ?>
                    <p style="color: #666; font-style: italic;">No email accounts configured yet.</p>
                    <?php endif; ?>

                    <button class="btn btn-primary add-account-btn"
                            onclick="window.location='?module=InboundEmail&action=config'">
                        + Add Email Account
                    </button>
                </div>

                <div class="config-form">
                    <form method="POST" id="configForm">
                        <input type="hidden" name="form_action" id="form_action" value="save">
                        <input type="hidden" name="record_id" value="<?php echo $editRecord ? $editRecord->id : ''; ?>">

                        <div class="form-section">
                            <h4><?php echo $editRecord ? 'Edit Email Account' : 'Add Email Account'; ?></h4>

                            <div class="form-group">
                                <label>Account Name *</label>
                                <input type="text" name="name" required
                                       value="<?php echo $editRecord ? htmlspecialchars($editRecord->name) : ''; ?>"
                                       placeholder="e.g., Support Inbox">
                            </div>
                        </div>

                        <div class="form-section">
                            <h4>Server Settings</h4>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Server *</label>
                                    <input type="text" name="server" required
                                           value="<?php echo $editRecord ? htmlspecialchars($editRecord->server) : ''; ?>"
                                           placeholder="e.g., imap.gmail.com">
                                    <div class="help-text">IMAP server hostname</div>
                                </div>
                                <div class="form-group">
                                    <label>Port</label>
                                    <input type="number" name="port"
                                           value="<?php echo $editRecord ? $editRecord->port : '993'; ?>">
                                    <div class="help-text">993 for IMAPS, 143 for IMAP</div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Protocol</label>
                                    <select name="protocol">
                                        <option value="imap" <?php echo ($editRecord && $editRecord->protocol === 'imap') ? 'selected' : ''; ?>>IMAP</option>
                                        <option value="pop3" <?php echo ($editRecord && $editRecord->protocol === 'pop3') ? 'selected' : ''; ?>>POP3</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Folder</label>
                                    <input type="text" name="folder"
                                           value="<?php echo $editRecord ? htmlspecialchars($editRecord->folder) : 'INBOX'; ?>">
                                    <div class="help-text">Folder to monitor (default: INBOX)</div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Username *</label>
                                    <input type="text" name="username" required
                                           value="<?php echo $editRecord ? htmlspecialchars($editRecord->username) : ''; ?>"
                                           placeholder="email@example.com">
                                </div>
                                <div class="form-group">
                                    <label>Password <?php echo $editRecord ? '' : '*'; ?></label>
                                    <input type="password" name="password" <?php echo $editRecord ? '' : 'required'; ?>
                                           placeholder="<?php echo $editRecord ? '(unchanged)' : 'Enter password'; ?>">
                                    <?php if ($editRecord): ?>
                                    <div class="help-text">Leave blank to keep existing password</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-check">
                                    <input type="checkbox" name="ssl"
                                           <?php echo (!$editRecord || $editRecord->ssl) ? 'checked' : ''; ?>>
                                    Use SSL/TLS (recommended)
                                </label>
                            </div>
                        </div>

                        <div class="form-section">
                            <h4>Import Settings</h4>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Polling Interval (seconds)</label>
                                    <input type="number" name="polling_interval" min="60"
                                           value="<?php echo $editRecord ? $editRecord->polling_interval : '300'; ?>">
                                    <div class="help-text">How often to check for new emails</div>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="active" <?php echo ($editRecord && $editRecord->status === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($editRecord && $editRecord->status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-check">
                                    <input type="checkbox" name="auto_import"
                                           <?php echo (!$editRecord || $editRecord->auto_import) ? 'checked' : ''; ?>>
                                    Auto-import emails and link to matching Leads/Contacts
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="form-check">
                                    <input type="checkbox" name="delete_after_import"
                                           <?php echo ($editRecord && $editRecord->delete_after_import) ? 'checked' : ''; ?>>
                                    Delete emails from server after import (use with caution)
                                </label>
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $editRecord ? 'Update Account' : 'Save Account'; ?>
                            </button>
                            <button type="button" class="btn btn-success" onclick="testConnection()">
                                Test Connection
                            </button>
                            <?php if ($editRecord): ?>
                            <button type="button" class="btn btn-danger" onclick="deleteAccount()">
                                Delete
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-secondary"
                                    onclick="window.location='?module=InboundEmail&action=config'">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function testConnection() {
            document.getElementById('form_action').value = 'test';
            document.getElementById('configForm').submit();
        }

        function deleteAccount() {
            if (confirm('Are you sure you want to delete this email account?')) {
                document.getElementById('form_action').value = 'delete';
                document.getElementById('configForm').submit();
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
