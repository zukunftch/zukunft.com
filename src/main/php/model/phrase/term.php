<?php

/*

  term.php - either a word, verb, triple or formula
  --------
  
  mainly to check the term consistency of all objects
  a term must be unique for word, verb and triple e.g. "Company" is a word "is a" is a verb and "Kanton Zurich" is a triple
  all terms are the same for each user
  if a user changes a term and the term has been used already
  a new term is created and the deletion of the existing term is requested
  if all user have confirmed the deletion, the term is finally deleted
  each user can have its own language translation which must be unique only for one user
  so one user may use "Zurich" in US English for "Kanton Zurich"
  and another user may use "Zurich" in US English for "Zurich AG"
  
  
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
  
  TODO: load formula word
        check triple

*/

use api\term_api;
use cfg\phrase_type;
use html\term_dsp;
use html\word_dsp;

class term extends db_object
{

    // field names of the database view for terms
    // the database view is used e.g. for a fast check of a new term name
    const FLD_ID = 'term_id';
    const FLD_NAME = 'term_name';
    const FLD_USAGE = 'usage'; // included in the database view to be able to show the user the most relevant terms

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

    // the term vars, which is probably just the related object
    public ?object $obj = null;  // the word, triple, formula or verb object

    /*
     * construct and map
     */

    /**
     * always set the user because a term is always user specific
     * @param user $usr the user who requested to see this term
     */
    function __construct(user $usr, string $class = word::class)
    {
        parent::__construct();
        $this->reset();
        $this->set_obj($class);
        $this->set_user($usr);
    }

    function reset(): void
    {
        $this->id = 0;
    }

    /**
     * map a complete underlying object to a term
     * @return bool true if at least one term has been loaded
     */
    function row_mapper_obj(array $db_row, string $class): bool
    {
        $result = false;
        if ($class == word::class) {
            $result = $this->get_word()->row_mapper($db_row);
        } elseif ($class == triple::class) {
            $result = $this->get_triple()->row_mapper($db_row);
        } elseif ($class == formula::class) {
            $result = $this->get_formula()->row_mapper($db_row);
        } elseif ($class == verb::class) {
            $result = $this->get_verb()->row_mapper($db_row);
        } else {
            log_warning('Term ' . $this->dsp_id() . ' is of unknown type');
        }
        $this->set_id_from_obj($this->id_obj(), $class);
        return $result;
    }

    /**
     * map the main field from the term view to a term object
     * @return bool true if at least one term has been loaded
     */
    function row_mapper(array $db_row): bool
    {
        $result = false;
        $this->id = 0;
        if ($db_row != null) {
            if ($db_row[self::FLD_ID] != 0) {
                $this->id = $db_row[self::FLD_ID];
                $this->set_obj_from_id();
                $this->set_name($db_row[self::FLD_NAME]);
                $this->set_usage($db_row[self::FLD_USAGE]);
                $result = true;
            }
        }
        return $result;
    }

    /*
     * get, set and debug functions
     */

    /**
     * @param int|null $id the term (not the object!) id
     * @return void
     */
    function set_id(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * set the term id based id the word, triple, verb or formula id
     *
     * @param int $id the object id that is converted to the term id
     * @param string $class the class of the term object
     * @return void
     */
    function set_id_from_obj(int $id, string $class): void
    {
        if ($class == word::class) {
            if ($this->obj == null) {
                $this->obj = new word($this->user());
            }
            $this->id = ($id * 2) - 1;
        } elseif ($class == triple::class) {
            if ($this->obj == null) {
                $this->obj = new triple($this->user());
            }
            $this->id = ($id * -2) + 1;
        } elseif ($class == formula::class) {
            if ($this->obj == null) {
                $this->obj = new formula($this->user());
            }
            $this->id = ($id * 2);
        } elseif ($class == verb::class) {
            if ($this->obj == null) {
                $this->obj = new verb();
            }
            $this->id = ($id * -2);
        }
        $this->obj->set_id($id);
    }

    /**
     * set the id of the word, triple, formula or verb object based on the term id
     * @return void
     */
    private function set_obj_id(): void
    {
        $this->obj->id = $this->id_obj();
    }

    /**
     * create the expected object based on the id
     * @return void
     */
    private function set_obj_from_id(): void
    {
        if ($this->id > 0) {
            if ($this->id % 2 == 0) {
                $this->obj = new formula($this->user());
            } else {
                $this->obj = new word($this->user());
            }
        } else {
            if ($this->id % 2 == 0) {
                $this->obj = new verb();
            } else {
                $this->obj = new triple($this->user());
            }
        }
        $this->set_obj_id();
    }

    /**
     * create the word, triple, formula or verb object based on the given class
     *
     * @param string $class the calling class name
     * @return void
     */
    private function set_obj(string $class): void
    {
        if ($class == word::class) {
            $this->obj = new word($this->user());
        } elseif ($class == triple::class) {
            $this->obj = new triple($this->user());
        } elseif ($class == formula::class) {
            $this->obj = new formula($this->user());
        } elseif ($class == verb::class) {
            $this->obj = new verb();
        } else {
            log_err('Unexpected class ' . $class . ' when creating term ' . $this->dsp_id());
        }
    }

    /**
     * set the name of the term object, which is also the name of the term
     * because of this object name retrieval set and get of the name is needed for all linked objects
     *
     * @param string $name the name of the term set in the related object
     * @param string $class the class of the term object can be set to force the creation of the related object
     * @return void
     */
    function set_name(string $name, string $class = ''): void
    {
        if ($class != '' and $this->obj == null) {
            $this->set_obj($class);
        }
        $this->obj->set_name($name);
    }

    /**
     * set the user of the term object, which is also the user of the term
     * because of this object retrieval set and get of the user is needed for all linked objects
     *
     * @param user $usr the person who wants to add a term (word, verb, triple or formula)
     * @param string $class the class of the term object can be set to force the creation of the related object
     * @return void
     */
    function set_user(user $usr, string $class = ''): void
    {
        if ($class != '' and $this->obj == null) {
            $this->set_obj($class);
        }
        $this->obj->set_user($usr);
    }

    /**
     * set the value to rank the terms by usage
     *
     * @param int|null $usage a higher value moves the term to the top of the selection list
     * @return void
     */
    function set_usage(?int $usage): void
    {
        if ($usage == null) {
            $this->obj->set_usage(0);
        } else {
            $this->obj->set_usage($usage);
        }
    }

    /**
     * @return int the id of the term witch is  (corresponding to id_obj())
     * e.g 1 for a word, -1 for a triple, 2 for a formula and -2 for a verb
     */
    function id(): int
    {
        return $this->id;
    }

    /**
     * @return int the id of the containing object witch is (corresponding to id())
     * e.g 1 for word with id 1
     *     -1 for a phrase with id 1
     *     2 for a formula with id 1
     * and -2 for a verb with id 1
     */
    function id_obj(): int
    {
        $result = 0;
        if (isset($this->obj)) {
            if ($this->obj->id() != 0) {
                $result = $this->obj->id();
            }
        }
        if ($result == 0) {
            if ($this->id % 2 == 0) {
                $result = abs($this->id) / 2;
            } else {
                $result = (abs($this->id) + 1) / 2;
            }
        }
        return $result;
    }

    function name(): string
    {
        $result = '';
        if (isset($this->obj)) {
            $result = $this->obj->name();
        }
        return $result;
    }

    /**
     * @return user|null the person who wants to see a term (word, verb, triple or formula)
     *                   in case of a verb it can be null
     */
    function user(): ?user
    {
        $result = new user();
        if (isset($this->obj)) {
            $result = $this->obj->user();
        }
        return $result;
    }

    function type(): string
    {
        $result = '';
        if (isset($this->obj)) {
            $result = $this->obj::class;
        }
        return $result;
    }

    function usage(): int
    {
        return $this->obj->usage();
    }

    /*
     * casting objects
     */

    /**
     * @return term_api the term frontend api object
     */
    function api_obj(): term_api
    {
        if ($this->is_word()) {
            return $this->get_word()->api_obj()->term();
        } elseif ($this->is_triple()) {
            return $this->get_triple()->api_obj()->term();
        } elseif ($this->is_formula()) {
            return $this->get_formula()->api_obj()->term();
        } elseif ($this->is_verb()) {
            return $this->get_verb()->api_obj()->term();
        } else {
            log_warning('Term ' . $this->dsp_id() . ' is of unknown type');
            return (new term_api());
        }
    }

    /**
     * @return term_dsp the phrase object with the display interface functions
     */
    function dsp_obj(): term_dsp
    {
        if ($this->is_word()) {
            return $this->get_word()->dsp_obj()->term();
        } elseif ($this->is_triple()) {
            return $this->get_triple()->dsp_obj()->term();
        } elseif ($this->is_formula()) {
            return $this->get_formula()->dsp_obj()->term();
        } elseif ($this->is_verb()) {
            return $this->get_verb()->dsp_obj()->term();
        } else {
            log_warning('Term ' . $this->dsp_id() . ' is of unknown type');
            return (new term_dsp());
        }
    }

    /**
     * @return phrase the word or triple cast as a phrase
     */
    public function phrase(): phrase
    {
        $phr = new phrase($this->user());
        if ($this->is_word()) {
            $phr->set_id_from_obj($this->id_obj(), word::class);
            $phr->obj = $this->obj;
        }
        if ($this->is_triple()) {
            $phr->set_id_from_obj($this->id_obj(), triple::class);
            $phr->obj = $this->obj;
        }
        return $phr;
    }

    /*
     * load functions
     */

    /**
     * create the common part of an SQL statement to retrieve a term from the database
     * uses the term view which includes only the most relevant fields of words, triples, formulas and verbs
     *
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @param string $query_name the name of the query use to prepare and call the query
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    private function load_sql(sql_db $db_con, string $query_name): sql_par
    {
        $qp = new sql_par(self::class);
        $qp->name .= $query_name;

        $db_con->set_type(sql_db::VT_TERM);
        $db_con->set_name($qp->name);

        $db_con->set_usr_fields(self::FLD_NAMES_USR);
        $db_con->set_usr_num_fields(self::FLD_NAMES_NUM_USR);

        return $qp;
    }

    /**
     * create an SQL statement to retrieve a term by term id (not the object id) from the database
     *
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @param int $id the id of the term as defined in the database term view
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    function load_sql_by_id(sql_db $db_con, int $id): sql_par
    {
        $qp = $this->load_sql($db_con, 'id');
        $db_con->add_par_int($id);
        $qp->sql = $db_con->select_by_field(term::FLD_ID);
        $qp->par = $db_con->get_par();

        return $qp;
    }

    /**
     * create an SQL statement to retrieve a term by name from the database
     *
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @param string $name the name of the term and the related word, triple, formula or verb
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    function load_sql_by_name(sql_db $db_con, string $name): sql_par
    {
        $qp = $this->load_sql($db_con, 'name');
        $db_con->add_par_txt($name);
        $qp->sql = $db_con->select_by_field(term::FLD_NAME);
        $qp->par = $db_con->get_par();

        return $qp;
    }

    /**
     * load a term from the database view
     * @param sql_par $qp the query parameters created by the calling function
     * @return int the id of the object found and zero if nothing is found
     */
    private function load(sql_par $qp): int
    {
        global $db_con;

        $db_row = $db_con->get1($qp);
        $this->row_mapper($db_row);
        return $this->id();
    }

    /**
     * load the main term parameters by id from the database term view
     * @param int $id the id of the term as defined in the database term view
     * @param string $class not used for this term object just to be compatible with the db base object
     * @return int the id of the object found and zero if nothing is found
     */
    function load_by_id(int $id, string $class = self::class): int
    {
        global $db_con;

        log_debug($id);
        $qp = $this->load_sql_by_id($db_con, $id);
        return $this->load($qp);
    }

    /**
     * test if the name is used already via view table and just load the main parameters
     * @param string $name the name of the term and the related word, triple, formula or verb
     * @param string $class not used for this term object just to be compatible with the db base object
     * @return int the id of the object found and zero if nothing is found
     */
    function load_by_name(string $name, string $class = self::class): int
    {
        global $db_con;

        log_debug($name);
        $qp = $this->load_sql_by_name($db_con, $name);
        return $this->load($qp);
    }

    /**
     * load the term object by the word or triple id (not the phrase id)
     * @param int $id the id of the term object e.g. for a triple "-1"
     * @param string $class not used for this term object just to be compatible with the db base object
     * @param bool $including_triples to include the words or triple of a triple (not recursive)
     * @return int the id of the object found and zero if nothing is found
     */
    function load_by_obj_id(int $id, string $class, bool $including_triples = true): int
    {
        log_debug($this->name());
        $result = 0;

        if ($class == word::class) {
            if ($this->load_word_by_id($id)) {
                $result = $this->obj->id;
            }
        } elseif ($class == triple::class) {
            if ($this->load_triple_by_id($id, $including_triples)) {
                $result = $this->obj->id;
            }
        } elseif ($class == formula::class) {
            if ($this->load_formula_by_id($id)) {
                $result = $this->obj->id;
            }
        } elseif ($class == verb::class) {
            if ($this->load_verb_by_id($id)) {
                $result = $this->obj->id;
            }
        } else {
            log_err('Unexpected class ' . $class . ' when creating term ' . $this->dsp_id());
        }

        log_debug('term->load loaded id "' . $this->id() . '" for ' . $this->name());

        return $result;
    }

    /**
     * simply load a word
     * (separate functions for loading  for a better overview)
     */
    private function load_word_by_id(int $id): bool
    {
        $result = false;
        $wrd = new word($this->user());
        if ($wrd->load_by_id($id, word::class)) {
            log_debug('type is "' . $wrd->type_id . '" and the formula type is ' . cl(db_cl::PHRASE_TYPE, phrase_type::FORMULA_LINK));
            if ($wrd->type_id == cl(db_cl::PHRASE_TYPE, phrase_type::FORMULA_LINK)) {
                $result = $this->load_formula_by_id($id);
            } else {
                $this->set_id_from_obj($wrd->id, word::class);
                $this->obj = $wrd;
                $result = true;
            }
        }
        return $result;
    }

    /**
     * simply load a triple
     */
    private function load_triple_by_id(int $id, bool $including_triples): bool
    {
        $result = false;
        if ($including_triples) {
            $trp = new triple($this->user());
            if ($trp->load_by_id($id, triple::class)) {
                $this->set_id_from_obj($trp->id, triple::class);
                $this->obj = $trp;
                $result = true;
            }
        }
        return $result;
    }

    /**
     * simply load a formula
     * without fixing any missing related word issues
     */
    private function load_formula_by_id(int $id): bool
    {
        $result = false;
        $frm = new formula($this->user());
        if ($frm->load_by_id($id, formula::class)) {
            $this->set_id_from_obj($frm->id, formula::class);
            $this->obj = $frm;
            $result = true;
        }
        return $result;
    }

    /**
     * simply load a verb
     */
    private function load_verb_by_id(int $id): bool
    {
        $result = false;
        $vrb = new verb;
        $vrb->name = $this->name();
        $vrb->set_user($this->user());
        if ($vrb->load_by_id($id)) {
            $this->set_id_from_obj($vrb->id, verb::class);
            $this->obj = $vrb;
            $result = true;
        }
        return $result;
    }

    /**
     * test if the name is used already and load the object
     * @param string $name the name of the term (and word, triple, formula or verb) to load
     * @param bool $including_triples to include the words or triple of a triple (not recursive)
     * @return int the id of the object found and zero if nothing is found
     */
    function load_by_obj_name(string $name, bool $including_triples = true): int
    {
        log_debug($this->name());
        $result = 0;

        if ($this->load_word_by_name($name)) {
            $result = $this->obj->id;
        } elseif ($this->load_triple_by_name($name, $including_triples)) {
            $result = $this->obj->id;
        } elseif ($this->load_formula_by_name($name)) {
            $result = $this->obj->id;
        } elseif ($this->load_verb_by_name($name)) {
            $result = $this->obj->id;
        }
        log_debug('term->load loaded id "' . $this->id() . '" for ' . $this->name());

        return $result;
    }

    /**
     * simply load a word by name
     * (separate functions for loading  for a better overview)
     */
    private function load_word_by_name(string $name): bool
    {
        $result = false;
        $wrd = new word($this->user());
        if ($wrd->load_by_name($name, word::class)) {
            log_debug('type is "' . $wrd->type_id . '" and the formula type is ' . cl(db_cl::PHRASE_TYPE, phrase_type::FORMULA_LINK));
            if ($wrd->type_id == cl(db_cl::PHRASE_TYPE, phrase_type::FORMULA_LINK)) {
                $result = $this->load_formula_by_name($name);
            } else {
                $this->set_id_from_obj($wrd->id, word::class);
                $this->obj = $wrd;
                $result = true;
            }
        }
        return $result;
    }

    /**
     * simply load a triple by name
     */
    private function load_triple_by_name(string $name, bool $including_triples): bool
    {
        $result = false;
        if ($including_triples) {
            $trp = new triple($this->user());
            if ($trp->load_by_name($name, triple::class)) {
                $this->set_id_from_obj($trp->id, triple::class);
                $this->obj = $trp;
                $result = true;
            }
        }
        return $result;
    }

    /**
     * simply load a formula by name
     * without fixing any missing related word issues
     */
    private function load_formula_by_name(string $name): bool
    {
        $result = false;
        $frm = new formula($this->user());
        if ($frm->load_by_name($name, formula::class)) {
            $this->set_id_from_obj($frm->id, formula::class);
            $this->obj = $frm;
            $result = true;
        }
        return $result;
    }

    /**
     * simply load a verb by name
     */
    private function load_verb_by_name(string $name): bool
    {
        $result = false;
        $vrb = new verb;
        $vrb->name = $this->name();
        $vrb->set_user($this->user());
        if ($vrb->load_by_name($name)) {
            $this->set_id_from_obj($vrb->id, verb::class);
            $this->obj = $vrb;
            $result = true;
        }
        return $result;
    }

    /*
     * classification
     */

    /**
     * @return bool true if this term is a word or supposed to be a word
     */
    public function is_word(): bool
    {
        $result = false;
        if (isset($this->obj)) {
            if (get_class($this->obj) == word::class or get_class($this->obj) == word_dsp::class) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @return bool true if this term is a triple or supposed to be a triple
     */
    public function is_triple(): bool
    {
        $result = false;
        if (isset($this->obj)) {
            if (get_class($this->obj) == triple::class) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @return bool true if this term is a formula or supposed to be a triple
     */
    public function is_formula(): bool
    {
        $result = false;
        if (isset($this->obj)) {
            if (get_class($this->obj) == formula::class) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @return bool true if this term is a verb or supposed to be a triple
     */
    public function is_verb(): bool
    {
        $result = false;
        if (isset($this->obj)) {
            if (get_class($this->obj) == verb::class) {
                $result = true;
            }
        }
        return $result;
    }

    /*
     * conversion
     */

    public function get_word(): word
    {
        $wrd = new word($this->user());
        if (get_class($this->obj) == word::class) {
            $wrd = $this->obj;
        }
        return $wrd;
    }

    public function get_triple(): triple
    {
        $lnk = new triple($this->user());
        if (get_class($this->obj) == triple::class) {
            $lnk = $this->obj;
        }
        return $lnk;
    }

    public function get_formula(): formula
    {
        $frm = new formula($this->user());
        if (get_class($this->obj) == formula::class) {
            $frm = $this->obj;
        }
        return $frm;
    }

    public function get_verb(): verb
    {
        $vrb = new verb();
        if (get_class($this->obj) == verb::class) {
            $vrb = $this->obj;
        }
        return $vrb;
    }

    /*
    * user interface language specific functions
    */

    /**
     * create a message text that the name is already used
     */
    function id_used_msg(): string
    {
        $result = "";

        if ($this->id() > 0) {
            $result = dsp_err('A ' . $this->type() . ' with the name "' . $this->name() . '" already exists. Please use another name.');
        }

        return $result;
    }

    /*
     * information functions
     */

    /**
     * display the unique id fields
     */
    function dsp_id(): string
    {
        $result = '';

        if ($this->name() <> '') {
            $result .= '"' . $this->name() . '"';
            if ($this->id() > 0) {
                $result .= ' (' . $this->id() . ')';
            }
        } else {
            $result .= $this->id();
        }
        if ($this->user()->id > 0) {
            $result .= ' for user ' . $this->user()->id . ' (' . $this->user()->name . ')';
        }
        return $result;
    }

}