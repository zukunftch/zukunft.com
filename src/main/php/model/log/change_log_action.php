<?php

/*

    model/log/change_log_action.php - the const for the change log action table
    -------------------------------

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

global $change_log_actions
       ;
class change_log_action extends user_type_list
{

    // the basic change types that are logged
    const ADD = 'add';
    const UPDATE = 'update';
    const DELETE = 'del';


    /*
     * load
     */

    /**
     * overwrite the general user type list load function to keep the link to the action type capsuled
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
        parent::load_dummy();
        $type = new user_type(self::ADD, self::ADD);
        $this->lst[1] = $type;
        $this->hash[self::ADD] = 1;
        $type = new user_type(self::DELETE, self::DELETE);
        $this->lst[2] = $type;
        $this->hash[self::DELETE] = 2;
        $type = new user_type(self::UPDATE, self::UPDATE);
        $this->lst[3] = $type;
        $this->hash[self::UPDATE] = 3;
    }

    /**
     * return the database id of the default log type
     */
    function default_id(): int
    {
        return parent::id(self::ADD);
    }

}