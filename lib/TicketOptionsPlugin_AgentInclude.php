<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

class TicketOptionsPlugin_AgentInclude
{
   protected $_ticket;

   public function __construct( $o_ticket )
   {
      $this->_ticket = $o_ticket;
   }// /__construct()

   public function log( $n_priority, $c_title, $c_msg, $l_alert=false, $l_force=false )
   {
      global $ost;

      if( !$this->_ost )
      {
         $this->_ost = $ost ?$ost :new osTicket();
      }
      
      return $this->_ost->log( $n_priority, $c_title, $c_msg, $l_alert, $l_force );
      
   }// log()


   public function fetch_agents( &$errors )
   {
      $n_ticket_id = $this->_ticket->getNumber();

      // Fetch the ticket agent record if any.
      $c_sql = 'SELECT staff_id_list FROM `'.AIP_TICKET_AGENT_TABLE.'` WHERE ticket_id='.$n_ticket_id.';';
      $o_result = db_query( $c_sql );

      if( !$o_result )
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Failed to query table "'.AIP_TICKET_AGENT_TABLE.'" for agents.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_AgentInclude get_agents 101', $errors[ 'err' ] );
         return false;
      }



      if( db_num_rows( $o_result ) < 1 )
      {
         // There are no included agents on this ticket.
         return array();
      }
      
      $a_result = db_fetch_array( $o_result );
      //echo '<pre>', print_r( $a_result, 1 ), '</pre>';

      $a_id_list = explode( ' ', $a_result[ 'staff_id_list' ] );
      $a_included_staff = array();

      foreach( $a_id_list as $c_id )
      {
         $o_staff_member = Staff::lookup( array( 'staff_id' => $c_id ) );

         if( !$o_staff_member )
         {
            // Not sure yet if I should skip if the staff member isn't found.
            $errors[ 'err' ] = 'Staff member "'.$c_id.'" not found.';
            $this->log( LOG_ERR, 'TicketOptionsPlugin_AgentInclude get_agents 101', $errors[ 'err' ] );
            return false;
         }

         array_push( $a_included_staff, array(
            'staff_id' => $o_staff_member->getId(),
            'department' => $o_staff_member->getDept()->getName(),
            'role' => $o_staff_member->getRole()->getName(),
            'username' => $o_staff_member->getUserName(),
            'name' => $o_staff_member->getName()->name,
            'email' => $o_staff_member->getEmail(),
            'phone' => $o_staff_member->getVar( 'phone' ),
            'mobile' => $o_staff_member->getVar( 'mobile' ),
            'avatar' => $o_staff_member->getAvatar()->getUrl()
         ) );
         
         
         //echo '<pre>', print_r( $a_included_staff, 1 ), '</pre>';
      }// /foreach()

      return $a_included_staff;

   }// /fetch_agents()



   public function search_agents( $c_search, &$errors )
   {

      require_once( INCLUDE_DIR.'class.staff.php' );
      
      $o_query_set = Staff::objects();
      $o_query_set->filter(array(
         'isactive' => 1,
      ));
      $o_query_set = Staff::nsort( $o_query_set );

      $a_staff_list = array();
      foreach ($o_query_set as $o_staff_member ) 
      {
         array_push( $a_staff_list, array(
            'staff_id' => $o_staff_member->getId(),
            'department' => $o_staff_member->getDept()->getName(),
            'role' => $o_staff_member->getRole()->getName(),
            'username' => $o_staff_member->getUserName(),
            'name' => $o_staff_member->getName()->name,
            'email' => $o_staff_member->getEmail(),
            'phone' => $o_staff_member->getVar( 'phone' ),
            'mobile' => $o_staff_member->getVar( 'mobile' ),
            'avatar' => $o_staff_member->getAvatar()->getUrl()
         ) );
      }// /foreach()

      $a_results = array();

      switch( true ) {
      case is_numeric( $c_search ):
         // Fetch the agent by ID if any.
         $n_staff_id = (int) $c_search;
         
         foreach( $a_staff_list as $a_staff )
         {
            if( $a_staff[ 'staff_id' ] == $n_staff_id )
            {
               array_push( $a_results, $a_staff );
            }

         }// /foreach()
      break;

      default:
         // Fetch the agent by name or username if any.         
         foreach( $a_staff_list as $a_staff )
         {
            $c_name = $a_staff[ 'name' ].' '.$a_staff[ 'username' ];

            if( preg_match( '/'.$c_search.'/i', $c_name ) )
            {
               array_push( $a_results, $a_staff );
            }

         }// /foreach()
      break;

      }// /switch()
      
      return $a_results;

   }// /search_agents()

}// /class TicketOptionsPlugin_AgentInclude