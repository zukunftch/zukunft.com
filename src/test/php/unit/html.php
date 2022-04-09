<?php

/*

    test/unit/html.php - unit testing of the html code generating functions
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
    along with zukunft.com. If not, see <http://www.gnu.org/licenses/gpl.html>.

    To contact the authors write to:
    Timon Zielonka <timon@zukunft.com>

    Copyright (c) 1995-2022 zukunft.com AG, Zurich
    Heang Lor <heang@zukunft.com>

    http://zukunft.com

*/

class html_unit_tests
{
    function run(testing $t)
    {

        global $usr;

        $t->header('Unit tests of the html classes (src/main/php/web/html/*)');

        $t->subheader('Selector tests');

        // TODO test the creation of a phrase list API JSON
        // TODO create a selector using a list an with a simple test page header an footer
        //
        $html = new html_base();
        $sel = new html_selector();
        $sel->label = 'Test:';
        $sel->name = 'test_selector';
        $sel->form = 'test_form';
        $sel_lst = array();
        $sel_lst[1] = 'First';
        $sel_lst[2] = 'Second';
        $sel_lst[3] = 'Third (selected)';
        $sel_lst[4] = 'Fourth';
        $sel->lst = $sel_lst;
        $sel->selected = 3;
        $created_html = $html->form_start($sel->form);
        $created_html .= $sel->display();
        $created_html .= $html->form_end($sel->name, '');
        $expected_html = $t->file('web/html/selector.html');
        $t->dsp('html_selector', $t->trim_html($expected_html), $t->trim_html($created_html));

        // ... and check if the prepared sql name is unique
        //$t->assert_sql_name_unique($log_dsp->dsp_hist_links_sql($db_con, true));

    }

}
