<?php
// PowerPack Leads Custom Fields
// Note: No die() check - this file is compiled into vardefs.ext.php

// Funnel Type - Which sales vertical this lead belongs to
$dictionary['Lead']['fields']['funnel_type_c'] = array(
    'name' => 'funnel_type_c',
    'vname' => 'LBL_FUNNEL_TYPE',
    'type' => 'enum',
    'options' => 'funnel_type_list',
    'len' => 100,
    'comment' => 'Sales funnel: Realtors, Senior Living, Home Care',
    'audited' => true,
);

// Custom Pipeline Stage
$dictionary['Lead']['fields']['pipeline_stage_c'] = array(
    'name' => 'pipeline_stage_c',
    'vname' => 'LBL_PIPELINE_STAGE',
    'type' => 'enum',
    'options' => 'pipeline_stage_list',
    'len' => 100,
    'default' => 'New',
    'comment' => 'Custom pipeline stage',
    'audited' => true,
);

// Stage Entry Date - When lead entered current stage (for velocity tracking)
$dictionary['Lead']['fields']['stage_entry_date_c'] = array(
    'name' => 'stage_entry_date_c',
    'vname' => 'LBL_STAGE_ENTRY_DATE',
    'type' => 'datetime',
    'comment' => 'When lead entered current pipeline stage',
);

// Last Activity Date - For stalled lead detection
$dictionary['Lead']['fields']['last_activity_date_c'] = array(
    'name' => 'last_activity_date_c',
    'vname' => 'LBL_LAST_ACTIVITY_DATE',
    'type' => 'datetime',
    'comment' => 'Last touchpoint/activity date',
);

// Follow-up Due Date - For alert generation
$dictionary['Lead']['fields']['follow_up_due_date_c'] = array(
    'name' => 'follow_up_due_date_c',
    'vname' => 'LBL_FOLLOW_UP_DUE',
    'type' => 'date',
    'comment' => 'Next follow-up due date',
);

// Expected Revenue
$dictionary['Lead']['fields']['expected_revenue_c'] = array(
    'name' => 'expected_revenue_c',
    'vname' => 'LBL_EXPECTED_REVENUE',
    'type' => 'currency',
    'dbType' => 'decimal',
    'len' => '26,6',
    'comment' => 'Expected deal value',
);

// Qualification Score (0-100)
$dictionary['Lead']['fields']['qualification_score_c'] = array(
    'name' => 'qualification_score_c',
    'vname' => 'LBL_QUALIFICATION_SCORE',
    'type' => 'int',
    'len' => 11,
    'default' => 0,
    'comment' => 'Lead qualification score 0-100',
);

// Demo/Visit Scheduled
$dictionary['Lead']['fields']['demo_scheduled_c'] = array(
    'name' => 'demo_scheduled_c',
    'vname' => 'LBL_DEMO_SCHEDULED',
    'type' => 'bool',
    'default' => 0,
);

// Demo/Visit Date
$dictionary['Lead']['fields']['demo_date_c'] = array(
    'name' => 'demo_date_c',
    'vname' => 'LBL_DEMO_DATE',
    'type' => 'datetime',
);

// Demo/Visit Completed
$dictionary['Lead']['fields']['demo_completed_c'] = array(
    'name' => 'demo_completed_c',
    'vname' => 'LBL_DEMO_COMPLETED',
    'type' => 'bool',
    'default' => 0,
);

// Days in Current Stage (calculated)
$dictionary['Lead']['fields']['days_in_stage_c'] = array(
    'name' => 'days_in_stage_c',
    'vname' => 'LBL_DAYS_IN_STAGE',
    'type' => 'int',
    'len' => 11,
    'source' => 'non-db',
    'comment' => 'Calculated days in current stage',
);

// Add indexes
$dictionary['Lead']['indices']['idx_funnel_type_c'] = array(
    'name' => 'idx_funnel_type_c',
    'type' => 'index',
    'fields' => array('funnel_type_c'),
);

$dictionary['Lead']['indices']['idx_pipeline_stage_c'] = array(
    'name' => 'idx_pipeline_stage_c',
    'type' => 'index',
    'fields' => array('pipeline_stage_c'),
);

$dictionary['Lead']['indices']['idx_last_activity_c'] = array(
    'name' => 'idx_last_activity_c',
    'type' => 'index',
    'fields' => array('last_activity_date_c'),
);

$dictionary['Lead']['indices']['idx_follow_up_due_c'] = array(
    'name' => 'idx_follow_up_due_c',
    'type' => 'index',
    'fields' => array('follow_up_due_date_c'),
);
