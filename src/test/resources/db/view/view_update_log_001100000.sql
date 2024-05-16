CREATE OR REPLACE FUNCTION view_update_log_001100000
    (_user_id                 bigint,
     _change_action_id        smallint,
     _field_id_view_name      smallint,
     _view_name_old           text,
     _view_name               text,
     _view_id                 bigint,
     _field_id_description    smallint,
     _description_old         text,
     _description             text) RETURNS bigint AS
$$
BEGIN

    INSERT INTO changes ( user_id, change_action_id, change_field_id,    old_value,     new_value, row_id)
         SELECT          _user_id,_change_action_id,_field_id_view_name,_view_name_old,_view_name,_view_id ;
    INSERT INTO changes ( user_id, change_action_id, change_field_id,      old_value,       new_value,   row_id)
         SELECT          _user_id,_change_action_id,_field_id_description,_description_old,_description,_view_id ;

    UPDATE views
       SET view_name      = _view_name,
           description    = _description
     WHERE view_id = _view_id;

END
$$ LANGUAGE plpgsql;

SELECT view_update_log_001100000
       (1::bigint,
        2::smallint,
        42::smallint,
        'Word'::text,
        'System Test View Renamed'::text,
        1::bigint,
        43::smallint,
        'the default view for words'::text,
        null::text);