<?php

/*

  figure.php - either a value of a formula result object
  ----------
  
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
  
  Copyright (c) 1995-2018 zukunft.com AG, Zurich
  Heang Lor <heang@zukunft.com>
  
  http://zukunft.com
  
*/

class figure {

  public $id          = NULL; // the database id of the value or formula result
  public $usr         = NULL; // the person who wants to see the figure (value or formula result)
  public $is_std      = True; // true as long as no user specific value, formula or assignemend is used for this result
  public $type        = '';   // either "value" or "result"
  public $number      = '';   // the numeric value
  public $symbol      = '';   // the reference text that has lead to the value
  public $last_update = NULL; // the time of the last update of fields that may influence the calculated results
  public $time_wrd    = NULL; // the time word object, if the figure value time is adjusted by a special formula
  public $obj         = NULL; // the value or formula result object
  
  // return the html code to display a value
  // this is the opposite of the convert function 
  function display ($back, $debug) {
    zu_debug('figure->display.', $debug-10);
    $result = '';

    if ($this->type == 'value') {
      $result .= $this->obj->display($back, $debug-1);
    } elseif ($this->type == 'result') {
      $result .= $this->obj->display($back, $debug-1);
    }
    
    return $result;    
  }

  // html code to show the value with the possibility to click for the result explaination
  function display_linked($back, $debug) {
    zu_debug('figure->display_linked.', $debug-10);
    $result = '';

    zu_debug('figure->display_linked -> type '.$this->type.'.', $debug-10);
    if ($this->type == 'value') {
      zu_debug('figure->display_linked -> value '.$this->number.'.', $debug-10);
      $result .= $this->obj->display_linked($back, $debug-1);
    } elseif ($this->type == 'result') {
      zu_debug('figure->display_linked -> result '.$this->number.'.', $debug-10);
      $result .= $this->obj->display_linked($back, $debug-1);
    }
    
    return $result;    
  }
  
}

?>
