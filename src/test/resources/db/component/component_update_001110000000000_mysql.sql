PREPARE component_update_001110000000000 FROM
    'UPDATE components
        SET component_name    = ?,
            description       = ?,
            component_type_id = ?
      WHERE component_id = ?';