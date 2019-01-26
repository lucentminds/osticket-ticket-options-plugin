<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

$o_included = new TicketOptionsPlugin_AgentInclude( $ticket );

?>

<div class="ticket-options-plugin__include">
   <h3>Included Agents</h3>
   <p>
   This is a list of agents who are included on notifications when this thread
   gets a new comment or internal note.
   </p>

   <div id="agent-list-error"></div>
   <div id="agent-list-form"></div>
   <div id="agent-list"></div>

      <?php $a_agents = $o_included->fetch_agents( $errors ) ?>
      <?php if( !$a_agents ):?>

      <div class="ticket-options-plugin__error error-banner">
         <?= $errors[ 'err' ] ?>
         
      </div>

      <?php endif ?>
<?php
/*
   <?php if( $a_agents ):?>
      <div class="ticket-options-plugin__agents">
         
         <?php foreach( $a_agents as $agent ):?>

         <div class="ticket-options-plugin__agent">
            <i class="icon-remove-circle" data-staff_id="<?= $agent[ 'staff_id' ] ?>"
            title="Remove <?= $agent[ 'name' ] ?>"></i>
            
            <img class="ticket-options-plugin__agent-avatar" src="<?= $agent[ 'avatar' ] ?>"/>

            <div>

               <div class="ticket-options-plugin__agent-name ticket-options-plugin__agent-detail">
               <?= $agent[ 'name' ] ?>
                  
               </div>


               <div class="ticket-options-plugin__agent-email ticket-options-plugin__agent-detail">
               <?= $agent[ 'email' ] ?>
                  
               </div>


               <div class="ticket-options-plugin__agent-phone ticket-options-plugin__agent-detail">
               <?= $agent[ 'mobile' ] ?$agent[ 'mobile' ] :$agent[ 'phone' ] ?>
                  
               </div>


               <div class="ticket-options-plugin__agent-dept ticket-options-plugin__agent-detail">
               <?= $agent[ 'department' ] ?>
                  
               </div>

            </div>
         </div>

         <?php endforeach ?>

      </div>
   <?php endif ?>
   */
?>


</div>

<?php
TicketOptionsPlugin::add_javascript_src( 'ajax.php/ticket_options/static/app/ticket-view-agents.js' );
?>