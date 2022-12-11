<?php

/*

  view_del.php - delete a view
  -------------
  
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
  along with zukunft.com. If not, see <http://www.gnu.org/licenses/agpl.html>.
  
  To contact the authors write to:
  Timon Zielonka <timon@zukunft.com>
  
  Copyright (c) 1995-2022 zukunft.com AG, Zurich
  Heang Lor <heang@zukunft.com>
  
  http://zukunft.com
  
*/

// standard zukunft header for callable php files to allow debugging and lib loading
$debug = $_GET['debug'] ?? 0;
const ROOT_PATH = __DIR__ . '/../';
include_once ROOT_PATH . 'src/main/php/zu_lib.php';

// open database
$db_con = prg_start("view_del");

$result = ''; // reset the html code var
$msg = ''; // to collect all messages that should be shown to the user immediately

// load the session user parameters
$usr = new user;
$result .= $usr->get();

// check if the user is permitted (e.g. to exclude crawlers from doing stupid stuff)
if ($usr->id > 0) {

    load_usr_data();

    // prepare the display
    $dsp = new view_dsp_old($usr);
    $dsp->set_id(cl(db_cl::VIEW, view::DEL));
    $dsp->load_obj_vars();
    $back = $_GET['back']; // the original calling page that should be shown after the change if finished

    // get the parameters
    $dsp_del_id = $_GET['id'];
    $confirm = $_GET['confirm'];

    if ($dsp_del_id > 0) {

        // create the view object to have an object to update the parameters
        $dsp_del = new view($usr);
        $dsp_del->load_by_id($dsp_del_id);

        if ($confirm == 1) {
            $dsp_del->del();

            $result .= dsp_go_back($back, $usr);
        } else {
            // display the view header
            $result .= $dsp->dsp_navbar($back);

            $result .= \html\btn_yesno("Delete " . $dsp_del->name() . "? ", "/http/view_del.php?id=" . $dsp_del_id . "&back=" . $back);
        }
    } else {
        $result .= dsp_go_back($back, $usr);
    }
}

echo $result;

prg_end($db_con);
