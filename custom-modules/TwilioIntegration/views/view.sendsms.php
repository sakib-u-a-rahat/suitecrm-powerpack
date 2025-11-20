<?php
require_once('include/MVC/View/SugarView.php');

class TwilioIntegrationViewSendSMS extends SugarView {
    public function display() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $to = $input['to'] ?? '';
        $message = $input['message'] ?? '';
        $recordId = $input['record_id'] ?? '';
        $module = $input['module'] ?? '';
        
        if (empty($to)) {
            echo json_encode(['success' => false, 'error' => 'Phone number is required']);
            return;
        }
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Message is required']);
            return;
        }
        
        try {
            $twilio = new TwilioIntegration();
            $result = $twilio->sendSMS($to, $message);
            
            if ($result['success']) {
                // Log the SMS
                $twilio->logSMS(
                    $result['message_sid'],
                    $twilio->phone_number,
                    $to,
                    $message,
                    'sent',
                    'Outbound'
                );
                
                echo json_encode([
                    'success' => true,
                    'message_sid' => $result['message_sid'],
                    'message' => 'SMS sent successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to send SMS'
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
