<?php
/*******************************************************************************
	Unit Test Bootstrap

	Loads the pure data handling functions so they can be tested without
	a database or session. Anything that needs mysqlQuery() belongs in the
	e2e tests, not here.

*******************************************************************************/

require_once(__DIR__.'/../../includes/functions/data_handling_functions.php');
