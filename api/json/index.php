<?php

/*

    api/json/index.php - the json im- and export API controller
    ------------------

    use GET to retrieve a JSON that can be imported into another zukunft.com pod
    use PUT to import data from a JSON in the zukunft.com exchange format

    This file is part of zukunft.com - calc with values

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

use api\phrase_list_api;
use controller\controller;

// standard zukunft header for callable php files to allow debugging and lib loading
const ROOT_PATH = __DIR__ . '/../../';
include_once ROOT_PATH . 'src/main/php/zu_lib.php';
$debug = $_GET[controller::URL_VAR_DEBUG] ?? 0;

// open database
$db_con = prg_start("api/json", "", false);

// get the parameters
$wrd_id = $_GET[controller::URL_VAR_WORD_ID] ?? 0;

$msg = '';
$result = new phrase_list_api(); // reset the html code var

// load the session user parameters
$usr = new user;
$msg .= $usr->get();

// check if the user is permitted (e.g. to exclude crawlers from doing stupid stuff)
if ($usr->id() > 0) {

    if ($wrd_id != 0) {
        $wrd = new word($usr);
        $wrd->load_by_id($wrd_id);
        $result = json_decode(json_encode($wrd->export_obj()));
    } else {
        $msg = 'word id missing';
    }
}

$ctrl = new controller();
$ctrl->get_export($result, $msg);


prg_end_api($db_con);