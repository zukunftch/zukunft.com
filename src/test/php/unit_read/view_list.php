<?php

/*

    test/php/unit_read/view_list.php - TESTing of the VIEW LIST functions that only read from the database
    --------------------------------
  

    This file is part of zukunft.com - calc with words

    zukunft.com is free software: you can redistribute it and/or modify it
    under the words of the GNU General Public License as
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

namespace unit_read;

use api\view_api;
use cfg\view_list;
use test\test_cleanup;

class view_list_unit_db_tests
{

    function run(test_cleanup $t): void
    {

        global $usr;

        // init
        $t->name = 'view list read db->';

        $t->header('Test the view list class (classes/view_list.php)');

        // test loading view names
        $test_name = 'loading view names with pattern return the expected view';
        $pattern = substr(view_api::TN_ALL, 0, -1);
        $dsp_lst = new view_list($t->usr1);
        $dsp_lst->load_names($pattern);
        $t->assert_contains($test_name, $dsp_lst->names(), view_api::TN_ALL);
        // TODO do not exclude all system views
        //      e.g. allow the user to select the system default view Word aka TN_READ
        //      but do not allow to select system forms aka TN_FORM
        $test_name = 'system view are not included in the normal view list';
        $dsp_lst = new view_list($t->usr1);
        $dsp_lst->load_names(view_api::TN_FORM);
        $t->assert_contains_not($test_name, $dsp_lst->names(), view_api::TN_FORM);


        // test load by view list by ids
        /* TODO activate
        $test_name = 'load views by ids';
        $wrd_lst = new view_list($t->usr1);
        $wrd_lst->load_by_ids(array(1,3));
        $target = '"' . view_api::TN_READ . '","' . view_api::TN_READ . '"'; // order adjusted based on the number of usage
        $t->assert($test_name, $wrd_lst->name(), $target);
        $test_name = 'load views by names';
        $wrd_lst = new view_list($t->usr1);
        $wrd_lst->load_by_names(array(view_api::TN_READ,view_api::TN_READ_RATIO));
        $t->assert_contains($test_name, $wrd_lst->ids(), array(1,3));
        $test_name = 'load views staring with P';
        $wrd_lst = new view_list($t->usr1);
        $wrd_lst->load_like('W');
        $t->assert_contains($test_name, $wrd_lst->names(), view_api::TN_READ);
        */

    }

}

