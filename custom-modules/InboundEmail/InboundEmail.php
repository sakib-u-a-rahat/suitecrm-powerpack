<?php
/**
 * InboundEmail - Module for managing inbound email configurations
 *
 * Handles IMAP/POP3 email account settings for fetching inbound emails
 * and linking them to Leads/Contacts in the CRM.
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('data/SugarBean.php');

class InboundEmail extends SugarBean
{
    public $module_dir = 'InboundEmail';
    public $object_name = 'InboundEmail';
    public $table_name = 'inbound_email_config';
    public $new_schema = true;
    public $importable = false;

    // Fields
    public $id;
    public $name;
    public $date_entered;
    public $date_modified;
    public $modified_user_id;
    public $created_by;
    public $description;
    public $deleted;
    public $assigned_user_id;

    // Email server config
    public $server;
    public $port;
    public $protocol;
    public $username;
    public $password_enc;
    public $ssl;
    public $folder;
    public $polling_interval;
    public $last_poll_date;
    public $last_uid;
    public $status;
    public $auto_import;
    public $delete_after_import;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Override bean_implements to enable ACL
     */
    public function bean_implements($interface)
    {
        switch ($interface) {
            case 'ACL':
                return true;
            default:
                return false;
        }
    }

    /**
     * Get decrypted password
     */
    public function getPassword()
    {
        if (empty($this->password_enc)) {
            return '';
        }

        // Simple base64 decode (for demo - production should use proper encryption)
        return base64_decode($this->password_enc);
    }

    /**
     * Set encrypted password
     */
    public function setPassword($password)
    {
        if (empty($password)) {
            $this->password_enc = '';
        } else {
            // Simple base64 encode (for demo - production should use proper encryption)
            $this->password_enc = base64_encode($password);
        }
    }

    /**
     * Get all active email configurations
     */
    public static function getActiveConfigs()
    {
        $db = DBManagerFactory::getInstance();
        $configs = [];

        $sql = "SELECT id FROM inbound_email_config WHERE status = 'active' AND deleted = 0";
        $result = $db->query($sql);

        while ($row = $db->fetchByAssoc($result)) {
            $config = BeanFactory::getBean('InboundEmail', $row['id']);
            if ($config) {
                $configs[] = $config;
            }
        }

        return $configs;
    }

    /**
     * Update last poll information
     */
    public function updateLastPoll($lastUid = null)
    {
        $db = DBManagerFactory::getInstance();
        $now = gmdate('Y-m-d H:i:s');
        $id = $db->quote($this->id);

        $sql = "UPDATE inbound_email_config SET last_poll_date = '$now'";
        if ($lastUid !== null) {
            $sql .= ", last_uid = " . intval($lastUid);
        }
        $sql .= " WHERE id = '$id'";

        $db->query($sql);
        $this->last_poll_date = $now;
        if ($lastUid !== null) {
            $this->last_uid = $lastUid;
        }
    }

    /**
     * Set status with error message
     */
    public function setStatus($status, $errorMessage = '')
    {
        $db = DBManagerFactory::getInstance();
        $id = $db->quote($this->id);
        $statusSafe = $db->quote($status);

        $sql = "UPDATE inbound_email_config SET status = '$statusSafe'";
        if (!empty($errorMessage)) {
            $errorSafe = $db->quote($errorMessage);
            $sql .= ", description = CONCAT(IFNULL(description, ''), '\\n[', NOW(), '] Error: $errorSafe')";
        }
        $sql .= " WHERE id = '$id'";

        $db->query($sql);
        $this->status = $status;
    }

    /**
     * Get IMAP connection string
     */
    public function getConnectionString()
    {
        $server = $this->server;
        $port = $this->port ?: ($this->ssl ? 993 : 143);
        $protocol = strtolower($this->protocol ?: 'imap');

        $flags = '/' . $protocol;
        if ($this->ssl) {
            $flags .= '/ssl/novalidate-cert';
        }

        return '{' . $server . ':' . $port . $flags . '}' . ($this->folder ?: 'INBOX');
    }
}
