<?php
/**
 * InboundEmailProcessor - Processes and links inbound emails
 *
 * Fetches emails from configured accounts and links them to
 * Leads/Contacts based on email address matching.
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/InboundEmail/InboundEmail.php');
require_once('modules/InboundEmail/InboundEmailClient.php');
require_once('modules/LeadJourney/LeadJourneyLogger.php');

class InboundEmailProcessor
{
    private $config;
    private $client;
    private $processedCount = 0;
    private $linkedCount = 0;
    private $errorCount = 0;

    /**
     * Process all active email configurations
     *
     * @return array Summary of processing
     */
    public static function processAll()
    {
        $configs = InboundEmail::getActiveConfigs();
        $results = [];

        foreach ($configs as $config) {
            $processor = new InboundEmailProcessor($config);
            $results[$config->id] = $processor->process();
        }

        return $results;
    }

    /**
     * @param InboundEmail $config Email configuration
     */
    public function __construct(InboundEmail $config)
    {
        $this->config = $config;
        $this->client = new InboundEmailClient($config);
    }

    /**
     * Process emails for this configuration
     *
     * @return array Processing summary
     */
    public function process()
    {
        $GLOBALS['log']->info("InboundEmailProcessor: Starting processing for config " . $this->config->id);

        // Check if IMAP is available
        if (!InboundEmailClient::isAvailable()) {
            $this->config->setStatus('error', 'PHP IMAP extension not available');
            return [
                'success' => false,
                'message' => 'PHP IMAP extension not available',
                'processed' => 0,
                'linked' => 0,
                'errors' => 1
            ];
        }

        // Fetch new emails
        $emails = $this->client->fetchNewEmails(50);

        if (empty($emails)) {
            $this->config->updateLastPoll();
            $GLOBALS['log']->info("InboundEmailProcessor: No new emails for config " . $this->config->id);
            return [
                'success' => true,
                'message' => 'No new emails',
                'processed' => 0,
                'linked' => 0,
                'errors' => 0
            ];
        }

        $lastUid = 0;

        foreach ($emails as $email) {
            try {
                $this->processEmail($email);
                $this->processedCount++;
                $lastUid = max($lastUid, $email['uid']);
            } catch (Exception $e) {
                $GLOBALS['log']->error("InboundEmailProcessor: Error processing email - " . $e->getMessage());
                $this->errorCount++;
            }
        }

        // Update last poll info
        $this->config->updateLastPoll($lastUid);

        // Handle deletion if configured
        if ($this->config->delete_after_import) {
            $this->client->expunge();
        }

        $this->client->disconnect();

        $GLOBALS['log']->info("InboundEmailProcessor: Completed - Processed: {$this->processedCount}, Linked: {$this->linkedCount}, Errors: {$this->errorCount}");

        return [
            'success' => true,
            'message' => "Processed {$this->processedCount} emails",
            'processed' => $this->processedCount,
            'linked' => $this->linkedCount,
            'errors' => $this->errorCount
        ];
    }

    /**
     * Process a single email
     */
    private function processEmail($email)
    {
        // Find matching Lead or Contact
        $match = $this->findMatchingRecord($email['from']);

        // Create Email record in SuiteCRM
        $emailBean = $this->createEmailRecord($email, $match);

        if ($emailBean && $match) {
            // Link to parent record
            $this->linkToRecord($emailBean, $match);
            $this->linkedCount++;

            // Log to LeadJourney
            $this->logToJourney($email, $match);
        }

        // Mark as read in mailbox
        $this->client->markAsRead($email['uid']);

        // Delete if configured
        if ($this->config->delete_after_import) {
            $this->client->deleteEmail($email['uid']);
        }
    }

    /**
     * Find matching Lead or Contact by email address
     */
    private function findMatchingRecord($emailAddress)
    {
        $db = DBManagerFactory::getInstance();
        $emailSafe = $db->quote(strtolower($emailAddress));

        // Search Leads
        $sql = "SELECT l.id, l.first_name, l.last_name, l.assigned_user_id
                FROM leads l
                JOIN email_addr_bean_rel eabr ON eabr.bean_id = l.id AND eabr.bean_module = 'Leads' AND eabr.deleted = 0
                JOIN email_addresses ea ON ea.id = eabr.email_address_id AND ea.deleted = 0
                WHERE LOWER(ea.email_address) = '$emailSafe'
                AND l.deleted = 0
                LIMIT 1";

        $result = $db->query($sql);
        if ($row = $db->fetchByAssoc($result)) {
            return [
                'id' => $row['id'],
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'type' => 'Leads',
                'assigned_user_id' => $row['assigned_user_id']
            ];
        }

        // Search Contacts
        $sql = "SELECT c.id, c.first_name, c.last_name, c.assigned_user_id
                FROM contacts c
                JOIN email_addr_bean_rel eabr ON eabr.bean_id = c.id AND eabr.bean_module = 'Contacts' AND eabr.deleted = 0
                JOIN email_addresses ea ON ea.id = eabr.email_address_id AND ea.deleted = 0
                WHERE LOWER(ea.email_address) = '$emailSafe'
                AND c.deleted = 0
                LIMIT 1";

        $result = $db->query($sql);
        if ($row = $db->fetchByAssoc($result)) {
            return [
                'id' => $row['id'],
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'type' => 'Contacts',
                'assigned_user_id' => $row['assigned_user_id']
            ];
        }

        // Search Accounts by email domain
        $domain = substr($emailAddress, strpos($emailAddress, '@') + 1);
        if ($domain) {
            $domainSafe = $db->quote('%@' . strtolower($domain));

            $sql = "SELECT a.id, a.name, a.assigned_user_id
                    FROM accounts a
                    JOIN email_addr_bean_rel eabr ON eabr.bean_id = a.id AND eabr.bean_module = 'Accounts' AND eabr.deleted = 0
                    JOIN email_addresses ea ON ea.id = eabr.email_address_id AND ea.deleted = 0
                    WHERE LOWER(ea.email_address) LIKE '$domainSafe'
                    AND a.deleted = 0
                    LIMIT 1";

            $result = $db->query($sql);
            if ($row = $db->fetchByAssoc($result)) {
                return [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'type' => 'Accounts',
                    'assigned_user_id' => $row['assigned_user_id']
                ];
            }
        }

        return null;
    }

    /**
     * Create Email record in SuiteCRM
     */
    private function createEmailRecord($email, $match)
    {
        require_once('modules/Emails/Email.php');

        $emailBean = BeanFactory::newBean('Emails');

        $emailBean->name = $email['subject'];
        $emailBean->date_sent = $email['date'];
        $emailBean->date_entered = gmdate('Y-m-d H:i:s');
        $emailBean->type = 'inbound';
        $emailBean->status = 'read';
        $emailBean->from_addr = $email['from'];
        $emailBean->from_addr_name = $email['from_name'];
        $emailBean->to_addrs = implode(', ', $email['to']);
        $emailBean->cc_addrs = implode(', ', $email['cc']);
        $emailBean->message_id = $email['message_id'];

        // Set body
        if (!empty($email['body_html'])) {
            $emailBean->description_html = $email['body_html'];
            $emailBean->description = strip_tags($email['body_html']);
        } else {
            $emailBean->description = $email['body'];
            $emailBean->description_html = nl2br(htmlspecialchars($email['body']));
        }

        // Set parent if matched
        if ($match) {
            $emailBean->parent_type = $match['type'];
            $emailBean->parent_id = $match['id'];
            $emailBean->assigned_user_id = $match['assigned_user_id'];
        }

        $emailBean->save();

        $GLOBALS['log']->info("InboundEmailProcessor: Created Email record " . $emailBean->id . " from " . $email['from']);

        // Handle attachments
        if (!empty($email['attachments'])) {
            $this->processAttachments($emailBean, $email);
        }

        return $emailBean;
    }

    /**
     * Process email attachments
     */
    private function processAttachments($emailBean, $email)
    {
        foreach ($email['attachments'] as $attachment) {
            try {
                $content = $this->client->downloadAttachment($email['uid'], $attachment['part_number']);

                if ($content) {
                    require_once('modules/Notes/Note.php');

                    $note = BeanFactory::newBean('Notes');
                    $note->name = $attachment['filename'];
                    $note->parent_type = 'Emails';
                    $note->parent_id = $emailBean->id;
                    $note->filename = $attachment['filename'];
                    $note->file_mime_type = $attachment['mime_type'];

                    // Save to upload directory
                    $noteId = $note->save();

                    $uploadFile = 'upload/' . $noteId;
                    file_put_contents($uploadFile, $content);

                    $GLOBALS['log']->info("InboundEmailProcessor: Saved attachment " . $attachment['filename']);
                }
            } catch (Exception $e) {
                $GLOBALS['log']->error("InboundEmailProcessor: Failed to save attachment - " . $e->getMessage());
            }
        }
    }

    /**
     * Link email to parent record
     */
    private function linkToRecord($emailBean, $match)
    {
        // Create relationship if not already set
        if ($emailBean->parent_type !== $match['type'] || $emailBean->parent_id !== $match['id']) {
            $emailBean->parent_type = $match['type'];
            $emailBean->parent_id = $match['id'];
            $emailBean->save();
        }

        // Also create note for activity stream
        $this->createActivityNote($emailBean, $match);
    }

    /**
     * Create activity note for the email
     */
    private function createActivityNote($emailBean, $match)
    {
        require_once('modules/Notes/Note.php');

        $note = BeanFactory::newBean('Notes');
        $note->name = "Email: " . $emailBean->name;
        $note->parent_type = $match['type'];
        $note->parent_id = $match['id'];
        $note->assigned_user_id = $match['assigned_user_id'];

        $description = "Inbound Email Received\n";
        $description .= "========================\n\n";
        $description .= "From: " . $emailBean->from_addr_name . " <" . $emailBean->from_addr . ">\n";
        $description .= "Date: " . $emailBean->date_sent . "\n";
        $description .= "Subject: " . $emailBean->name . "\n\n";
        $description .= "Message:\n" . substr($emailBean->description, 0, 500);

        if (strlen($emailBean->description) > 500) {
            $description .= "...\n\n[View full email in Emails module]";
        }

        $note->description = $description;
        $note->save();
    }

    /**
     * Log email to LeadJourney timeline
     */
    private function logToJourney($email, $match)
    {
        if (!$match || $match['type'] === 'Accounts') {
            return; // Only log for Leads and Contacts
        }

        LeadJourneyLogger::logEmail([
            'message_id' => $email['message_id'],
            'from' => $email['from'],
            'from_name' => $email['from_name'],
            'to' => $email['to'],
            'subject' => $email['subject'],
            'body' => substr($email['body'], 0, 500),
            'direction' => 'inbound',
            'date' => $email['date'],
            'parent_type' => $match['type'],
            'parent_id' => $match['id'],
            'assigned_user_id' => $match['assigned_user_id']
        ]);

        $GLOBALS['log']->info("InboundEmailProcessor: Logged email to LeadJourney for " . $match['type'] . " " . $match['id']);
    }

    /**
     * Process a single configuration by ID (for manual fetch)
     */
    public static function processOne($configId)
    {
        $config = BeanFactory::getBean('InboundEmail', $configId);

        if (!$config || !$config->id) {
            return [
                'success' => false,
                'message' => 'Configuration not found'
            ];
        }

        $processor = new InboundEmailProcessor($config);
        return $processor->process();
    }
}
