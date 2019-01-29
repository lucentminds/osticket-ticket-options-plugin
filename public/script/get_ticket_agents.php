<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

   header( 'Content-Type: application/json' );

   function reply_error( $n_code, $c_message )
   {
      $a_response = array(
         'error' => array(
            'code' => $n_code,
            'message' => $c_message
         )
      );

      echo json_encode( $a_response );

   }// /reply_error()

   function reply_result( $a_agents )
   {
      $a_response = array(
         'agents' => $a_agents,
         'error' => null,
         'result' => 'ok'
      );

      echo json_encode( $a_response );

   }// /reply_result()

   function process_request()
   {
      if( !isset( $_GET[ 'ticket_id' ] ) )
      {
         return reply_error( 400, 'Ticket ID is missing or invalid.' );
      }

      $c_ticket_id = $_GET[ 'ticket_id' ];

      require_once( INCLUDE_DIR.'class.ticket.php' );

      $o_ticket = Ticket::lookupByNumber( $c_ticket_id );

      if( !$o_ticket )
      {
         return reply_error( 400, 'Ticket #'.$c_ticket_id.' not found.' );
      }

      $o_included = new TicketOptionsPlugin_AgentInclude( $o_ticket );
      $a_agents = $o_included->fetch_agents( $errors );

      if( $errors )
      {
         return reply_error( 500, $errors[ 'err' ] );
      }

      return reply_result( $a_agents );

   }// /process_request()

   process_request();

?>