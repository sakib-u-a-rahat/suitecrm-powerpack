<?php
/**
 * Twilio Scheduler Job for SuiteCRM Scheduler
 * This integrates with SuiteCRM's built-in scheduler system
 *
 * To enable: Admin > Schedulers > Create Scheduler
 * - Name: Twilio SMS Follow-up Check
 * - Job: function::TwilioSchedulerJob::checkSMSFollowups
 * - Interval: 0 * * * * (every hour)
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/TwilioIntegration/TwilioScheduler.php');

class TwilioSchedulerJob
{
    /**
     * Scheduler job function for SMS follow-ups
     *
     * @return bool
     */
    public static function checkSMSFollowups()
    {
        try {
            $GLOBALS['log']->info("TwilioSchedulerJob: Starting SMS follow-up check");

            $count = TwilioScheduler::checkUnrepliedSMS();

            $GLOBALS['log']->info("TwilioSchedulerJob: SMS follow-up check completed - $count tasks created");

            return true;

        } catch (Exception $e) {
            $GLOBALS['log']->error("TwilioSchedulerJob: Error - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Scheduler job function for recording cleanup
     *
     * @return bool
     */
    public static function cleanupRecordings()
    {
        try {
            $GLOBALS['log']->info("TwilioSchedulerJob: Starting recording cleanup");

            $count = TwilioScheduler::cleanupOldRecordings();

            $GLOBALS['log']->info("TwilioSchedulerJob: Recording cleanup completed - $count files deleted");

            return true;

        } catch (Exception $e) {
            $GLOBALS['log']->error("TwilioSchedulerJob: Error - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Scheduler job function for daily summary
     *
     * @return bool
     */
    public static function generateDailySummary()
    {
        try {
            $GLOBALS['log']->info("TwilioSchedulerJob: Starting daily summary generation");

            $summary = TwilioScheduler::generateActivitySummary('daily');

            $GLOBALS['log']->info("TwilioSchedulerJob: Daily summary generated");

            return true;

        } catch (Exception $e) {
            $GLOBALS['log']->error("TwilioSchedulerJob: Error - " . $e->getMessage());
            return false;
        }
    }
}
