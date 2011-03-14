<?php

if(SEARCH_PROVIDER == "sphinx") {
	$sphinx = new SphinxClient();
	$sphinx->setServer("localhost", 9312);
	$sphinx->setMatchMode(SPH_MATCH_ANY);
	$sphinx->setMaxQueryTime(10);
	view_manager::set_value("sphinx", $sphinx);
}
