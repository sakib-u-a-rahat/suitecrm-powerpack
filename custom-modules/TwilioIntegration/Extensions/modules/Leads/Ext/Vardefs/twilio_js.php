<?php
// Inject Twilio Click-to-Call JavaScript on Detail View
$viewdefs['Leads']['DetailView']['templateMeta']['includes'][] = array(
    'file' => 'modules/TwilioIntegration/click-to-call.js',
);
