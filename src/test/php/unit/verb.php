<?php

/*

    test/unit/verb.php - unit testing of the verb or phrase link functions
    ------------------
  

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

class verb_unit_tests
{
    function run(testing $t)
    {

        global $usr;

        // init
        $db_con = new sql_db();
        $t->name = 'verb->';
        $t->resource_path = 'db/verb/';
        $usr->id = 1;


        $t->header('Unit tests of the verb class (src/main/php/model/verb/verb.php)');

        $t->subheader('SQL statement tests');

        // sql to load a verb by id
        $vrb = new verb();
        $vrb->id = 4;
        $t->assert_load_sql($db_con, $vrb);

        // sql to load a verb by code id
        $vrb = new verb();
        $vrb->code_id = verb::DBL_FOLLOW;
        $t->assert_load_sql($db_con, $vrb);

        // sql to load a source by name
        $vrb = new verb();
        $vrb->name = verb::DBL_FOLLOW;
        $t->assert_load_sql($db_con, $vrb);


        $t->header('Unit tests of the verb list class (src/main/php/model/verb/verb_list.php)');

        $t->subheader('SQL statement tests');

        // sql to load a list with all verbs
        $vrb_lst = new verb_list($usr);
        $t->assert_load_sql($db_con, $vrb_lst);

        // sql to load a verb list by phrase id and direction up
        $vrb_lst = new verb_list($usr);
        $phr = new phrase($usr);
        $phr->id = 5;
        $this->assert_load_by_linked_phrases_sql($t, $db_con, $vrb_lst, $phr, word_select_direction::UP);

        // ... same for direction down
        $this->assert_load_by_linked_phrases_sql($t, $db_con, $vrb_lst, $phr, word_select_direction::DOWN);

    }

    /**
     * similar to $t->assert_load_sql but calling load_by_linked_phrases_sql instead of load_sql
     *
     * @param testing $t the forwarded testing object
     * @param sql_db $db_con does not need to be connected to a real database
     * @param verb_list $vrb_lst the verb list object used for testing
     * @param phrase $phr the phrase used for testing
     * @param string $direction
     */
    private function assert_load_by_linked_phrases_sql(testing $t, sql_db $db_con, verb_list $vrb_lst, phrase $phr, string $direction)
    {
        // check the PostgreSQL query syntax
        $db_con->db_type = sql_db::POSTGRES;
        $qp = $vrb_lst->load_by_linked_phrases_sql($db_con, $phr, $direction);
        $result = $t->assert_qp($qp, $db_con->db_type);

        // ... and check the MySQL query syntax
        if ($result) {
            $db_con->db_type = sql_db::MYSQL;
            $qp = $vrb_lst->load_by_linked_phrases_sql($db_con, $phr, $direction);
            $t->assert_qp($qp, $db_con->db_type);
        }
    }

}
