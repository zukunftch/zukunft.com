PREPARE word_update_0111000000 FROM
    'UPDATE words
        SET word_name = ?,
            description = ?,
            phrase_type_id = ?
      WHERE word_id = ?';