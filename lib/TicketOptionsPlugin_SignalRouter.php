<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

class TicketOptionsPlugin_SignalRouter
{

   public function __construct()
   {
   }// /__construct()

   public function route_ticket_reply_sent( $something, $a_event )
   {
      error_log( 'route_ticket_reply_sent' );
      file_put_contents( '/application/route_ticket_reply_sent.txt', 
         print_r( $a_event, 1 ) );
   }// route_ticket_reply_sent()

   public function route_message_alert_sent( $something, $a_event )
   {
      global $errors;


      $o_ticket = $a_event[ 'ticket' ];
      $agent_include = new TicketOptionsPlugin_AgentInclude( $o_ticket );
      $a_agents = $agent_include->fetch_agents( $errors );


      if( $errors )
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Failed to notify included agents.';
         return false;
      }

      $c_msg = $a_event[ 'message_source' ][ 'body' ];
      $c_subject = $a_event[ 'message_source' ][ 'subj' ];
      $a_sent = $a_event[ 'sent_list' ];
      $options = $a_event[ 'options' ];
      $emailer = $a_event[ 'emailer' ];
      $c_log_msg = $a_event['response'];
      $c_log_msg = strip_tags( $c_log_msg );
      $c_log_msg = substr( $c_log_msg, 0, 50 );

      // Loop over each agent and send them an e-mail.
      foreach( $a_agents as $a_agent )
      {

         if( !in_array( $a_agent[ 'email' ], $a_sent ) )
         {
            // This agent wasn't sent an e-mail yet.
            $emailer->sendAlert( $a_agent[ 'email' ], $c_subject, $c_msg, null, $options );
            TOPLog::log( 'info', 'sent to: '.$a_agent[ 'email' ].' subject: '.$c_subject.' msg: '.$c_log_msg );
         }

      }// /foreach()

   }// route_message_alert_sent()

   public function log( $n_priority, $c_title, $c_msg, $l_alert=false, $l_force=false )
   {
      global $ost;

      if( !$this->_ost )
      {
         $this->_ost = $ost ?$ost :new osTicket();
      }
      
      return $this->_ost->log( $n_priority, $c_title, $c_msg, $l_alert, $l_force );
      
   }// log()

}// /class TicketOptionsPlugin_SignalRouter