<?php
/*
 * @ Chess League Manager (CLM) Component 
 * @Copyright (C) 2008-2019 CLM Team. All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.chessleaguemanager.de
*/
// Einlesen von Post-Variablen vom Typ Integer
function clm_function_request_array_int($input, $standard = NULL) {
	if (!isset($_POST[$input])) return $standard;
	$value = $_POST[$input];
	$result = array(); 
	foreach($value as $key => $value1) 
	{
		$result[] = clm_core::$load->make_valid($value1, 0, $standard);
	} 
	return $result;		
}
?>
