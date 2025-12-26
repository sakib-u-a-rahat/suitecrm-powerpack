<?php
/**
 * InboundEmailScheduler
 *
 * SuiteCRM scheduler job for polling inbound email accounts
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/**
 * Scheduler function to process inbound emails
 *
 * This function is registered as a scheduler job and runs periodically
 * to fetch emails from all configured IMAP accounts.
 *
 * @return bool True on success
 */
function processInboundEmails()
{
    $GLOBALS['log']->info('InboundEmailScheduler: Starting scheduled email fetch');

    require_once('modules/InboundEmail/InboundEmailProcessor.php');

    try {
        $results = InboundEmailProcessor::processAll();

        $totalProcessed = 0;
        $totalLinked = 0;
        $totalErrors = 0;

        foreach ($results as $configId => $result) {
            $totalProcessed += $result['processed'] ?? 0;
            $totalLinked += $result['linked'] ?? 0;
            $totalErrors += $result['errors'] ?? 0;

            if (!$result['success']) {
                $GLOBALS['log']->warn("InboundEmailScheduler: Config $configId failed - " . ($result['message'] ?? 'Unknown error'));
            }
        }

        $GLOBALS['log']->info("InboundEmailScheduler: Completed - Processed: $totalProcessed, Linked: $totalLinked, Errors: $totalErrors");

        return true;

    } catch (Exception $e) {
        $GLOBALS['log']->error('InboundEmailScheduler: Exception - ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if a specific configuration needs polling based on its interval
 *
 * @param string $configId Configuration ID
 * @return bool True if polling is due
 */
function shouldPollConfig($configId)
{
    $config = BeanFactory::getBean('InboundEmail', $configId);

    if (!$config || $config->status !== 'active') {
        return false;
    }

    // If never polled, poll now
    if (empty($config->last_poll_date)) {
        return true;
    }

    $lastPoll = strtotime($config->last_poll_date);
    $interval = intval($config->polling_interval) ?: 300;
    $nextPoll = $lastPoll + $interval;

    return time() >= $nextPoll;
}
