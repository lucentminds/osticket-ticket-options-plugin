// <!-- Replaced by TicketOptionsPlugin -->
/* jshint esversion:8 */

// <!-- Replaced by TicketOptionsPlugin -->
// TicketOptionsPlugin.wide_ticket_queues
jQuery(function($) {
   var n_resize_timeout = 0;
   const $window = $(window);
   
   // $window
   // .on( 'resize', function(){
   //    clearTimeout( n_resize_timeout );
   //    n_resize_timeout = setTimeout(function(){
   //       $window.trigger( 'ticketoptionsplugin_resized' );
   //    }, 500 );
   // })
   // .on( 'ticketoptionsplugin_resized', function(){
   //    clearTimeout( n_resize_timeout );

   //    // Reset the sticky/non-stick title menu thing width.
   //    const $sticky_bar = $('div.sticky.bar:not(.stop)');
   //    const n_sticky_bar_width = $sticky_bar.width();
   //    $sticky_bar.find( '.content' ).width( n_sticky_bar_width );
   // });
   
   // Determines the global plugin object.
   const TicketOptionsPlugin = window.TicketOptionsPlugin;

   // Initialize regex patterns.
   const reg_scp = /(^\/scp\/.*$)/;
   const reg_path = /(^\/scp\/$)|(^\/scp\/(tickets|index)\.php$)/;
   const reg_search = /[?|&]id=\d+/;
   const reg_namespaced = /^ticketoptionsplugin__/;
   const reg_root_path = new RegExp( `^${TicketOptionsPlugin.root_path}` );

   // const resize_content

   const refresh_changes = function(){
      const $body = $(document.body);

      // Determines a helper object for working with the current url.
      const o_url = new URL( window.location.href );

      // Determines if the current url looks like a ticket queue url.
      const is_ticket_queue = reg_path.test( o_url.pathname ) &&
         !reg_search.test( o_url.search );
      
      // Determines a Set of classnames currently in use by the body tag.
      const o_classes_old = (function(){
         const a_names = document.body.className.split( ' ' );
         a_names.sort();
         return new Set( a_names );
      }());

      // Determines a sorted list of the old body classnames.
      const c_classnames_old = [... o_classes_old].join( ' ' );

      /**
       * Determines a sorted array of the new body classnames initialized
       * without plugin namespaced values.
       */
      const a_classes_new = [... o_classes_old]
         .filter( c => !reg_namespaced.test( c )  );

      if( is_ticket_queue ){
         // Add the "tickets-queue" class name to the array.
         a_classes_new.push( 'ticketoptionsplugin__tickets-queue' );
      }

      // Determines a sorted list of the new body classnames.
      const c_classnames_new = a_classes_new.join( ' ' );

      if( c_classnames_new != c_classnames_old ){
         // The class names have changed. Update the body tag in the DOM.
         document.body.className = c_classnames_new;
      }
   };// /refresh_changes()


   $(document).on('pjax:complete pjax:send', function( event ) {
      /** 
       * An ajax content request has just been sent or completed. Refresh
       * changes.
       */
      refresh_changes();
   });

   refresh_changes();
   $window.trigger( 'ticketoptionsplugin_resized' );
});