CREATE OR REPLACE FUNCTION view_insert_log_011151111
    (_view_name               text,
     _user_id                 bigint,
     _change_action_id        smallint,
     _field_id_view_name      smallint,
     _field_id_user_id        smallint,
     _field_id_description    smallint,
     _description             text,
     _field_id_view_type_id   smallint,
     _type_name               text,
     _view_type_id            smallint,
     _field_id_code_id        smallint,
     _code_id                 text,
     _field_id_excluded       smallint,
     _excluded                smallint,
     _field_id_share_type_id  smallint,
     _share_type_id           smallint,
     _field_id_protect_id     smallint,
     _protect_id              smallint) RETURNS bigint AS
$$
DECLARE new_view_id bigint;
BEGIN

    INSERT INTO views ( view_name)
         SELECT        _view_name
      RETURNING         view_id INTO new_view_id;

    INSERT INTO changes ( user_id, change_action_id, change_field_id,    new_value, row_id)
         SELECT          _user_id,_change_action_id,_field_id_view_name,_view_name, new_view_id ;

    INSERT INTO changes ( user_id, change_action_id, change_field_id,  new_value, row_id)
         SELECT          _user_id,_change_action_id,_field_id_user_id,_user_id,   new_view_id ;

    INSERT INTO changes ( user_id, change_action_id, change_field_id,      new_value,  row_id)
         SELECT          _user_id,_change_action_id,_field_id_description,_description,new_view_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id,       new_value, new_id,      row_id)
         SELECT         _user_id,_change_action_id,_field_id_view_type_id,_type_name,_view_type_id,new_view_id ;

    INSERT INTO changes ( user_id, change_action_id, change_field_id,  new_value, row_id)
         SELECT          _user_id,_change_action_id,_field_id_code_id,_code_id,   new_view_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id,   new_value, row_id)
         SELECT         _user_id,_change_action_id,_field_id_excluded,_excluded,  new_view_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id,        new_value,    row_id)
         SELECT         _user_id,_change_action_id,_field_id_share_type_id,_share_type_id,new_view_id ;

    INSERT INTO changes (user_id, change_action_id, change_field_id,     new_value, row_id)
         SELECT         _user_id,_change_action_id,_field_id_protect_id,_protect_id,new_view_id ;

    UPDATE views
       SET user_id        = _user_id,
           description    = _description,
           view_type_id   = _view_type_id,
           code_id        = _code_id,
           excluded       = _excluded,
           share_type_id  = _share_type_id,
           protect_id     = _protect_id
     WHERE views.view_id = new_view_id;

    RETURN new_view_id;

END
$$ LANGUAGE plpgsql;

PREPARE view_insert_log_011151111_call
    (text, bigint, smallint, smallint, smallint, smallint, text, smallint, text, smallint, smallint, text, smallint, smallint, smallint, smallint, smallint, smallint) AS
SELECT view_insert_log_011151111
    ($1,$2, $3, $4, $5, $6, $7, $8, $9,$10,$11,$12,$13,$14,$15,$16,$17,$18);

SELECT view_insert_log_011151111 (
               'Word'::text,
               1::bigint,
               1::smallint,
               42::smallint,
               278::smallint,
               43::smallint,
               'the default view for words'::text,
               45::smallint,
               'detail_view'::text,
               6::smallint,
               44::smallint,
               'word'::text,
               72::smallint,
               1::smallint,
               131::smallint,
               3::smallint,
               132::smallint,
               2::smallint);