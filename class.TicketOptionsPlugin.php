<?php


// Determines the full local path to to this plugin dir.
define( 'AIP_PATH', __DIR__ );

// Determines the relative local path to this plugin dir.
define( 'AIP_PATH_RELATIVE', str_replace( ROOT_DIR.'include/', '', AIP_PATH ) );

// Determines the full local path to the ticket-view.inc.php in production.
define( 'AIP_PATH_VIEW', __DIR__. '/view' );

// Determines the full local path to the ticket-view.inc.php in production.
define( 'AIP_PATH_TICKET_VIEW', INCLUDE_DIR.'staff/ticket-view.inc.php' );

// Determines the full local path to the ticket-view.inc.php in production.
define( 'AIP_PATH_TICKET_VIEW_REPLACED', __DIR__.'/replaced/ticket-view.inc.php' );

//exit( INCLUDE_DIR . 'staff/staff.inc.php' );
//public_html_56/ost/scp/admin.inc.php
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');

// Import the Application class
require_once(INCLUDE_DIR . 'class.app.php');

require_once(INCLUDE_DIR . 'class.osticket.php');
require_once('config.php');

class TicketOptionsPlugin extends Plugin
{
   var $config_class = 'TicketOptionsConfig';

   protected $_ost;

   public static function render_included_agents()
   {
      include( AIP_PATH_VIEW.'/ticket-view-agents.php' );
   }// /render_included_agents()

   function bootstrap()
   {
      // if( $this->first_run() )
      // {
      //    if( !$this->configure_first_run() )
      //    {
      //       return false;
      //    }
      // }


      // require_once( 'staff/ticket-view.inc.php' );


      Application::registerStaffApp ( 'Equipment', 'dispatcher.php/equipment/dashboard/', array (
      iconclass => 'faq-categories' 
      ) );


      $config = $this->getConfig();
      $this->_ost = new osTicket();
      $this->_ost->log( LOG_DEBUG, 'Hello, World!', 'We created a plugin!' );
      //print_r( $config );
      
      Signal::connect('user_reply.sent', array($this, 'on_user_reply') );
   }// bootstrap()

   // Have we already setup this plugin after installing?
   function first_run( $a_email_settings )
   {
      // For now, always.
      return false;
   }// /first_run()

   // Configure this plugin installation.
   function configure_first_run( $a_email_settings )
   {
      $this->_ost->log( LOG_DEBUG, 'AgentIncludePlugin::configure_first_run', '', false, true );

      
      // For now, always.
      return true;
   }// /configure_first_run()

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
      $this->_ost->log( LOG_DEBUG, 'AgentIncludePlugin', 'Posted reply sent to owner agent.', false, true );
   }// /on_user_reply()

   /** 
    * This method gets called by /include/class.plugin.php before uninstalling 
    * the plugin. Return `true` if successful.
    */
   function pre_uninstall( &$errors )
   {
      return true;
   }// /pre_uninstall()

   function enable()
   {
      global $ost;


      $c_path_replacer = AIP_PATH.'/replace/ticket-view.inc.php';


      if( !file_exists( AIP_PATH_TICKET_VIEW ) )
      {
         // The page we want to replace doesn't exist.
         $ost->log( LOG_ERR, 'TicketOptionsPlugin enable fail 101', 'Path "'.AIP_PATH_TICKET_VIEW.'" does not exist.' );
         return false;
      }


      if( !file_exists( $c_path_replacer ) )
      {
         // The page we want to use as a replacement doesn't exist!?
         $ost->log( LOG_ERR, 'TicketOptionsPlugin enable fail 102', 'Path "'.$c_path_replacer.'" does not exist.' );
         return false;
      }

      // Copy the original file to the "replaced" directory.
      if( !copy( AIP_PATH_TICKET_VIEW, AIP_PATH_TICKET_VIEW_REPLACED ) )
      {
         $ost->log( LOG_ERR, 'TicketOptionsPlugin enable fail 103', 'Failed to copy original "'.AIP_PATH_TICKET_VIEW.'".' );
         return false;
      }

      // Copy the replacement file over the original.
      if( !copy( $c_path_replacer, AIP_PATH_TICKET_VIEW ) )
      {
         $ost->log( LOG_ERR, 'TicketOptionsPlugin enable fail 104', 'Failed to copy replacer "'.$c_path_replacer.'".' );
         return false;
      }

      // $ost->log( LOG_ERR, 'TicketOptionsPlugin enable fail 500', 'I can\'t do that right now Dave.' );
      // return false;

      // // Determines the full local path to the ticket-view.inc.php in production.
      // define( 'AIP_PATH_TICKET_VIEW', INCLUDE_DIR.'staff/ticket-view.inc.php' );

      // // Determines the full local path to the ticket-view.inc.php in production.
      // define( 'AIP_PATH_TICKET_VIEW_REPLACED', __DIR__.'replaced/ticket-view.inc.php' );


      return parent::enable();
   }// /enable()

   function disable()
   {
      global $ost, $errors;




      if( !file_exists( AIP_PATH_TICKET_VIEW_REPLACED ) )
      {
         // The page we replaced doesn't exist.
         $errors[ 'err' ] = 'Backup path "'.AIP_PATH_TICKET_VIEW.'" does not exist.';
         $ost->log( LOG_ERR, 'TicketOptionsPlugin disable fail 101', $errors[ 'err' ] );
         return false;
      }

      // Copy the original file back from the "replaced" directory.
      if( !copy( AIP_PATH_TICKET_VIEW_REPLACED, AIP_PATH_TICKET_VIEW ) )
      {
         $errors[ 'err' ] = 'Failed to restore original "'.AIP_PATH_TICKET_VIEW.'".';
         $ost->log( LOG_ERR, 'TicketOptionsPlugin disable fail 102', $errors[ 'err' ] );
         return false;
      }


      // $errors[ 'err' ] = 'I can\'t do that right now Dave.';
      // $ost->log( LOG_ERR, 'TicketOptionsPlugin disable fail', 'I can\'t do that right now Dave.' );
      // return false;

      return parent::disable();
   }// /disable()

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