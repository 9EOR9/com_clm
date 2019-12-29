<?php
/**
 * @ Chess League Manager (CLM) Component 
 * @Copyright (C) 2008-2019 CLM Team.  All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.fishpoke.de
 * @author Fjodor Sch�fer
 * @email ich@vonfio.de
*/

jimport( 'joomla.application.component.view');

class CLMViewTermine extends JViewLegacy
{
	function display($tpl = null)
	{
		
		$model	  		= $this->getModel();
		$termine     	= $model->getTermine();
		$this->termine = $termine;
		
		$model	  		= $this->getModel();
		$termine_detail     	= $model->getTermine_Detail();
		$this->termine_detail = $termine_detail;
		
		$model	  		= $this->getModel();
		$schnellmenu  	= $model->getSchnellmenu();
		$this->schnellmenu = $schnellmenu;
		
		parent::display($tpl);
	}	
}
?>