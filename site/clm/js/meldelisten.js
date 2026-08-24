/*
 * @ Chess League Manager (CLM) Component
 * @Copyright (C) 2008-2022 CLM Team  All rights reserved
 *            (C) 2026 Georg Richter
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.chessleaguemanager.de
 */

Joomla.submitbutton = function (pressbutton) {
  var form = document.adminForm;
  var double = 0;
  if (pressbutton == "cancel") {
    Joomla.submitform(pressbutton);
    return;
  }
  // do field validation
  for (var ispieler = 1; ispieler <= clm_meldelisten_number; ispieler++) {
    var iispieler = "spieler" + ispieler;
    for (
      var jspieler = ispieler + 1;
      jspieler <= clm_meldelisten_number;
      jspieler++
    ) {
      var jjspieler = "spieler" + jspieler;
      // get references to select list and display text box
      var sel = document.getElementById(iispieler);
      var opt;
      var ival = 0;
      for (var i = 0, len = sel.options.length; i < len; i++) {
        opt = sel.options[i];
        if (opt.selected === true) {
          ival = opt.value;
          break;
        }
      }
      var sel = document.getElementById(jjspieler);
      var opt;
      var jval = 0;
      for (var i = 0, len = sel.options.length; i < len; i++) {
        opt = sel.options[i];
        if (opt.selected === true) {
          jval = opt.value;
          break;
        }
      }
      if (ival != "0" && ival == jval) {
        alert(
          unescape(clm_meldelisten_double) +
            ":  " +
            ispieler +
            " = " +
            jspieler,
        );
        double = 1;
      }
    }
  }
  if (double == 0) {
    Joomla.submitform(pressbutton);
  }
};

function insertPosition(spieler, selvalue) {
  var selectedValue = spieler.value;

  // Process only if the "insert a player" option (value 9999) was selected
  if (selectedValue !== "9999") {
    return;
  }

  var totalBoards = Number(clm_meldelisten_number);
  var selName = spieler.name;
  var selNumber = parseInt(selName.substr(7), 10); // Extract board number (e.g., "spieler3" -> 3)

  // Prevent insertion if the last available position is already occupied
  var lastSelect = document.getElementById("spieler" + totalBoards);
  if (
    lastSelect &&
    lastSelect.value !== "0" &&
    lastSelect.value !== "" &&
    lastSelect.value !== "9999"
  ) {
    alert(clm_meldelisten_last);
    spieler.value = selvalue;
    return;
  }

  // Capture the current state of all boards into temporary arrays
  var currentPlayers = [];
  var currentAttrs = [];

  for (var i = 1; i <= totalBoards; i++) {
    var sElem = document.getElementById("spieler" + i);
    var aElem = document.getElementById("attr" + i);

    currentPlayers.push(sElem ? sElem.value : "0");
    currentAttrs.push(aElem ? aElem.value : "");
  }

  // Retrieve the active player prior to the "insert" selection.
  // Falls back to defaultSelected state or hidden attribute if parameter is invalid.
  var realOldValue = "0";

  if (selvalue && selvalue !== "9999" && selvalue !== "0") {
    realOldValue = selvalue;
  } else {
    for (var optIndex = 0; optIndex < spieler.options.length; optIndex++) {
      if (
        spieler.options[optIndex].defaultSelected &&
        spieler.options[optIndex].value !== "9999"
      ) {
        realOldValue = spieler.options[optIndex].value;
        break;
      }
    }
  }

  if (realOldValue === "0" && currentAttrs[selNumber - 1]) {
    realOldValue = currentAttrs[selNumber - 1];
  }

  // Preserve the previously selected player in the array before shifting
  currentPlayers[selNumber - 1] = realOldValue;

  // Shift all player entries down by one position starting from the target index
  for (var pos = totalBoards - 1; pos >= selNumber; pos--) {
    currentPlayers[pos] = currentPlayers[pos - 1];
    currentAttrs[pos] = currentAttrs[pos - 1];
  }

  // Clear the target board position to allow selecting a new player
  currentPlayers[selNumber - 1] = "0";
  currentAttrs[selNumber - 1] = "";

  // Write updated values back to the DOM and sync defaultSelected states for subsequent operations
  for (var k = 1; k <= totalBoards; k++) {
    var targetSelect = document.getElementById("spieler" + k);
    var targetAttr = document.getElementById("attr" + k);

    if (targetSelect) {
      targetSelect.value = currentPlayers[k - 1];

      // Explicitly set defaultSelected on option tags so future shifts remember the new state
      for (var o = 0; o < targetSelect.options.length; o++) {
        targetSelect.options[o].defaultSelected =
          targetSelect.options[o].value === currentPlayers[k - 1];
      }
    }
    if (targetAttr) {
      targetAttr.value = currentAttrs[k - 1];
    }
  }
}
