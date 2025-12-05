<?php
/**
 * Twilio Security Helper
 * Validates webhook requests to ensure they come from Twilio
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class TwilioSecurity
{
    /**
     * Validate that a webhook request came from Twilio
     *
     * @param string $authToken Twilio auth token
     * @param string $url The full URL of the webhook (including https://)
     * @param array $postData POST data from the request
     * @param string $signature The X-Twilio-Signature header value
     * @return bool True if valid, false otherwise
     */
    public static function validateRequest($authToken, $url, $postData, $signature)
    {
        if (empty($authToken) || empty($signature)) {
            return false;
        }

        // Build the data string for signature validation
        $data = $url;

        // Sort parameters alphabetically
        if (is_array($postData)) {
            ksort($postData);
            foreach ($postData as $key => $value) {
                $data .= $key . $value;
            }
        }

        // Compute the expected signature
        $expectedSignature = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        // Use hash_equals to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate webhook and log if invalid
     * Terminates script execution if validation fails
     *
     * @param string $webhookType Type of webhook (for logging)
     */
    public static function validateOrDie($webhookType = 'webhook')
    {
        global $sugar_config;

        // Get auth token
        $authToken = getenv('TWILIO_AUTH_TOKEN') ?: ($sugar_config['twilio_auth_token'] ?? '');

        if (empty($authToken)) {
            $GLOBALS['log']->error("Twilio $webhookType: Auth token not configured");
            http_response_code(500);
            die('Configuration error');
        }

        // Skip validation in development mode
        if (!empty($sugar_config['twilio_skip_validation'])) {
            $GLOBALS['log']->warn("Twilio $webhookType: Signature validation SKIPPED (development mode)");
            return true;
        }

        // Get signature from header
        $signature = $_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '';

        if (empty($signature)) {
            $GLOBALS['log']->error("Twilio $webhookType: Missing X-Twilio-Signature header");
            http_response_code(403);
            die('Forbidden: Missing signature');
        }

        // Build the full URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $url = $protocol . '://' . $host . $uri;

        // Validate
        $isValid = self::validateRequest($authToken, $url, $_POST, $signature);

        if (!$isValid) {
            $GLOBALS['log']->error("Twilio $webhookType: Invalid signature - possible attack");
            $GLOBALS['log']->debug("Expected URL: $url");
            $GLOBALS['log']->debug("Received signature: $signature");
            http_response_code(403);
            die('Forbidden: Invalid signature');
        }

        $GLOBALS['log']->info("Twilio $webhookType: Signature validated successfully");
        return true;
    }

    /**
     * Check if IP is from Twilio (additional security layer)
     *
     * @return bool
     */
    public static function isFromTwilioIP()
    {
        // Twilio's IP ranges (as of 2024)
        // These should be updated periodically from: https://www.twilio.com/docs/ips
        $twilioIPRanges = [
            '54.252.254.64/26',
            '54.65.63.192/26',
            '54.169.127.128/26',
            '54.252.254.64/26',
            '177.71.206.192/26',
            '3.112.80.0/26',
            '3.112.80.64/26',
        ];

        $clientIP = self::getClientIP();

        foreach ($twilioIPRanges as $range) {
            if (self::ipInRange($clientIP, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get client IP address
     */
    private static function getClientIP()
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Check if IP is in CIDR range
     */
    private static function ipInRange($ip, $range)
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $mask) = explode('/', $range);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
