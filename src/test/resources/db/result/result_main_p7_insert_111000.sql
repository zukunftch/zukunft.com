PREPARE result_main_p7_insert_111000 (smallint, smallint, smallint, smallint, smallint, smallint, smallint, smallint, bigint, numeric, bigint) AS
    INSERT INTO results_main
                (formula_id,
                 phrase_id_1,
                 phrase_id_2,
                 phrase_id_3,
                 phrase_id_4,
                 phrase_id_5,
                 phrase_id_6,
                 phrase_id_7,
                 user_id,
                 numeric_value,
                 last_update,
                 source_group_id)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, Now(), $11)
      RETURNING formula_id, phrase_id_1, phrase_id_2, phrase_id_3, phrase_id_4, phrase_id_5, phrase_id_6, phrase_id_7;