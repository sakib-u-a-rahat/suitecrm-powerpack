<?php
// Add "View Journey Timeline" button to Leads DetailView
$viewdefs['Leads']['DetailView']['templateMeta']['form']['buttons'][] = array(
    'customCode' => '<input type="button" class="button" onclick="window.open(\'index.php?module=LeadJourney&action=timeline&parent_type=Leads&parent_id={$fields.id.value}\', \'_blank\', \'width=1200,height=800,scrollbars=yes\');" value="View Journey Timeline" title="View complete interaction timeline">',
);
