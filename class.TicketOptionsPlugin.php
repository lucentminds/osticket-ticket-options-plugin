<?php


// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Determines the full local path to to this plugin dir.
define( 'AIP_PATH', __DIR__ );

// Determines the relative local path to this plugin dir.
define( 'AIP_PATH_RELATIVE', str_replace( ROOT_DIR.'include/', '', AIP_PATH ) );

// Determines the full local path to the ticket-view.inc.php in production.
define( 'AIP_PATH_VIEW', __DIR__. '/view' );

// Determines the full local path to the lib in production.
define( 'AIP_PATH_LIB', __DIR__. '/lib' );

// Determines the full local path to the ticket-view.inc.php in production.
define( 'AIP_PATH_TICKET_VIEW', INCLUDE_DIR.'staff/ticket-view.inc.php' );

// Determines the full local path to the ticket-view.inc.php in production.
define( 'AIP_PATH_TICKET_VIEW_REPLACED', __DIR__.'/replaced/ticket-view.inc.php' );

define( 'AIP_TICKET_AGENT_TABLE', TABLE_PREFIX.'plugin_ticketoptions_ticket_agent' );

//exit( INCLUDE_DIR . 'staff/staff.inc.php' );
//public_html_56/ost/scp/admin.inc.php
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');



// Import the Application class
require_once(INCLUDE_DIR . 'class.app.php');

require_once(INCLUDE_DIR . 'class.osticket.php');
require_once('config.php');
require_once( AIP_PATH_LIB.'/TicketOptionsPlugin_AgentInclude.php' );

class TicketOptionsPlugin extends Plugin
{
   var $config_class = 'TicketOptionsConfig';

   protected $_ost;

   protected $_included;

   public static function render_included_agents()
   {
      if( !self::agent_include_enabled() )
      {
         return;
      }

      include( AIP_PATH_VIEW.'/ticket-view-agents.php' );
   }// /render_included_agents()

   function bootstrap()
   {

      $o_config = $this->getConfig();

      $this->log( LOG_DEBUG, 'TicketOptionsPlugin bootstrap', 'Hello, World!' );


      // Listen for ticket responses being e-mailed out.
      if( $o_config->get( 'enable_agent_include' ) == '1' )
      {
         //Signal::connect('user_reply.sent', array($this, 'on_user_reply') );
         error_log( 'connect before' );
         require_once( AIP_PATH_LIB.'/TicketOptionsPlugin_ApiController.php' );
         Signal::connect( 'ajax.scp', array ( 'TicketOptionsPlugin_ApiController', 'route_dispatch' ) );
         error_log( 'connect after' );
      }

      
   }// bootstrap()

   public function log( $n_priority, $c_title, $c_msg, $l_alert=false, $l_force=false )
   {
      global $ost;

      if( !$this->_ost )
      {
         $this->_ost = $ost ?$ost :new osTicket();
      }
      
      return $this->_ost->log( $n_priority, $c_title, $c_msg, $l_alert, $l_force );
      
   }// log()

   function enable()
   {
      global $errors;

      // Make sure we've replaced files.
      if( $this->replace_original_files( $errors ) === false )
      {
         return false;
      }


      require_once( AIP_PATH.'/install/TicketOptionsPlugin_Installer.php' );

      // Determines the db installer.
      $installer = new TicketOptionsPlugin_Installer();

      // Make sure we've installed the db tables.
      if( $installer->install( $errors ) === false )
      {
         return false;
      }

      return parent::enable();

   }// /enable()

   function disable()
   {
      global $errors;

      // Make sure we've restored files we've replaced.
      if( $this->restore_original_files( $errors ) === false )
      {
         return false;
      }


      require_once( AIP_PATH.'/install/TicketOptionsPlugin_Installer.php' );

      // Determines the db installer.
      $installer = new TicketOptionsPlugin_Installer();

      // Make sure we've renived the db tables.
      if( $installer->remove( $errors ) === false )
      {
         return false;
      }


      // $errors[ 'err' ] = 'I can\'t do that right now Dave.';
      // $this->log( LOG_ERR, 'TicketOptionsPlugin disable fail', 'I can\'t do that right now Dave.' );
      // return false;

      return parent::disable();
   }// /disable()

   /** 
    * This method gets called by /include/class.plugin.php before uninstalling 
    * the plugin. Return `true` if successful.
    */
   function pre_uninstall( &$errors )
   {

      // Make sure we've restored files we've changed.
      if( $this->restore_original_files( $errors ) === false )
      {
         return false;
      }

      return true;
   }// /pre_uninstall()


   protected function is_replaced_file( $c_file_path )
   {
      if( !file_exists( $c_file_path ) )
      {
         return false;
      }
      
      // Load file contents into an array of lines.
      $a_file = file( $c_file_path );

      if( preg_match( '/<!-- Replaced by TicketOptionsPlugin -->/', $a_file[0] ) )
      {
         // Found an instance of the string "<!-- Replaced by TicketOptionsPlugin -->" in this file.
         return true;
      }

      return false;
      
   }// /is_replaced_file()

   /** 
    * This method gets called by /include/class.plugin.php before uninstalling 
    * the plugin. Return `true` if successful.
    */
   protected function replace_original_files( &$errors )
   {
      $c_path_replacer = AIP_PATH.'/replace/ticket-view.inc.php';

      // $errors[ 'err' ] = 'I can\'t do that right now Dave.';
      // $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 500', $errors[ 'err' ] );
      // return false;


      if( !file_exists( AIP_PATH_TICKET_VIEW ) )
      {
         exit( 'a' );
         // The page we want to replace doesn't exist.
         $errors[ 'err' ] = 'Path "'.AIP_PATH_TICKET_VIEW.'" does not exist.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 101', $errors[ 'err' ] );
         return false;
      }


      if( !file_exists( $c_path_replacer ) )
      {
         exit( 'b' );
         // The page we want to use as a replacement doesn't exist!?
         $errors[ 'err' ] = 'Path "'.$c_path_replacer.'" does not exist.';
         $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 102', $errors[ 'err' ] );
         return false;
      }

      // Copy the original file to the "replaced" directory.
      if( !copy( AIP_PATH_TICKET_VIEW, AIP_PATH_TICKET_VIEW_REPLACED ) )
      {
         // Get the copy error.
         $a_error = error_get_last();

         if( $a_error )
         {
            $errors[ 'err' ] = $a_error[ 'message' ];
         }
         else
         {
            $errors[ 'err' ] = 'Failed to copy original "'.AIP_PATH_TICKET_VIEW.'".';
         }
         
         $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 103', $errors[ 'err' ] );
         return false;
      }

      // Copy the replacement file over the original.
      if( !copy( $c_path_replacer, AIP_PATH_TICKET_VIEW ) )
      {
         // Get the copy error.
         $a_error = error_get_last();

         if( $a_error )
         {
            $errors[ 'err' ] = $a_error[ 'message' ];
         }
         else
         {
            $errors[ 'err' ] = 'Failed to copy replacer "'.$c_path_replacer.'".';
         }
         
         $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 104', $errors[ 'err' ] );
         return false;
      }

      // $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 500', 'I can\'t do that right now Dave.' );
      // return false;

      // // Determines the full local path to the ticket-view.inc.php in production.
      // define( 'AIP_PATH_TICKET_VIEW', INCLUDE_DIR.'staff/ticket-view.inc.php' );

      // // Determines the full local path to the ticket-view.inc.php in production.
      // define( 'AIP_PATH_TICKET_VIEW_REPLACED', __DIR__.'replaced/ticket-view.inc.php' );
   }// /replace_original_files()

   /** 
    * This method gets called by /include/class.plugin.php before uninstalling 
    * the plugin. Return `true` if successful.
    */
   protected function restore_original_files( &$errors )
   {

      // Do not bother with this if the original file is not in our "replaced" directory.
      if( file_exists( AIP_PATH_TICKET_VIEW_REPLACED ) )
      {
         /** 
          * A copy of original page we replaced is currently in the "replaced"
          * directory. We need to copy it back to it's original location and 
          * then delete the one in the "replaced" directory.
          */

         if( !$this->is_replaced_file( AIP_PATH_TICKET_VIEW ) )
         {
            // The file at AIP_PATH_TICKET_VIEW doesn't look like one we put there.
            $errors[ 'err' ] = 'Failed to confirm replaced "'.AIP_PATH_TICKET_VIEW.'".';
            $this->log( LOG_ERR, 'TicketOptionsPlugin restore fail 101', $errors[ 'err' ] );
            return false;
         }

         // Copy the original file back from the "replaced" directory.
         if( !copy( AIP_PATH_TICKET_VIEW_REPLACED, AIP_PATH_TICKET_VIEW ) )
         {
            $errors[ 'err' ] = 'Failed to restore original "'.AIP_PATH_TICKET_VIEW.'".';
            $this->log( LOG_ERR, 'TicketOptionsPlugin restore fail 102', $errors[ 'err' ] );
            return false;
         }

         // Delete the file in the "replaced" directory.
         if( !unlink( AIP_PATH_TICKET_VIEW_REPLACED ) )
         {
            $errors[ 'err' ] = 'Failed to delete original backup "'.AIP_PATH_TICKET_VIEW_REPLACED.'".';
            $this->log( LOG_ERR, 'TicketOptionsPlugin restore fail 103', $errors[ 'err' ] );
            return false;
         }

      }
      
      return true;
   }// /restore_original_files()

   // An e-mail has just been sent to the end user.
   function on_user_reply( $a_email_settings )
   {

      $o_dept = $ticket->getDept();
      $o_tpl = $o_dept->getTemplate();
      $a_tpl = $o_tpl->getReplyMsgTemplate();

      $o_email = $a_email_settings[ 'email' ];

      $email->send('scott@personalva.com', 'XXX--'.$a_tpl['subj'], $a_tpl['body'], $attachments,
            $options);
      echo '<pre>', print_r( $email, 1 );
      $this->log( LOG_DEBUG, 'AgentIncludePlugin', 'Posted reply sent to owner agent.', false, true );
   }// /on_user_reply()

   function on_config_change( $a_changes, &$errors )
   {
      $o_config = $this->getConfig();

      $this->set_details_tab( $a_changes[ 'enable_details_tab' ], $errors );

      if( !$errors )
      {
         return true;
      }

      return false;
   }// /on_config_change()

   function set_details_tab( $l_new_value, &$errors )
   {
      $o_config = $this->getConfig();
      $l_old_value = $o_config->get( 'enable_details_tab' ) == '1';

      if( $l_old_value === $l_new_value )
      {
         return true;
      }

      // Do change stuff.
      // if( $this->isActive() )
      // {
      //    $errors[ 'err' ] = 'I can\'t do that right now Dave.';
      //    return false;
      // }

      return true;
   }// /set_details_tab()

   // Returns the current instance of this class.
   public static function instance()
   {
      global $ost;
      return $ost->plugins->getInstance( AIP_PATH_RELATIVE );
   }// /instance()

   // Returns a current config value.
   public static function get_value( $c_value )
   {
      return self::instance()->getConfig()->get( $c_value );
   }// /get_value()

   // Returns `true` if the details tab is enabled.
   public static function details_tab_enabled()
   {
      return self::get_value( 'enable_details_tab' ) == '1';
   }// /details_tab_enabled()

   // Returns `true` if the details tab is enabled.
   public static function agent_include_enabled()
   {
      return self::get_value( 'enable_agent_include' ) == '1';
   }// /agent_include_enabled()

   // Returns `true` if the details tab is enabled.
   public static function staff_thread_order()
   {
      return self::get_value( 'sort_staff_thread_desc' ) ?'desc' :'asc';
   }// /staff_thread_order()

   // Returns the current instance of this class.
   public static function resolve_view( $c_filename )
   {
      return AIP_PATH_VIEW.'/'.$c_filename;
   }// /resolve_view()


}// /class TicketOptionsPlugin