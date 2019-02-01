<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

$o_included = new TicketOptionsPlugin_AgentInclude( $ticket );

?>

<div class="ticket-options-plugin__include">
   <h3>Included Agents</h3>
   <p>
   This is a list of agents who are included on notifications when this thread
   gets a new comment or internal note.
   </p>

   <div id="agent-list-error">error</div>
   <div id="agent-list-search"></div>
   <div id="agent-list-add"></div>
   <div id="agent-add-progress"></div>
   <div id="agent-remove-progress"></div>
   <div id="agent-confirm-remove"></div>
   <div id="agent-list"></div>

   <?php if( $errors ): ?>
   <div class="ticket-options-plugin__error error-banner">
      <?= $errors[ 'err' ] ?>
      
   </div>
   <?php endif ?>


</div>

<script type="text/javascript">
// Make the CSRF token available to javascript. jQuery can hold it for us.
$.csrf_token = '<?= $ost->getCSRF()->getToken() ?>';

// Make the ticket ID available to javascript. jQuery can hold it for us.
$.ticket_id = '<?= $ticket->getId() ?>';
</script>
