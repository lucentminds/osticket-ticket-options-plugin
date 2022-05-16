--
-- Table structure for table `_plugin_ticketoptions_agent_settings`
--
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%plugin_ticketoptions_agent_settings` (
  `agent_id` int(11) NOT NULL,
  `time_add` datetime DEFAULT current_timestamp(),
  `time_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `settings` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT='One record for each agent with custom settings (for future)';



--
-- Indexes for table `_plugin_ticketoptions_agent_settings`
--
ALTER TABLE `%TABLE_PREFIX%plugin_ticketoptions_agent_settings`
  ADD CONSTRAINT PRIMARY KEY IF NOT EXISTS (`agent_id`);
