<?php
/**
 * @ Chess League Manager (CLM) Component 
 * @Copyright (C) 2008-2026 CLM Team.  All rights reserved
 *                2026 Georg Richter
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link https://chessleaguemanager.org
 * @author Thomas Schwietert
 * @email fishpoke@fishpoke.de
 * @author Andreas Dorn
 * @email webmaster@sbbl.org
*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
// Include the AddressHandler class
require_once JPATH_COMPONENT_ADMINISTRATOR. '/helpers/addresshandler.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Pagination\Pagination;

class CLMControllerMannschaften extends JControllerLegacy
{
    /**
     * Constructor
     */
    function __construct( $config = array() )
    {
        parent::__construct( $config );
        // Register Extra tasks
        $this->registerTask( 'add','edit' );
        $this->registerTask( 'apply','save' );
        $this->registerTask( 'unpublish','publish' );
    }

    function display($cachable = false, $urlparams = array())
    {
        $mainframe = Factory::getApplication();
        $option    = clm_core::$load->request_string('option');
        $section   = clm_core::$load->request_string('section');

        $clmAccess = clm_core::$access;

        $db = Factory::getDBO();

        $filter_order     = $mainframe->getUserStateFromRequest( "$option.filter_order",'filter_order','a.id',    'cmd' );
        $filter_order_Dir = $mainframe->getUserStateFromRequest( "$option.filter_order_Dir",'filter_order_Dir','','word' );
        $filter_state     = $mainframe->getUserStateFromRequest( "$option.filter_state",'filter_state','','word' );
        $filter_sid       = $mainframe->getUserStateFromRequest( "$option.filter_sid",'filter_sid',0,'int' );
        $filter_lid       = $mainframe->getUserStateFromRequest( "$option.filter_lid",'filter_lid',0,'int' );
        $filter_vid       = $mainframe->getUserStateFromRequest( "$option.filter_vid",'filter_vid',0,'string' );
        $filter_catid     = $mainframe->getUserStateFromRequest( "$option.filter_catid",'filter_catid',0,'int' );
        $search           = $mainframe->getUserStateFromRequest( "$option.search",'search','','string' );
        $search           = strtolower( $search );
        $limit            = $mainframe->getUserStateFromRequest( 'global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
        $limitstart       = $mainframe->getUserStateFromRequest( $option.'.limitstart', 'limitstart', 0, 'int' );

        $where = array();
        $where[] = ' c.archiv = 0';
        if ( $filter_catid ) { $where[] = 'a.published = '.(int) $filter_catid; }
        if ( $filter_sid )   { $where[] = 'a.sid = '.(int) $filter_sid.' AND c.archiv = 0'; }
        if ( $filter_lid )   { $where[] = 'a.liga = '.(int) $filter_lid; }
        if ( $filter_vid )   { $where[] = "a.zps = '$filter_vid'"; }
        if ($search)         { $where[] = 'LOWER(a.name) LIKE "'.$db->escape('%'.$search.'%').'"'; }

        if ( $filter_state ) {
            if ( $filter_state == 'P' ) {
                $where[] = 'a.published = 1';
            } else if ($filter_state == 'U' ) {
                $where[] = 'a.published = 0';
            }
        }
        $count_man = ( count( $where ) ? ' WHERE ZPS =1 AND ' . implode( ' AND ', $where ) : '' );
        $where     = ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
        
        if ($filter_order == 'a.id'){
            $orderby = ' ORDER BY liga ASC, a.tln_nr '.$filter_order_Dir;
        } else {
            if ($filter_order =='a.name' OR $filter_order == 'a.man_nr' OR $filter_order == 'd.name' OR $filter_order == 'a.tln_nr' OR $filter_order == 'a.mf' OR $filter_order == 'a.liste' OR $filter_order == 'b.Vereinname' OR $filter_order == 'c.name' OR $filter_order == 'a.ordering' OR $filter_order == 'a.published' OR $filter_order == 'ms.durchschnitt_dwz') { 
                $orderby = ' ORDER BY '. $filter_order .' '. $filter_order_Dir .', a.id';
            } else { 
                $filter_order = 'a.id'; 
                $orderby = "";
            }
        }

        // Zugangscheck
        if ($clmAccess->access('BE_team_edit') === false) {
            $section = 'info';
            $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
        if ($clmAccess->access('BE_team_edit') === true) $where_sl = '';
        else $where_sl = ' AND d.sl = '.clm_core::$access->getJid();

        // get the total number of records
        $query = ' SELECT COUNT(*) '
            .' FROM #__clm_mannschaften AS a'
            .' LEFT JOIN #__clm_saison AS c ON c.id = a.sid'
            .' LEFT JOIN #__clm_liga AS d ON a.liga = d.id'
            . $where.$where_sl
            ;
        $db->setQuery( $query );
        $total = $db->loadResult();

        jimport('joomla.html.pagination');
        $pageNav = new Pagination( $total, $limitstart, $limit );

        // Mannschaften ohne Verein zählen
        $query = ' SELECT COUNT(a.id) as id'
            .' FROM #__clm_mannschaften AS a '
            .' LEFT JOIN #__clm_saison AS c ON c.id = a.sid'
            .' LEFT JOIN #__clm_liga AS d ON a.liga = d.id'
            . $count_man.$where_sl
            ;
        $db->setQuery( $query);
        $counter_man = $db->loadResult();

        if($counter_man > 0){
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_ES_GIBT').' '.$counter_man.' '.Text::_('MANNSCHAFTEN_ERROR_MANNSCHAFT_VEREIN'), 'notice' );
        } 

        // Haupt-Query mit Berechnung des DWZ-Schnitts für Stammspieler (snr <= stamm)
        $query = ' SELECT a.*, c.name AS saison, b.Vereinname AS verein, u.name AS editor, d.name AS liga_name, ms.durchschnitt_dwz '
            .' FROM #__clm_mannschaften AS a '
            .' LEFT JOIN #__clm_saison AS c ON c.id = a.sid '
            .' LEFT JOIN #__clm_liga AS d ON a.liga = d.id '
            .' LEFT JOIN #__users AS u ON u.id = a.checked_out '
            .' LEFT JOIN #__clm_dwz_vereine AS b ON a.zps = b.ZPS AND b.sid = a.sid '
            .' LEFT JOIN #__clm_vereine AS e ON e.zps = a.zps AND e.sid = a.sid '
            .' LEFT JOIN ( '
            .'     SELECT m.lid, m.mnr, ROUND(AVG(m.start_dwz), 0) AS durchschnitt_dwz '
            .'     FROM #__clm_meldeliste_spieler AS m '
            .'     INNER JOIN #__clm_liga AS l ON l.id = m.lid '
            .'     WHERE m.snr <= l.stamm AND m.start_dwz > 0 '
            .'     GROUP BY m.lid, m.mnr '
            .' ) AS ms ON ms.lid = a.liga AND ms.mnr = a.man_nr '
            . $where . $where_sl
            . $orderby
            ;

        try {
            $db->setQuery( $query, $pageNav->limitstart, $pageNav->limit );
            $rows = $db->loadObjectList();
        }
        catch (Exception $e) {
            $mainframe->enqueueMessage($e->getMessage(), 'error');
        }
        
        // CLM parameter auslesen
        $clm_config = clm_core::$db->config();
        if ($clm_config->field_search == 1) $field_search = "js-example-basic-single";
        else $field_search = "inputbox";
        
        // Filter setzen
        $lists['state'] = CLMForm::selectState( $filter_state );
        
        // Saisonfilter
        $sql = 'SELECT id, name FROM #__clm_saison WHERE archiv =0';
        $db->setQuery($sql);
        $saisonlist[] = HTMLHelper::_('select.option', '0', Text::_( 'MANNSCHAFTEN_SAISON' ), 'id', 'name' );
        $saisonlist   = array_merge( $saisonlist, $db->loadObjectList() );
        $lists['sid'] = HTMLHelper::_('select.genericlist', $saisonlist, 'filter_sid', 'class="'.$field_search.'" size="1" onchange="document.adminForm.submit();"','id', 'name', intval( $filter_sid ) );

        // Ligafilter
        $sql = 'SELECT d.id AS cid, d.name FROM #__clm_liga as d'
            ." LEFT JOIN #__clm_saison as s ON s.id = d.sid"
            ." WHERE s.archiv = 0 ".$where_sl;
        $db->setQuery($sql);
        $ligalist[] = HTMLHelper::_('select.option', '0', Text::_( 'MANNSCHAFTEN_LIGA' ), 'cid', 'name' );
        $ligalist   = array_merge( $ligalist, $db->loadObjectList() );
        $lists['lid'] = HTMLHelper::_('select.genericlist', $ligalist, 'filter_lid', 'class="'.$field_search.'" size="1" onchange="document.adminForm.submit();"','cid', 'name', intval( $filter_lid ) );

        // Vereinefilter laden
        $vlist        = CLMFilterVerein::vereine_filter(0);
        $lists['vid'] = HTMLHelper::_('select.genericlist', $vlist, 'filter_vid', 'class="'.$field_search.'" size="1" onchange="document.adminForm.submit();"','zps', 'name', $filter_vid );

        // Ordering & Suche
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order']     = $filter_order;
        $lists['search']    = $search;

        require_once(JPATH_COMPONENT.DS.'views'.DS.'mannschaft.php');
        CLMViewMannschaften::mannschaften( $rows, $lists, $pageNav, $option );
    }

    function geo(){
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
        $this->setRedirect( 'index.php?option='.$option.'&section='.$section );
        $cid   = clm_core::$load->request_array_int( 'cid');
        $db    = Factory::getDBO();
        $table = Table::getInstance('mannschaften', 'TableCLM');
        $user  = Factory::getUser();
        $n     = count( $cid );
        $clm_config = clm_core::$db->config();
        $unsuccessArray = array();
        $addressHandler = new AddressHandler();

        if(!$clm_config->googlemaps){
            $mainframe->enqueueMessage( Text::_('MANNSCHAFT_GEO_OFF'), 'warning' );
            $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
        } 
        else{
            if ($n > 0) {
                foreach ($cid as $id) {
                    if ($table->load( (int)$id )) {
                        $lokal_coord = $addressHandler->convertAddress($table->lokal);
                        if(is_null($lokal_coord)||$lokal_coord==-1){
                            $unsuccessArray[] = $table->name;
                        } else {
                            $addressHandler->updateTeamCoordinates($lokal_coord, $table->id);
                        }
                    } else {
                        $mainframe->enqueueMessage( $table->getError(), 'error' );
                        $link = 'index.php?option='.$option.'&section='.$section;
                        $mainframe->redirect( $link);
                    }
                }
            } else {
                $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_SELECT' ), 'warning' );
                $link = 'index.php?option='.$option.'&section='.$section;
                $mainframe->redirect( $link);
            }

            $msg = ($n-count($unsuccessArray)) . "/" . $n . " " . Text::_('MANNSCHAFT_GEO_UPDATE') . "!<br>";
            if(count($unsuccessArray)>0){
                $msg = $msg . Text::_('MANNSCHAFT_GEO_FAILURE') . implode("<br>", $unsuccessArray);
            }

            $clmLog = new CLMLog();
            $clmLog->aktion = "Geodaten geupdated";
            $clmLog->params = array('rnd' => $cid[0], 'cids' => implode( ',', $cid ));
            $clmLog->write();

            $this->setMessage( Text::_( $msg ) );
            $mainframe->enqueueMessage( Text::_( $msg ) );
            $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
        }
    }

    function edit()
    {
        $mainframe = Factory::getApplication();
        $db        = Factory::getDBO();
        $user      = Factory::getUser();
        $task      = clm_core::$load->request_string( 'task');
        $cid       = clm_core::$load->request_array_int('cid');
        if (is_null($cid)) 
            $cid[0] = clm_core::$load->request_int('id');
        $option    = clm_core::$load->request_string('option');
        $section   = clm_core::$load->request_string('section');

        $clm_config = clm_core::$db->config();
        if ($clm_config->field_search == 1) $field_search = "js-example-basic-single";
        else $field_search = "inputbox";
        
        $row = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0] );
        $sid = $row->sid;
        if ($task =="add"){
            $sql = 'SELECT id FROM #__clm_saison WHERE archiv = 0 and published = 1';
            $db->setQuery($sql);
            $sid = $db->loadResult();
        } else {
            $sql = " SELECT sl, params FROM #__clm_liga "
                ." WHERE id =".$row->liga;
            $db->setQuery($sql);
            $lid = $db->loadObjectList();
        }

        $clmAccess = clm_core::$access;      

        if ($task == 'edit') {
            $saison = Table::getInstance( 'saisons', 'TableCLM' );
            $saison->load( $sid );
            if ($saison->archiv == "1") {
                $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_ERROR_LIGA_ARCHIV' ), 'warning' );
                $mainframe->redirect( 'index.php?option='. $option.'&section='.$section, $msg,"message" );
            }
            if ($clmAccess->access('BE_team_edit') === false) {
                $section = 'info';
                $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
                $link = 'index.php?option='.$option.'&section='.$section;
                $mainframe->redirect( $link);
            }

            if (isset($lid[0]) && $lid[0]->sl != clm_core::$access->getJid() AND $clmAccess->access('BE_team_edit') !== true ) {
                $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_ERROR_MANNSCHAFT_STAFFEL' ), 'warning' );
                $link = 'index.php?option='.$option.'&section='.$section;
                $mainframe->redirect( $link);
            }
            if ($clmAccess->access('BE_team_edit') === true) $where_sl = '';
            else $where_sl = ' AND a.sl = '.clm_core::$access->getJid();
            
            $row->checkout( $user->get('id') );
        } else {
            if ($clmAccess->access('BE_team_create') === false) {
                $section = 'info';
                $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
                $link = 'index.php?option='.$option.'&section='.$section;
                $mainframe->redirect( $link);
            }
            $where_sl = '';
            $row->published = 0;
        }

        $sql = " SELECT a.id as liga, a.name FROM #__clm_liga as a"
            ." LEFT JOIN #__clm_saison as s ON s.id = a.sid "
            ." WHERE  s.archiv = 0 ".$where_sl;
        $db->setQuery( $sql );
        $non_sl = $db->loadObjectList();
        
        if (!isset($non_sl[0]->liga) AND $clmAccess->access('BE_team_create') === false) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_ERROR_STAFFEL_MANNSCHAFT' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }

        $db->setQuery($sql);
        if (!clm_core::$db->query($sql)){
            $mainframe->enqueueMessage( $db->getErrorMsg(), 'error' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
        $ligalist[] = HTMLHelper::_('select.option', '0', Text::_( 'MANNSCHAFTEN_LIGA') , 'liga', 'name' );
        $ligalist   = array_merge( $ligalist, $db->loadObjectList() );
        $lists['liga'] = HTMLHelper::_('select.genericlist', $ligalist, 'liga', 'class="'.$field_search.'" size="1" style="width:300px"','liga', 'name', $row->liga );
        $lists['published'] = HTMLHelper::_('select.booleanlist', 'published', 'class="inputbox"', $row->published );

        $vereinlist = CLMFilterVerein::vereine_filter(0);
        $lists['verein'] = HTMLHelper::_('select.genericlist', $vereinlist, 'zps', 'class="'.$field_search.'" size="1" style="width:300px"','zps', 'name', $row->zps );

        if ($task == 'edit') { $where = " AND ( a.zps = '".$row->zps."' OR FIND_IN_SET(a.zps,'".$row->sg_zps."')) AND a.published = 1";}
        else { $where = ' AND a.zps = 0 AND a.published = 1';}
        
        $tql = ' SELECT a.jid as mf, a.name as mfname'
            .' FROM #__clm_user AS a '
            ." LEFT JOIN #__clm_saison as s ON s.id = a.sid "
            ." WHERE s.archiv = 0 "
            .$where;
        $db->setQuery($tql);
        if (!clm_core::$db->query($tql)){
            $mainframe->enqueueMessage( $db->getErrorMsg(), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
        $mflist[] = HTMLHelper::_('select.option', '0', Text::_( 'MANNSCHAFTEN_MANNSCHAFTFUEHRER' ), 'mf', 'mfname' );
        $mflist   = array_merge( $mflist, $db->loadObjectList() );
        $lists['mf'] = HTMLHelper::_('select.genericlist', $mflist, 'mf', 'class="'.$field_search.'" size="1" style="width:300px"', 'mf', 'mfname', $row->mf );

        if($task =="edit"){ 
            $sql = 'SELECT id as sid, name FROM #__clm_saison WHERE id='.$sid;
        } else { 
            $sql = 'SELECT id as sid, name FROM #__clm_saison WHERE archiv =0'; 
        }
        $db->setQuery($sql);
        if (!clm_core::$db->query($sql)){
            $mainframe->enqueueMessage( $db->getErrorMsg(), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
        if ($task !="edit") {
            $saisonlist[] = HTMLHelper::_('select.option', '0', Text::_( 'MANNSCHAFTEN_SAISON' ), 'sid', 'name' );
            $saisonlist   = array_merge( $saisonlist, $db->loadObjectList() );
        } else { 
            $saisonlist   = $db->loadObjectList(); 
        }
        $lists['saison'] = HTMLHelper::_('select.genericlist', $saisonlist, 'sid', 'class="'.$field_search.'" size="1" style="width:300px"','sid', 'name', $row->sid );

        $lid_params = array();
        if (isset($lid[0]->params)) {
            $paramsStringArray = explode("\n", $lid[0]->params);
            foreach ($paramsStringArray as $value) {
                $ipos = strpos ($value, '=');
                if ($ipos !==false) {
                    $lid_params[substr($value,0,$ipos)] = substr($value,$ipos+1);
                }
            }
        }
        $lists['pgntype']        = isset($lid_params['pgntype']) ? $lid_params['pgntype'] : 0;
        $lists['anz_sgp']        = isset($lid_params['anz_sgp']) ? $lid_params['anz_sgp'] : 1;
        $lists['noOrgReference'] = isset($lid_params['noOrgReference']) ? $lid_params['noOrgReference'] : 0;

        $sg_string = $row->sg_zps;
        $row->sg_zps = array();
        $row->sg_zps = explode(',',$sg_string);
        for ($i = 0; $i < $lists['anz_sgp']; $i++) { 
            if (!isset($row->sg_zps[$i]) OR $row->sg_zps[$i] === 0) $row->sg_zps[$i] = '0';
            $lists['sg'.$i] = HTMLHelper::_('select.genericlist', $vereinlist, 'sg_zps['.$i.']', 'class="'.$field_search.'" size="1" style="width:300px"','zps', 'name', $row->sg_zps[$i] );
        }
        require_once(JPATH_COMPONENT.DS.'views'.DS.'mannschaft.php');
        CLMViewMannschaften::mannschaft( $row, $lists, $option );
    }

    function save()
    {
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );

        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
        $db      = Factory::getDBO();
        $task    = clm_core::$load->request_string( 'task');
        $row     = Table::getInstance( 'mannschaften', 'TableCLM' );
        $pre_man = clm_core::$load->request_int( 'pre_man');
        $pre_liga = clm_core::$load->request_int( 'pre_liga');

        $post = $_POST; 
        if (!$row->bind($post)) {
            $mainframe->enqueueMessage($db->getErrorMsg(), 'error');
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
        // Als Debug-Meldung in die Joomla-Logs schreiben

        if ($row->sg_zps != '') {
            $sg_array = $row->sg_zps;
            $row->sg_zps = implode(',',$sg_array);
        }

        if (!$row->check()) {
            $mainframe->enqueueMessage("Die Eingaben sind unvollständig.", 'error');
            switch ($task) {
                case 'apply':
                    $link = ($row->id!="") ? 'index.php?option='.$option.'&section='.$section.'&task=edit&id='. $row->id : 'index.php?option='.$option.'&section='.$section.'&task=add';
                    break;
                case 'save':
                default:
                    $link = 'index.php?option='.$option.'&section='.$section;
                    break;
            }
            $mainframe->redirect( $link);
            return;
        }

        $liga_dat = Table::getInstance( 'ligen', 'TableCLM' );
        $liga_dat->load( $row->liga );

        if($liga_dat->rang!="" && $row->man_nr!=""){
            $query = " SELECT COUNT(man_nr) as countman FROM #__clm_mannschaften as m "
                ." LEFT JOIN #__clm_liga AS l ON m.liga = l.id"
                ." WHERE m.zps = '".$row->zps."'"
                ." AND m.man_nr = ".$row->man_nr
                ." AND m.sid =".$row->sid
                ." AND l.rang =".$liga_dat->rang;
            $db->setQuery($query);
            $count_mnr = $db->loadObjectList();

            $query = " SELECT m.id FROM #__clm_mannschaften as m "
                ." LEFT JOIN #__clm_liga AS l ON m.liga = l.id"
                ." WHERE m.zps = '".$row->zps."'"
                ." AND m.man_nr = ".$row->man_nr
                ." AND m.sid =".$row->sid
                ." AND l.rang =".$liga_dat->rang
                ." ORDER BY m.id ASC LIMIT 1 ";
            $db->setQuery($query);
            $count_id = $db->loadObjectList();
        } else {
            $count_id = 0;
            $count_mnr = 0;
        }

        if ($count_mnr[0]->countman > 0 AND ( !isset($row->id) OR $count_id[0]->id != $row->id)) {
            $mainframe->enqueueMessage(Text::_( 'MANNSCHAFTEN_ERROR_MANNSCHAFT_IST'), 'warning');
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link );
        }

        if ($row->name == Text::_( 'MANNSCHAFT' ).' '.$row->tln_nr AND $row->lokal == '' AND $task == 'apply') {
            $query = " SELECT id, name, lokal, adresse FROM #__clm_vereine "
                ." WHERE zps = '".$row->zps."'"
                ." AND sid =".$row->sid
                ." LIMIT 1 ";
            $db->setQuery($query);
            $club = $db->loadObjectList();
            if (isset($club[0])) {
                if ($row->name == 'Mannschaft '.$row->tln_nr || $row->name == Text::_( 'MANNSCHAFT' ).' '.$row->tln_nr) { 
                    $row->name = $club[0]->name; 
                }
                if ($row->lokal == '') { $row->lokal = $club[0]->lokal; }
                if ($task == 'save') { $task = 'apply'; }
            } else {
                $mainframe->enqueueMessage(Text::_( 'MANNSCHAFTEN_ERROR_MANNSCHAFT_CLUB'), 'warning');
                $link = 'index.php?option='.$option.'&section='.$section.'&task=edit&id='. $row->id ;
                $mainframe->redirect( $link );
            }
        }
        
        $aktion = Text::_( 'MANNSCHAFT_LOG_TEAM_EDIT');
        if (!$row->id) {
            $aktion = Text::_( 'MANNSCHAFT_LOG_TEAM_CREATE');
            $where = "sid = " . (int) $row->sid;
            $row->ordering = $row->getNextOrder( $where );
        }

        if (!$row->store()) {
            $mainframe->enqueueMessage($row->getError(), 'error');
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect($link);
        } else {
            $addressHandler = new AddressHandler();
            $lokal_coord = $addressHandler->convertAddress($row->lokal);
            $addressHandler->updateTeamCoordinates($lokal_coord, $row->id);

            if(is_null($lokal_coord)){
                $mainframe->enqueueMessage(Text::_('WARNING_ADDRESS_LOOKUP'), 'warning');
            }
        }

        if ($liga_dat->rang == 0 AND $pre_man != $row->man_nr) {
            $query = " UPDATE #__clm_meldeliste_spieler "
                ." SET  mnr = ".$row->man_nr
                ." WHERE sid = ".$row->sid
                ." AND lid = ".$row->liga
                ." AND mnr = ".$pre_man
                ." AND (zps = '".$row->zps."' OR zps = '".$row->sg_zps."')";
            clm_core::$db->query($query);
        }

        if ($liga_dat->rang == 0 AND $pre_liga != $row->liga) {
				  	$query= "UPDATE #__clm_meldeliste_spieler "
                . "SET lid = ".$row->liga
                ." WHERE sid = ".$row->sid
                ." AND lid = ".$pre_liga
                ." AND mnr = ".$row->man_nr
                ." AND (zps = '".$row->zps."' OR zps = '".$row->sg_zps."')";
            clm_core::$db->query($query);
				}

        clm_core::$api->db_tournament_ranking($row->liga,true); 

        switch ($task) {
            case 'apply':
                $msg = Text::_( 'MANNSCHAFTEN_AENDERUNGEN' );
                $link = 'index.php?option='.$option.'&section='.$section.'&task=edit&id='. $row->id ;
                break;
            case 'save':
            default:
                $msg = Text::_( 'MANNSCHAFTEN_MANNSCHAFT');
                $link = 'index.php?option='.$option.'&section='.$section;
                break;
        }
        
        $clmLog = new CLMLog();
        $clmLog->aktion = $aktion;
        $clmLog->params = array('sid' => $row->sid, 'lid' => $row->liga, 'zps' => $row->zps);
        $clmLog->write();
        
        $mainframe->enqueueMessage( $msg );
        $mainframe->redirect( $link );
    }

    function cancel()
    {
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
        $msg = Text::_( 'MANNSCHAFTEN_AKTION');
        $mainframe->enqueueMessage( $msg, 'message' );
        $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
    }

    function remove()
    {
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );

        $clmAccess = clm_core::$access;      
        $db      = Factory::getDBO();
        $cid     = clm_core::$load->request_array_int('cid');
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
                               
        if (count($cid) < 1) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_SELECT', true ), 'warning' );
            $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
        }

        $row = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0] );

        $sql = " SELECT sl FROM #__clm_liga WHERE id =".$row->liga." AND sid =".$row->sid;
        $db->setQuery($sql);
        $lid = $db->loadObjectList();

        $ligen = array();
        $vorher = 0;

        foreach($cid as $id) {
            $row->load( $id );
            if($vorher != $row->liga) {
                $ligen[] = $row->liga;
                $vorher  = $row->liga;
            }
        }
        $counter = implode( ',', $ligen );

        if($counter != ""){
            $query = " SELECT COUNT(id) as count FROM #__clm_rnd_man "
                .' WHERE lid IN ( '. $counter .' )'
                ." AND sid =".$row->sid
                .' AND gemeldet > 0';
            $db->setQuery($query);
            $liga_count = $db->loadObjectList();
            $count = $liga_count[0]->count;
        } else {
            $count = 0;
        }

        if ( $count > 0 ) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_NO_LOESCH' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
        
        if ($clmAccess->access('BE_team_delete') === false) {
            $section = 'info';
            $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
                                                                                                    
        if ( $lid[0]->sl != clm_core::$access->getJid() AND $clmAccess->access('BE_team_delete') !== true ) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_MANNSCHAFT_LOESCH' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        } else {
            if ( $clmAccess->access('BE_team_delete') === true) {
                $cids = implode( ',', $cid );
                foreach($cid as $c_id) {
                    $row->load( $c_id );
                    $query = " DELETE FROM #__clm_meldeliste_spieler "
                        .' WHERE mnr ='.$row->man_nr
                        .' AND lid ='.$row->liga
                        ." AND sid =".$row->sid;
                    clm_core::$db->query($query); 
                }
                $query = " DELETE FROM #__clm_mannschaften WHERE id IN ($cids)";
                if (!clm_core::$db->query($query)) {
                    echo "<script> alert('".$db->getErrorMsg(true)."'); window.history.go(-1); </script>\n";
                }

                $msg = (!is_array($cid)) ? Text::_( 'MANNSCHAFT_MSG_DEL_ENTRY' ) : count($cid).Text::_( 'MANNSCHAFT_MSG_DEL_ENTRYS' );
            } else {
                $row->load( $cid[0] );
                $query = " DELETE FROM #__clm_meldeliste_spieler "
                    .' WHERE mnr ='.$row->man_nr
                    .' AND lid ='.$row->liga
                    ." AND sid =".$row->sid;
                clm_core::$db->query($query); 

                $query = " DELETE FROM #__clm_mannschaften WHERE id = ".$cid[0];
                $msg = Text::_( 'MANNSCHAFT_MSG_DEL_ENTRY' );
            }
        }
        
        if (!clm_core::$db->query($query)) {
            echo "<script> alert('".$db->getErrorMsg(true)."'); window.history.go(-1); </script>\n"; 
        }

        $clmLog = new CLMLog();
        $clmLog->aktion = Text::_( 'MANNSCHAFT_LOG_TEAM_DELETE');
        $clmLog->params = array('cids' => $cids, 'zps' => $row->zps);
        $clmLog->write();
        
        $mainframe->enqueueMessage( $msg, 'message' );
        $mainframe->redirect( 'index.php?option='. $option.'&section='.$section);
    }

    function publish()
    {
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );

        $db      = Factory::getDBO();
        $user    = Factory::getUser();
        $cid     = clm_core::$load->request_array_int('cid');
        $task    = clm_core::$load->request_string( 'task' );
        $publish = ($task == 'publish');
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
                               
        if (empty( $cid )) {
            $mainframe->enqueueMessage( 'No items selected', 'warning' );
            $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
        }
        
        $row = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0] );

        $sql = " SELECT sl FROM #__clm_liga WHERE id =".$row->liga." AND sid =".$row->sid;
        $db->setQuery($sql);
        $lid = $db->loadObjectList();

        $clmAccess = clm_core::$access;      
        if ($clmAccess->access('BE_team_edit') === false) {
            $section = 'info';
            $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
                                                                                                    
        if ( $lid[0]->sl != clm_core::$access->getJid() AND $clmAccess->access('BE_team_edit') !== true ) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_MANNSCHAFT_PUB' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        } else {
            if ( $clmAccess->access('BE_team_edit') === true ) {
                $cids = implode( ',', $cid );
                $query = ' UPDATE #__clm_mannschaften'
                    .' SET published = '.(int) $publish
                    .' WHERE id IN ( '. $cids .' )'
                    .' AND ZPS !="0" '
                    .' AND ( checked_out IS NULL OR checked_out = 0 OR ( checked_out = '.(int) $user->get('id') .' ) )';
            } else {
                $query = 'UPDATE #__clm_mannschaften'
                    . ' SET published = '.(int) $publish
                    . ' WHERE id = '.$cid[0]
                    . ' AND ( checked_out IS NULL OR checked_out = 0 OR ( checked_out = '.(int) $user->get('id') .' ) )';
            }
        }
        
        if (!clm_core::$db->query($query)) { 
            $mainframe->enqueueMessage( $db->getErrorMsg(), 'error' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }

        $clmLog = new CLMLog();
        $clmLog->aktion = Text::_( 'MANNSCHAFT_LOG_TEAM')." ".$task;
        $table = Table::getInstance( 'mannschaften', 'TableCLM');
        $table->load($cid[0]);
        $clmLog->params = array('sid' => $table->sid, 'lid' => $table->liga, 'zps' => $table->zps, 'cids' => $cids);
        $clmLog->write();
        
        $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
    }

    function orderdown() { CLMControllerMannschaften::order( 1 ); }
    function orderup()   { CLMControllerMannschaften::order( -1 ); }

    function order( $inc )
    {
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );

        $cid     = clm_core::$load->request_array_int('cid');
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');

        $row = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0]);
        $row->move( $inc, 'liga = '.(int) $row->liga.' AND published != 0' );

        $mainframe->enqueueMessage( Text::_( 'MANNSCHAFT_MSG_SORT') );
        $mainframe->redirect( 'index.php?option='. $option.'&section='.$section);
    }

    function saveOrder()
    {
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );

        $db      = Factory::getDBO();
        $cid     = clm_core::$load->request_array_int('cid');
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
                               
        $total = count( $cid );
        $order = clm_core::$load->request_array_int('order');

        $row = Table::getInstance( 'mannschaften', 'TableCLM' );
        $groupings = array();

        for( $i=0; $i < $total; $i++ ) {
            $row->load( (int) $cid[$i] );
            $groupings[] = $row->liga;

            if ($row->ordering != $order[$i]) {
                $row->ordering = $order[$i];
                if (!$row->store()) {
                    $mainframe->enqueueMessage( $db->getErrorMsg(), 'error' );
                    $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
                }
            }
        }

        $groupings = array_unique( $groupings );
        foreach ($groupings as $group){
            $row->reorder('liga = '.(int) $group);
        }
                                  
        $mainframe->enqueueMessage( Text::_('CLM_NEW_ORDERING_SAVED') );
        $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
    }

    function copy()
    {
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
        $cid     = clm_core::$load->request_array_int('cid');
        $db      = Factory::getDBO();
        $table   = Table::getInstance('mannschaften', 'TableCLM');
        $n       = count( $cid );
        $this->setRedirect( 'index.php?option='.$option.'&section='.$section );

        $row = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0] );

        $sql = " SELECT sl FROM #__clm_liga WHERE id =".$row->liga." AND sid =".$row->sid;
        $db->setQuery($sql);
        $lid = $db->loadObjectList();

        $clmAccess = clm_core::$access;      
        if ($clmAccess->access('BE_team_create') === false) {
            $section = 'info';
            $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
                                                                                                    
        if ( $lid[0]->sl != clm_core::$access->getJid() AND $clmAccess->access('BE_team_create') !== true ) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_MANNSCHAFT_KOPIE' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }

        $query = ' SELECT man_nr FROM #__clm_mannschaften WHERE sid ='.$row->sid.' ORDER BY man_nr DESC LIMIT 1';
        $db->setQuery( $query );
        $high_mnr = $db->loadResult();

        $query = ' SELECT tln_nr FROM #__clm_mannschaften WHERE sid ='.$row->sid.' ORDER BY tln_nr DESC LIMIT 1';
        $db->setQuery( $query );
        $high_tlnr = $db->loadResult();

        $p=1;
        if ($n > 0) {
            foreach ($cid as $id) {
                if ($table->load( (int)$id )) {
                    $table->id        = 0;
                    $table->name      = 'Kopie von ' . $table->name;
                    $table->published = 0;
                    $table->man_nr    = $high_mnr + $p;
                    $table->tln_nr    = $high_tlnr + $p;
                    $table->liste     = 0;
                    $table->mf        = 0;
                    $p++;
                    if (!$table->store()) {    
                        $mainframe->enqueueMessage( $table->getError(), 'warning' );
                        $link = 'index.php?option='.$option.'&section='.$section;
                        $mainframe->redirect( $link);
                    }
                } else {    
                    $mainframe->enqueueMessage( $table->getError(), 'warning' );
                    $link = 'index.php?option='.$option.'&section='.$section;
                    $mainframe->redirect( $link);
                }
            }
        } else {    
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_ITEMS' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }

        $msg = ($n > 1) ? Text::_( 'MANNSCHAFT_MSG_COPY_ENTRYS') : Text::_( 'MANNSCHAFT_MSG_COPY_ENTRY');
        
        $clmLog = new CLMLog();
        $clmLog->aktion = Text::_( 'MANNSCHAFT_LOG_TEAM_COPY');
        $table = Table::getInstance( 'mannschaften', 'TableCLM');
        $table->load($cid[0]);
        $clmLog->params = array('sid' => $table->sid, 'lid' => $table->liga, 'zps' => $table->zps, 'cids' => implode( ',', $cid ));
        $clmLog->write();
        
        $this->setMessage( Text::_( $n.$msg ) );
        $mainframe->enqueueMessage( Text::_( $n.$msg ) );
        $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
    }

    public static function meldeliste()
    {
        defined('clm') or die( 'Invalid Token' );
        $mainframe = Factory::getApplication();

        $user = Factory::getUser();
        $cid  = clm_core::$load->request_array_int( 'cid');
        if (is_null($cid)) $cid[0] = clm_core::$load->request_int('id');
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
                                          
        if ($cid[0] < 1) {
            $msg = Text::_( 'MANNSCHAFTEN_MELDELISTE');
            $mainframe->enqueueMessage( $msg );
            $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
        }

        $row = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0] );

        $config = clm_core::$db->config();
        $rang   = $config->rangliste;

        $rowliga = Table::getInstance( 'ligen', 'TableCLM' );
        $rowliga->load( $row->liga );

        $link = 'index.php?option='.$option.'&section='.$section;

        $clmAccess = clm_core::$access;      
        if ($clmAccess->access('BE_team_registration_list') === false) {
            $section = 'info';
            $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }

        if ( $rang == 0 AND $rowliga->sl != clm_core::$access->getJid() AND $clmAccess->access('BE_team_registration_list') !== true) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_MELDELISTE_BEARBEITEN' ), 'warning' );
            $mainframe->redirect( $link);
        }

        if ( $rowliga->rang > 0) {
            $mainframe->enqueueMessage( Text::_('MANNSCHAFTEN_NO_MELDELISTE' ), 'warning' );
            $mainframe->enqueueMessage( Text::_('MANNSCHAFTEN_MANNSCHAFT_RANG' ), 'notice' );
            $msg = Text::_( 'MANNSCHAFTEN_RANG_VEREIN' );
            $mainframe->enqueueMessage( $msg, 'message' );
            $mainframe->redirect( $link );
        }

        $row->checkout( $user->get('id') );
        $mainframe->redirect( 'index.php?option='.$option.'&section=meldelisten&task=edit&id='.$cid[0]);
    }

    public static function copy_meldeliste()
    {
        defined('clm') or die( 'Invalid Token' );
        $mainframe = Factory::getApplication();

        $cid = clm_core::$load->request_array_int( 'cid');
        if (is_null($cid)) $cid[0] = clm_core::$load->request_int('id');
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
                                          
        if ($cid[0] < 1) {
            $msg = Text::_( 'MANNSCHAFTEN_MELDELISTE');
            $mainframe->enqueueMessage( $msg );
            $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
        }

        $row = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0] );

        $config = clm_core::$db->config();
        $rang   = $config->rangliste;

        $rowliga = Table::getInstance( 'ligen', 'TableCLM' );
        $rowliga->load( $row->liga );

        $link = 'index.php?option='.$option.'&section='.$section;

        $clmAccess = clm_core::$access;      
        if ($clmAccess->access('BE_team_registration_list') === false) {
            $section = 'info';
            $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }

        if ( $rang == 0 AND $rowliga->sl != clm_core::$access->getJid() AND $clmAccess->access('BE_team_registration_list') !== true) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_MELDELISTE_BEARBEITEN' ), 'warning' );
            $mainframe->redirect( $link);
        }

        if ( $rowliga->rang > 0) {
            $mainframe->enqueueMessage( Text::_('MANNSCHAFTEN_NO_MELDELISTE' ), 'warning' );
            $mainframe->enqueueMessage( Text::_('MANNSCHAFTEN_MANNSCHAFT_RANG' ), 'notice' );
            $msg = Text::_( 'MANNSCHAFTEN_RANG_VEREIN' );
            $mainframe->enqueueMessage( $msg, 'message' );
            $mainframe->redirect( $link );
        }

        if (is_null($row->zps) OR $row->zps < '2') {
            $mainframe->enqueueMessage( Text::_( 'Es ist noch kein Verein zugeordnet' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }

        if (!is_null($row->liste) AND $row->liste > 0) {
            $mainframe->enqueueMessage( Text::_( 'Meldeliste ist bereits angelegt' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }

        $mainframe->redirect( 'index.php?option='.$option.'&view=copymeldeliste&id='.$cid[0]);
    }

    public static function delete_meldeliste()
    {
        defined('clm') or die( 'Invalid Token' );
        $mainframe = Factory::getApplication();

        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
        $link    = 'index.php?option='.$option.'&section='.$section;

        $cid = clm_core::$load->request_array_int( 'cid');
        if (is_null($cid)) $cid[0] = clm_core::$load->request_int('id');

        if (count($cid) < 1) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_LISTE_LOSCH'), 'warning' );
            $mainframe->redirect( $link );
        }

        $row = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0]);
        $rowliga = Table::getInstance( 'ligen', 'TableCLM' );
        $rowliga->load( $row->liga );

        $clmAccess = clm_core::$access;      
        if ($clmAccess->access('BE_team_registration_list') === false) {
            $section = 'info';
            $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
            $mainframe->redirect( $link);
        }

        if ( $rowliga->rang > 0) {
            $mainframe->enqueueMessage( Text::_('MANNSCHAFTEN_NO_LOESCH' ), 'warning' );
            $mainframe->enqueueMessage( Text::_('MANNSCHAFTEN_MANNSCHAFT_RANG' ), 'notice' );
            $msg = Text::_( 'MANNSCHAFTEN_RANG_VEREIN' );
            $mainframe->enqueueMessage( $msg, 'message' );
            $mainframe->redirect( $link );
        }

        if ( $rowliga->sl != clm_core::$access->getJid() AND $clmAccess->access('BE_team_registration_list') !== true) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_MELDE_LOESCH' ), 'warning' );
            $mainframe->redirect( $link);
        }

        $zps    = $row->zps;
        $sg_zps = $row->sg_zps;
        $man_nr = $row->man_nr;
        $sid    = $row->sid;
        $lid    = $row->liga;

        $query = "DELETE FROM #__clm_meldeliste_spieler"
            ." WHERE ( zps = '$zps' OR FIND_IN_SET(zps,'".$sg_zps."') != 0 )"
            ." AND  mnr = ".$man_nr
            ." AND sid = ".$sid 
            ." AND lid = ".$lid;
        clm_core::$db->query($query);    

        $date = Factory::getDate();
        $now  = $date->toSQL();

        $query = "UPDATE #__clm_mannschaften"
            ." SET edit_liste = ".clm_core::$access->getJid()
            ." , edit_datum = '$now'"
            ." , liste = 0"
            ." WHERE sid = ".$sid
            ." AND man_nr = ".$man_nr
            ." AND zps = '$zps'";
        clm_core::$db->query($query);    

        $clmLog = new CLMLog();
        $clmLog->aktion = Text::_( 'MANNSCHAFT_LOG_LIST_DELETE');
        $clmLog->params = array('sid' => $sid, 'lid' => $lid, 'zps' => $zps, 'man' => $man_nr, 'cids' => $cid[0]);
        $clmLog->write();
        
        $msg = Text::_( 'MANNSCHAFTEN_MELDE_GELOESCHT');
        $mainframe->enqueueMessage( $msg, 'message' );
        $mainframe->redirect( $link );
    }

    public static function save_meldeliste()
    {
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );

        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
        $user    = Factory::getUser();
        $meldung = $user->get('id');

        $task   = clm_core::$load->request_string( 'task');
        $row    = Table::getInstance( 'mannschaften', 'TableCLM' );
        $cid    = clm_core::$load->request_int( 'id');
        $row->load( $cid);

        $stamm   = clm_core::$load->request_string( 'stamm');
        $ersatz  = clm_core::$load->request_string( 'ersatz');
        $zps     = clm_core::$load->request_string( 'zps');
        $mnr     = clm_core::$load->request_string( 'mnr');
        $sid     = clm_core::$load->request_string( 'sid');
        $editor  = clm_core::$load->request_string( 'editor');
        $liga    = $row->liga;
        $sg_zps  = $row->sg_zps;

        $date = Factory::getDate();
        $now  = $date->toSQL();
        
        $config = clm_core::$db->config();
        $countryversion = $config->countryversion;
        
        if ($row->liste > 0) {
            $aktion = Text::_( 'MANNSCHAFT_LOG_LIST_EDIT');
            $query  = "UPDATE #__clm_mannschaften SET edit_liste = ".$meldung." , edit_datum = '$now' WHERE sid = ".$sid." AND man_nr = ".$mnr." AND zps = '$zps'";
        } else {
            $aktion = Text::_( 'MANNSCHAFT_LOG_LIST_CREATE');
            $query  = "UPDATE #__clm_mannschaften SET liste = ".$meldung." , datum = '$now' WHERE sid = ".$sid." AND man_nr = ".$mnr." AND zps = '$zps'";
        }
        clm_core::$db->query($query);    

        $query = "DELETE FROM #__clm_meldeliste_spieler WHERE lid = $liga AND mnr = ".$mnr." AND sid = ".$sid." AND ( zps ='".$zps."' OR FIND_IN_SET(zps,'".$sg_zps."') != 0 )";
        clm_core::$db->query($query);    

        for ($y=1; $y< 1+($stamm+$ersatz); $y++){
            $spl    = clm_core::$load->request_string( 'spieler'.$y);
            $block  = clm_core::$load->request_int( 'check'.$y);
            $blockm = clm_core::$load->request_int( 'checkm'.$y);
            $attr   = clm_core::$load->request_string( 'attr'.$y);
            if ($attr == '') $attr = NULL;
            
            $teil = explode("-", $spl);
            if ($countryversion == "de") {
                $mgl_nr = $teil[0];
                $PKZ    = '';
            } else {
                $mgl_nr = 0;
                $PKZ    = $teil[0];
            }
            $tzps   = isset($teil[1]) ? $teil[1] : '';
            $dwz    = (isset($teil[2]) && $teil[2] != '') ? $teil[2] : '0';
            $dwz_I0 = (isset($teil[3]) && $teil[3] != '') ? $teil[3] : '0';

            if ($spl > 0) {
                $query = "REPLACE INTO #__clm_meldeliste_spieler"
                    ." ( `sid`, `lid`, `mnr`, `snr`, `mgl_nr`, `PKZ`, `zps`, `ordering`, `gesperrt`, `gesperrtm`, `start_dwz`, `start_I0`, `attr`) "
                    . " VALUES ('$sid','$liga','$mnr','$y','$mgl_nr','$PKZ','$tzps', 0, '$block', '$blockm','$dwz','$dwz_I0'";
                $query .= (!is_null($attr)) ? ",'$attr') " : ", NULL) ";
                clm_core::$db->query($query);    
            }
        }

        for ($y=1; $y< 1+($stamm+$ersatz); $y++){
            $spl   = clm_core::$load->request_string( 'spieler'.$y, '');
            $block = clm_core::$load->request_int( 'check'.$y, -1);
            if ($spl > 0) {
                $teil = explode("-", $spl);
                if ($countryversion == "de") {
                    $mgl_nr = $teil[0];
                    $PKZ    = '';
                } else {
                    $mgl_nr = 0;
                    $PKZ    = $teil[0];
                }
                $tzps = isset($teil[1]) ? $teil[1] : '';
                $rc   = clm_core::$api->db_syn_player_block($sid,$tzps,$mgl_nr,$block);
                if ($rc[0] === false) {
                    $mainframe->enqueueMessage( "m_updateError".$rc[1], 'error' );
                } else {
                    $mainframe->enqueueMessage( $rc[1], 'message' );
                }
            }
        }

        switch ($task) {
            case 'apply':
                $msg  = Text::_( 'MANNSCHAFTEN_AENDERUNGN');
                $link = 'index.php?option='.$option.'&section=meldelisten&task=edit&id='. $cid ;
                break;
            case 'save':
            default:
                $msg  = Text::_( 'MANNSCHAFTEN_MANNSCHAFT_GESPEICHERT' );
                $link = 'index.php?option='.$option.'&section='.$section;
                break;
        }
        
        $clmLog = new CLMLog();
        $clmLog->aktion = $aktion;
        $clmLog->params = array('sid' => $sid, 'lid' => $liga, 'zps' => $zps, 'cids' => $cid);
        $clmLog->write();
        
        $mainframe->enqueueMessage( $msg );
        $mainframe->redirect( $link );
    }

    public static function apply_meldeliste()
    {
        $mainframe = Factory::getApplication();
        defined('clm') or die( 'Invalid Token' );

        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
        $user    = Factory::getUser();
        $meldung = $user->get('id');

        $task   = clm_core::$load->request_string( 'task');
        $row    = Table::getInstance( 'mannschaften', 'TableCLM' );
        $cid    = clm_core::$load->request_int( 'id');
        $row->load( $cid);

        $stamm   = clm_core::$load->request_string( 'stamm');
        $ersatz  = clm_core::$load->request_string( 'ersatz');
        $zps     = clm_core::$load->request_string( 'zps');
        $mnr     = clm_core::$load->request_string( 'mnr');
        $sid     = clm_core::$load->request_string( 'sid');
        $liga    = $row->liga;
        $sg_zps  = $row->sg_zps;

        $date = Factory::getDate();
        $now  = $date->toSQL();
        
        $config = clm_core::$db->config();
        $countryversion = $config->countryversion;

        if ($row->liste > 0) {
            $aktion = Text::_( 'MANNSCHAFT_LOG_LIST_EDIT');
            $query  = "UPDATE #__clm_mannschaften SET edit_liste = ".$meldung." , edit_datum = '$now' WHERE sid = ".$sid." AND man_nr = ".$mnr." AND zps = '$zps'";
        } else {
            $aktion = Text::_( 'MANNSCHAFT_LOG_LIST_CREATE');
            $query  = "UPDATE #__clm_mannschaften SET liste = ".$meldung." , datum = '$now' WHERE sid = ".$sid." AND man_nr = ".$mnr." AND zps = '$zps'";
        }
        clm_core::$db->query($query);    

        $query = "DELETE FROM #__clm_meldeliste_spieler WHERE lid = $liga AND mnr = ".$mnr." AND sid = ".$sid." AND ( zps ='".$zps."' OR FIND_IN_SET(zps,'".$sg_zps."') != 0 )";
        clm_core::$db->query($query);    

        for ($y=1; $y< 1+($stamm+$ersatz); $y++){
            $spl    = clm_core::$load->request_string( 'spieler'.$y);
            $block  = clm_core::$load->request_int( 'check'.$y);
            $blockm = clm_core::$load->request_int( 'checkm'.$y);
            $attr   = clm_core::$load->request_string( 'attr'.$y);
            if ($attr == '') $attr = NULL;

            $teil = explode("-", $spl);
            if ($countryversion == "de") {
                $mgl_nr = $teil[0];
                $PKZ    = '';
            } else {
                $mgl_nr = 0;
                $PKZ    = $teil[0];
            }
            $tzps   = isset($teil[1]) ? $teil[1] : '';
            $dwz    = (isset($teil[2]) && $teil[2] != '') ? $teil[2] : '0';
            $dwz_I0 = (isset($teil[3]) && $teil[3] != '') ? $teil[3] : '0';

            if($spl > 0){
                $query = "REPLACE INTO #__clm_meldeliste_spieler"
                    ." ( `sid`, `lid`, `mnr`, `snr`, `mgl_nr`, `PKZ`, `zps`, `ordering`, `gesperrt`, `gesperrtm`, `start_dwz`, `start_I0`, `attr`) "
                    . " VALUES ('$sid','$liga','$mnr','$y','$mgl_nr','$PKZ','$tzps', 0,'$block','$blockm','$dwz','$dwz_I0'";
                $query .= (!is_null($attr)) ? ",'$attr') " : ", NULL) ";
                clm_core::$db->query($query);    
            }
        }

        $clmLog = new CLMLog();
        $clmLog->aktion = $aktion;
        $clmLog->params = array('sid' => $sid, 'lid' => $liga, 'zps' => $zps, 'cids' => $cid);
        $clmLog->write();
        
        $msg = Text::_( 'MANNSCHAFTEN_AENDERUNGN' );
        $mainframe->enqueueMessage( $msg );
        $link = 'index.php?option=com_clm&section=meldelisten&task=edit&id='. $cid ;
        $mainframe->redirect( $link );
    }

    public static function spielfrei()
    {
        defined('clm') or die( 'Invalid Token' );
        $mainframe = Factory::getApplication();

        $db      = Factory::getDBO();
        $cid     = clm_core::$load->request_array_int('cid');
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
                                          
        if ($cid[0] < 1) {
            $msg = Text::_( 'MANNSCHAFTEN_MANNSCHAFT_AUS');
            $mainframe->enqueueMessage( $msg, 'warning' );
            $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
        }

        $row  = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0] );
        $tlnr = $row->tln_nr;

        $rowliga = Table::getInstance( 'ligen', 'TableCLM' );
        $rowliga->load( $row->liga );

        $link = 'index.php?option='.$option.'&section='.$section;

        $clmAccess = clm_core::$access;      
        if ($clmAccess->access('BE_team_edit') === false) {
            $section = 'info';
            $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
        if ( $rowliga->sl != clm_core::$access->getJid() AND $clmAccess->access('BE_team_edit') !== true) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_MANNSCHAFT_SPIELFREI' ), 'warning' );
            $mainframe->redirect( $link);
        }

        $query = "DELETE FROM #__clm_rnd_spl WHERE sid = ".$row->sid." AND lid = ".$row->liga." AND tln_nr = $tlnr ";
        clm_core::$db->query($query);    

        $query = "UPDATE #__clm_rnd_man SET brettpunkte = NULL, manpunkte = NULL, bp_sum = NULL, mp_sum = NULL, gemeldet = 1, wertpunkte = NULL WHERE sid = ".$row->sid." AND lid = ".$row->liga." AND ( tln_nr = $tlnr OR gegner = $tlnr) ";
        clm_core::$db->query($query);    

        $query = "UPDATE #__clm_mannschaften SET name = 'spielfrei', zps = '0', man_nr = 0, liste = 0, edit_liste = 0, mf = 0, sg_zps = '0', published = 0 WHERE sid = ".$row->sid." AND liga = ".$row->liga." AND tln_nr = $tlnr ";
        clm_core::$db->query($query);    

        $clmLog = new CLMLog();
        $clmLog->aktion = Text::_( 'MANNSCHAFT_LOG_NO_GAMES');
        $clmLog->params = array('sid' => $row->sid, 'lid' => $row->liga, 'man' => $tlnr, 'cids' => $cid[0]);
        $clmLog->write();
        
        $msg = Text::_( 'MANNSCHAFTEN_MANNSCHAFT_SPIELF' );
        $mainframe->enqueueMessage( $msg, 'message' );
        $mainframe->redirect( 'index.php?option='.$option.'&section='.$section);
    }

    public static function annull()
    {
        defined('clm') or die( 'Invalid Token' );
        $mainframe = Factory::getApplication();

        $db      = Factory::getDBO();
        $cid     = clm_core::$load->request_array_int('cid');
        $option  = clm_core::$load->request_string('option');
        $section = clm_core::$load->request_string('section');
                                          
        if ($cid[0] < 1) {
            $msg = Text::_( 'MANNSCHAFTEN_MANNSCHAFT_AUS');
            $mainframe->enqueueMessage( $msg, 'warning' );
            $mainframe->redirect( 'index.php?option='. $option.'&section='.$section );
        }

        $row  = Table::getInstance( 'mannschaften', 'TableCLM' );
        $row->load( $cid[0] );
        $tlnr = $row->tln_nr;

        $rowliga = Table::getInstance( 'ligen', 'TableCLM' );
        $rowliga->load( $row->liga );

        $link = 'index.php?option='.$option.'&section='.$section;

        $clmAccess = clm_core::$access;      
        if ($clmAccess->access('BE_team_edit') === false) {
            $section = 'info';
            $mainframe->enqueueMessage( Text::_( 'TEAM_NO_ACCESS' ), 'warning' );
            $link = 'index.php?option='.$option.'&section='.$section;
            $mainframe->redirect( $link);
        }
        if ( $rowliga->sl != clm_core::$access->getJid() AND $clmAccess->access('BE_team_edit') !== true) {
            $mainframe->enqueueMessage( Text::_( 'MANNSCHAFTEN_MANNSCHAFT_ANNULL' ), 'warning' );
            $mainframe->redirect( $link);
        }

        $tparams = new clm_class_params($rowliga->params);
        $params_annul_proc = $tparams->get('annul_proc', '0');
        
        if ($params_annul_proc == '0') {
            $query = "UPDATE #__clm_rnd_man SET brettpunkte = 0, manpunkte = 0, bp_sum = NULL, mp_sum = NULL, gemeldet = 1, wertpunkte = 0 WHERE sid = ".$row->sid." AND lid = ".$row->liga." AND ( tln_nr = $tlnr OR gegner = $tlnr) ";
            clm_core::$db->query($query);
        } else {
            $query = "UPDATE #__clm_rnd_man SET brettpunkte = 0, manpunkte = 0, bp_sum = NULL, mp_sum = NULL, gemeldet = 1, wertpunkte = 0 WHERE sid = ".$row->sid." AND lid = ".$row->liga." AND tln_nr = $tlnr ";
            clm_core::$db->query($query);
            
            $query = "UPDATE #__clm_rnd_man SET brettpunkte = '".$rowliga->stamm."', manpunkte = 2, bp_sum = NULL, mp_sum = NULL, gemeldet = 1, wertpunkte = 0 WHERE sid = ".$row->sid." AND lid = ".$row->liga." AND gegner = $tlnr ";
            clm_core::$db->query($query);
        }

        clm_core::$api->db_tournament_ranking($row->liga,true);
        
        $clmLog = new CLMLog();
        $clmLog->aktion = Text::_( 'MANNSCHAFT_LOG_ANNULL');
        $clmLog->params = array('sid' => $row->sid, 'lid' => $row->liga, 'man' => $tlnr, 'cids' => $cid[0]);
        $clmLog->write();
        
        $msg = Text::_( 'MANNSCHAFTEN_MANNSCHAFT_ANNULL' );
        $mainframe->enqueueMessage( $msg, 'message' );
        $mainframe->redirect( 'index.php?option='.$option.'&section='.$section);
    }
}

