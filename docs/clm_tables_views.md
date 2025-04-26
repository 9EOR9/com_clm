# CLM Tables and Views

In Joomla each table and view is prefixed by a 5 char prefix followed by an underscore, e.g.
`[#clm_config`. In this documentation we omit the prefix and replace the prefix by `#`,
as we do in php source code.

## Tables

| Table | BSV | Description |
|-------|-----|.....--------|
| #clm_arbiter                   | | Not in use yet ?! |
| #clm_arbiter_arbiterlicense    | | Not in use yet ?! |
| #clm_arbiter_turnier           | | Not in use yet ?! |
| #clm_arbiterlicense            | | Not in use yet ?!|
| #clm_categories                | | Not in use yet, or not used anymore |
| [#clm_config](tables/tbl_clm_config.md) | Stores the CLM global configuration |
| [#clm_dwz_spieler](tables/tbl_clm_dwz_spieler.md) | Players (imported from DEWIS) |
| #clm_dwz_verbaende             | x | Regional associations and districts (imported from DEWIS) |
| #clm_dwz_vereine               | x | Chess clubs (imported from DEWIS) |
| #clm_ergebnis                  | x | Configuration table for displaying results |
| #clm_liga                      | x | Chess leagues |
| #clm_logging                   | CLM Logging |
| #clm_mannschaften              | CLM chess teams |
| #clm_meldeliste_spieler        | CLM registration list for tournaments |
| #clm_online_registration       | CLM online registration |
| #clm_pgn                       | Games in portable game notation (PGN) |
| #clm_player_decode             | ? |
| #clm_rangliste_id              | ? |
| #clm_rangliste_name            | ? |
| #clm_rangliste_spieler         | ? |
| #clm_rnd_man                   | Team results |
| #clm_rnd_spl                   | Player results |
| #clm_runden_termine            | Matchdays |
| #clm_saison                    | Seasons |
| #clm_swt_dwz_spieler           | ? |
| #clm_swt_liga                  | ? |
| #clm_swt_mannschaften          | ? |
| #clm_swt_meldeliste_spieler    | ? |
| #clm_swt_rnd_man               | ? |
| #clm_swt_rnd_spl               | ? |
| #clm_swt_runden_termine        | ? |
| #clm_swt_turniere              | ? |
| #clm_swt_turniere_rnd_spl      | ? |
| #clm_swt_turniere_rnd_termine  | ? |
| #clm_swt_turniere_teams        | ? |
| #clm_swt_turniere_tlnr         | ? |
| #clm_termine                   | Tournament dates |
| #clm_turniere                  | Tournaments (no league matches) |
| #clm_turniere_grand_prix       | ? |
| #clm_turniere_rnd_spl          | ? |
| #clm_turniere_rnd_termine      | ? |
| #clm_turniere_sonderranglisten | ? |
| #clm_turniere_teams            | ? |
| #clm_turniere_tlnr             | ? |
| #clm_user                      | CLM user |
| #clm_usertype                  | CLM user types |
| #clm_vereine                   | Clubs (per season) |

