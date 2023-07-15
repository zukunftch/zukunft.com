<?php

/*

    model/sandbox/sandbox_list.php - a base object for a list of user sandbox objects
    ------------------------------


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

namespace cfg;

include_once MODEL_SYSTEM_PATH . 'base_list.php';

class sandbox_list extends base_list
{

    /*
     *  object vars
     */

    private user $usr; // the person for whom the list has been created


    /*
     * construct and map
     */

    /**
     * always set the user because a link list is always user specific
     * @param user $usr the user who requested to see e.g. the formula links
     */
    function __construct(user $usr, array $lst = array())
    {
        parent::__construct($lst);
        $this->set_user($usr);
    }

    /**
     * dummy function to be overwritten by the child class
     * @param array $db_rows is an array of an array with the database values
     * @return bool true if at least one object has been loaded
     */
    protected function rows_mapper(array $db_rows): bool
    {
        log_err('Unexpected call of the parent rows_mapper function');
        return false;
    }


    /*
     * set and get
     */

    /**
     * set the user of the phrase list
     *
     * @param user $usr the person who wants to access the phrases
     * @return void
     */
    function set_user(user $usr): void
    {
        $this->usr = $usr;
    }

    /**
     * @return user the person who wants to see the phrases
     */
    function user(): user
    {
        return $this->usr;
    }


    /*
     * load
     */

    /**
     * load a list of sandbox objects (e.g. phrases or values) based on the given query parameters
     * @param sql_par $qp the SQL statement, the unique name of the SQL statement and the parameter list
     * @return bool true if at least one object has been loaded
     */
    protected function load(sql_par $qp): bool
    {

        global $db_con;
        $result = false;

        // check the all minimal input parameters are set
        if ($this->user()->id() <= 0) {
            log_err('The user must be set to load ' . self::class, self::class . '->load');
        } elseif ($qp->name == '') {
            log_err('The query name cannot be created to load a ' . self::class, self::class . '->load');
        } else {
            $db_lst = $db_con->get($qp);
            $result = $this->rows_mapper($db_lst);
        }
        return $result;
    }


    /*
     * modification
     */

    /**
     * add one object to the list of user sandbox objects, but only if it is not yet part of the list
     * @param object $obj_to_add the formula backend object that should be added
     * @returns bool true the formula has been added
     */
    function add_obj(object $obj_to_add): bool
    {
        $result = false;

        // check parameters
        if ($obj_to_add->user() == null) {
            $obj_to_add->set_user($this->user());
        }
        if ($obj_to_add->id() <> 0 or $obj_to_add->name() != '') {
            $result = parent::add_obj($obj_to_add);
        }
        return $result;
    }


    /*
     * debug functions
     */

    /**
     * @return string to display the unique id fields
     */
    function dsp_id(): string
    {
        global $debug;
        $lib = new library();
        $result = '';

        // show at least 4 elements by name
        $min_names = $debug;
        if ($min_names < LIST_MIN_NAMES) {
            $min_names = LIST_MIN_NAMES;
        }


        if ($this->lst != null) {
            $pos = 0;
            foreach ($this->lst as $sbx_obj) {
                if ($min_names > $pos) {
                    if ($result <> '') $result .= ' / ';
                    $name = $sbx_obj->name();
                    if ($sbx_obj::class == value::class) {
                        $name .= $sbx_obj->number();
                    }
                    if ($name <> '""') {
                        $name = $name . ' (' . $sbx_obj->id() . ')';
                    } else {
                        $name = $sbx_obj->id();
                    }
                    $result .= $name;
                    $pos++;
                }
            }
            if (count($this->lst) > $pos) {
                $result .= ' ... total ' . $lib->dsp_count($this->lst);
            }
            if ($debug > DEBUG_SHOW_USER) {
                if ($this->user() != null) {
                    $result .= ' for user ' . $this->user()->id() . ' (' . $this->user()->name . ')';
                }
            }
        }
        return $result;
    }

    /**
     * to show the list name to the user in the most simple form (without any ids)
     * this function is called from dsp_id, so no other call is allowed
     * @return string a simple name of the list
     */
    function name(): string
    {
        return implode(", ", $this->names());
    }

    /**
     * @return array with all names of the list
     */
    function names(): array
    {
        $result = [];
        foreach ($this->lst as $sbx_obj) {
            $result[] = $sbx_obj->name();
        }
        return $result;
    }

}