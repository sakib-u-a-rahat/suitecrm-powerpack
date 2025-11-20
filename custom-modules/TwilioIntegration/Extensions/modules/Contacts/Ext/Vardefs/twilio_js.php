<?php
// Inject Twilio Click-to-Call JavaScript on Detail View
$viewdefs['Contacts']['DetailView']['templateMeta']['includes'][] = array(
    'file' => 'modules/TwilioIntegration/click-to-call.js',
);
