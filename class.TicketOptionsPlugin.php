<?php


// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Determines the full local path to this plugin dir.
define( 'AIP_PATH', __DIR__ );

// Determines the full local path to this log dir.
define( 'AIP_PATH_LOG', __DIR__. '/log' );

// Determines the relative local path to this plugin dir.
define( 'AIP_PATH_RELATIVE', str_replace( ROOT_DIR.'include/', '', AIP_PATH ) );

// Determines the full local path to the ticket-view.inc.php in production.
define( 'AIP_PATH_INCLUDE', __DIR__. '/include' );

// Determines the full local path to the public files.
define( 'AIP_PATH_PUBLIC', __DIR__. '/public' );

// Determines the full local path to the lib in production.
define( 'AIP_PATH_LIB', __DIR__. '/lib' );

// Determines the full local path to the ticket-view.inc.php in production.
define( 'AIP_PATH_TICKET_VIEW', INCLUDE_DIR.'staff/ticket-view.inc.php' );

// Determines the full local path to the ticket-view.inc.php in production.
define( 'AIP_PATH_TICKET_VIEW_REPLACED', __DIR__.'/replaced/ticket-view.inc.php' );

define( 'AIP_TICKET_AGENT_TABLE', TABLE_PREFIX.'plugin_ticketoptions_ticket_agent' );

// Determines the full path to the "/scp" directory.
define('AIP_SCP_DIR', ROOT_DIR.'scp' );

//exit( INCLUDE_DIR . 'staff/staff.inc.php' );
//public_html_56/ost/scp/admin.inc.php

// Need this for plugins.
// require_once(INCLUDE_DIR . 'class.plugin.php');

// // So we can listen to events.
// require_once(INCLUDE_DIR . 'class.signal.php');



// // Import the Application class
// require_once(INCLUDE_DIR . 'class.app.php');

// require_once(INCLUDE_DIR . 'class.osticket.php');

require_once('config.php');
require_once( AIP_PATH_LIB.'/TicketOptionsPlugin_AgentInclude.php' );

class TOPLog
{
   public static function log( $c_level, $c_message = null )
   {
      switch( $c_level ){
         case 'debug':
            $c_message = 'DEBUG: '.$c_message;
            break;

         case 'info':
            $c_message = 'INFO: '.$c_message;
            break;

         default:
         exit( 'TOPLog invalid log level: '.$c_level );
      }// /switch()
      
      $c_message = date( 'H:i:s' ).' '.$c_message;
      $c_message = str_replace( ROOT_DIR, '.../', $c_message );
      $c_lines = wordwrap( $c_message, 120, "\n\t" );

      file_put_contents( AIP_PATH_LOG.'/'.date( 'Y-m-d' ).'.log', $c_lines."\n", FILE_APPEND );

      return $c_message;
   }
}// /class TOPLog

class TicketOptionsPlugin extends Plugin
{
   var $config_class = 'TicketOptionsConfig';

   protected $_ost;

   protected $_included;

   protected $a_replace_paths = [
      [
         'src' => AIP_PATH.'/replace/class.queue.php',
         'original' => INCLUDE_DIR.'class.queue.php',
         'dest' => AIP_PATH.'/replaced/class.queue.php',
      ],

      [
         'src' => AIP_PATH.'/replace/class.ticket.php',
         'original' => INCLUDE_DIR.'class.ticket.php',
         'dest' => AIP_PATH.'/replaced/class.ticket.php',
      ],

      [
         'src' => AIP_PATH.'/replace/footer.inc.php',
         'original' => INCLUDE_DIR.'staff/footer.inc.php',
         'dest' => AIP_PATH.'/replaced/footer.inc.php',
      ],
      
      [
         'src' => AIP_PATH.'/replace/header.inc.php',
         'original' => INCLUDE_DIR.'staff/header.inc.php',
         'dest' => AIP_PATH.'/replaced/header.inc.php',
      ],
      
      [
         'src' => AIP_PATH.'/replace/queue-tickets.tmpl.php',
         'original' => INCLUDE_DIR.'staff/templates/queue-tickets.tmpl.php',
         'dest' => AIP_PATH.'/replaced/queue-tickets.tmpl.php',
      ],

      [
         'src' => AIP_PATH.'/replace/ticket-view.inc.php',
         'original' => INCLUDE_DIR.'staff/ticket-view.inc.php',
         'dest' => AIP_PATH.'/replaced/ticket-view.inc.php',
      ],

      // [
      //    'src' => AIP_PATH.'/replace/tickets.inc.php',
      //    'original' => INCLUDE_DIR.'staff/tickets.inc.php',
      //    'dest' => AIP_PATH.'/replaced/tickets.inc.php',
      // ],
   ];

   protected static $_javascript_src_urls = array();

   // public static function render_included_agents()
   // {
      
   //    if( !self::agent_context_email_enabled() )
   //    {
   //       return;
   //    }

   //    include( AIP_PATH_INCLUDE.'/ticket-view-agents.php' );
   // }// /render_included_agents()

   function bootstrap()
   {

      $o_config = $this->getConfig();

      // $this->log( LOG_DEBUG, 'TicketOptionsPlugin bootstrap', 'Hello, World!', false, true );

      // Setup url routing.
      require_once( AIP_PATH_LIB.'/TicketOptionsPlugin_ApiController.php' );
      Signal::connect( 'ajax.scp', array ( 'TicketOptionsPlugin_ApiController', 'route_dispatch' ) );

      // Listen for ticket responses being e-mailed out.
      if( $o_config->get( 'enable_agent_context_email' ) == '1' )
      {
         require_once( AIP_PATH_LIB.'/TicketOptionsPlugin_SignalRouter.php' );

         /**
          * Who should receive an alert.
          * assigned staff/team
          * included staff
          * collaborators (non staff)
          */
         Signal::connect('message-alert.sent', array ( 'TicketOptionsPlugin_SignalRouter', 'route_message_alert_sent' ) );
         // Signal::connect('ticket-reply.sent', array ( 'TicketOptionsPlugin_SignalRouter', 'route_ticket_reply_sent' ) );

            
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
      TOPLog::log( 'debug', "\n\n***** TicketOptionsPlugin enable*****\n" );

      // Make sure we've replaced files.
      TOPLog::log( 'debug', '------- replace_original_files.' );
      if( $this->replace_original_files( $errors ) === false )
      {
         // Try and restore, but don't worry about errors.
         $no_err = array();
         TOPLog::log( 'debug', '------- restore_original_files.' );
         $this->restore_original_files( $no_err );
         return false;
      }


      require_once( AIP_PATH.'/install/TicketOptionsPlugin_Installer.php' );

      // Determines the db installer.
      $installer = new TicketOptionsPlugin_Installer();

      // Make sure we've installed the db tables.
      TOPLog::log( 'debug', '------- Run the TicketOptionsPlugin_Installer.' );
      if( $installer->install( $errors ) === false )
      {
         return false;
      }

      TOPLog::log( 'debug', "\n***** TicketOptionsPlugin enabled*****\n\n" );
      return parent::enable();

   }// /enable()

   function disable()
   {
      global $errors;
      TOPLog::log( 'debug', "\n\n***** TicketOptionsPlugin disable*****\n\n" );

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

      TOPLog::log( 'debug', "\n***** TicketOptionsPlugin disabled*****\n\n" );
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
         TOPLog::log( 'debug', 'TicketOptionsPlugin is_replaced_file: file does not exist: '.$c_file_path );
         return false;
      }
      
      // Load file contents into an array of lines.
      $a_file = file( $c_file_path );

      if( preg_match( '/<!-- Replaced by TicketOptionsPlugin -->/', $a_file[0] ) )
      {
         // Found an instance of the string "<!-- Replaced by TicketOptionsPlugin -->" in this file.
         return true;
      }

      TOPLog::log( 'debug', 'TicketOptionsPlugin is_replaced_file: no replaced tag in '.$c_file_path );
      return false;
      
   }// /is_replaced_file()

   /** 
    * This method gets called by /include/class.plugin.php before uninstalling 
    * the plugin. Return `true` if successful.
    */
   protected function replace_original_files( &$errors )
   {


      // $errors[ 'err' ] = 'I can\'t do that right now Dave.';
      // $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 500', $errors[ 'err' ] );
      // return false;

      foreach( $this->a_replace_paths as $a_path )
      {

         if( !file_exists( $a_path[ 'original' ] ) )
         {
            // The page we want to replace doesn't exist.
            $errors[ 'err' ] = 'Path "'.$a_path[ 'original' ].'" does not exist.';
            $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 101', $errors[ 'err' ] );
            return false;
         }

         if( $this->is_replaced_file( $a_path[ 'original' ] ) )
         {
            // This is already done.
            continue;
         }


         if( !file_exists( $a_path[ 'src' ] ) )
         {
            // The page we want to use as a replacement doesn't exist!?
            $errors[ 'err' ] = 'Path "'.$a_path[ 'src' ].'" does not exist.';
            $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 102', $errors[ 'err' ] );
            return false;
         }

         if( !is_readable( $a_path[ 'src' ] ) )
         {
            // The page we want to replace is not writable.
            $errors[ 'err' ] = 'Path "'.$a_path[ 'src' ].'" is not readable.';
            $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 105', $errors[ 'err' ] );
            return false;
         }

         if( !is_writable( $a_path[ 'original' ] ) )
         {
            // The page we want to replace is not writable.
            $errors[ 'err' ] = 'Path "'.$a_path[ 'original' ].'" is not writable.';
            $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 106', $errors[ 'err' ] );
            return false;
         }

         // Copy the original file to the "replaced" directory.
         if( !$this->copy_content( $a_path[ 'original' ], $a_path[ 'dest' ] ) )
         {
            // Get the copy error.
            $a_error = error_get_last();

            if( $a_error )
            {
               $errors[ 'err' ] = $a_error[ 'message' ];
            }
            else
            {
               $errors[ 'err' ] = 'Failed to copy original "'.$a_path[ 'original' ].'".';
            }
            
            $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 103', $errors[ 'err' ] );
            return false;
         }

         // Copy the replacement file over the original.
         if( !$this->copy_content( $a_path[ 'src' ], $a_path[ 'original' ] ) )
         {
            // Get the copy error.
            $a_error = error_get_last();

            if( $a_error )
            {
               $errors[ 'err' ] = $a_error[ 'message' ];
            }
            else
            {
               $errors[ 'err' ] = 'Failed to copy replacer "'.$a_path[ 'src' ].'".';
            }
            
            $this->log( LOG_ERR, 'TicketOptionsPlugin enable fail 104', $errors[ 'err' ] );
            return false;
         }

         TOPLog::log( 'debug', '+++++++ replaced '.$a_path[ 'original' ] );
      }// /foreach()

      return true;





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
      foreach( $this->a_replace_paths as $a_path )
      {

         // Do not bother with this if the original file is not in our "replaced" directory.
         if( file_exists( $a_path[ 'dest' ] ) )
         {
            /** 
            * A copy of original page we replaced is currently in the "replaced"
            * directory. We need to copy it back to it's original location and 
            * then delete the one in the "replaced" directory.
            */

            
            if( !$this->is_replaced_file( $a_path[ 'original' ] ) )
            {
               // The file at "original" doesn't look like one we put there.
               $errors[ 'err' ] = 'Failed to confirm replaced "'.$a_path[ 'original' ].'".';
               $this->log( LOG_ERR, 'TicketOptionsPlugin restore fail 101', $errors[ 'err' ] );
               return false;
            }

            // Copy the original file back from the "replaced" directory.
            if( !$this->copy_content( $a_path[ 'dest' ], $a_path[ 'original' ] ) )
            {
               $errors[ 'err' ] = 'Failed to restore original "'.$a_path[ 'original' ].'".';
               $this->log( LOG_ERR, 'TicketOptionsPlugin restore fail 102', $errors[ 'err' ] );
               return false;
            }

            // Delete the file in the "replaced" directory.
            if( !unlink( $a_path[ 'dest' ] ) )
            {
               $errors[ 'err' ] = 'Failed to delete original backup "'.$a_path[ 'dest' ].'".';
               $this->log( LOG_ERR, 'TicketOptionsPlugin restore fail 103', $errors[ 'err' ] );
               return false;
            }

         }
      }// /foreach()
      
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

   // Returns `true` if the  agent response e-mails are enabled.
   public static function agent_context_email_enabled()
   {
      return self::get_value( 'enable_agent_context_email' ) == '1';
   }// /agent_context_email_enabled()

   // Returns `true` if the ticket thread should have the latest response on top.
   public static function staff_thread_order()
   {
      return self::get_value( 'sort_staff_thread_desc' ) ?'desc' :'asc';
   }// /staff_thread_order()

   // Returns `true` if ticket queues should be wide.
   public static function show_all_ticket_columns()
   {
      return self::get_value( 'show_all_ticket_columns' ) == '1';
   }// /show_all_ticket_columns()

   // Returns the current instance of this class.
   public static function resolve_view( $c_filename )
   {
      return AIP_PATH_INCLUDE.'/'.$c_filename;
   }// /resolve_view()

   // public static function add_javascript_src( $c_src_url )
   // {
   //    array_push( self::$_javascript_src_urls, $c_src_url );

   // }// /add_javascript_src()

   // public static function render_javascript_sources()
   // {
   //    foreach( self::$_javascript_src_urls as $c_url )
   //    {
   //       echo "\n\n".'<script type="text/javascript" src="'.$c_url.'"></script>'."\n\n";
   //    }// /foreach()
      
   // }// /render_javascript_sources()

   function copy_content( $c_path_src, $c_path_dest )
   {
      TOPLog::log( 'debug', 'TicketOptionsPlugin copy_content get: '.$c_path_src );
      $c_content = file_get_contents( $c_path_src );

      if( $c_content === false )
      {
         $this->log( LOG_ERR, 'TicketOptionsPlugin copy_content fail 101', 
         'Failed to get contents of "'.$c_path_src.'".' );
         return false;
      }
      
      TOPLog::log( 'debug', 'TicketOptionsPlugin copy_content put: '.$c_path_dest );
      if( file_put_contents( $c_path_dest, $c_content ) === false )
      {
         $this->log( LOG_ERR, 'TicketOptionsPlugin copy_content fail 102', 
         'Failed to write content to "'.$c_path_dest.'".' );
         return false;
      }

      return true;
   }// /copy_content()

}// /class TicketOptionsPlugin