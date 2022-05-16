<?php
 
/**
 * Integrate Trello into OSTicket
 *
 * @author
 */
 
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__file__).'/include');
return array(
   'id' => 'lucentminds:osticket-ticket-options-plugin', # notrans
   'version' => '0.1',
   'name' => 'Ticket options for osTicket 1.16.2',
   'author' => 'Scott Johnson',
   'description' => 'Adds handy features and tweaks to individual tickets. <a href="https://github.com/lucentminds/osticket-ticket-options-plugin" target="_blank">https://github.com/lucentminds/osticket-ticket-options-plugin</a> .',
   'url' => 'https://lucentminds.com',
   'plugin' => 'class.TicketOptionsPlugin.php:TicketOptionsPlugin'
);
 
