<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/MVC/View/views/view.detail.php');

class TwilioIntegrationViewDetail extends ViewDetail
{
    public function __construct()
    {
        parent::__construct();
        $this->options['show_title'] = true;
    }
}
