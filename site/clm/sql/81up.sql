-- Mannschaften mit DWZ Schnitt

-- BSV 1.1

CREATE OR REPLACE VIEW `#__clm_mannschaften_view` AS
SELECT a.*, ms.durchschnitt_dwz FROM `#__clm_mannschaften` AS a LEFT JOIN (SELECT m.lid, m.mnr, ROUND(AVG(m.start_dwz), 0) AS durchschnitt_dwz FROM `#__clm_meldeliste_spieler` AS m INNER JOIN `#__clm_liga` AS l ON l.id = m.lid WHERE m.snr <= l.stamm AND m.start_dwz > 0 GROUP BY m.lid, m.mnr ) AS ms ON ms.lid = a.liga AND ms.mnr = a.man_nr;
