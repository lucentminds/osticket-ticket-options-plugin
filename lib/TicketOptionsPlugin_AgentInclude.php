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

         array_push( $a_included_staff, self::staff_factory( $o_staff_member ) );
         
         
         //echo '<pre>', print_r( $a_included_staff, 1 ), '</pre>';
      }// /foreach()

      return $a_included_staff;

   }// /fetch_agents()


   public function add_agent( $n_staff_id, &$errors )
   {
      $n_ticket_id = $this->_ticket->getNumber();
      $n_staff_id = (int) $n_staff_id;

      if( $_POST[ 'staff_id' ] < 1 )
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Staff ID is not a valid integer.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_AgentInclude add_agent 101', $errors[ 'err' ] );
         return false;
      }

      // Fetch the ticket agent record if any.
      $c_sql = 'SELECT staff_id_list FROM `'.AIP_TICKET_AGENT_TABLE.'` WHERE ticket_id='.$n_ticket_id.';';
      $o_result = db_query( $c_sql );

      if( !$o_result )
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Failed to query table "'.AIP_TICKET_AGENT_TABLE.'" for agents.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_AgentInclude add_agent 102', $errors[ 'err' ] );
         return false;
      }



      if( db_num_rows( $o_result ) > 0 )
      {      
         $a_result = db_fetch_array( $o_result );
         //echo '<pre>', print_r( $a_result, 1 ), '</pre>';

         $a_included_staff = explode( ' ', $a_result[ 'staff_id_list' ] );
      }
      else
      {
         // There are no included agents on this ticket.
         $a_included_staff = array();
      }

      array_push( $a_included_staff, $n_staff_id );
      sort( $a_included_staff, SORT_NUMERIC );
      $c_staff_id_list = join( ' ', $a_included_staff );

      // Insert or update the staff ID.
      $c_sql = 'INSERT INTO `'.AIP_TICKET_AGENT_TABLE.'` (ticket_id,staff_id_list)'
         . ' VALUES ('.$n_ticket_id.',"'.$c_staff_id_list.'")'
         . ' ON DUPLICATE KEY UPDATE `staff_id_list`="'.$c_staff_id_list.'";';

      //exit( $c_sql );
      $o_result = db_query( $c_sql );

      if( !$o_result )
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Failed to update table "'.AIP_TICKET_AGENT_TABLE.'" with agents.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_AgentInclude add_agent 103', $errors[ 'err' ] );
         return false;
      }

      return $c_staff_id_list;

   }// /add_agent()


   public function remove_agent( $n_staff_id, &$errors )
   {
      $n_ticket_id = $this->_ticket->getNumber();
      $n_staff_id = (int) $n_staff_id;
      $c_staff_id_list = '';

      if( $_POST[ 'staff_id' ] < 1 )
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Staff ID is not a valid integer.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_AgentInclude remove_agent 101', $errors[ 'err' ] );
         return false;
      }

      // Fetch the ticket agent record if any.
      $c_sql = 'SELECT staff_id_list FROM `'.AIP_TICKET_AGENT_TABLE.'` WHERE ticket_id='.$n_ticket_id.';';
      $o_result = db_query( $c_sql );

      if( !$o_result )
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Failed to query table "'.AIP_TICKET_AGENT_TABLE.'" for agents.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_AgentInclude remove_agent 102', $errors[ 'err' ] );
         return false;
      }



      if( db_num_rows( $o_result ) < 1 )
      {
         // There are no included agents on this ticket.
         return $c_staff_id_list;
      }

      $a_result = db_fetch_array( $o_result );
      //echo '<pre>', print_r( $a_result, 1 ), '</pre>';

      $a_included_staff = explode( ' ', $a_result[ 'staff_id_list' ] );

      // Get the index of the staff ID being removed.
      $n_index = array_search( $n_staff_id, $a_included_staff );

      if( $n_index === false )
      {
         // This staff ID does not exist in the list.
         return $c_staff_id_list;
      }


      array_splice( $a_included_staff, $n_index, 1 );
      sort( $a_included_staff, SORT_NUMERIC );
      $c_staff_id_list = join( ' ', $a_included_staff );


      // Insert or update the staff ID.
      $c_sql = 'INSERT INTO `'.AIP_TICKET_AGENT_TABLE.'` (ticket_id,staff_id_list)'
         . ' VALUES ('.$n_ticket_id.',"'.$c_staff_id_list.'")'
         . ' ON DUPLICATE KEY UPDATE `staff_id_list`="'.$c_staff_id_list.'";';

      //exit( $c_sql );
      $o_result = db_query( $c_sql );

      if( !$o_result )
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Failed to update table "'.AIP_TICKET_AGENT_TABLE.'" with agents.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_AgentInclude remove_agent 103', $errors[ 'err' ] );
         return false;
      }

      return $c_staff_id_list;

   }// /remove_agent()



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
         array_push( $a_staff_list, self::staff_factory( $o_staff_member ) );
         
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

   public static function staff_factory( $o_staff_member )
   {
      return array(
         'staff_id' => $o_staff_member->getId(),
         'department' => $o_staff_member->getDept()->getName(),
         'role' => $o_staff_member->getRole()->getName(),
         'username' => $o_staff_member->getUserName(),
         'name' => $o_staff_member->getName()->name,
         'email' => $o_staff_member->getEmail(),
         'phone' => $o_staff_member->getVar( 'phone' ),
         'mobile' => $o_staff_member->getVar( 'mobile' ),
         'avatar' => $o_staff_member->getAvatar()->getUrl()
      );

   }// /staff_factory()

}// /class TicketOptionsPlugin_AgentInclude