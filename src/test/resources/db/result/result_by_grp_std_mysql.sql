PREPARE result_by_grp_std FROM
   'SELECT result_id,
           formula_id,
           user_id,
           source_group_id,
           group_id,
           result,
           last_update,
           dirty
      FROM results
     WHERE group_id = ?';