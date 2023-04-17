<?php

/*

    model/sandbox/sandbox_description.php - adding the description and type field to the _sandbox superclass
    -------------------------------------

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

namespace model;

use cfg\export\exp_obj;

include_once MODEL_SANDBOX_PATH . 'sandbox_link.php';

class sandbox_link_named extends sandbox_link
{
    // the word, triple, verb oder formula description that is shown as a mouseover explain to the user
    // if description is NULL the database value should not be updated
    // or for triples the description that may differ from the generic created text
    // e.g. Zurich AG instead of Zurich (Company)
    // if the description is empty the generic created name is used
    protected ?string $name = '';   // simply the object name, which cannot be empty if it is a named object
    public ?string $description = null;

    function reset(): void
    {
        parent::reset();
        $this->description = null;
    }

    /*
     * set and get
     */

    /**
     * set the name of this named user sandbox link object
     * set and get of the name is needed to use the same function for phrase or term
     *
     * @param string $name the name of this named user sandbox object e.g. word set in the related object
     * @return void
     */
    function set_name(string $name): void
    {
        $this->name = $name;
    }

    /**
     * get the name of the word object
     *
     * @return string the name from the object e.g. word using the same function as the phrase and term
     */
    function name(): string
    {
        return $this->name;
    }


    /*
     * im- and export
     */

    /**
     * import the name and dscription of a sandbox link object
     *
     * @param array $in_ex_json an array with the data of the json object
     * @param bool $do_save can be set to false for unit testing
     * @return user_message the status of the import and if needed the error messages that should be shown to the user
     */
    function import_obj(array $in_ex_json, bool $do_save = true): user_message
    {
        $result = parent::import_obj($in_ex_json, $do_save);

        // reset of object not needed, because the calling function has just created the object
        foreach ($in_ex_json as $key => $value) {
            if ($key == exp_obj::FLD_NAME) {
                $this->set_name($value);
            }
            if ($key == exp_obj::FLD_DESCRIPTION) {
                $this->description = $value;
            }
        }

        return $result;
    }


    /*
     * save function
     */

    /**
     * set the update parameters for the word description
     */
    function save_field_description(sql_db $db_con, sandbox $db_rec, sandbox $std_rec): string
    {
        $result = '';
        // if the description is not set, don't overwrite any db entry
        if ($this->description <> Null) {
            if ($this->description <> $db_rec->description) {
                $log = $this->log_upd();
                $log->old_value = $db_rec->description;
                $log->new_value = $this->description;
                $log->std_value = $std_rec->description;
                $log->row_id = $this->id;
                $log->set_field(sql_db::FLD_DESCRIPTION);
                $result = $this->save_field_do($db_con, $log);
            }
        }
        return $result;
    }

}