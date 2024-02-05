PREPARE result_list_prime_big_by_frm (bigint, bigint) AS
    SELECT '' AS group_id,
           '' AS user_group_id,
           phrase_id_1,
           phrase_id_2,
           phrase_id_3,
           phrase_id_4,
           0 AS user_id,
           0 AS formula_id,
           0 AS source_group_id,
           numeric_value,
           now() AS last_update,
           0 AS excluded,
           0 AS protect_id,
           0 AS change_user_id,
           0 AS share_type_id
      FROM results_standard_prime
     WHERE formula_id = $1
UNION
    SELECT group_id,
           '' AS user_group_id,
           0 AS phrase_id_1,
           0 AS phrase_id_2,
           0 AS phrase_id_3,
           0 AS phrase_id_4,
           0 AS user_id,
           0 AS formula_id,
           0 AS source_group_id,
           numeric_value,
           now() AS last_update,
           0 AS excluded,
           0 AS protect_id,
           0 AS change_user_id,
           0 AS share_type_id
      FROM results_standard
     WHERE formula_id = $1
UNION
    SELECT s.group_id,
           u.group_id AS user_group_id,
           0 AS phrase_id_1,
           0 AS phrase_id_2,
           0 AS phrase_id_3,
           0 AS phrase_id_4,
           s.user_id,
           s.formula_id,
           s.source_group_id,
           CASE WHEN (u.numeric_value IS NULL) THEN s.numeric_value ELSE u.numeric_value END AS numeric_value,
           CASE WHEN (u.last_update   IS NULL) THEN s.last_update   ELSE u.last_update   END AS last_update,
           CASE WHEN (u.excluded      IS NULL) THEN s.excluded      ELSE u.excluded      END AS excluded,
           CASE WHEN (u.protect_id    IS NULL) THEN s.protect_id    ELSE u.protect_id    END AS protect_id,
           u.user_id AS change_user_id,
           u.share_type_id
      FROM results s
 LEFT JOIN user_results u ON s.group_id = u.group_id AND u.user_id = $1
     WHERE s.formula_id = $2
UNION
    SELECT '' AS group_id,
           '' AS user_group_id,
           s.phrase_id_1,
           s.phrase_id_2,
           s.phrase_id_3,
           s.phrase_id_4,
           s.user_id,
           s.formula_id,
           s.source_group_id,
           CASE WHEN (u.numeric_value IS NULL) THEN s.numeric_value ELSE u.numeric_value END AS numeric_value,
           CASE WHEN (u.last_update   IS NULL) THEN s.last_update   ELSE u.last_update   END AS last_update,
           CASE WHEN (u.excluded      IS NULL) THEN s.excluded      ELSE u.excluded      END AS excluded,
           CASE WHEN (u.protect_id    IS NULL) THEN s.protect_id    ELSE u.protect_id    END AS protect_id,
           u.user_id AS change_user_id,
           u.share_type_id
      FROM results_prime s
 LEFT JOIN user_results_prime u ON s.phrase_id_1 = u.phrase_id_1
                               AND s.phrase_id_2 = u.phrase_id_2
                               AND s.phrase_id_3 = u.phrase_id_3
                               AND s.phrase_id_4 = u.phrase_id_4
                               AND u.user_id = $1
    WHERE s.formula_id = $2
    UNION
    SELECT s.group_id,
           u.group_id AS user_group_id,
           0 AS phrase_id_1,
           0 AS phrase_id_2,
           0 AS phrase_id_3,
           0 AS phrase_id_4,
           s.user_id,
           s.formula_id,
           s.source_group_id,
           CASE WHEN (u.numeric_value IS NULL) THEN s.numeric_value ELSE u.numeric_value END AS numeric_value,
           CASE WHEN (u.last_update   IS NULL) THEN s.last_update   ELSE u.last_update   END AS last_update,
           CASE WHEN (u.excluded      IS NULL) THEN s.excluded      ELSE u.excluded      END AS excluded,
           CASE WHEN (u.protect_id    IS NULL) THEN s.protect_id    ELSE u.protect_id    END AS protect_id,
           u.user_id AS change_user_id,
           u.share_type_id
      FROM results_big s
 LEFT JOIN user_results_big u ON s.group_id = u.group_id AND u.user_id = $1
    WHERE s.formula_id = $2;