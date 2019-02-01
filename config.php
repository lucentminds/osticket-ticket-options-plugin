<?php

require_once(INCLUDE_DIR.'/class.plugin.php');
require_once(INCLUDE_DIR.'/class.forms.php');

class TicketOptionsConfig extends PluginConfig
{
   
   function getOptions() 
   {
      return array(
         'enable_details_tab' => new BooleanField(array(
            'id' => 'enable_details_tab',
            'label' => 'Enable details tab',
            'required' => false,
            'hint' => __( 'Display the ticket details in a different tab on the ticket along with "Ticket Thread" and "Tasks".' ),
            'configuration' => array(
               'desc' => 'enabled'
            )
         )),

         'sort_staff_thread_desc' => new BooleanField(array(
            'id' => 'sort_staff_thread_desc',
            'label' => 'Sort staff thread descending',
            'required' => false,
            'hint' => __( 'Display the ticket comments in descending order from newest to oldest.' ),
            'configuration' => array(
               'desc' => 'enabled'
            )
         )),

         'enable_agent_include' => new BooleanField(array(
            'id' => 'enable_agent_include',
            'label' => 'Enable agent respsponse e-mails.',
            'required' => false,
            'hint' => __( 'Allow agents to be added to a ticket and be copied on ticket response e-mails.' ),
            'configuration' => array(
               'desc' => 'enabled'
            )
         )),

         'show_all_ticket_columns' => new BooleanField(array(
            'id' => 'show_all_ticket_columns',
            'label' => 'Show all columns in ticket list.',
            'required' => false,
            'hint' => __( 'Show all columns in ticket list including priority and status.' ),
            'configuration' => array(
               'desc' => 'enabled'
            )
         ))

         
      );
   }// /getOptions()


    /**
    * Pre-save hook to check configuration for errors (other than obvious
    * validation errors) prior to saving. Add an error to the errors list
    * or return boolean FALSE if the config commit should be aborted.
    */
   function pre_save($config, &$errors) {
      global $plugin, $msg;
      
      $plugin->on_config_change( $config, $errors );

      if (!$errors) {
         $msg = 'Configuration updated successfully'; // This is the default, and doesn't need to be set.
         return true;
      }
      
      return false;
   }
}