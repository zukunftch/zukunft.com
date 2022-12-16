<?php

/*

    api\formula.php - the minimal formula object for the frontend API
    ---------------


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

namespace api;

use formula;
use html\formula_dsp;
use html\term_dsp;

class formula_api extends user_sandbox_named_with_type_api
{

    /*
     * const for system testing
     */

    // formulas for stand-alone unit tests that are added with the system initial data load
    // TN_* is the name of the formula used for testing
    // TF_* is the formula expression in the human-readable format
    // TR_* is the formula expression in the database reference format
    const TN_READ = 'scale minute to sec';
    const TN_INCREASE = 'increase';
    const TF_INCREASE = '"percent" = ( "this" - "prior" ) / "prior"';
    const TF_INCREASE_ALTERNATIVE = '"percent" = 1 - ( "this" / "prior" )';
    const TR_INCREASE = '{w1}=({f18}-{f20})/{f20}';
    const TN_READ_THIS = 'this';
    const TN_READ_PRIOR = 'prior';
    const TN_DIAMETER = 'diameter';
    const TF_DIAMETER = '= "circumference" / "Pi"';
    const TR_DIAMETER = '={w1}/{t2}';
    const TN_READ_SCALE_MIO = 'scale millions to one';
    const TF_READ_SCALE_MIO = '"one" = "millions" * 1000000';
    const TR_SCALE_MIO = '{w1} = {w2} * 1000000';
    const TN_PARTS_IN_PERCENT = 'parts in percent';
    const TF_PARTS_IN_PERCENT = '"percent" = "parts" "of" / "total"';
    const TR_PARTS_IN_PERCENT = '{w1}={w2}{w3}/{w4}';

    // persevered formula names for unit and integration tests
    const TN_ADD = 'System Test Formula'; // to test adding a new formula to the database and using the increase formula
    const TN_RENAMED = 'System Test Formula Renamed';
    const TN_THIS = 'System Test Formula This'; // to test if another formula of the functional type "this" can be created
    const TF_THIS = '= "System Test Formula This"';
    const TN_RATIO = 'System Test Formula PE Ratio'; // to test a simple ration calculation like how many times Switzerland is bigger than the canton zurich or the price to earning ration for equity
    const TF_RATIO = '"System Test Word PE Ratio" = "System Test Word Share Price" / "System Test Word Earnings"';
    const TN_SECTOR = 'System Test Formula Sector'; // to test the selection by a phrases and parents e.g. split all country totals by canton
    const TF_SECTOR = '= "Country" "differentiator" "Canton" / "System Test Word Total"';
    const TN_SCALE_K = 'System Test Formula scale thousand to one';
    const TF_SCALE_K = '"one" = "System Test Scaling Word e.g. thousands" * 1000';
    const TN_SCALE_TO_K = 'System Test Formula scale one to thousand';
    const TF_SCALE_TO_K = '"System Test Scaling Word e.g. thousands" = "one" / 1000';
    const TN_SCALE_MIO = 'System Test Formula scale millions to one';
    const TF_SCALE_MIO = '"one" = "million" * 1000000';
    const TN_SCALE_BIL = 'System Test Formula scale billions to one';
    const TF_SCALE_BIL = '"one" = "System Test Scaling Word e.g. billions" * 1000000000';

    // formula names that are reserved for creating the test formulas, that are removed after the test
    // so these formula names cannot be used for user formulas
    const RESERVED_FORMULAS = array(
        self::TN_ADD,
        self::TN_RENAMED,
        self::TN_THIS,
        self::TN_RATIO,
        self::TN_SECTOR,
        self::TN_SCALE_K,
        self::TN_SCALE_TO_K,
        self::TN_SCALE_BIL
    );


    /*
     * object vars
     */

    // the formula expression as shown to the user
    private string $usr_text;


    /*
     * construct and map
     */

    function __construct(int $id = 0, string $name = '')
    {
        parent::__construct($id, $name);
        $this->usr_text = '';
    }

    /*
     * set and get
     */

    public function set_usr_text(string $usr_text)
    {
        $this->usr_text = $usr_text;
    }

    public function usr_text(): string
    {
        return $this->usr_text;
    }

    /*
     * casting objects
     */

    /**
     * @returns formula_dsp the cast object with the HTML code generating functions
     */
    function dsp_obj(): formula_dsp
    {
        $dsp_obj = new formula_dsp($this->id, $this->name);
        $dsp_obj->set_usr_text($this->usr_text());
        return $dsp_obj;
    }

    function term(): term_api|term_dsp
    {
        return new term_api($this->id, $this->name, formula::class);
    }

}
