<?php

require_once(INCLUDE_DIR.'/class.plugin.php');
require_once(INCLUDE_DIR.'/class.forms.php');

class TicketOptionsConfig extends PluginConfig
{
   
   function getOptions() 
   {
      return [
         'enable_details_tab' => new BooleanField([
            'id' => 'enable_details_tab',
            'label' => 'Enable details tab',
            'required' => false,
            'hint' => __( 'Display the ticket details in a different tab on the ticket along with "Ticket Thread" and "Tasks".' ),
            'configuration' => [
               'desc' => 'enabled',
            ],
         ]),

         'sort_staff_thread_desc' => new BooleanField([
            'id' => 'sort_staff_thread_desc',
            'label' => 'Sort staff thread descending',
            'required' => false,
            'hint' => __( 'Display the ticket comments in descending order from newest to oldest.' ),
            'configuration' => [
               'desc' => 'enabled',
            ],
         ]),

         'enable_agent_context_email' => new BooleanField([
            'id' => 'enable_agent_context_email',
            'label' => 'Enable agent response e-mails',
            'required' => false,
            'hint' => __( 'Allow agent/staff collaborators see content based on the "scp" view of the ticket when copied on response e-mails.' ),
            'configuration' => [
               'desc' => 'enabled',
            ],
         ]),

         'show_all_ticket_columns' => new BooleanField([
            'id' => 'show_all_ticket_columns',
            'label' => 'Show all columns in ticket list',
            'required' => false,
            'hint' => __( 'Show all columns in ticket list including priority and status. Also enables wide view of ticket queues.' ),
            'configuration' => [
               'desc' => 'enabled',
            ],
         ]),

      ];
   }// /getOptions()


    /**
    * Pre-save hook to check configuration for errors (other than obvious
    * validation errors) prior to saving. Add an error to the errors list
    * or return boolean FALSE if the config commit should be aborted.
    */
    function pre_save(&$config, &$errors) {
      global $plugin, $msg;
      
      $plugin->on_config_change( $config, $errors );

      if (!$errors) {
         $msg = 'Configuration updated successfully'; // This is the default, and doesn't need to be set.
         return true;
      }
      
      return false;
   }
}