--
-- Table structure for table `_plugin_ticketoptions_ticket_agent`
--
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%plugin_ticketoptions_ticket_agent` (
  `ticket_id` int(11) NOT NULL,
  `time_add` datetime DEFAULT current_timestamp(),
  `time_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `staff_id_list` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT='One record for each ticket with staff ID list for e-mails';



--
-- Indexes for table `_plugin_ticketoptions_ticket_agent`
--
ALTER TABLE `%TABLE_PREFIX%plugin_ticketoptions_ticket_agent`
  ADD CONSTRAINT PRIMARY KEY IF NOT EXISTS (`ticket_id`);
