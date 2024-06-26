DROP PROCEDURE IF EXISTS ref_insert_log_0151551151011;
CREATE PROCEDURE  ref_insert_log_0151551151011
    (_phrase_id              bigint,
     _ref_type_id            smallint,
     _external_key           text,
     _user_id                bigint,
     _change_action_id       smallint,
     _change_table_id        smallint,
     _new_text_from          text,
     _new_text_link          text,
     _new_text_to            text,
     _field_id_user_id       smallint,
     _field_id_url           smallint,
     _url                    text,
     _field_id_source_id     smallint,
     _source_name            text,
     _source_id              bigint,
     _field_id_description   smallint,
     _description            text,
     _field_id_share_type_id smallint,
     _share_type_id          smallint,
     _field_id_protect_id    smallint,
     _protect_id             smallint)

BEGIN

    INSERT INTO refs (phrase_id, ref_type_id, external_key)
         SELECT      _phrase_id,_ref_type_id,_external_key ;

    SELECT LAST_INSERT_ID() AS @new_ref_id;

    INSERT INTO change_links (user_id, change_action_id, change_table_id, new_text_from, new_text_link, new_text_to, new_from_id, new_link_id, row_id)
         SELECT              _user_id,_change_action_id,_change_table_id,_new_text_from,_new_text_link,_new_text_to,_phrase_id,  _ref_type_id,@new_ref_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id, new_value, row_id)
         SELECT         _user_id,_change_action_id,_field_id_user_id,_user_id, @new_ref_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id, new_value, row_id)
         SELECT         _user_id,_change_action_id,_field_id_url,   _url,      @new_ref_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id,    new_value,   new_id,    row_id)
         SELECT         _user_id,_change_action_id,_field_id_source_id,_source_name,_source_id,@new_ref_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id,      new_value,   row_id)
         SELECT         _user_id,_change_action_id,_field_id_description,_description,@new_ref_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id,        new_value,      row_id)
         SELECT         _user_id,_change_action_id,_field_id_share_type_id,_share_type_id, @new_ref_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id,     new_value,   row_id)
         SELECT         _user_id,_change_action_id,_field_id_protect_id,_protect_id, @new_ref_id ;

    UPDATE refs
       SET user_id       = _user_id,
           `url`         = _url,
           source_id     = _source_id,
           description   = _description,
           share_type_id = _share_type_id,
           protect_id    = _protect_id
     WHERE refs.ref_id = @new_ref_id;

END;

PREPARE ref_insert_log_0151551151011_call FROM
    'SELECT ref_insert_log_0151551151011 (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

SELECT ref_insert_log_0151551151011 (
               4,
               2,
               'Q167',
               1,
               1,
               22,
               'Pi',
               'wikidata',
               'Q167',
               246,
               66,
               'https://www.wikidata.org/wiki/Special:EntityData/Q167.json',
               67,
               'The International System of Units',
               1,
               65,
               'pi - ratio of the circumference of a circle to its diameter',
               247,
               3,
               248,
               2);