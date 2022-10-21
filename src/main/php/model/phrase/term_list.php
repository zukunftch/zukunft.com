<?php

/*

    term_list.php - a list of word, triple, verb or formula objects
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

use html\term_list_dsp;

class term_list
{

    // array of the loaded phrase objects
    // (key is at the moment the database id, but it looks like this has no advantages,
    // so a normal 0 to n order could have more advantages)
    public array $lst;
    public user $usr;  // the user object of the person for whom the phrase list is loaded, so to say the viewer

    // object specific database and JSON object field names
    const FLD_NAME = 'term_name';
    const FLD_USAGE = 'usage';

    // list of the user specific database field names
    const FLD_NAMES_USR = array(
        sql_db::FLD_DESCRIPTION
    );
    // list of the user specific numeric database field names
    const FLD_NAMES_NUM_USR = array(
        self::FLD_USAGE,
        user_sandbox::FLD_EXCLUDED,
        user_sandbox::FLD_SHARE,
        user_sandbox::FLD_PROTECT
    );

    /**
     * always set the user because a phrase list is always user specific
     * @param user $usr the user who requested to see this phrase list
     */
    function __construct(user $usr)
    {
        $this->lst = array();
        $this->usr = $usr;
    }

    /**
     * @return term_list_dsp the word object with the display interface functions
     */
    function dsp_obj(): term_list_dsp
    {
        $dsp_obj = new term_list_dsp();
        foreach ($this->lst as $trm) {
            $dsp_obj->add($trm->dsp_obj());
        }
        return $dsp_obj;
    }

    /*
     * load function
     */

    /**
     * create an SQL statement to retrieve a list of terms from the database
     *
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    function load_sql(sql_db $db_con, string $pattern = ''): sql_par
    {
        $qp = new sql_par(self::class);
        $qp->name .= 'name_like';

        $db_con->set_type(sql_db::VT_TERM);
        $db_con->set_name($qp->name);

        $db_con->set_usr_fields(self::FLD_NAMES_USR);
        $db_con->set_usr_num_fields(self::FLD_NAMES_NUM_USR);
        $db_con->add_name_pattern($pattern);
        $qp->sql = $db_con->select_by_field(self::FLD_NAME);
        $qp->par = $db_con->get_par();

        return $qp;
    }

    /**
     * load the terms that matches the given pattern
     */
    function load_like(): bool
    {
        global $db_con;
        $result = false;

        $qp = $this->load_sql($db_con);
        $trm_lst = $db_con->get($qp);
        foreach ($trm_lst as $db_row) {
            $trm = new term($this->usr);
            $trm->row_mapper($db_row);
            if ($trm->id() != 0) {
                $this->add($trm);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * add one term to the term list, but only if it is not yet part of the term list
     * @returns bool true the term has been added
     */
    function add(?term $trm_to_add): bool
    {
        $result = false;
        // check parameters
        if ($trm_to_add->usr == null) {
            $trm_to_add->usr = $this->usr;
        }
        if ($trm_to_add != null) {
            log_debug($trm_to_add->dsp_id());
            if ($trm_to_add->id() <> 0 or $trm_to_add->name != '') {
                if (count($this->id_lst()) > 0) {
                    if (!in_array($trm_to_add->id(), $this->id_lst())) {
                        $this->lst[] = $trm_to_add;
                        $result = true;
                    }
                } else {
                    $this->lst[] = $trm_to_add;
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * @returns array the phrase ids as an array
     * switch to ids() if possible
     */
    function id_lst(): array
    {
        return $this->ids()->lst;
    }

    /**
     * @return trm_ids with the sorted term ids where a triple has a negative id
     */
    function ids(): trm_ids
    {
        $lst = array();
        if (count($this->lst) > 0) {
            foreach ($this->lst as $trm) {
                // use only valid ids
                if ($trm->id_obj() <> 0) {
                    $lst[] = $trm->id();
                }
            }
        }
        asort($lst);
        return (new trm_ids($lst));
    }

}