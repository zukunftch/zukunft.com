<?php

/*

  link_edit.php - adjust a word link
  ------------
  
  This file is part of zukunft.com - calc with words

  zukunft.com is free software: you can redistribute it and/or modify it
  under the terms of the GNU General Public License as
  published by the Free Software Foundation, either version 3 of
  the License, or (at your option) any later version.
  zukunft.com is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.
  
  You should have received a copy of the GNU General Public License
  along with zukunft.com. If not, see <http://www.gnu.org/licenses/gpl.html>.
  
  To contact the authors write to:
  Timon Zielonka <timon@zukunft.com>
  
  Copyright (c) 1995-2021 zukunft.com AG, Zurich
  Heang Lor <heang@zukunft.com>
  
  http://zukunft.com
  
*/

$debug = $_GET['debug'] ?? 0;
include_once '../src/main/php/zu_lib.php';

// open database
$db_con = prg_start("link_edit");

$result = ''; // reset the html code var
$msg = ''; // to collect all messages that should be shown to the user immediately

// load the session user parameters
$usr = new user;
$result .= $usr->get();

// check if the user is permitted (e.g. to exclude crawlers from doing stupid stuff)
if ($usr->id > 0) {

    load_usr_data();

    // prepare the display
    $dsp = new view_dsp;
    $dsp->id = cl(db_cl::VIEW, view::LINK_EDIT);
    $dsp->usr = $usr;
    $dsp->load();
    $back = $_GET['back']; // the original calling page that should be shown after the change if finished

    // create the link object to have an place to update the parameters
    $lnk = new word_link;
    $lnk->id = $_GET['id'];
    $lnk->usr = $usr;
    $lnk->load();

    // edit the link or ask for confirmation
    if ($lnk->id <= 0) {
        $result .= log_err("No triple found to change because the id is missing.", "link_edit.php");
    } else {

        if ($_GET['confirm'] == 1) {

            // get the parameters
            $lnk->from->id = $_GET['phrase1']; // the word or triple linked from
            $lnk->verb->id = $_GET['verb'];    // the link type (verb)
            $lnk->to->id = $_GET['phrase2']; // the word or triple linked to

            // save the changes
            $upd_result = $lnk->save();

            // if update was successful ...
            if (str_replace('1', '', $upd_result) == '') {
                // ... display the calling view
                $result .= dsp_go_back($back, $usr);
            } else {
                // ... or in case of a problem prepare to show the message
                $msg .= $upd_result;
            }
        }

        // if nothing yet done display the add view (and any message on the top)
        if ($result == '') {
            // display the view header
            $result .= $dsp->dsp_navbar($back);
            $result .= dsp_err($msg);

            // display the word link to allow the user to change it
            $result .= $lnk->dsp_edit($back);
        }
    }
}

echo $result;

prg_end($db_con);