--
-- @ Chess League Manager (CLM) Component 
-- @Copyright (C) 2008-2025 CLM Team.  All rights reserved
-- @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
-- @link http://www.chessleaguemanager.de

--
-- 4.3.4  Update for InnoDB migration
--

ALTER TABLE #__clm_categories Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_config Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_dwz_spieler Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_dwz_verbaende Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_dwz_vereine Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_ergebnis Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_liga Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_logging Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_mannschaften Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_meldeliste_spieler Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_online_registration Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_pgn Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_player_decode Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_rangliste_id Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_rangliste_name Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_rangliste_spieler Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_rnd_man Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_rnd_spl Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_runden_termine Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_saison Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_dwz_spieler Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_liga Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_mannschaften Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_meldeliste_spieler Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_rnd_man Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_rnd_spl Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_runden_termine Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_turniere Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_turniere_rnd_spl Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_turniere_rnd_termine Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_turniere_teams Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_swt_turniere_tlnr Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_termine Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_turniere Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_turniere_rnd_spl Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_turniere_rnd_termine Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_turniere_sonderranglisten Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_turniere_teams Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_turniere_tlnr Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_user Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_usertype Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE #__clm_vereine Engine=InnoDB, CONVERT TO CHARACTER SET utf8mb4;

# Issue #23 - Avoid use of reserved words
ALTER TABLE #__clm_rangliste_name RENAME COLUMN `alter` TO _alter;
