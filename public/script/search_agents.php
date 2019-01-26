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


      if( !isset( $_GET[ 'q' ] ) )
      {
         return reply_error( 400, 'Staff search string is missing or invalid.' );
      }

      $c_query = $_GET[ 'q' ];
      $o_included = new TicketOptionsPlugin_AgentInclude( null );
      $a_agents = $o_included->search_agents( $c_query, $errors );


      if( $errors )
      {
         return reply_error( $errors[ 'err' ] );
      }

      return reply_result( $a_agents );

   }// /process_request()

   process_request();

?>