<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

require_once 'class.setup.php';

class TicketOptionsPlugin_Installer extends \SetupWizard 
{

   public function log( $n_priority, $c_title, $c_msg, $l_alert=false, $l_force=false )
   {
      global $ost;

      if( !$this->_ost )
      {
         $this->_ost = $ost ?$ost :new osTicket();
      }
      
      return $this->_ost->log( $n_priority, $c_title, $c_msg, $l_alert, $l_force );
      
   }// log()
   
   public function install( &$errors )
   {
      $c_path_schema_file = TICKET_OPTIONS_PLUGIN_PATH.'/install/install_ticket_options.sql';
      return $this->execute_schema_file( $c_path_schema_file, $errors );

   }// /install()

   public function remove( &$errors )
   {
      $c_path_schema_file = TICKET_OPTIONS_PLUGIN_PATH.'/install/delete_ticket_options.sql';
      return $this->execute_schema_file( $c_path_schema_file, $errors );

   }// /remove()
   
   protected function execute_schema_file( $c_path_schema_file, &$errors ) {


      // Last minute checks.
      if ( !file_exists ( $c_path_schema_file ) ) 
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Path "'.$c_path_schema_file.'" does not exist.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_Installer execute_schema_file 101', $errors[ 'err' ] );
         return false;


         // $this->error = 'File Access Error!';
      } 

      if ( !$this->load_sql_file( $c_path_schema_file, TABLE_PREFIX, true, true ))
      {
         // The sql schema file doesn't exist.
         $errors[ 'err' ] = 'Path "'.$c_path_schema_file.'" failed to parse.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin_Installer execute_schema_file 102', $errors[ 'err' ] );
         return false;
      }
      
      return true;

   }// /execute_schema_file()

}// /TicketOptionsPlugin_Installer