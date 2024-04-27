DROP FUNCTION IF EXISTS word_update_log_00111000000;
CREATE FUNCTION word_update_log_00111000000
    (_user_id                 bigint,
     _change_action_id        smallint,
     _field_id_word_name      smallint,
     _word_name_old           text,
     _word_name               text,
     _word_id                 bigint,
     _field_id_description    smallint,
     _description_old         text,
     _description             text,
     _field_id_phrase_type_id smallint,
     _phrase_type_id_old      smallint,
     _phrase_type_id          smallint) RETURNS void
BEGIN

    WITH
        change_insert_word_name AS (
            INSERT INTO changes ( user_id, change_action_id, change_field_id,    old_value,     new_value, row_id)
                 SELECT          _user_id,_change_action_id,_field_id_word_name,_word_name_old,_word_name,_word_id),
        change_insert_description
                     AS (
            INSERT INTO changes ( user_id, change_action_id, change_field_id,      old_value,       new_value,   row_id)
                 SELECT          _user_id,_change_action_id,_field_id_description,_description_old,_description,_word_id),
        change_insert_phrase_type_id
                     AS (
            INSERT INTO changes ( user_id, change_action_id, change_field_id,         old_value,          new_value,      row_id)
                 SELECT          _user_id,_change_action_id,_field_id_phrase_type_id,_phrase_type_id_old,_phrase_type_id,_word_id)

    UPDATE words
       SET word_name      = _word_name,
           description    = _description,
           phrase_type_id = _phrase_type_id
     WHERE word_id = _word_id;

END;

SELECT word_update_log_00111000000
       (10::bigint,
        3::smallint,
        10::smallint,
        'Mathematics'::text,
        'System Test Word Renamed'::text,
        10::bigint,
        11::smallint,
        'Mathematics is an area of knowledge that includes the topics of numbers and formulas'::text,
        null::text,
        12::smallint,
        1::smallint,
        null::smallint);