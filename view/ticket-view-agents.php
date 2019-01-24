<?php if(!defined('INCLUDE_DIR')) die('Fatal error'); ?>

<?php $o_included = new TicketOptionsPlugin_AgentInclude(); ?>

<div class="ticket-options-plugin__include">
   <p>
   This is a list of agents who are included on notifications when this thread
   gets a new comment or internal note.
   </p>

   <div class="ticket-options-plugin__agents">

      <?php foreach( $o_included->get_agents() as $agent ):?>

      <div class="ticket-options-plugin__agent">
         <i class="icon-remove-circle"></i>
         <?= $agent[ 'name' ] ?>
      </div>

      <?php endforeach ?>

   </div>
</div>