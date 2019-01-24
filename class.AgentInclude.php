<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

class TicketOptionsPlugin_AgentInclude
{
   public function __construct()
   {

   }// /__construct()


   public function get_agents()
   {
      return array(
         [
            'name' => 'George Jetson'
         ],

         [
            'name' => 'Fred Flintstone'
         ],

         [
            'name' => 'El Kabong'
         ],
      );
   }
}// /class TicketOptionsPlugin_AgentInclude