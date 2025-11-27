<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/MVC/View/views/view.list.php');

class FunnelDashboardViewList extends ViewList
{
    public function __construct()
    {
        parent::__construct();
        $this->options['show_title'] = true;
    }
}
