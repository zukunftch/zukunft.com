<?php

/*

  test/unit/formula_link.php - unit testing of the formula link functions
  --------------------------
  

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

class formula_link_unit_tests
{
    function run(testing $t)
    {

        global $usr;
        global $sql_names;

        $t->header('Unit tests of the formula link class (src/main/php/model/formula/formula_link.php)');

        $t->subheader('SQL statement tests');

        $db_con = new sql_db();

        // sql to load the formula link by id
        $lnk = new formula_link();
        $lnk->id = 2;
        $lnk->usr = $usr;
        $db_con->db_type = sql_db::POSTGRES;
        $created_sql = $lnk->load_sql($db_con);
        $expected_sql = $t->file('db/formula/formula_link_by_id.sql');
        $t->assert('formula_link->load_sql by formula link id', $t->trim($created_sql), $t->trim($expected_sql));

        // ... and check if the prepared sql name is unique
        $t->assert_sql_name_unique($lnk->load_sql($db_con, true));

        // ... and for MySQL
        $db_con->db_type = sql_db::MYSQL;
        $created_sql = $lnk->load_sql($db_con);
        $expected_sql = $t->file('db/formula/formula_link_by_id_mysql.sql');
        $t->assert('formula_link->load_sql for MySQL by formula link id', $t->trim($created_sql), $t->trim($expected_sql));

        // sql to load the standard formula link by id
        $db_con->db_type = sql_db::POSTGRES;
        $created_sql = $lnk->load_standard_sql($db_con);
        $expected_sql = $t->file('db/formula/formula_link_std_by_id.sql');
        $t->assert('formula_link->load_standard_sql by formula link id', $t->trim($created_sql), $t->trim($expected_sql));

        // ... and check if the prepared sql name is unique
        $t->assert_sql_name_unique($lnk->load_standard_sql($db_con, true));

        // ... and for MySQL
        $db_con->db_type = sql_db::MYSQL;
        $created_sql = $lnk->load_standard_sql($db_con);
        $expected_sql = $t->file('db/formula/formula_link_std_by_id_mysql.sql');
        $t->assert('formula_link->load_standard_sql for MySQL by formula link id', $t->trim($created_sql), $t->trim($expected_sql));

        // sql to load the user formula link by id
        $db_con->db_type = sql_db::POSTGRES;
        $created_sql = $lnk->load_user_sql($db_con);
        $expected_sql = $t->file('db/formula/formula_link_by_id_e_user.sql');
        $t->assert('formula_link->load_user_sql by formula link id', $t->trim($created_sql), $t->trim($expected_sql));

        // sql to check if no one else has changed the formula link
        $lnk = new formula_link();
        $lnk->id = 2;
        $lnk->owner_id = 3;
        $db_con->db_type = sql_db::POSTGRES;
        $created_sql = $lnk->not_changed_sql();
        $expected_sql = $t->file('db/formula/formula_link_by_id_other_user.sql');
        $t->assert('formula_link->not_changed_sql by owner id', $t->trim($created_sql), $t->trim($expected_sql));

        // ... and check if the prepared sql name is unique
        $t->assert_sql_name_unique($lnk->not_changed_sql(true));

        // MySQL check not needed, because it is the same as for PostgreSQL

        /*
        $t->subheader('Im- and Export tests');

        $json_in = json_decode(file_get_contents(PATH_TEST_IMPORT_FILES . 'unit/formula/scale_second_to_minute.json'), true);
        $lnk = new formula;
        $lnk->import_obj($json_in, false);
        $json_ex = json_decode(json_encode($lnk->export_obj(false)), true);
        $result = json_is_similar($json_in, $json_ex);
        $target = true;
        $t->dsp('formula_link->import check name', $target, $result);
        */

    }

}