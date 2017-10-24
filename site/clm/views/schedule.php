<?php
function clm_view_schedule($out) {
	clm_core::$load->load_css("schedule");
	clm_core::$load->load_css("buttons");
	clm_core::$load->load_css("notification");
	//clm_core::$load->load_js("report");
	$lang = clm_core::$lang->schedule;
	//clm_core::$cms->setTitle($lang->title.' '.$out["club"][0]->name);
	clm_core::$cms->setTitle(html_entity_decode($lang->title." ".$out["club"][0]->name));

	// Variablen initialisieren
	$paar 		= $out["paar"];
	$club 		= $out["club"];
	//CLM parameter auslesen
	$config = clm_core::$db->config();
	$countryversion = $config->countryversion;

	echo '<table style="width:100%"><tr><th><h4>'.$club[0]->name." - ".$club[0]->season_name; 
	echo '</h4></th><th style="align:right">';
	if (isset($paar[0])) echo clm_core::$load->create_link_pdf('schedule', 'pdf-Ausgaabe', array('layout' => 'schedule', 'season' => $paar[0]->sid, 'liga' => $paar[0]->lid, 'club' => $club[0]->zps));
	echo "</th></tr></table>";
?>
<div class="flex title">
<div class="element date"><?php echo $lang->date; ?></div>
<div class="element league"><?php echo $lang->lname; ?></div>
<div class="element dg"><?php echo $lang->dg; ?></div>
<div class="element round"><?php echo $lang->round; ?></div>
<div class="element home"><?php echo $lang->home; ?></div>
<div class="element result"><?php echo $lang->result; ?></div>
<div class="element guest"><?php echo $lang->guest; ?></div>
</div>

<?php	$x = 0;
		foreach ($paar as $paar1) { ?>
<?php //echo "<br>paar1:"; var_dump($paar1);
	$x++;
	if ($x%2 != 0) { $zeilenr = 'zeile1'; }
	else { $zeilenr = 'zeile2'; } ?>
<div class="flex <?php echo $zeilenr; ?>">
<div class="element date"><?php 
							if ($paar1->rdate > "1970-01-01") { echo clm_core::$cms->showDate($paar1->rdate, "d M Y"); 
								if ($paar1->rtime != "00:00:00" AND $paar1->rtime != "24:00:00") echo "<br>".substr($paar1->rtime,0,5); }
							?></div>
<div class="element league">
    <a href="index.php?option=com_clm&view=paarungsliste&saison=<?php echo $paar1->sid; ?>&liga=<?php echo $paar1->lid; ?>">
	<?php echo $paar1->lname; ?></a></div>
<div class="element dg"><?php echo $paar1->dg; ?></div>
<div class="element round"><?php echo $paar1->runde; ?></div>
<div class="element home">
    <a href="index.php?option=com_clm&view=mannschaft&saison=<?php echo $paar1->sid; ?>&liga=<?php echo $paar1->lid; ?>&tlnr=<?php echo $paar1->htln; ?>">
	<?php echo $paar1->hname; ?></a></div>
<div class="element result"><?php echo $paar1->brettpunkte." : ".$paar1->gbrettpunkte; ?></div>
<div class="element guest">
    <a href="index.php?option=com_clm&view=mannschaft&saison=<?php echo $paar1->sid; ?>&liga=<?php echo $paar1->lid; ?>&tlnr=<?php echo $paar1->gtln; ?>">
	<?php echo $paar1->gname; ?></a></div>
</div>

<?php } 

echo '<div class="button_container">';
echo '<button type="button" onclick="javascript:history.back(1);" class="clm_button button_back">'.$lang->button_back.'</button>';
echo '</div><div class="space"></div>';
 } ?>
