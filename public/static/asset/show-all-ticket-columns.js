// <!-- Replaced by TicketOptionsPlugin -->
/* jshint esversion:8 */

// <!-- Replaced by TicketOptionsPlugin -->
// TicketOptionsPlugin.show_all_ticket_columns
jQuery(function($) {
   const TicketOptionsPlugin = window.TicketOptionsPlugin;

   const refresh_changes = function(){
      const $body = $(document.body);
      const c_last_body_class_path = TicketOptionsPlugin.body_class_path;
      TicketOptionsPlugin.relative_path = location.pathname.replace( TicketOptionsPlugin.root_path, '' );
      let c_body_class_path = TicketOptionsPlugin.relative_path.replace( /\.[^\.]*$/, '' ).replace( /\//g, '__' );
      c_body_class_path = `ticketoptionsplugin__${c_body_class_path}`;
      TicketOptionsPlugin.body_class_path = c_body_class_path;
      
      
      if( !$body.hasClass( c_body_class_path ) ){
         $body.removeClass( c_last_body_class_path );
         $body.addClass( c_body_class_path );
         $('.content').width( '100%' );
      }
   };


   $(document).on('pjax:complete pjax:send', function( event ) {
      // An ajax content request has just been sent or completed. Refresh changes.
      refresh_changes();
   });

   refresh_changes();
});