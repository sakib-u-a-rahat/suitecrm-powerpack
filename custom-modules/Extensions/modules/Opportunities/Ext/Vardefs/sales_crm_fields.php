<?php
// PowerPack Opportunities Custom Fields
// Note: No die() check - this file is compiled into vardefs.ext.php

// Funnel Type - Inherited from lead or set directly
$dictionary['Opportunity']['fields']['funnel_type_c'] = array(
    'name' => 'funnel_type_c',
    'vname' => 'LBL_FUNNEL_TYPE',
    'type' => 'enum',
    'options' => 'funnel_type_list',
    'len' => 100,
    'comment' => 'Sales funnel: Realtors, Senior Living, Home Care',
    'audited' => true,
);

// Link to Package
$dictionary['Opportunity']['fields']['package_id_c'] = array(
    'name' => 'package_id_c',
    'vname' => 'LBL_PACKAGE',
    'type' => 'id',
    'reportable' => false,
);

$dictionary['Opportunity']['fields']['package_name_c'] = array(
    'name' => 'package_name_c',
    'rname' => 'name',
    'id_name' => 'package_id_c',
    'vname' => 'LBL_PACKAGE',
    'type' => 'relate',
    'table' => 'packages',
    'module' => 'Packages',
    'dbType' => 'varchar',
    'len' => 255,
    'source' => 'non-db',
);

// Commission Amount (calculated)
$dictionary['Opportunity']['fields']['commission_amount_c'] = array(
    'name' => 'commission_amount_c',
    'vname' => 'LBL_COMMISSION_AMOUNT',
    'type' => 'currency',
    'dbType' => 'decimal',
    'len' => '26,6',
    'comment' => 'Calculated commission for this deal',
);

// Commission Paid flag
$dictionary['Opportunity']['fields']['commission_paid_c'] = array(
    'name' => 'commission_paid_c',
    'vname' => 'LBL_COMMISSION_PAID',
    'type' => 'bool',
    'default' => 0,
);

// Commission Paid Date
$dictionary['Opportunity']['fields']['commission_paid_date_c'] = array(
    'name' => 'commission_paid_date_c',
    'vname' => 'LBL_COMMISSION_PAID_DATE',
    'type' => 'date',
);

// Demo/Visit Scheduled
$dictionary['Opportunity']['fields']['demo_scheduled_c'] = array(
    'name' => 'demo_scheduled_c',
    'vname' => 'LBL_DEMO_SCHEDULED',
    'type' => 'bool',
    'default' => 0,
);

// Demo/Visit Scheduled Date
$dictionary['Opportunity']['fields']['demo_scheduled_date_c'] = array(
    'name' => 'demo_scheduled_date_c',
    'vname' => 'LBL_DEMO_SCHEDULED_DATE',
    'type' => 'datetime',
);

// Demo/Visit Completed
$dictionary['Opportunity']['fields']['demo_completed_c'] = array(
    'name' => 'demo_completed_c',
    'vname' => 'LBL_DEMO_COMPLETED',
    'type' => 'bool',
    'default' => 0,
);

// Demo/Visit Completed Date
$dictionary['Opportunity']['fields']['demo_completed_date_c'] = array(
    'name' => 'demo_completed_date_c',
    'vname' => 'LBL_DEMO_COMPLETED_DATE',
    'type' => 'datetime',
);

// Proposal Sent
$dictionary['Opportunity']['fields']['proposal_sent_c'] = array(
    'name' => 'proposal_sent_c',
    'vname' => 'LBL_PROPOSAL_SENT',
    'type' => 'bool',
    'default' => 0,
);

// Proposal Sent Date
$dictionary['Opportunity']['fields']['proposal_sent_date_c'] = array(
    'name' => 'proposal_sent_date_c',
    'vname' => 'LBL_PROPOSAL_SENT_DATE',
    'type' => 'datetime',
);

// Source Lead ID (for tracking conversion)
$dictionary['Opportunity']['fields']['source_lead_id_c'] = array(
    'name' => 'source_lead_id_c',
    'vname' => 'LBL_SOURCE_LEAD',
    'type' => 'id',
    'reportable' => false,
);

// Add indexes
$dictionary['Opportunity']['indices']['idx_opp_funnel_type_c'] = array(
    'name' => 'idx_opp_funnel_type_c',
    'type' => 'index',
    'fields' => array('funnel_type_c'),
);

$dictionary['Opportunity']['indices']['idx_opp_package_c'] = array(
    'name' => 'idx_opp_package_c',
    'type' => 'index',
    'fields' => array('package_id_c'),
);
