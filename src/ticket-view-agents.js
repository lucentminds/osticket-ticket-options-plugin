/**
 * {%= date %}
 * {%= description %}
 * ~~ {%= author_name %}
 */

/** List jshint ignore directives here. **/
/* jshint undef: true, unused: true */
/* jshint browser:true */
/* global App:false */

(function ($, undefined) {
   var $agent_list = $( '#agent-list' );
   var $agent_list_error = $( '#agent-list-error' );
   var $agent_list_search = $( '#agent-list-search' );

   var show_add = function(){
      $agent_list_search.agentSearch();
   };// /show_add()

   var hide_add = function(){
      $agent_list_search.agentSearch( 'destroy' );
   };// /hide_add()

   var show_agents = function(){

      $.ajax({
         url: 'ajax.php/ticket_options/script/get_ticket_agents.php?ticket_id=20000',
         method: 'get',
         type: 'json',
         dataType: 'json'
      })
      .then(function( o_result, status, o_xhr ){
         if( o_result.error ) {
            return $agent_list_error.errorBanner({ message: o_result.error.message });
         }

         if( o_result.result == 'ok' )
         {
            $agent_list.userList({
               users: o_result.agents,
               on_add_click: function( event, ui ){
                  hide_agents();
                  show_add();
               }// /on_add_click()
            });
            return;
         }

         debugger;
         
      })
      .fail(function( o_xhr, c_status, o_error  ){
         if( c_status == 'parsererror' )
         {
            return $agent_list_error.errorBanner({ message: 'get_ticket_agents failure: '.concat( o_error.message ) });
            
         }

         debugger;
      });

   };// /show_agents()


   var hide_agents = function(){
      $agent_list.userList( 'destroy' );
      $agent_list_error.errorBanner( 'destroy' );

   };// /hide_agents()

   show_agents();

}( jQuery ))