-- --------------------------------------------------------

--
-- indexes for table change_links
--

CREATE INDEX change_links_change_idx ON change_links (change_id);
CREATE INDEX change_links_change_time_idx ON change_links (change_time);
CREATE INDEX change_links_user_idx ON change_links (user_id);
