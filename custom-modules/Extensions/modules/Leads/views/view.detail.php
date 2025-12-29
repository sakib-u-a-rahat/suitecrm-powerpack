<?php
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");

require_once("include/MVC/View/views/view.detail.php");

class LeadsViewDetail extends ViewDetail
{
    public function display()
    {
        parent::display();

        // Add custom buttons for Timeline and Recordings
        if (!empty($this->bean->id)) {
            $recordId = $this->bean->id;

            echo "<style>
                .leadjourney-buttons { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
                .leadjourney-buttons a { display: inline-block; padding: 8px 16px; margin-right: 10px; background: #0d6efd; color: white; text-decoration: none; border-radius: 4px; }
                .leadjourney-buttons a:hover { background: #0b5ed7; }
                .leadjourney-buttons a.secondary { background: #6c757d; }
                .leadjourney-buttons a.secondary:hover { background: #5c636a; }
            </style>
            <div class=\"leadjourney-buttons\">
                <a href=\"index.php?module=LeadJourney&action=timeline&parent_type=Leads&parent_id=" . $recordId . "\">View Journey Timeline</a>
                <a href=\"index.php?module=LeadJourney&action=recordings&parent_type=Leads&parent_id=" . $recordId . "\" class=\"secondary\">View Call Recordings</a>
            </div>";
        }
    }
}
