<?php
require_once('include/MVC/View/SugarView.php');

class TwilioIntegrationViewMakeCall extends SugarView {
    public function display() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $to = $input['to'] ?? '';
        $recordId = $input['record_id'] ?? '';
        $module = $input['module'] ?? '';
        
        if (empty($to)) {
            echo json_encode(['success' => false, 'error' => 'Phone number is required']);
            return;
        }
        
        try {
            $twilio = new TwilioIntegration();
            $result = $twilio->makeCall($to, $recordId, $module);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'call_sid' => $result['call_sid'],
                    'message' => 'Call initiated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to initiate call'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
