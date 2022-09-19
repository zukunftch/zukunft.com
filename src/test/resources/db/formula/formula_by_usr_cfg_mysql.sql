PREPARE formula_by_usr_cfg FROM
   'SELECT formula_id,
           formula_name,
           formula_text,
           resolved_text,
           description,
           formula_type_id,
           all_values_needed,
           last_update,
           excluded,
           share_type_id,
           protect_id
      FROM user_formulas
     WHERE formula_id = ?
       AND user_id = ?';
