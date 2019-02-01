<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

class TicketOptionsPlugin_TicketAgent
{
   protected $_ticket_id;

   public function __construct( $n_ticket_id )
   {
      $this->_ticket_id = $n_ticket_id;
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

   protected static function log_static( $n_priority, $c_title, $c_msg, $l_alert=false, $l_force=false )
   {
      $ticket_agent = new TicketOptionsPlugin_TicketAgent( 0 );
      $ticket_agent->log(  $n_priority, $c_title, $c_msg, $l_alert, $l_force  );
   }// /protected()


   public function fetch( &$errors )
   {
      $n_ticket_id = $this->_ticket_id;

      // Fetch the ticket agent record if any.
      $c_sql = 'SELECT staff_id_list FROM `'.AIP_TICKET_AGENT_TABLE.'` WHERE ticket_id='.$n_ticket_id.';';
      $o_result = db_query( $c_sql );

      if( !$o_result )
      {
         $errors[ 'err' ] = 'Failed to query table "'.AIP_TICKET_AGENT_TABLE.'" for agents.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_AgentInclude fetch 101', $errors[ 'err' ] );
         return null;
      }



      if( db_num_rows( $o_result ) < 1 )
      {
         // There are no included agents on this ticket.
         return array();
      }
      
      $a_result = db_fetch_array( $o_result );



      if( strlen( $a_result[ 'staff_id_list' ] ) < 1 )
      {
         // There are no included agents on this ticket.
         return array();
      }

      $a_id_list = explode( ' ', $a_result[ 'staff_id_list' ] );
      //$a_included_staff = self::lookup_staff( $a_id_list, $errors );

      return $a_id_list;

   }// /fetch()


   public function set_list( $c_staff_id_list, &$errors )
   {
      $n_ticket_id = $this->_ticket->getNumber();

      // Insert or update the staff ID.
      $c_sql = 'INSERT INTO `'.AIP_TICKET_AGENT_TABLE.'` (ticket_id,staff_id_list)'
         . ' VALUES ('.$n_ticket_id.',"'.$c_staff_id_list.'")'
         . ' ON DUPLICATE KEY UPDATE `staff_id_list`="'.$c_staff_id_list.'";';

      $o_result = db_query( $c_sql );

      if( !$o_result )
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Failed to update table "'.AIP_TICKET_AGENT_TABLE.'".';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_TicketAgent set_list 101', $errors[ 'err' ] );
         return null;
      }

      return $n_added;

   }// /set_list()

   public static function lookup_staff( $a_id_list, &$errors )
   {
      $a_expanded_list = array();

      foreach( $a_id_list as $c_id )
      {
         $o_staff_member = Staff::lookup( array( 'staff_id' => $c_id ) );

         if( !$o_staff_member )
         {
            // Not sure yet if I should skip if the staff member isn't found.
            $errors[ 'err' ] = 'Staff member "'.$c_id.'" not found.';
            self::log_static( LOG_ERR, 'TicketOptionsPlugin_TicketAgent lookup_staff 101', $errors[ 'err' ] );
            return null;
         }

         array_push( $a_expanded_list, self::staff_factory( $o_staff_member ) );
         
      }// /foreach()
      
      return $a_expanded_list;
   }// /lookup_staff()

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

}// /class TicketOptionsPlugin_TicketAgent