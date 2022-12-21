<?php

/*

    user_log_field.php - the const for the change log field table
    -----------------

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


class change_log_field extends user_type_list
{

    const TN_WORD_VIEW = "values";

    /*
     * database link
     */

    // user log database and JSON object field names
    const FLD_TABLE = 'table_id';
    const FLD_WORD_NAME = 'word_name';
    const FLD_TRIPLE_NAME = 'name_given';
    const FLD_TABLE_FIELD = 'table_field_name';

    /**
     * overwrite the general user type list load function to keep the link to the field type capsuled
     * @param sql_db $db_con the database connection that can be either the real database connection or a simulation used for testing
     * @return bool true if load was successful
     */
    function load(sql_db $db_con, string $db_type = sql_db::VT_TABLE_FIELD): bool
    {
        return parent::load($db_con, $db_type);
    }

    /**
     * adding the system log stati used for unit tests to the dummy list
     */
    function load_dummy(): void
    {
        global $change_log_tables;

        parent::load_dummy();
        $table_id = $change_log_tables->id(change_log_table::WORD);
        $table_field_name = $table_id . change_log_field::FLD_WORD_NAME;
        $type = new user_type($table_field_name, $table_field_name);
        $this->lst[2] = $type;
        $this->hash[$table_field_name] = 2;
    }

    /**
     * return the database id of the default log type
     */
    function default_id(): int
    {
        global $change_log_tables;
        $table_id = $change_log_tables->id(change_log_table::WORD);
        $table_field_name = $table_id . change_log_field::FLD_WORD_NAME;
        return parent::id($table_field_name);
    }

}