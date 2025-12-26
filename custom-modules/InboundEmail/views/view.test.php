<?php
/**
 * InboundEmail Test Connection View
 *
 * Quick test connection endpoint (can also be used via AJAX)
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/MVC/View/SugarView.php');
require_once('modules/InboundEmail/InboundEmailClient.php');

class InboundEmailViewTest extends SugarView
{
    public function display()
    {
        global $current_user;

        // Admin only
        if (!$current_user->isAdmin()) {
            $this->outputJson(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $configId = $_REQUEST['config_id'] ?? '';

        if (empty($configId)) {
            $this->outputJson(['success' => false, 'message' => 'No configuration ID provided']);
            return;
        }

        $config = BeanFactory::getBean('InboundEmail', $configId);

        if (!$config || !$config->id) {
            $this->outputJson(['success' => false, 'message' => 'Configuration not found']);
            return;
        }

        // Check IMAP availability
        if (!InboundEmailClient::isAvailable()) {
            $this->outputJson([
                'success' => false,
                'message' => 'PHP IMAP extension is not installed'
            ]);
            return;
        }

        $client = new InboundEmailClient($config);
        $result = $client->testConnection();

        if ($result['success']) {
            // Also list folders
            $folders = $client->listFolders();
            $result['folders'] = $folders;
        }

        $this->outputJson($result);
    }

    private function outputJson($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }
}
