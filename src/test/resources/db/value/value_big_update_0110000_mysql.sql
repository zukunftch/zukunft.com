PREPARE value_big_update_0110000 FROM
    'UPDATE values_big
        SET numeric_value = ?,last_update = Now()
      WHERE group_id = ?';