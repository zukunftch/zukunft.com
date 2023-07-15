<?php

/*

    model/view/view_cmp_link_list.php - a list of view component links
    ---------------------------------

    This links list object is used to update or delete a list of links with one SQL statement

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

    Copyright (c) 1995-2023 zukunft.com AG, Zurich
    Heang Lor <heang@zukunft.com>

    http://zukunft.com
  
*/

namespace cfg;

class component_link_list extends sandbox_list
{

    /**
     * map only the valid view component links
     *
     * @param array $db_rows with the data directly from the database
     * @return bool true if the view component link is loaded and valid
     */
    protected function rows_mapper(array $db_rows): bool
    {
        $result = false;
        foreach ($db_rows as $db_row) {
            if (is_null($db_row[sandbox::FLD_EXCLUDED]) or $db_row[sandbox::FLD_EXCLUDED] == 0) {
                $dsp_cmp_lnk = new component_link($this->user());
                $dsp_cmp_lnk->row_mapper_sandbox($db_row);
                $this->lst[] = $dsp_cmp_lnk;
                $result = true;
            }
        }
        return $result;
    }

    /**
     * create an SQL statement to retrieve a list of view component links from the database
     *
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @param view|null $dsp if set to get all links for this view
     * @param component|null $cmp if set to get all links for this view component
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    function load_sql(sql_db $db_con, ?view $dsp = null, ?component $cmp = null): sql_par
    {
        $db_con->set_type(sql_db::TBL_COMPONENT_LINK);
        $qp = new sql_par(self::class);
        $sql_by = '';

        if ($dsp != null) {
            if ($dsp->id() > 0) {
                $sql_by = view::FLD_ID;
            }
        } elseif ($cmp != null) {
            if ($cmp->id() > 0) {
                $sql_by = component::FLD_ID;
            }
        }
        if ($sql_by == '') {
            log_err('Either the view id or the component id and the user (' . $this->user()->id() .
                ') must be set to load a ' . self::class, self::class . '->load_sql');
            $qp->name = '';
        } else {
            $qp->name .= $sql_by;
            $db_con->set_name($qp->name);
            $db_con->set_usr($this->user()->id());
            $db_con->set_fields(component_link::FLD_NAMES);
            $db_con->set_usr_num_fields(component_link::FLD_NAMES_NUM_USR);
            if ($dsp != null) {
                $db_con->set_join_fields(array(view::FLD_ID), sql_db::TBL_VIEW);
            } else {
                $db_con->set_join_fields(array(component::FLD_ID), sql_db::TBL_COMPONENT);
            }
            if ($dsp != null) {
                if ($dsp->id() > 0) {
                    $db_con->add_par(sql_db::PAR_INT, $dsp->id());
                    $qp->sql = $db_con->select_by_field_list(array(view::FLD_ID));
                }
            } elseif ($cmp != null) {
                if ($cmp->id() > 0) {
                    $db_con->add_par(sql_db::PAR_INT, $cmp->id());
                    $qp->sql = $db_con->select_by_field_list(array(component::FLD_ID));
                }
            }
            $qp->par = $db_con->get_par();
        }

        return $qp;
    }

    /**
     * interface function to load all phrases linked to a given value
     *
     * @param view $dsp if set to get all links for this view
     * @return bool true if phrases are found
     */
    function load_by_view(view $dsp): bool
    {
        global $db_con;
        $qp = $this->load_sql($db_con, $dsp, null);
        return $this->load($qp);
    }

    /**
     * interface function to load all values linked to a given phrase
     *
     * @param component $cmp if set to get all links for this view component
     * @return bool true if phrases are found
     */
    function load_by_component(component $cmp): bool
    {
        global $db_con;
        $qp = $this->load_sql($db_con, null, $cmp);
        return $this->load($qp);
    }

    /**
     * delete all loaded view component links e.g. to delete all the links linked to a view
     * @return user_message
     */
    function del(): user_message
    {
        $result = new user_message();

        if ($this->lst != null) {
            foreach ($this->lst as $dsp_cmp_lnk) {
                $result->add($dsp_cmp_lnk->del());
            }
        }
        return new user_message();
    }

    /*
     * extract function
     */

    /**
     * @return array with all view ids
     */
    function view_ids(): array
    {
        $result = array();
        foreach ($this->lst as $lnk) {
            if ($lnk->fob != null) {
                if ($lnk->fob->id() <> 0) {
                    if (!in_array($lnk->fob->id(), $result)) {
                        $result[] = $lnk->fob->id();
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @return array with all component ids
     */
    function cmp_ids(): array
    {
        $result = array();
        foreach ($this->lst as $lnk) {
            if ($lnk->tob->id() <> 0) {
                if (in_array($lnk->tob->id(), $result)) {
                    $result[] = $lnk->tob->id();
                }
            }
        }
        return $result;
    }

}