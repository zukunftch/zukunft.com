PREPARE triple_update_0022400000000_user FROM
   'UPDATE user_triples
       SET triple_name = ?,
           description = ?,
           phrase_type_id = ?
     WHERE triple_id = ?
       AND user_id = ?';