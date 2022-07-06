<?php

/*

    phrase_list.php - a list of phrase (word or triple) objects
    ---------------

    Compared to phrase_groups a phrase list is a memory only object that cannot be saved to the database

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

class phrase_list
{

    // array of the loaded phrase objects
    // (key is at the moment the database id, but it looks like this has no advantages,
    // so a normal 0 to n order could have more advantages)
    public array $lst;
    public user $usr;  // the user object of the person for whom the phrase list is loaded, so to say the viewer

    /**
     * always set the user because a phrase list is always user specific
     * @param user $usr the user who requested to see this phrase list
     */
    function __construct(user $usr)
    {
        $this->lst = array();
        $this->usr = $usr;
    }

    /**
     * @return phrase_list_dsp_old the word object with the display interface functions
     */
    function dsp_obj(): object
    {
        $dsp_obj = new phrase_list_dsp_old($this->usr);
        $dsp_obj->lst = $this->lst;
        return $dsp_obj;
    }

    /*
     * load function
     */

    /**
     * create an SQL statement to retrieve a list of words from the database
     *
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @param array $ids word ids that should be loaded
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    function load_by_wrd_ids_sql(sql_db $db_con, array $ids): sql_par
    {
        $qp = new sql_par(self::class);
        $qp->name .= count($ids) . 'ids_word_part';

        $db_con->set_type(DB_TYPE_WORD);
        $db_con->set_name($qp->name);
        $db_con->set_usr($this->usr->id);
        $db_con->set_fields(word::FLD_NAMES);
        $db_con->set_usr_fields(word::FLD_NAMES_USR);
        $db_con->set_usr_num_fields(word::FLD_NAMES_NUM_USR);
        $db_con->set_where_id_in(word::FLD_ID, $ids);
        $qp->sql = $db_con->select_by_id();
        $qp->par = $db_con->get_par();

        return $qp;
    }

    /**
     * create an SQL statement to retrieve a list of triples from the database
     *
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @param array $ids triple ids that should be loaded
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    function load_by_trp_ids_sql(sql_db $db_con, array $ids): sql_par
    {
        $qp = new sql_par(self::class);
        $qp->name .= count($ids) . 'ids_triple_part';

        $db_con->set_type(DB_TYPE_TRIPLE);
        $db_con->set_name($qp->name);
        $db_con->set_usr($this->usr->id);
        $db_con->set_link_fields(word_link::FLD_FROM, word_link::FLD_TO, verb::FLD_ID);
        $db_con->set_fields(word_link::FLD_NAMES);
        $db_con->set_usr_fields(word_link::FLD_NAMES_USR);
        $db_con->set_usr_num_fields(word_link::FLD_NAMES_NUM_USR);
        $db_con->set_where_id_in(word_link::FLD_ID, $ids);
        $qp->sql = $db_con->select_by_id();
        $qp->par = $db_con->get_par();

        return $qp;
    }

    /**
     * load the phrases selected by the id
     *
     * @param phr_ids $ids of phrase ids that should be loaded
     * @return bool true if at least one phrase has been loaded
     */
    function load_by_ids(phr_ids $ids): bool
    {
        global $db_con;
        $result = false;

        // clear list before loading
        $this->lst = array();

        // split the ids by type
        $wrd_ids = [];
        $lnk_ids = [];
        foreach ($ids->lst as $id) {
            if ($id > 0) {
                $wrd_ids[] = $id;
            } elseif ($id < 0) {
                $lnk_ids[] = $id;
            }
        }

        if (count($wrd_ids) > 0) {
            if (!$db_con->connected()) {
                // add the words just with the id for unit testing
                foreach ($wrd_ids as $id) {
                    $wrd = new word($this->usr);
                    $wrd->id = $id;
                    $this->lst[] = $wrd->phrase();
                    $result = true;
                }
            } else {
                $qp = $this->load_by_wrd_ids_sql($db_con, $wrd_ids);
                $db_con->usr_id = $this->usr->id;
                $db_wrd_lst = $db_con->get($qp);
                foreach ($db_wrd_lst as $db_wrd) {
                    if (is_null($db_wrd[user_sandbox::FLD_EXCLUDED]) or $db_wrd[user_sandbox::FLD_EXCLUDED] == 0) {
                        $wrd = new word($this->usr);
                        $wrd->row_mapper($db_wrd);
                        $this->lst[] = $wrd->phrase();
                        $result = true;
                    }
                }
            }
        }

        if (count($lnk_ids) > 0) {
            if (!$db_con->connected()) {
                // add the triple just with the id for unit testing
                foreach ($lnk_ids as $id) {
                    $wrd = new word_link($this->usr);
                    $wrd->id = $id;
                    $this->lst[] = $wrd->phrase();
                    $result = true;
                }
            } else {
                $qp = $this->load_by_trp_ids_sql($db_con, $lnk_ids);
                $db_con->usr_id = $this->usr->id;
                $db_trp_lst = $db_con->get($qp);
                foreach ($db_trp_lst as $db_trp) {
                    if (is_null($db_trp[user_sandbox::FLD_EXCLUDED]) or $db_trp[user_sandbox::FLD_EXCLUDED] == 0) {
                        $trp = new word_link($this->usr);
                        $trp->row_mapper($db_trp);
                        $this->lst[] = $trp->phrase();
                        $result = true;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * load the phrases selected by the given names
     * TODO create one fast SQL loading statement
     *
     * @param array $names of phrase names that should be loaded
     * @return bool true if at least one phrase has been loaded
     */
    function load_by_names(array $names): bool
    {
        $result = false;

        // clear list before loading
        $this->lst = array();

        if (count($names) > 0) {
            foreach ($names as $name) {
                $wrd = new word($this->usr);
                $wrd->name = $name;
                $wrd->load();
                if ($wrd->id <> 0) {
                    $this->lst[] = $wrd->phrase();
                } else {
                    $trp = new word_link($this->usr);
                    $trp->name = $name;
                    $trp->load();
                    if ($trp->id <> 0) {
                        $this->lst[] = $trp->phrase();
                    } else {
                        log_warning('Cannot load ' . $name);
                    }
                }

            }
            $result = true;
        }

        return $result;
    }

    /**
     * create an SQL statement to retrieve a list of "related" phrases from the database
     * see load_related for a more detailed description
     *
     * @param sql_db $db_con the db connection object as a function parameter for unit testing
     * @param phrase $phr the base phrase which should be used for the selection
     * @param phrase $grp_phr to define the preferred phrase for the selection
     * @return sql_par the SQL statement, the name of the SQL statement and the parameter list
     */
    function load_related_sql(sql_db $db_con, phrase $phr, ?phrase $grp_phr = null): sql_par
    {
        $qp = new sql_par(self::class);
        $qp->name .= 'related';
        if ($grp_phr != null) {
            $qp->name .= '_and_group';
        }

        // if no phrase type is define, list all words and triples
        // TODO: but if word has several types don't offer to the user to select the simple word
        $usr_par = $db_con->get_par();
        $sql_words = 'SELECT DISTINCT w.' . word::FLD_ID . ' AS id, 
                             ' . $db_con->get_usr_field(word::FLD_NAME, "w", "u", sql_db::FLD_FORMAT_TEXT, "name") . ',
                             ' . $db_con->get_usr_field(user_sandbox::FLD_EXCLUDED, "w", "u", sql_db::FLD_FORMAT_BOOL) . '
                        FROM ' . $db_con->get_table_name(DB_TYPE_WORD) . ' w   
                   LEFT JOIN user_' . $db_con->get_table_name(DB_TYPE_WORD) . ' u ON u.' . word::FLD_ID . ' = w.' . word::FLD_ID . ' 
                                         AND u.user_id = ' . $this->usr->id . ' ';
        $sql_triples = 'SELECT DISTINCT l.word_link_id * -1 AS id, 
                               ' . $db_con->get_usr_field("name_given", "l", "u", sql_db::FLD_FORMAT_TEXT, "name") . ',
                               ' . $db_con->get_usr_field("excluded", "l", "u", sql_db::FLD_FORMAT_BOOL) . '
                          FROM word_links l
                     LEFT JOIN user_word_links u ON u.word_link_id = l.word_link_id 
                                                AND u.user_id = ' . $this->usr->id . ' ';

        if (isset($type)) {
            if ($type->id > 0) {

                // select all phrase ids of the given type e.g. ABB, DANONE, Zurich
                $sql_where_exclude = 'excluded = 0';
                $sql_field_names = 'id, name, excluded';
                $sql_wrd_all = 'SELECT from_phrase_id AS id FROM (
                                        SELECT DISTINCT
                                               l.from_phrase_id,    
                                               ' . $db_con->get_usr_field("excluded", "l", "u", sql_db::FLD_FORMAT_BOOL) . '
                                          FROM word_links l
                                     LEFT JOIN user_word_links u ON u.word_link_id = l.word_link_id 
                                                                AND u.user_id = ' . $this->usr->id . '
                                         WHERE l.to_phrase_id = ' . $type->id . ' 
                                           AND l.verb_id = ' . cl(db_cl::VERB, verb::IS_A) . ' ) AS a 
                                         WHERE ' . $sql_where_exclude . ' ';

                // ... out of all those get the phrase ids that have also other types e.g. Zurich (Canton)
                $sql_wrd_other = 'SELECT from_phrase_id FROM (
                                        SELECT DISTINCT
                                               l.from_phrase_id,    
                                               ' . $db_con->get_usr_field("excluded", "l", "u", sql_db::FLD_FORMAT_BOOL) . '
                                          FROM word_links l
                                     LEFT JOIN user_word_links u ON u.word_link_id = l.word_link_id 
                                                                AND u.user_id = ' . $this->usr->id . '
                                         WHERE l.to_phrase_id <> ' . $type->id . ' 
                                           AND l.verb_id = ' . cl(db_cl::VERB, verb::IS_A) . '
                                           AND l.from_phrase_id IN (' . $sql_wrd_all . ') ) AS o 
                                         WHERE ' . $sql_where_exclude . ' ';

                // if a word has no other type, use the word
                $sql_words = 'SELECT DISTINCT ' . $sql_field_names . ' FROM (
                      SELECT DISTINCT
                             w.' . word::FLD_ID . ' AS id, 
                             ' . $db_con->get_usr_field("word_name", "w", "u", sql_db::FLD_FORMAT_TEXT, "name") . ',
                             ' . $db_con->get_usr_field("excluded", "w", "u", sql_db::FLD_FORMAT_BOOL) . '
                        FROM ( ' . $sql_wrd_all . ' ) a, words w
                   LEFT JOIN user_words u ON u.' . word::FLD_ID . ' = w.' . word::FLD_ID . ' 
                                         AND u.user_id = ' . $this->usr->id . '
                       WHERE w.' . word::FLD_ID . ' NOT IN ( ' . $sql_wrd_other . ' )                                        
                         AND w.' . word::FLD_ID . ' = a.id ) AS w 
                       WHERE ' . $sql_where_exclude . ' ';

                // if a word has another type, use the triple
                $sql_triples = 'SELECT DISTINCT ' . $sql_field_names . ' FROM (
                        SELECT DISTINCT
                               l.word_link_id * -1 AS id, 
                               ' . $db_con->get_usr_field("name_given", "l", "u", sql_db::FLD_FORMAT_TEXT, "name") . ',
                               ' . $db_con->get_usr_field("excluded", "l", "u", sql_db::FLD_FORMAT_BOOL) . '
                          FROM word_links l
                     LEFT JOIN user_word_links u ON u.word_link_id = l.word_link_id 
                                                AND u.user_id = ' . $this->usr->id . '
                         WHERE l.from_phrase_id IN ( ' . $sql_wrd_other . ')                                        
                           AND l.verb_id = ' . cl(db_cl::VERB, verb::IS_A) . '
                           AND l.to_phrase_id = ' . $type->id . ' ) AS t 
                         WHERE ' . $sql_where_exclude . ' ';
                /*
                $sql_type_from = ', word_links t LEFT JOIN user_word_links ut ON ut.word_link_id = t.word_link_id
                                                                             AND ut.user_id = '.$this->usr->id.'';
                $sql_type_where_words   = 'WHERE w.' . word::FLD_ID . ' = t.from_phrase_id
                                             AND t.verb_id = '.cl(SQL_LINK_TYPE_IS).'
                                             AND t.to_phrase_id = '.$type->id.' ';
                $sql_type_where_triples = 'WHERE l.to_phrase_id = t.from_phrase_id
                                             AND t.verb_id = '.cl(SQL_LINK_TYPE_IS).'
                                             AND t.to_phrase_id = '.$type->id.' ';
                $sql_words   = 'SELECT w.' . word::FLD_ID . ' AS id,
                                      IF(u.word_name IS NULL, w.word_name, u.word_name) AS name,
                                      IF(u.excluded IS NULL, COALESCE(w.excluded, 0), COALESCE(u.excluded, 0)) AS excluded
                                  FROM words w
                            LEFT JOIN user_words u ON u.' . word::FLD_ID . ' = w.' . word::FLD_ID . '
                                                  AND u.user_id = '.$this->usr->id.'
                                      '.$sql_type_from.'
                                      '.$sql_type_where_words.'
                              GROUP BY name';
                $sql_triples = 'SELECT l.word_link_id * -1 AS id,
                                      IF(u.name IS NULL, l.name, u.name) AS name,
                                      IF(u.excluded IS NULL, COALESCE(l.excluded, 0), COALESCE(u.excluded, 0)) AS excluded
                                  FROM word_links l
                            LEFT JOIN user_word_links u ON u.word_link_id = l.word_link_id
                                                        AND u.user_id = '.$this->usr->id.'
                                      '.$sql_type_from.'
                                      '.$sql_type_where_triples.'
                              GROUP BY name';
                              */
            }
        }
        $sql_avoid_code_check_prefix = "SELECT";
        $sql = $sql_avoid_code_check_prefix . ' DISTINCT id, name
              FROM ( ' . $sql_words . ' UNION ' . $sql_triples . ' ) AS p
             WHERE excluded = 0
          ORDER BY p.name;';
        log_debug('phrase->sql_list -> ' . $sql);

        $qp->sql = $sql
        ;
        /*
        // select the related words
        $db_con->set_type(DB_TYPE_WORD);
        $db_con->set_name($qp->name);
        $db_con->set_usr($this->usr->id);
        $db_con->set_fields(word_link::FLD_NAMES);
        $db_con->set_usr_fields(word_link::FLD_NAMES_USR);
        $db_con->set_usr_num_fields(word_link::FLD_NAMES_NUM_USR);
        $db_con->set_where_id_in(word_link::FLD_ID, $ids);
        $qp->sql = $db_con->select_by_id();
        $qp->par = $db_con->get_par();


        // select the related triple
        $db_con->set_type(DB_TYPE_TRIPLE);
        $db_con->set_name($qp->name);
        $db_con->set_usr($this->usr->id);
        $db_con->set_link_fields(word_link::FLD_FROM, word_link::FLD_TO, verb::FLD_ID);
        $db_con->set_fields(word_link::FLD_NAMES);
        $db_con->set_usr_fields(word_link::FLD_NAMES_USR);
        $db_con->set_usr_num_fields(word_link::FLD_NAMES_NUM_USR);
        $db_con->set_where_id_in(word_link::FLD_ID, $ids);
        $qp->sql = $db_con->select_by_id();
        $qp->par = $db_con->get_par();

        // union
        */

        return $qp;
    }

    /**
     * load all phrases similar to the given phrase
     * e.g. if the phrase is Zurich (Canton) a list of all Cantons of Switzerland is returned
     * this is used to selecting related phrases
     *
     * @param phrase $phr the base phrase which should be used for the selection
     *                    e.g. if Zurich (Canton) if the base phrase, a list of all cantons of Switzerland should be returnedd
     * @param phrase $grp_phr to define the preferred phrase for the selection
     *                        e.g. if Zurich is the base phrase (the word Zurich in this case) it is not clear, if City or Canton should be used
     *                             for the selection of the related phrases, so the $grp-phr Canton can be used as a selection
     *
     * if only the word Zurich is given as base $phr, the selection should include City and Canton as optional preselection
     * by selection the preselection e.g. Canton the selection should be modified so that the 20 most often used Cantons are on the top
     * with "more Canton" the user can increase to list of cantons
     * at the end of the preselection list alternative groups such as City should be shown
     *
     * @return bool true if at least one phrase is found
     */
    function load_related(phrase $phr, ?phrase $grp_phr = null): bool
    {
        $result = false;

        // get group phrase if needed
        if ($grp_phr == null) {
            $grp_lst = new phrase_list($this->usr);

        }

        return $result;
    }

    /**
     * load a list of phrases by a given phrase, verb and direction
     * e.g. for "Zurich" "is a" and "UP" the result is "Canton", "City" and "Company"
     *
     * @param phrase $phr the phrase which should be used for selecting the words or triples
     * @param verb|null $vrb if set to filter the selection
     * @param string $direction to select either the parents, children or all related words ana triples
     * @return bool true if at least one triple found
     */
    function load_by_phr(phrase $phr, ?verb $vrb = null, string $direction = word_link_list::DIRECTION_BOTH): bool
    {
        $this->lst = array();

        $wrd_lst = new word_list($this->usr);
        $wrd_lst->load_linked_words($vrb->id, $direction);
        $wrd_added =  $this->add_wrd_lst($wrd_lst);

        $trp_lst = new word_link_list($this->usr);
        $trp_lst->load_by_phr($phr, $vrb, $direction);
        $trp_added =  $this->add_trp_lst($trp_lst);

        if ($wrd_added or $trp_added) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * load the related phrases of a given type
     *
     * used to create a selector that contains the time words
     * @param phrase $phr the base phrase used for the selection
     *         e.g. "year" to show the years first
     *         or "next years" to show the future years
     *         or "past years" to show the last years
     */
    function load_by_phr_vrb_and_type(
        phrase $phr,
        ?verb $vrb = null,
        word_type_list $wrd_types,
        string $direction = word_link_list::DIRECTION_BOTH): phrase_list
    {
        $result = new phrase_list($this->usr);
        /*
         * if ($pos > 0) {
            $field_name = "phrase" . $pos;
            //$field_name = "time".$pos;
        } else {
            $field_name = "phrase";
            //$field_name = "time";
        }
        //
        if ($type->id > 0) {
            $sql_from = "word_links l, words w";
            $sql_where_and = "AND w.word_id = l.from_phrase_id
                        AND l.verb_id = " . cl(db_cl::VERB, verb::IS_A) . "
                        AND l.to_phrase_id = " . $type->id;
        } else {
            $sql_from = "words w";
            $sql_where_and = "";
        }
        $sql_avoid_code_check_prefix = "SELECT";
        $sql = $sql_avoid_code_check_prefix . " id, name
              FROM ( SELECT w.word_id AS id,
                            " . $db_con->get_usr_field("word_name", "w", "u", sql_db::FLD_FORMAT_TEXT, "name") . ",
                            " . $db_con->get_usr_field("excluded", "w", "u", sql_db::FLD_FORMAT_BOOL) . "
                       FROM " . $sql_from . "
                  LEFT JOIN user_words u ON u.word_id = w.word_id
                                        AND u.user_id = " . $this->usr->id . "
                      WHERE w.word_type_id = " . cl(db_cl::WORD_TYPE, word_type_list::DBL_TIME) . "
                        " . $sql_where_and . "
                   GROUP BY name) AS s
            WHERE (excluded <> 1 OR excluded is NULL)
          ORDER BY name;";
        $sel = new html_selector;
        $sel->form = $form_name;
        $sel->name = $field_name;
        $sel->sql = $sql;
        $sel->selected = $this->id;
        $sel->dummy_text = '... please select';
        $result .= $sel->display();
         */
        return $result;
    }

    /**
     * add the given phrase ids to the list without loading the phrases from the database
     *
     * @param string|null $wrd_ids_txt with comma seperated word ids
     * @param string|null $trp_ids_txt with comma seperated triple ids
     * @return void
     */
    function add_by_ids(?string $wrd_ids_txt, ?string $trp_ids_txt)
    {
        // add the word ids
        $ids = $this->id_lst();
        if ($wrd_ids_txt != null) {
            if ($wrd_ids_txt != '') {
                $wrd_ids = explode(",", $wrd_ids_txt);
                foreach ($wrd_ids as $id) {
                    if (!in_array($id, $ids)) {
                        $phr = new phrase($this->usr);
                        $phr->id = $id;
                        $this->lst[] = $phr;
                        $ids[] = $id;
                    }
                }
            }
        }

        // add the triple ids
        if ($trp_ids_txt != null) {
            if ($trp_ids_txt != '') {
                $trp_ids = explode(",", $trp_ids_txt);
                foreach ($trp_ids as $id) {
                    $id = $id * -1;
                    if (!in_array($id, $ids)) {
                        $phr = new phrase($this->usr);
                        $phr->id = $id;
                        $this->lst[] = $phr;
                        $ids[] = $id;
                    }
                }
            }
        }
    }

    /**
     * build a word list including the triple words or in other words flatten the list e.g. for parent inclusions
     * @return word_list with all words of the phrases split into single words
     */
    function wrd_lst_all(): word_list
    {
        log_debug('phrase_list->wrd_lst_all for ' . $this->dsp_id());

        $wrd_lst = new word_list($this->usr);

        // check the basic settings
        if (!isset($this->usr)) {
            log_err('User for phrase list ' . $this->dsp_id() . ' missing', 'phrase_list->wrd_lst_all');
        }

        // fill the word list
        foreach ($this->lst as $phr) {
            if ($phr->obj == null) {
                log_err('Phrase ' . $phr->dsp_id() . ' could not be loaded', 'phrase_list->wrd_lst_all');
            } else {
                if ($phr->obj->id == 0) {
                    log_err('Phrase ' . $phr->dsp_id() . ' could not be loaded', 'phrase_list->wrd_lst_all');
                } else {
                    if ($phr->obj->name == '') {
                        $phr->load();
                        log_warning('Phrase ' . $phr->dsp_id() . ' needs unexpected reload', 'phrase_list->wrd_lst_all');
                    }
                    // TODO check if old can ge removed: if ($phr->id > 0) {
                    if (get_class($phr->obj) == word::class or get_class($phr->obj) == word_dsp::class) {
                        $wrd_lst->add($phr->obj);
                    } elseif (get_class($phr->obj) == DB_TYPE_TRIPLE) {
                        // use the recursive triple function to include the foaf words
                        $sub_wrd_lst = $phr->obj->wrd_lst();
                        foreach ($sub_wrd_lst->lst as $wrd) {
                            if ($wrd->name == '') {
                                $wrd->load();
                                log_warning('Word ' . $wrd->dsp_id() . ' needs unexpected reload', 'phrase_list->wrd_lst_all');
                            }
                            $wrd_lst->add($wrd);
                        }
                    } else {
                        log_err('The phrase list ' . $this->dsp_id() . ' contains ' . $phr->obj->dsp_id() . ', which is neither a word nor a phrase, but it is a ' . get_class($phr->obj), 'phrase_list->wrd_lst_all');
                    }
                }
            }
        }

        log_debug('phrase_list->wrd_lst_all -> ' . $wrd_lst->dsp_id());
        return $wrd_lst;
    }

    /**
     * get a word list from the phrase list
     * @return word_list list of the words from the phrase list
     */
    function wrd_lst(): word_list
    {
        $wrd_lst = new word_list($this->usr);
        foreach ($this->lst as $phr) {
            if ($phr->id > 0) {
                if (isset($phr->obj)) {
                    $wrd_lst->add($phr->obj);
                }
            }
        }
        return $wrd_lst;
    }

    /**
     * get a triple list from the phrase list
     * @return word_link_list list of the triples from the phrase list
     */
    function trp_lst(): word_link_list
    {
        $trp_lst = new word_link_list($this->usr);
        foreach ($this->lst as $phr) {
            if ($phr->id < 0) {
                if (isset($phr->obj)) {
                    $trp_lst->add($phr->obj);
                }
            }
        }
        return $trp_lst;
    }

    /*
      tree building function
      ----------------------

      Overview for words, triples and phrases and it's lists

                 children and            parents return the direct parents and children   without the original phrase(s)
            foaf_children and       foaf_parents return the    all parents and children   without the original phrase(s)
                    are and                 is return the    all parents and children including the original phrase(s) for the specific verb "is a"
               contains                        return the    all             children including the original phrase(s) for the specific verb "contains"
                                    is part of return the    all parents              without the original phrase(s) for the specific verb "contains"
                   next and              prior return the direct parents and children   without the original phrase(s) for the specific verb "follows"
            followed_by and        follower_of return the    all parents and children   without the original phrase(s) for the specific verb "follows"
      differentiated_by and differentiator_for return the    all parents and children   without the original phrase(s) for the specific verb "can_contain"

      Samples

      the      parents of  "ABB" can be "public limited company"
      the foaf_parents of  "ABB" can be "public limited company" and "company"
                  "is" of  "ABB" can be "public limited company" and "company" and "ABB" (used to get all related values)
      the       children for "company" can include "public limited company"
      the  foaf_children for "company" can include "public limited company" and "ABB"
                 "are" for "company" can include "public limited company" and "ABB" and "company" (used to get all related values)

            "contains" for "balance sheet" is "assets" and "liabilities" and "company" and "balance sheet" (used to get all related values)
          "is part of" for "assets" is "balance sheet" but not "assets"

                "next" for "2016" is "2017"
               "prior" for "2017" is "2016"
      "is followed by" for "2016" is "2017" and "2018"
      "is follower of" for "2016" is "2015" and "2014"

      "wind energy" and "energy" "can be differentiator for" "sector"
                        "sector" "can be differentiated_by"  "wind energy" and "energy"

      if "wind energy" "is part of" "energy"

    */

    /**
     * @returns array a list of phrases, that characterises the given phrase e.g. for the "ABB Ltd." it will return "Company" if the verb_id is "is a"
     * ex foaf_parent
     */
    function foaf_parents($verb_id): phrase_list
    {
        log_debug('phrase_list->foaf_parents (type id ' . $verb_id . ')');
        $wrd_lst = $this->wrd_lst_all();
        $added_wrd_lst = $wrd_lst->foaf_parents($verb_id);
        $added_phr_lst = $added_wrd_lst->phrase_lst();

        log_debug('phrase_list->foaf_parents -> (' . $added_phr_lst->dsp_name() . ')');
        return $added_phr_lst;
    }

    // similar to foaf_parents, but for only one level
    // $level is the number of levels that should be looked into
    // ex foaf_parent_step
    function parents($verb_id, $level)
    {
        log_debug('phrase_list->parents(' . $verb_id . ')');
        $wrd_lst = $this->wrd_lst_all();
        $added_wrd_lst = $wrd_lst->parents($verb_id, $level);
        $added_phr_lst = $added_wrd_lst->phrase_lst();

        log_debug('phrase_list->parents -> (' . $added_phr_lst->name() . ')');
        return $added_phr_lst;
    }

    // similar to foaf_parent, but the other way round e.g. for "Companies" it will return "ABB Ltd." and others if the link type is "are"
    // ex foaf_child
    function foaf_children($verb_id)
    {
        log_debug('phrase_list->foaf_children type ' . $verb_id);
        $added_phr_lst = null;

        if ($verb_id > 0) {
            $wrd_lst = $this->wrd_lst_all();
            $added_wrd_lst = $wrd_lst->foaf_children($verb_id);
            $added_phr_lst = $added_wrd_lst->phrase_lst();

            log_debug('phrase_list->foaf_children -> (' . $added_phr_lst->name() . ')');
        }
        return $added_phr_lst;
    }

    // similar to foaf_child, but for only one level
    // $level is the number of levels that should be looked into
    // ex foaf_child_step
    function children($verb_id, $level)
    {
        log_debug('phrase_list->children type ' . $verb_id);
        $wrd_lst = $this->wrd_lst_all();
        $added_wrd_lst = $wrd_lst->children($verb_id, $level);
        $added_phr_lst = $added_wrd_lst->phrase_lst();

        log_debug('phrase_list->children -> (' . $added_phr_lst->name() . ')');
        return $added_phr_lst;
    }

    // returns a list of phrases that are related to this phrase list e.g. for "ABB" and "Daimler" it will return "Company" (but not "ABB"???)
    function is(): phrase_list
    {
        $phr_lst = $this->foaf_parents(cl(db_cl::VERB, verb::IS_A));
        log_debug('phrase_list->is -> (' . $this->dsp_id() . ' is ' . $phr_lst->dsp_name() . ')');
        return $phr_lst;
    }

    // returns a list of phrases that are related to this phrase list e.g. for "Company" it will return "ABB" and "Daimler" and "Company"
    // e.g. to get all related values
    function are(): phrase_list
    {
        log_debug('phrase_list->are -> ' . $this->dsp_id());
        $phr_lst = $this->foaf_children(cl(db_cl::VERB, verb::IS_A));
        log_debug('phrase_list->are -> ' . $this->dsp_id() . ' are ' . $phr_lst->dsp_id());
        $phr_lst->merge($this);
        log_debug('phrase_list->are -> ' . $this->dsp_id() . ' merged into ' . $phr_lst->dsp_id());
        return $phr_lst;
    }

    /**
     * @returns phrase_list a list of phrases that are related to this phrase list
     */
    function contains(): phrase_list
    {
        $phr_lst = $this->foaf_children(cl(db_cl::VERB, verb::IS_PART_OF));
        $phr_lst->merge($this);
        log_debug('phrase_list->contains -> (' . $this->dsp_id() . ' contains ' . $phr_lst->name() . ')');
        return $phr_lst;
    }

    /**
     * @returns int the number of phrases in this list
     */
    function count(): int
    {
        return count($this->lst);
    }

    /**
     * @returns bool true if the phrase list is empty
     */
    function empty(): bool
    {
        if ($this->count() <= 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @returns bool true if all phrases of the list have a name and an id
     */
    function loaded(): bool
    {
        $result = true;
        foreach ($this->lst as $phr) {
            if ($phr->id == 0 or $phr->name == '') {
                $result = false;
            }
        }
        return $result;
    }

    // makes sure that all combinations of "are" and "contains" are included
    function are_and_contains()
    {
        log_debug('phrase_list->are_and_contains for ' . $this->dsp_id());

        // this first time get all related items
        $phr_lst = clone $this;
        $phr_lst = $phr_lst->are();
        $phr_lst = $phr_lst->contains();
        $added_lst = clone $phr_lst;
        $added_lst->diff($this);
        // ... and after that get only for the new
        if ($added_lst->count() > 0) {
            $loops = 0;
            log_debug('phrase_list->are_and_contains -> added ' . $added_lst->dsp_id() . ' to ' . $phr_lst->name());
            do {
                $next_lst = clone $added_lst;
                $next_lst = $next_lst->are();
                $added_lst = $next_lst->contains();
                $added_lst->diff($phr_lst);
                if ($added_lst->count() > 0) {
                    log_debug('phrase_list->are_and_contains -> add ' . $added_lst->dsp_id() . ' to ' . $phr_lst->name());
                }
                $phr_lst->merge($added_lst);
                $loops++;
            } while ($added_lst->count() > 0 and $loops < MAX_LOOP);
        }
        log_debug('phrase_list->are_and_contains -> ' . $this->dsp_id() . ' are_and_contains ' . $phr_lst->name());
        return $phr_lst;
    }

    // add all potential differentiator phrases of the phrase lst e.g. get "energy" for "sector"
    function differentiators()
    {
        log_debug('phrase_list->differentiators for ' . $this->dsp_id());
        $phr_lst = $this->foaf_children(cl(db_cl::VERB, verb::DBL_CAN_CONTAIN));
        log_debug('phrase_list->differentiators merge ' . $this->dsp_id());
        $this->merge($phr_lst);
        log_debug('phrase_list->differentiators -> ' . $phr_lst->dsp_id() . ' for ' . $this->dsp_id());
        return $phr_lst;
    }

    // same as differentiators, but including the subtypes e.g. get "energy" and "wind energy" for "sector" if "wind energy" is part of "energy"
    function differentiators_all()
    {
        log_debug('phrase_list->differentiators_all for ' . $this->dsp_id());
        // this first time get all related items
        $phr_lst = $this->foaf_children(cl(db_cl::VERB, verb::DBL_CAN_CONTAIN));
        $phr_lst = $phr_lst->are();
        $added_lst = $phr_lst->contains();
        $added_lst->diff($this);
        // ... and after that get only for the new
        if ($added_lst->count() > 0) {
            $loops = 0;
            log_debug('phrase_list->differentiators -> added ' . $added_lst->dsp_id() . ' to ' . $phr_lst->name());
            do {
                $next_lst = $added_lst->foaf_children(cl(db_cl::VERB, verb::DBL_CAN_CONTAIN));
                $next_lst = $next_lst->are();
                $added_lst = $next_lst->contains();
                $added_lst->diff($phr_lst);
                if ($added_lst->count() > 0) {
                    log_debug('phrase_list->differentiators -> add ' . $added_lst->name() . ' to ' . $phr_lst->name());
                }
                $phr_lst->merge($added_lst);
                $loops++;
            } while ($added_lst->count() > 0 and $loops < MAX_LOOP);
        }
        log_debug('phrase_list->differentiators -> ' . $phr_lst->name() . ' for ' . $this->dsp_id());
        return $phr_lst;
    }

    // similar to differentiators, but only a filtered list of differentiators is viewed to increase speed
    function differentiators_filtered($filter_lst)
    {
        log_debug('phrase_list->differentiators_filtered for ' . $this->dsp_id());
        $result = $this->differentiators_all();
        $result = $result->filter($filter_lst);
        log_debug('phrase_list->differentiators_filtered -> ' . $result->dsp_id());
        return $result;
    }

    /*
      im- and export functions
    */

    /**
     * import a phrase list from an inner part of a JSON array object
     *
     * @param array $json_obj an array with the data of the json object
     * @param bool $do_save can be set to false for unit testing
     * @return string an empty string if the import has been successfully saved al phrases to the database
     *                and otherwise the error message that should be shown to the user
     */
    function import_lst(array $json_obj, bool $do_save = true): string
    {
        global $word_types;

        $result = '';
        foreach ($json_obj as $value) {
            if ($value != '') {
                $phr = new phrase($this->usr);
                $phr->name = $value;
                if ($do_save) {
                    $phr->load();
                    if ($phr->id == 0) {
                        $wrd = new word($this->usr);
                        $wrd->name = $value;
                        $wrd->load();
                        if ($wrd->id == 0) {
                            $wrd->name = $value;
                            $wrd->type_id = $word_types->default_id();
                            $result .= $wrd->save();
                        }
                        if ($wrd->id == 0) {
                            log_err('Cannot add word "' . $value . '" when importing ' . $this->dsp_id(), 'value->import_obj');
                        } else {
                            $phr = $wrd->phrase();
                        }
                    }
                }
                $this->add($phr);
            }
        }

        // save the word in the database
        // TODO check why this is needed
        if ($do_save) {
            $result .= $this->save();
        }

        return $result;
    }

    /**
     * import a phrase list object from a JSON array object
     *
     * @param array $json_obj an array with the data of the json object
     * @param bool $do_save can be set to false for unit testing
     * @return bool true if the import has been successfully saved to the database
     */
    function import_obj(array $json_obj, bool $do_save = true): bool
    {
        $result = false;
        foreach ($json_obj as $key => $value) {
            if ($key == 'words') {
                $result = $this->import_lst($value, $do_save);
            }
        }

        return $result;
    }


    /*
     * check and information functions
     */

    /**
     * @return bool true if this phrase list has at least one entry
     */
    function is_valid(): bool
    {
        $result = true;
        if ($this->count() <= 0) {
            $result = false;
        }
        return $result;
    }

    /*
     * extract functions
     */

    // return a unique id of the phrase list
    function id(): string
    {
        $result = 'null';
        $id_lst = $this->id_lst();
        if ($this->count() > 0) {
            asort($id_lst);
            $result = implode(",", $id_lst);
        }
        return $result;
    }

    /**
     * get the phrase ids as an array
     * switch to ids() if possible
     */
    function id_lst(): array
    {
        return $this->ids()->lst;
    }

    /**
     * @return phr_ids with the sorted phrase ids where a triple has a negative id
     */
    function ids(): phr_ids
    {
        $lst = array();
        if (count($this->lst) > 0) {
            foreach ($this->lst as $phr) {
                // use only valid ids
                if ($phr->id <> 0) {
                    $lst[] = $phr->id;
                }
            }
        }
        asort($lst);
        return (new phr_ids($lst));
    }

    /**
     * @return array with the word ids
     */
    function wrd_ids(): array
    {
        $result = array();
        if (count($this->lst) > 0) {
            foreach ($this->lst as $phr) {
                // use only valid word ids
                if ($phr->id > 0) {
                    $result[] = $phr->id;
                }
            }
        }
        asort($result);
        return $result;
    }

    /**
     * @return array with the triple ids (converted from the negative phrase ids)
     */
    function trp_ids(): array
    {
        $result = array();
        if (count($this->lst) > 0) {
            foreach ($this->lst as $phr) {
                // use only valid triple ids
                if ($phr->id < 0) {
                    $result[] = $phr->id * -1;
                }
            }
        }
        asort($result);
        return $result;
    }

    // return an url with the phrase ids
    // the order of the ids is used to sort the phrases for the user
    function id_url(): string
    {
        $result = '';
        if (count($this->lst) > 0) {
            $result = '&phrases=' . implode(",", $this->id_lst());
        }
        return $result;
    }

    // the old long form to encode
    function id_url_long(): string
    {
        return zu_ids_to_url($this->id_lst(), "phrase");
    }

    /*
     * display functions
     */

    /**
     * return best possible id for this element mainly used for debugging
     */
    function dsp_id(): string
    {
        $name = $this->dsp_name();
        if ($name <> '""') {
            $result = $name . ' (' . dsp_array($this->id_lst()) . ')';
        } else {
            $result = dsp_array($this->id_lst());
        }

        /* the user is in most cases no extra info
        if (isset($this->usr)) {
          $result .= ' for user '.$this->usr->name;
        }
        */

        return $result;
    }

    /**
     * @return string one string with all names of the list and reduced in size mainly for debugging
     * this function is called from dsp_id, so no other call is allowed
     */
    function dsp_name(): string
    {
        global $debug;

        $name_lst = $this->names();

        if ($debug > 10) {
            $result = '"' . implode('","', $name_lst) . '"';
        } else {
            $result = '"' . implode('","', array_slice($name_lst, 0, 7));
            if (count($name_lst) > 8) {
                $result .= ' ... total ' . dsp_count($this->lst);
            }
            $result .= '"';
        }
        return $result;
    }

    /**
     * @return string one string with all names of the list
     */
    function name(): string
    {
        $name_lst = $this->names();
        return '"' . implode('","', $name_lst) . '"';
    }

    /**
     * @return array with all phrase names
     */
    function names(): array
    {
        $name_lst = array();
        foreach ($this->lst as $phr) {
            $name_lst[] = $phr->name;
        }
        // TODO allow to fix the order
        asort($name_lst);
        return $name_lst;
    }

    // true if the phrase is part of the phrase list
    function does_contain($phr_to_check): bool
    {
        $result = false;

        foreach ($this->lst as $phr) {
            if ($phr->id == $phr_to_check->id) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * add one phrase to the phrase list, but only if it is not yet part of the phrase list
     * @returns bool true the phrase has been added
     */
    function add(?phrase $phr_to_add): bool
    {
        $result = false;
        // check parameters
        if ($phr_to_add != null) {
            log_debug('phrase_list->add ' . $phr_to_add->dsp_id());
            if (get_class($phr_to_add) <> phrase::class) {
                log_err("Object to add must be of type phrase, but it is " . get_class($phr_to_add) . ".", "phrase_list->add");
            } else {
                if ($phr_to_add->id <> 0 or $phr_to_add->name != '') {
                    if (count($this->id_lst()) > 0) {
                        if (!in_array($phr_to_add->id, $this->id_lst())) {
                            $this->lst[] = $phr_to_add;
                            $result = true;
                        }
                    } else {
                        $this->lst[] = $phr_to_add;
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * add a list of words to the phrase list, but only if it is not yet part of the phrase list
     *
     * @param word_list|null $wrd_lst_to_add the list of words to add as a word list object
     * @returns bool true is at least one word has been added
     */
    function add_wrd_lst(?word_list $wrd_lst_to_add): bool
    {
        $result = false;
        // check parameters
        if ($wrd_lst_to_add != null) {
            if ($wrd_lst_to_add->lst != null) {
                foreach ($wrd_lst_to_add->lst as $wrd) {
                    if ($this->add($wrd->phrase())) {
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * add a list of triples to the phrase list, but only if it is not yet part of the phrase list
     *
     * @param word_list|null $trp_lst_to_add the list of words to add as a word list object
     * @returns bool true is at least one word has been added
     */
    function add_trp_lst(?word_link_list $trp_lst_to_add): bool
    {
        $result = false;
        // check parameters
        if ($trp_lst_to_add != null) {
            if ($trp_lst_to_add->lst != null) {
                foreach ($trp_lst_to_add->lst as $trp) {
                    if ($this->add($trp->phrase())) {
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * add one phrase by the id to the phrase list, but only if it is not yet part of the phrase list
     */
    function add_id($phr_id_to_add)
    {
        log_debug('phrase_list->add_id (' . $phr_id_to_add . ')');
        if (!in_array($phr_id_to_add, $this->id_lst())) {
            if ($phr_id_to_add <> 0) {
                $phr_to_add = new phrase($this->usr);
                $phr_to_add->id = $phr_id_to_add;
                $phr_to_add->load();

                $this->add($phr_to_add);
            }
        }
    }

    /**
     * add one phrase to the phrase list defined by the phrase name
     */
    function add_name($phr_name_to_add)
    {
        log_debug('phrase_list->add_name "' . $phr_name_to_add . '"');
        if (is_null($this->usr->id)) {
            log_err("The user must be set.", "phrase_list->add_name");
        } else {
            $phr_to_add = new phrase($this->usr);
            $phr_to_add->name = $phr_name_to_add;
            $phr_to_add->load();

            if ($phr_to_add->id <> 0) {
                $this->add($phr_to_add);
            } else {
                log_err('"' . $phr_name_to_add . '" not found.', "phrase_list->add_name");
            }
        }
        log_debug('phrase_list->add_name -> added "' . $phr_name_to_add . '" to ' . $this->dsp_id() . ')');
    }

    // del one phrase to the phrase list, but only if it is not yet part of the phrase list
    function del($phr_to_del)
    {
        log_debug('phrase_list->del ' . $phr_to_del->name . ' (' . $phr_to_del->id . ')');
        $phr_ids = $this->id_lst();
        if (count($phr_ids) > 0) {
            if (in_array($phr_to_del->id, $phr_ids)) {
                $del_pos = array_search($phr_to_del->id, $phr_ids);
                if ($this->lst[$del_pos]->id == $phr_to_del->id) {
                    unset ($this->lst[$del_pos]);
                } else {
                    log_err('Remove of ' . $phr_to_del->dsp_id() . ' failed');
                }
            }
        }
    }

    /**
     * merge as a function, because the array_merge does not create an object
     */
    function merge($new_phr_lst): phrase_list
    {
        log_debug('phrase_list->merge ' . $new_phr_lst->dsp_id() . ' to ' . $this->dsp_id());
        if (isset($new_phr_lst->lst)) {
            log_debug('phrase_list->merge -> do');
            foreach ($new_phr_lst->lst as $new_phr) {
                log_debug('phrase_list->merge -> add');
                log_debug('phrase_list->merge add ' . $new_phr->dsp_id());
                $this->add($new_phr);
                log_debug('phrase_list->merge -> added');
            }
        }
        log_debug('phrase_list->merge -> to ' . $this->dsp_id());
        return $this;
    }

    /**
     * filters a phrase list
     * e.g. out of "2014", "2015", "2016", "2017"
     *      with the filter "2016", "2017","2018"
     *      the result is "2016", "2017"
     * @param phrase_list $filter_lst a phrase list with the phrases that should be removed from this list
     * @returns phrase_list list a phrase excluding the given phrases
     */
    function filter(phrase_list $filter_lst): phrase_list
    {
        $result = clone $this;

        // check and adjust the parameters
        if (get_class($filter_lst) == word_list::class) {
            $filter_phr_lst = $filter_lst->phrase_lst();
        } else {
            $filter_phr_lst = $filter_lst;
        }
        if (!isset($filter_phr_lst)) {
            log_err('Phrases to delete are missing.', 'phrase_list->diff');
        }
        if (get_class($filter_phr_lst) <> phrase_list::class) {
            log_err(get_class($filter_phr_lst) . ' cannot be used to delete phrases.', 'phrase_list->diff');
        }

        if (!empty($result->lst)) {
            $phr_lst = array();
            $lst_ids = $filter_phr_lst->id_lst();
            foreach ($result->lst as $phr) {
                if (in_array($phr->id, $lst_ids)) {
                    $phr_lst[] = $phr;
                }
            }
            $result->lst = $phr_lst;
            log_debug('phrase_list->filter -> ' . $result->dsp_id());
        }
        return $result;
    }

    /**
     * diff as a function, because the array_diff does not seem to work for an object list
     *
     * e.g. for "2014", "2015", "2016", "2017"
     * and delete list of "2016", "2017","2018"
     * the result is "2014", "2015"
     *
     * @param phrase_list $del_lst is the list of phrases that should be removed from this list object
     */
    function diff(phrase_list $del_lst): void
    {
        log_debug('phrase_list->diff of ' . $del_lst->dsp_id() . ' and ' . $this->dsp_id());

        if (!empty($this->lst)) {
            $result = array();
            $lst_ids = $del_lst->id_lst();
            foreach ($this->lst as $phr) {
                if (!in_array($phr->id, $lst_ids)) {
                    $result[] = $phr;
                }
            }
            $this->lst = $result;
        }

        log_debug('phrase_list->diff -> ' . $this->dsp_id());
    }

    /**
     * same as diff but sometimes this name looks better
     */
    function not_in(phrase_list $del_phr_lst)
    {
        log_debug('phrase_list->not_in get out of ' . $this->dsp_name() . ' not in ' . $del_phr_lst->name() . ')');
        $this->diff($del_phr_lst);
    }
    /*
    // keep only those phrases in the list that are not in the list to delete
    // e.g. for "2014", "2015", "2016", "2017" and the exclude list of "2016", "2017","2018" the result is "2014", "2015"
    function not_in($del_phr_lst) {
      zu_debug('phrase_list->not_in');
      foreach ($this->lst AS $phr) {
        if ($phr->id <> 0) {
          if (in_array($phr->id, $del_phr_lst->ids)) {
            $del_pos = array_search($phr->id, $this->ids);
            zu_debug('phrase_list->not_in -> to exclude ('.$this->lst[$del_pos]->name.')');
            unset ($this->lst[$del_pos]);
            unset ($this->ids[$del_pos]);
          }
        }
      }
      zu_debug('phrase_list->not_in -> '.$this->dsp_id());
    }
    */

    /**
     * similar to diff, but using an id array to exclude instead of a phrase list object
     */
    function diff_by_ids($del_phr_ids)
    {
        $this->id_lst();
        foreach ($del_phr_ids as $del_phr_id) {
            if ($del_phr_id > 0) {
                log_debug('phrase_list->diff_by_ids ' . $del_phr_id);
                if ($del_phr_id > 0 and in_array($del_phr_id, $this->id_lst())) {
                    $del_pos = array_search($del_phr_id, $this->id_lst());
                    unset ($this->lst[$del_pos]);
                }
            }
        }
        //$this->ids = array_diff($this->ids, $del_phr_ids);
        log_debug('phrase_list->diff_by_ids -> ' . $this->dsp_id());
    }

    /**
     * look at a phrase list and remove the general phrase, if there is a more specific phrase also part of the list e.g. remove "Country", but keep "Switzerland"
     */
    function keep_only_specific()
    {
        log_debug('phrase_list->keep_only_specific (' . $this->dsp_id());

        $result = $this->id_lst();
        foreach ($this->lst as $phr) {
            // temp workaround utils the reason is found, why the user is sometimes not set
            if (!isset($phr->usr)) {
                $phr->usr = $this->usr;
            }
            $phr_lst_is = $phr->is();
            if (isset($phr_lst_is)) {
                if (!empty($phr_lst_is->ids)) {
                    $result = zu_lst_not_in_no_key($result, $phr_lst_is->ids);
                    log_debug('phrase_list->keep_only_specific -> "' . $phr->name . '" is of type ' . $phr_lst_is->dsp_id());
                }
            }
        }

        log_debug('phrase_list->keep_only_specific -> (' . implode(",", $result) . ')');
        return $result;
    }

    /**
     * @return bool true if a phrase lst contains a time phrase
     */
    function has_time(): bool
    {
        $result = false;
        // loop over the phrase ids and add only the time ids to the result array
        foreach ($this->lst as $phr) {
            log_debug('phrase_list->has_time -> check (' . $phr->name . ')');
            if ($result == false) {
                if ($phr->is_time()) {
                    $result = true;
                }
            }
        }
        log_debug('phrase_list->has_time -> (' . zu_dsp_bool($result) . ')');
        return $result;
    }

    /**
     * @return bool true if a phrase lst contains a measure phrase
     */
    function has_measure(): bool
    {
        log_debug('phrase_list->has_measure for ' . $this->dsp_id());
        $result = false;
        // loop over the phrase ids and add only the time ids to the result array
        foreach ($this->lst as $phr) {
            log_debug('phrase_list->has_measure -> check ' . $phr->dsp_id());
            if ($result == false) {
                if ($phr->is_measure()) {
                    $result = true;
                }
            }
        }
        log_debug('phrase_list->has_measure -> (' . zu_dsp_bool($result) . ')');
        return $result;
    }

    /**
     * @return bool true if a phrase lst contains a scaling phrase
     */
    function has_scaling(): bool
    {
        $result = false;
        // loop over the phrase ids and add only the time ids to the result array
        foreach ($this->lst as $phr) {
            log_debug('phrase_list->has_scaling -> check ' . $phr->dsp_id());
            if ($result == false) {
                if ($phr->is_scaling()) {
                    $result = true;
                }
            }
        }
        log_debug('phrase_list->has_scaling -> (' . zu_dsp_bool($result) . ')');
        return $result;
    }

    /**
     * @return bool true if a phrase lst contains a percent scaling phrase, which is used for a predefined formatting of the value
     */
    function has_percent(): bool
    {
        $result = false;
        // loop over the phrase ids and add only the time ids to the result array
        foreach ($this->lst as $phr) {
            // temp solution for testing
            $phr->usr = $this->usr;
            log_debug('phrase_list->has_percent -> check ' . $phr->dsp_id());
            if ($result == false) {
                if ($phr->is_percent()) {
                    $result = true;
                }
            }
        }
        log_debug('phrase_list->has_percent -> (' . zu_dsp_bool($result) . ')');
        return $result;
    }

    /**
     * get all phrases of this phrase list that have at least one time term
     * TODO to be replaced by time_lst
     * @return array of time phrases
     */
    function time_lst_old(): array
    {
        log_debug('phrase_list->time_lst_old(' . $this->dsp_id() . ')');

        $result = array();
        $time_type = cl(db_cl::WORD_TYPE, word_type_list::DBL_TIME);
        // loop over the phrase ids and add only the time ids to the result array
        foreach ($this->lst as $phr) {
            if ($phr->type_id() == $time_type) {
                $result[] = $phr;
            }
        }
        //zu_debug('phrase_list->time_lst_old -> ('.zu_lst_dsp($result).')');
        return $result;
    }

    /**
     * get all words of this phrase list that have at least one time term
     * TODO use a phrase list instead of a word list because the same word can be of type time and id
     * @return word_list the list object of the time words (not the time phrases!)
     */
    function time_lst(): word_list
    {
        log_debug('phrase_list->time_lst for phrases ' . $this->dsp_id());

        $wrd_lst = $this->wrd_lst_all();
        $result = $wrd_lst->time_lst();
        $result->usr = $this->usr;
        return $result;
    }

    /**
     * @return phrase with the most useful time phrase
     */
    function time_useful(): ?phrase
    {
        log_debug('phrase_list->time_useful for ' . $this->dsp_name());

        $result = null;

        $wrd_lst = $this->wrd_lst_all();
        $time_wrds = $wrd_lst->time_lst();
        log_debug('phrase_list->time_useful times ');
        log_debug('phrase_list->time_useful times ' . implode(",", $time_wrds->ids()));
        foreach ($time_wrds->ids() as $time_id) {
            if (is_null($result)) {
                $time_wrd = new word($this->usr);
                $time_wrd->id = $time_id;
                $time_wrd->load();
                // return a phrase not a word because "Q1" can be also a wikidata Qualifier and to differentiate this, "Q1 (Quarter)" should be returned
                $result = $time_wrd->phrase();
            } else {
                log_warning("The word list contains more time word than supported by the program.", "phrase_list->time_useful");
            }
        }
        //$result = zu_lst_to_flat_lst($phrase_lst);
        //$result = clone $this;
        //$result->wlsort();
        //$result = $phrase_lst;
        //asort($result);
        // sort
        //print_r($phrase_lst);

        // get the most often time type e.g. years if the list contains more than 5 years
        //$type_most_used = zut_time_type_most_used ($phrase_lst);

        // if nothing special is defined try to select 20 % outlook to the future
        // get the latest time without estimate
        // check the number of none estimate results
        // if the hist is longer than it should be, define the start phrase
        // fill from the start phrase the default number of phrases


        //zu_debug('phrase_list->time_useful -> '.$result->name());
        return $result;
    }

    /**
     * get the most useful time for the given words
     * TODO: review
     * @return phrase with the most useful time phrase
     */
    function assume_time(): ?phrase
    {
        $time_phr = null;
        $wrd_lst = $this->wrd_lst_all();
        $time_wrd = $wrd_lst->assume_time();
        if (isset($time_wrd)) {
            $time_phr = $time_wrd;
        }
        return $time_phr;
    }

    /**
     * filter the measure phrases out of the list of phrases
     * @return phrase_list with the measure phrases
     */
    function measure_lst(): phrase_list
    {
        log_debug('phrase_list->measure_lst(' . $this->dsp_id());

        $result = new phrase_list($this->usr);
        $measure_type = cl(db_cl::WORD_TYPE, word_type_list::DBL_MEASURE);
        // loop over the phrase ids and add only the time ids to the result array
        foreach ($this->lst as $phr) {
            if (get_class($phr) <> phrase::class and get_class($phr) <> word::class and get_class($phr) <> word_dsp::class) {
                log_warning('The phrase list contains ' . $this->dsp_id() . ' of type ' . get_class($phr) . ', which is not supposed to be in the list.', 'phrase_list->measure_lst');
                log_debug('phrase_list->measure_lst contains object ' . get_class($phr) . ', which is not a phrase');
            } else {
                if ($phr->type_id() == $measure_type) {
                    $result->add($phr);
                    log_debug('phrase_list->measure_lst -> found (' . $phr->name . ')');
                } else {
                    log_debug('phrase_list->measure_lst -> ' . $phr->name . ' has type id ' . $phr->type_id() . ', which is not the measure type id ' . $measure_type);
                }
            }
        }
        log_debug('phrase_list->measure_lst -> (' . dsp_count($result->lst) . ')');
        return $result;
    }

    /**
     * filter the scaling phrases out of the list of phrases
     * @return phrase_list with the scaling phrases
     */
    function scaling_lst(): phrase_list
    {
        log_debug('phrase_list->scaling_lst(' . $this->dsp_id());

        $result = new phrase_list($this->usr);
        $scale_type = cl(db_cl::WORD_TYPE, word_type_list::DBL_SCALING);
        $scale_hidden_type = cl(db_cl::WORD_TYPE, word_type_list::DBL_SCALING_HIDDEN);
        // loop over the phrase ids and add only the time ids to the result array
        foreach ($this->lst as $phr) {
            if ($phr->type_id() == $scale_type or $phr->type_id() == $scale_hidden_type) {
                $result->add($phr);
                log_debug('phrase_list->scaling_lst -> found (' . $phr->name . ')');
            } else {
                log_debug('phrase_list->scaling_lst -> not found (' . $phr->name . ')');
            }
        }
        log_debug('phrase_list->scaling_lst -> (' . dsp_count($result->lst) . ')');
        return $result;
    }

    /**
     * Exclude all time phrases out of the list of phrases
     */
    function ex_time()
    {
        log_debug('phrase_list->ex_time ' . $this->dsp_id());
        $del_wrd_lst = $this->time_lst();
        $del_phr_lst = $del_wrd_lst->phrase_lst();
        $this->diff($del_phr_lst);
        //$this->diff_by_ids($del_phr_lst->ids);
        log_debug('phrase_list->ex_time ' . $this->dsp_name() . ' (exclude times ' . $del_phr_lst->name() . ')');
    }

    /**
     * Exclude all measure phrases out of the list of phrases
     */
    function ex_measure()
    {
        $del_phr_lst = $this->measure_lst();
        $this->diff($del_phr_lst);
        log_debug('phrase_list->ex_measure ' . $this->dsp_name() . ' (exclude measure ' . $del_phr_lst->dsp_name() . ')');
    }

    /**
     * Exclude all scaling phrases out of the list of phrases
     */
    function ex_scaling()
    {
        $del_phr_lst = $this->scaling_lst();
        $this->diff($del_phr_lst);
        log_debug('phrase_list->ex_scaling ' . $this->dsp_name() . ' (exclude scaling ' . $del_phr_lst->dsp_name() . ')');
    }

    /**
     * sort the phrase object list by name
     * @return array list with the phrases (not a phrase list object!) sorted by name
     */
    function name_sort(): array
    {
        log_debug('phrase_list->wlsort ' . $this->dsp_id() . ' and user ' . $this->usr->name);
        $name_lst = array();
        $result = array();
        $pos = 0;
        foreach ($this->lst as $phr) {
            $name_lst[$pos] = $phr->name;
            $pos++;
        }
        asort($name_lst);
        log_debug('phrase_list->wlsort names sorted "' . implode('","', $name_lst) . '" (' . dsp_array(array_keys($name_lst)) . ')');
        foreach (array_keys($name_lst) as $sorted_id) {
            log_debug('phrase_list->wlsort get ' . $sorted_id);
            $phr_to_add = $this->lst[$sorted_id];
            log_debug('phrase_list->wlsort got ' . $phr_to_add->name);
            $result[] = $phr_to_add;
        }
        // check
        if (count($this->lst) <> count($result)) {
            log_err("Sorting changed the number of phrases from " . dsp_count($this->lst) . " to " . dsp_count($result) . ".", "phrase_list->wlsort");
        } else {
            $this->lst = $result;
            $this->id_lst();
        }
        log_debug('phrase_list->wlsort sorted ' . $this->dsp_id());
        return $result;
    }

    /**
     * get the last time phrase of the phrase list
     * @return phrase with the last phrase of the type time
     */
    function max_time(): phrase
    {
        log_debug('phrase_list->max_time (' . $this->dsp_id() . ' and user ' . $this->usr->name . ')');
        $max_phr = new phrase($this->usr);
        if (count($this->lst) > 0) {
            foreach ($this->lst as $phr) {
                // to be replaced by "is following"
                if ($phr->name > $max_phr->name) {
                    log_debug('phrase_list->max_time -> select (' . $phr->name . ' instead of ' . $max_phr->name . ')');
                    $max_phr = clone $phr;
                }
            }
        }
        return $max_phr;
    }

    /**
     * get the best matching phrase group (but don't create a new group)
     * @return phrase_group|null the best matching phrase group or null if no group matches
     */
    function get_grp(): ?phrase_group
    {
        log_debug('phrase_list->get_grp ' . $this->dsp_id());
        $grp = null;

        // get or create the group
        if (count($this->id_lst()) <= 0) {
            log_err('Cannot create phrase group for an empty list.', 'phrase_list->get_grp');
        } else {
            $grp = new phrase_group($this->usr);
            $grp->phr_lst = $this;
            $grp->get();
        }

        log_debug('phrase_list->get_grp -> ' . $this->dsp_id());
        return $grp;
    }

    /**
     * @return array all phrases that are part of each phrase group of the list
     */
    function common(phrase_list $filter_lst): array
    {
        $result = array();
        log_debug('phrase_list->common of ' . $this->dsp_name() . ' and ' . $filter_lst->name());
        if (count($this->lst) > 0) {
            foreach ($this->lst as $phr) {
                if (isset($phr)) {
                    log_debug('phrase_list->common check if "' . $phr->name . '" is in ' . $filter_lst->name());
                    if (in_array($phr, $filter_lst->lst)) {
                        $result[] = $phr;
                    }
                }
            }
            $this->lst = $result;
            $this->id_lst();
        }
        log_debug('phrase_list->common (' . dsp_count($this->lst) . ')');
        return $result;
    }

    /**
     * @return phrase_list the combined list of this list and the given list without changing this phrase list
     */
    function concat_unique($join_phr_lst): phrase_list
    {
        log_debug('phrase_list->concat_unique');
        $result = clone $this;
        foreach ($join_phr_lst->lst as $phr) {
            if (!in_array($phr, $result->lst)) {
                $result->lst[] = $phr;
            }
        }
        log_debug('phrase_list->concat_unique (' . dsp_count($result->lst) . ')');
        return $result;
    }

    /*
     * data request function
     */

    /**
     * @return value_list all values related to this phrase list
     */
    function val_lst(): value_list
    {
        $val_lst = new value_list($this->usr);
        $val_lst->phr_lst = $this;
        $val_lst->load_all();

        return $val_lst;
    }

    /**
     * @return formula_list all formulas related to this phrase list
     */
    function frm_lst(): formula_list
    {
        $frm_lst = new formula_list($this->usr);
        $frm_lst->load_by_phr_lst($this);

        return $frm_lst;
    }


    /**
     * get the best matching value or value list for this phrase list
     * e.g. if for "ABB", "Sales" no direct number is found,
     *   1) try to get a formula result, if also no formula result,
     *   2) assume an additional phrase by getting the phrase with the most values for the phrase list
     *      which could be in this case "millions"
     *   3) repeat with 2)
     *
     * e.g. if many numbers matches the phrase list e.g. Nestlé Sales million, CHF (and Water, and Coffee)
     *      the value with the least additional phrases is selected
     *
     * @return value the best matching value
     */
    function value(): value
    {
        $val = new value($this->usr);
        $val->grp = $this->get_grp();
        $val->load();

        return $val;
    }

    /**
     * @return value the best matching value scaled to one
     */
    function value_scaled(): value
    {
        $val = $this->value();
        $wrd_lst = $this->wrd_lst_all();
        $val->number = $val->scale($wrd_lst);

        return $val;
    }

    /**
     * TODO this should create a value matrix
     * @return array with all value related to this phrase list as a matrix
     */
    function val_matrix($col_lst): array
    {
        if ($col_lst != null) {
            log_debug('word_list->val_matrix for ' . $this->dsp_id() . ' with ' . $col_lst->dsp_id());
        } else {
            log_debug('word_list->val_matrix for ' . $this->dsp_id());
        }
        return array();
    }

    /*
     * database function
     */

    /**
     * save all changes of the phrase list to the database
     * TODO speed up by creation one SQL statement
     *
     * @return void
     */
    function save(): string
    {
        $result = '';
        foreach ($this->lst as $phr) {
            $result .= $phr->save();
        }
        return $result;
    }
}

/**
 * helper class to make sure that a triple id list is never mixed with a phrase id list
 */
class phr_ids
{
    public ?array $lst = null;

    function __construct(array $ids)
    {
        $this->lst = $ids;
    }

    function count(): int
    {
        return (count($this->lst));
    }
}