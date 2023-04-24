<?php

/*

  model/formula/figure_list.php - a list of figures, so either a value of a formula result object
  -----------------------------
  
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

namespace model;

include_once API_FORMULA_PATH . 'figure_list.php';
include_once MODEL_SANDBOX_PATH . 'sandbox_list.php';

use api\figure_list_api;
use html\figure\figure as figure_dsp;
use test\test_api;

class figure_list extends sandbox_list
{

    // array $lst is the list of figures
    public ?bool $fig_missing = false; // true if at least one of the results is not set which means is NULL (but zero is a value)


    /*
     * cast
     */

    /**
     * @return figure_list_api the word list object with the display interface functions
     */
    function api_obj(): figure_list_api
    {
        $api_obj = new figure_list_api();
        foreach ($this->lst as $phr) {
            $api_obj->add($phr->api_obj());
        }
        return $api_obj;
    }


    /*
     * set and get
     */

    /**
     * map a figure list api json to this model figure list object
     * @param array $api_json the api array with the figures that should be mapped
     */
    function set_by_api_json(array $api_json): user_message
    {
        $msg = new user_message();

        foreach ($api_json as $json_phr) {
            $fig = new figure($this->user());
            $msg->add($fig->set_by_api_json($json_phr));
            if ($msg->is_ok()) {
                $this->add($fig);
            }
        }

        return $msg;
    }


    /*
     * load
     */

    /**
     * set the SQL query parameters to load a list of figure objects
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    function load_sql(sql_db $db_con, string $query_name): sql_par
    {
        $qp = new sql_par(self::class);
        $qp->name .= $query_name;

        $db_con->set_type(sql_db::VT_FIGURE);
        $db_con->set_name($qp->name);

        $db_con->set_usr($this->user()->id());
        $db_con->set_fields(figure::FLD_NAMES);
        //$db_con->set_usr_fields(figure::FLD_NAMES_USR_NO_NAME);
        //$db_con->set_usr_num_fields(figure::FLD_NAMES_NUM_USR);
        //$db_con->set_order_text(sql_db::STD_TBL . '.' . $db_con->name_sql_esc(figure::FLD_VALUES) . ' DESC, ' . figure::FLD_NAME);
        return $qp;
    }

    /**
     * create an SQL statement to retrieve a list of phrase objects by the id from the database
     *
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @param array $ids phrase ids that should be loaded
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    function load_sql_by_ids(sql_db $db_con, array $ids): sql_par
    {
        $qp = $this->load_sql($db_con, count($ids) . 'ids');
        $db_con->set_where_id_in(figure::FLD_ID, $ids);
        $qp->sql = $db_con->select_by_set_id();
        $qp->par = $db_con->get_par();

        return $qp;
    }

    /**
     * load this list of figures
     * @param sql_par $qp the SQL statement, the unique name of the SQL statement and the parameter list
     * @return bool true if at least one phrase has been loaded
     */
    function load(sql_par $qp): bool
    {
        global $db_con;
        $result = false;

        if ($qp->name == '') {
            log_err('The query name cannot be created to load a ' . self::class);
        } else {
            $db_rows = $db_con->get($qp);
            if ($db_rows != null) {
                foreach ($db_rows as $db_row) {
                    if ($db_row[figure::FLD_ID] > 0) {
                        $fig = new figure(new value($this->user()));
                    } else {
                        $fig = new figure(new value($this->user()));
                    }
                    $fig->row_mapper($db_row);
                    $this->lst[] = $fig;
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * load the figures including the related value or result object by the given id list from the database
     *
     * @param array $ids figure ids that should be loaded
     * @return bool true if at least one phrase has been loaded
     */
    function load_by_ids(array $ids): bool
    {
        global $db_con;
        $qp = $this->load_sql_by_ids($db_con, $ids);
        return $this->load($qp);
    }

    /*
     * modification function
     */

    /**
     * add one figure to the figure list, but only if it is not yet part of the figure list
     * @param figure|null $fig_to_add the figure that should be added to this list (if it does not yet exist)
     * @returns bool true the term has been added
     */
    function add(?figure $fig_to_add): bool
    {
        $result = false;
        // check parameters
        if ($fig_to_add != null) {
            log_debug($fig_to_add->dsp_id());
            if ($fig_to_add->id() <> 0 or $fig_to_add->name() != '') {
                $result = parent::add_obj($fig_to_add);
            }
        }
        return $result;
    }


    /*
     * display functions
     */

    /**
     * @return string to display the unique id fields
     */
    function dsp_id(): string
    {
        $id = $this->ids_txt();
        $name = $this->name();
        if ($name <> '""') {
            $result = $name . ' (' . $id . ')';
        } else {
            $result = $id;
        }

        return $result;
    }

    function name(): string
    {
        $result = '';

        foreach ($this->lst as $fig) {
            $result .= $fig->name() . ' ';
        }

        return $result;
    }

    /**
     * return a list of the figure list ids as sql compatible text
     */
    function ids_txt(): string
    {
        $lib = new library();
        return $lib->dsp_array($this->ids());
    }

    /**
     * this function is called from dsp_id, so no other call is allowed
     */
    function ids(): array
    {
        $result = array();
        foreach ($this->lst as $fig) {
            // use only valid ids
            if ($fig->id() <> 0) {
                $result[] = $fig->id();
            }
        }
        return $result;
    }

    /*
     * TODO review
     */
    function get_first_id(): int
    {
        $result = 0;
        if ($this != null) {
            if (count($this->lst) > 0) {
                $fig = $this->lst[0];
                if ($fig != null) {
                    $result = $fig->id();
                }
            }
        }
        return $result;
    }

    /**
     * TODO to be moved to the frontend object
     * return the html code to display a value
     * this is the opposite of the convert function
     * this function is called from dsp_id, so no other call is allowed
     */
    function display($back = ''): string
    {
        $result = '';

        foreach ($this->lst as $fig) {
            $t = new test_api();
            $fig_dsp = $t->dsp_obj($fig, new figure_dsp());
            $result .= $fig_dsp->display($back) . ' ';
        }

        return $result;
    }



}