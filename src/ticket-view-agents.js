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
$(document).on('ready pjax:success', function() { // This is required because osticket uses pjax for stuff.


   var $agent_list = $( '#agent-list' );

   if( $agent_list.length < 1 ){
      // We are not on a ticket page.
      return;
   }

   var $agent_list_error = $( '#agent-list-error' ).errorBanner();
   var $agent_list_search = $( '#agent-list-search' );
   var $agent_add_progress = $( '#agent-add-progress' );
   var $agent_remove_progress = $( '#agent-remove-progress' );
   var $agent_confirm_remove = $( '#agent-confirm-remove' );
   

   var view = {
      _views: {},
      _current: null,

      add: function( c_name, o_view ) {
         return this._views[ c_name ] = o_view;
      },// /add()

      show: function( c_name, o_data ) {
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
               self._show_now( c_name, o_data );
            });

            return;
         }

         return self._show_now( c_name, o_data );

      },// /show()

      _show_now: function( c_name, o_data ) {
         var c_previous = this._current;

         this._current = c_name;
         this._views[ c_name ].show( c_previous, o_data );

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
            url: 'ajax.php/ticket_options/script/get_ticket_agents.php',
            method: 'get',
            type: 'json',
            dataType: 'json',
            data: {
               ticket_id: $.ticket_id
            }
         })
         .then(function( o_result/* , status, o_xhr */ ){
            if( o_result.error ) {
               return show_error( o_result.error.message );
            }

            if( o_result.result == 'ok' )
            {
               $agent_list.userList({
                  users: o_result.agents,
                  on_add_click: function( event, ui ){
                     view.show( 'add_agent_form' );
                  },// /on_add_click()

                  on_remove_click: function( event, ui ){
                     console.log( ui );
                     view.show( 'remove_agent_confirm', { agent: ui.user });
                  }// /on_remove_click()
                  
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
            on_agent_click: function( event, ui ){
               view.show( 'add_agent_progress', { staff_id: ui.staff_id } );
            }
         });
      },

      hide: function(){
         $agent_list_search.agentSearch( 'destroy' );
         clear_error();
      }
   });


   view.add( 'add_agent_progress', {
      show: function( c_previous, o_data ){
         $agent_add_progress.showAgentAdd({
            staff_id: o_data.staff_id,
            ticket_id: $.ticket_id,
            on_response: function( /* event, ui */ ){
               view.show( 'default' );
            }
         });
      },

      hide: function(){
         $agent_add_progress.showAgentAdd( 'destroy' );
      }
   });


   view.add( 'remove_agent_progress', {
      show: function( c_previous, o_data ){

         $agent_remove_progress.showAgentRemove({
            staff_id: o_data.agent.staff_id,
            ticket_id: $.ticket_id,

            on_response: function( /* event, ui */ ){
               view.show( 'default' );
            }
         });
      },

      hide: function(){
         $agent_remove_progress.showAgentRemove( 'destroy' );
      }
   });


   view.add( 'remove_agent_confirm', {
      show: function( c_previous, o_data ){
         
         $agent_confirm_remove.dialogConfirm({
            message: ''.concat( 'This cannot be undone.',
            ' Are you sure you wish to remove staff member "',o_data.agent.name,'" from this ticket?' ),

            text_confirm: 'Yes, remove this agent',
            text_cancel: 'No, Do NOT remove this agent',

            on_cancel_click: function( /* event, ui */ ){
               view.show( 'default' );
            },

            on_confirm_click: function( /* event, ui */ ){
               view.show( 'remove_agent_progress', o_data );
            }
         });
      },

      hide: function(){
         $agent_confirm_remove.dialogConfirm( 'destroy' );
      }
   });

   


   view.show( 'default' );
});

}( jQuery ));