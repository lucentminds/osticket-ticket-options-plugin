<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

/**
 * Before posting a request to this script, the caller needs to pass a csrf 
 * token in a parameter called "__CSRFToken__". The osticket application 
 * requires this. You can generate the token by calling 
 * $ost->getCSRF()->getToken() in class.osticket.php.
 */

   // error_reporting(E_ALL);
   // ini_set('display_errors', 1);


   header( 'Content-Type: application/json' );
   process_request();

   function process_request()
   {
      if( !isset( $_POST[ 'ticket_id' ] ) )
      {
         return reply_error( 400, 'Ticket ID is missing or invalid.' );
      }

      if( !isset( $_POST[ 'staff_id' ] ) )
      {
         return reply_error( 400, 'Staff ID is missing or invalid.' );
      }

      $c_ticket_id = $_POST[ 'ticket_id' ];
      $n_staff_id = (int) $_POST[ 'staff_id' ];


      if( $_POST[ 'staff_id' ] < 1 )
      {
         return reply_error( 400, 'Staff ID is not a valid integer.' );
      }

      require_once( INCLUDE_DIR.'class.ticket.php' );

      $o_ticket = Ticket::lookupByNumber( $c_ticket_id );

      if( !$o_ticket )
      {
         return reply_error( 400, 'Ticket #'.$c_ticket_id.' not found.' );
      }

      $o_included = new TicketOptionsPlugin_AgentInclude( $o_ticket );

      if( $errors )
      {
         return reply_error( 500, $errors[ 'err' ] );
      }

      
      $n_added = $o_included->add_agent( $n_staff_id, $errors );

      if( $errors )
      {
         return reply_error( 500, $errors[ 'err' ] );
      }


      return reply_result( $n_added );

   }// /process_request()

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

   function reply_result( $n_added )
   {
      $a_response = array(
         'added' => $n_added,
         'error' => null,
         'result' => 'ok'
      );

      echo json_encode( $a_response );

   }// /reply_result()


?>