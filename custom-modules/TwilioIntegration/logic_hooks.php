<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$hook_array['after_ui_frame'][] = array(
    1,
    'Inject Twilio Click-to-Call JavaScript',
    'modules/TwilioIntegration/TwilioHooks.php',
    'TwilioHooks',
    'injectClickToCallJS'
);
