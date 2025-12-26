<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/**
 * LeadJourney Timeline Subpanel for Contacts
 */
$layout_defs['Contacts']['subpanel_setup']['leadjourney_timeline'] = array(
    'order' => 5,
    'sort_order' => 'desc',
    'sort_by' => 'touchpoint_date',
    'title_key' => 'LBL_LEAD_JOURNEY_TIMELINE',
    'subpanel_name' => 'leadjourney_timeline',
    'module' => 'LeadJourney',
    'get_subpanel_data' => 'function:getLeadJourneySubpanelData',
    'function_parameters' => array(
        'import_function_file' => 'modules/LeadJourney/LeadJourneySubpanel.php',
    ),
    'generate_select' => false,
    'top_buttons' => array(),
    'override_subpanel_name' => 'leadjourney_timeline',
);

// Also add call recordings subpanel
$layout_defs['Contacts']['subpanel_setup']['call_recordings'] = array(
    'order' => 6,
    'sort_order' => 'desc',
    'sort_by' => 'date_entered',
    'title_key' => 'LBL_CALL_RECORDINGS',
    'subpanel_name' => 'call_recordings',
    'module' => 'Documents',
    'get_subpanel_data' => 'function:getCallRecordingsSubpanelData',
    'function_parameters' => array(
        'import_function_file' => 'modules/LeadJourney/CallRecordingsSubpanel.php',
    ),
    'generate_select' => false,
    'top_buttons' => array(),
);
