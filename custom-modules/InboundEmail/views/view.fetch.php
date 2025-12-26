<?php
/**
 * InboundEmail Fetch View
 *
 * Manually triggers email fetch for a configuration
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/MVC/View/SugarView.php');
require_once('modules/InboundEmail/InboundEmailProcessor.php');

class InboundEmailViewFetch extends SugarView
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
            // Process all configurations
            $results = InboundEmailProcessor::processAll();

            $totalProcessed = 0;
            $totalLinked = 0;

            foreach ($results as $result) {
                $totalProcessed += $result['processed'] ?? 0;
                $totalLinked += $result['linked'] ?? 0;
            }

            $this->outputJson([
                'success' => true,
                'message' => "Processed $totalProcessed emails, linked $totalLinked to records",
                'results' => $results
            ]);
        } else {
            // Process single configuration
            $result = InboundEmailProcessor::processOne($configId);
            $this->outputJson($result);
        }
    }

    private function outputJson($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }
}
