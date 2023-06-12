<?php

/*

    test/php/unit_save/term.php - TESTing of the TERM database read functions
    ---------------------------
  

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

namespace test;

use api\formula_api;
use api\triple_api;
use api\verb_api;
use api\word_api;
use model\formula;
use model\term;
use model\triple;
use model\verb;
use model\word;

class term_unit_db_tests
{
    function run(test_cleanup $t): void
    {

        global $db_con;

        // init
        $t->name = 'term->';

        $t->header('Test the term class (src/main/php/model/term.php)');

        // test load by term by a word db row
        $wrd = new word($t->usr1);
        $qp = $wrd->load_sql_by_id($db_con,1, word::class);
        $db_row = $db_con->get1($qp);
        $trm = new term($t->usr1);
        $trm->set_obj_from_class(word::class);
        $trm->row_mapper_obj($db_row, word::class, word::FLD_ID, word::FLD_NAME, word::FLD_TYPE);
        $t->assert($t->name . ' word row mapper', $trm->name(), word_api::TN_READ);
        $trm_by_obj_id = new term($t->usr1);
        $trm_by_obj_id->load_by_obj_id($trm->id_obj(), word::class);
        $t->assert($t->name . ' word by object id', $trm_by_obj_id->name(), word_api::TN_READ);

        // test load by term by a triple db row
        $trp = new triple($t->usr1);
        $qp = $trp->load_sql_by_id($db_con,1, triple::class);
        $db_row = $db_con->get1($qp);
        $trm = new term($t->usr1);
        $trm->set_obj_from_class(triple::class);
        $trm->row_mapper_obj($db_row, triple::class, triple::FLD_ID, triple::FLD_NAME, triple::FLD_TYPE);
        $t->assert($t->name . ' triple row mapper', $trm->name(), triple_api::TN_READ_NAME);
        $trm_by_obj_id = new term($t->usr1);
        $trm_by_obj_id->load_by_obj_id($trm->id_obj(), triple::class);
        $t->assert($t->name . ' triple by object id', $trm_by_obj_id->name(), triple_api::TN_READ_NAME);

        // test load by term by a formula db row
        $frm = new formula($t->usr1);
        $qp = $frm->load_sql_by_id($db_con,1, formula::class);
        $db_row = $db_con->get1($qp);
        $trm = new term($t->usr1);
        $trm->set_obj_from_class(formula::class);
        $trm->row_mapper_obj($db_row, formula::class, formula::FLD_ID, formula::FLD_NAME, formula::FLD_TYPE);
        $t->assert($t->name . ' formula row mapper', $trm->name(), formula_api::TN_READ);
        $trm_by_obj_id = new term($t->usr1);
        $trm_by_obj_id->load_by_obj_id($trm->id_obj(), formula::class);
        $t->assert($t->name . ' formula by object id', $trm_by_obj_id->name(), formula_api::TN_READ);

        // test load by term by a verb db row
        $vrb = new verb();
        $qp = $vrb->load_sql_by_id($db_con,1);
        $db_row = $db_con->get1($qp);
        $trm = new term($t->usr1);
        $trm->set_obj_from_class(verb::class);
        $trm->row_mapper_obj($db_row, verb::class, verb::FLD_ID, verb::FLD_NAME);
        $t->assert($t->name . ' verb row mapper', $trm->name(), verb_api::TN_READ);
        $trm_by_obj_id = new term($t->usr1);
        $trm_by_obj_id->load_by_obj_id($trm->id_obj(), verb::class);
        $t->assert($t->name . ' verb by object id', $trm_by_obj_id->name(), verb_api::TN_READ);

        // test loading by term by id and name
        $trm = new term($t->usr1);
        $trm->set_obj_from_class(word::class);
        $t->assert_load_combine($trm, word_api::TN_READ);


    }
}
