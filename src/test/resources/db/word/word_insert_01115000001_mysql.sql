PREPARE word_insert_01115000001 FROM
    'INSERT INTO words (user_id, word_name, description, phrase_type_id, protect_id)
          VALUES       (?, ?, ?, ?, ?)';