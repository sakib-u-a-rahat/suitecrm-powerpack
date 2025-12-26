<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/**
 * Get LeadJourney subpanel data for a parent record
 * This renders the timeline directly in the subpanel
 */
function getLeadJourneySubpanelData($params)
{
    global $db, $current_user;

    $parentBean = $params['parent_bean'] ?? null;
    if (!$parentBean || empty($parentBean->id)) {
        return array();
    }

    $parentType = $parentBean->module_dir;
    $parentId = $parentBean->id;

    require_once('modules/LeadJourney/LeadJourney.php');

    $timeline = LeadJourney::getJourneyTimeline($parentType, $parentId);

    // Convert to subpanel format
    $results = array();
    foreach ($timeline as $item) {
        $bean = BeanFactory::newBean('LeadJourney');
        $bean->id = $item['id'] ?? create_guid();
        $bean->name = $item['title'] ?? '';
        $bean->touchpoint_type = $item['type'] ?? '';
        $bean->touchpoint_date = $item['date'] ?? '';
        $bean->description = $item['description'] ?? '';
        $results[] = $bean;
    }

    return $results;
}
