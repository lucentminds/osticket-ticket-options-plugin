<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

// Import the Api class
require_once(INCLUDE_DIR . 'class.api.php');


class TicketOptionsPlugin_ApiController extends ApiController
{
   protected static $_route_dispatch_object;

   public function show_view( $c_path )
   {

      switch( true )
      {
         case preg_match( '/.css$/', $c_path );
         return $this->show_view_css( $c_path );
         break;

         case preg_match( '/.js$/', $c_path );
         return $this->show_view_js( $c_path );
         break;
         
         default:
         header('HTTP/1.0 404 Not Found');
         echo '404 File not found: '.$c_path;
      }
   }// /show_view()

   public function show_view_css( $c_path )
   {
      $c_full_local_path = AIP_PATH.$c_path;

      if( !file_exists( $c_full_local_path ) )
      {
         header('HTTP/1.0 404 Not Found');
         echo '404 File not found: '.$c_path ;
      }

      header( 'Content-Type: text/css' );
      echo file_get_contents( $c_full_local_path );
   }// /show_view_css()

   public function show_view_js( $c_path )
   {
      $c_full_local_path = AIP_PATH.$c_path;

      if( !file_exists( $c_full_local_path ) )
      {
         header('HTTP/1.0 404 Not Found');
         echo '404 File not found: '.$c_path;
      }

      header( 'Content-Type: text/javascript' );
      echo file_get_contents( $c_full_local_path );
   }// /show_view_js()


   public static function route_dispatch( $object, $data )
   {
      self::$_route_dispatch_object = $object;
      self::get( '^/ticket_options(/static/[^\?]*)$', array( TicketOptionsPlugin_ApiController, 'show_view' ) );

   }// /route_dispatch()

   public static function get( $c_match, $a_callback )
   {
      self::$_route_dispatch_object->append( url_get( $c_match, $a_callback ) );
   }// /route_dispatch()

}// /TicketOptionsPlugin_ApiController()