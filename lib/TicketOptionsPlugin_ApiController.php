<?php if(!defined('INCLUDE_DIR')) die('Fatal error');

// Import the Api class
require_once(INCLUDE_DIR . 'class.api.php');


class TicketOptionsPlugin_ApiController extends ApiController
{
   protected static $_route_dispatch_object;

   public function show_404( $c_path )
   {
      header( 'HTTP/1.0 404 Not Found' );
      echo '404 File not found: '.$c_path ;
   }// /show_404()

   public function show_view( $c_path )
   {
      switch( true )
      {
         case preg_match( '/.css$/', $c_path ):
         return $this->show_static_content( $c_path, 'text/css' );
         break;

         case preg_match( '/.js$/', $c_path ):
         return $this->show_static_content( $c_path, 'text/javascript' );
         break;

         case preg_match( '/.php$/', $c_path ):
         return $this->include_php( $c_path );
         break;
         
         default:
         $this->show_404( $c_path );
      }
   }// /show_view()

   public function include_php( $c_path )
   {
      $c_full_local_path = AIP_PATH_PUBLIC.$c_path;

      if( !file_exists( $c_full_local_path ) )
      {
         $this->show_404( $c_path );
      }

      include( $c_full_local_path );

   }// /include_php()

   public function show_static_content( $c_path, $c_content_type = 'text/html' )
   {
      $c_full_local_path = AIP_PATH_PUBLIC.$c_path;

      if( !file_exists( $c_full_local_path ) )
      {
         $this->show_404( $c_path );
      }

      header( 'Content-Type: '.$c_content_type );
      echo file_get_contents( $c_full_local_path );

   }// /show_static_content()

   // public function show_view_css( $c_path )
   // {
   //    $c_full_local_path = AIP_PATH_PUBLIC.$c_path;

   //    if( !file_exists( $c_full_local_path ) )
   //    {
   //       $this->show_404( $c_path );
   //    }

   //    header( 'Content-Type: text/css' );
   //    echo file_get_contents( $c_full_local_path );
   // }// /show_view_css()

   // public function show_view_js( $c_path )
   // {
   //    $c_full_local_path = AIP_PATH_PUBLIC.$c_path;

   //    if( !file_exists( $c_full_local_path ) )
   //    {
   //       $this->show_404( $c_path );
   //    }

   //    header( 'Content-Type: text/javascript' );
   //    echo file_get_contents( $c_full_local_path );
   // }// /show_view_js()


   public static function route_dispatch( $object, $data )
   {
      self::$_route_dispatch_object = $object;
      $instance = 
      self::get( '^/ticket_options(/static/[^\?]*)$', array( 'TicketOptionsPlugin_ApiController', 'show_view' ) );
      self::get( '^/ticket_options(/script/[^\?]*)$', array( 'TicketOptionsPlugin_ApiController', 'show_view' ) );
      self::post( '^/ticket_options(/script/[^\?]*)$', array( 'TicketOptionsPlugin_ApiController', 'show_view' ) );

   }// /route_dispatch()

   public static function get( $c_match, $a_callback )
   {
      self::$_route_dispatch_object->append( url_get( $c_match, $a_callback ) );
   }// /get()

   public static function post( $c_match, $a_callback )
   {
      self::$_route_dispatch_object->append( url_post( $c_match, $a_callback ) );
   }// /post()

}// /TicketOptionsPlugin_ApiController()