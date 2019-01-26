/**
 * 01-25-2019
 * Main app/tab.
 * ~~ {%= author_name %}
 */

/** List jshint ignore directives here. **/
/* jshint undef: true, unused: true */
/* jshint browser:true */
/* global jQuery:false */

(function ($, undefined) {
   var $agent_list = $( '#agent-list' );
   var $agent_list_error = $( '#agent-list-error' ).errorBanner();
   var $agent_list_search = $( '#agent-list-search' );

   var view = {
      _views: {},
      _current: null,

      add: function( c_name, o_view ) {
         return this._views[ c_name ] = o_view;
      },// /add()

      show: function( c_name ) {
         var self = this;

         if( !this._views[ c_name ] ) {
            throw new Error( ''.concat( 'View "',c_name,'" is not defined.' ) );
         }

         if( this._current == this._views[ c_name ] ) {
            return;
         }

         if( this._current != null ) {
            $.when( this._views[ this._current ].hide() )
            .then(function(){
               self._show_now( c_name );
            });

            return;
         }

         return self._show_now( c_name );

      },// /show()

      _show_now: function( c_name ) {
         var c_previous = this._current;

         this._current = c_name;
         this._views[ c_name ].show( c_previous );

      }// /_show_now()


   };

   var show_error = function( c_msg ){
      return $agent_list_error.errorBanner( 'set_message', c_msg );
   };// /show_error()

   var clear_error = function(){
      return $agent_list_error.errorBanner( 'clear' );
   };// /clear_error()

   view.add( 'default', {
      show: function(){
         $.ajax({
            url: 'ajax.php/ticket_options/script/get_ticket_agents.php?ticket_id=20000',
            method: 'get',
            type: 'json',
            dataType: 'json'
         })
         .then(function( o_result, status, o_xhr ){
            if( o_result.error ) {
               return show_error( o_result.error.message );
            }

            if( o_result.result == 'ok' )
            {
               $agent_list.userList({
                  users: o_result.agents,
                  on_add_click: function( event, ui ){
                     view.show( 'add_agent_form' );
                  }// /on_add_click()
               });
               return;
            }

            debugger;
            
         })
         .fail(function( o_xhr, c_status, o_error  ){
            if( c_status == 'parsererror' )
            {
               return show_error( 'get_ticket_agents failure: '.concat( o_error.message ) );
               
            }

            debugger;
         });

      },

      hide: function(){
         $agent_list.userList( 'destroy' );
         clear_error();
      }
   });

   view.add( 'add_agent_form', {
      show: function(){
         $agent_list_search.agentSearch({
            on_add_click: function( event, ui ){
               console.log( ui.staff_id );
            }
         });
      },

      hide: function(){
         $agent_list_search.agentSearch( 'destroy' );
         clear_error();
      }
   });


   view.show( 'default' );

}( jQuery ))