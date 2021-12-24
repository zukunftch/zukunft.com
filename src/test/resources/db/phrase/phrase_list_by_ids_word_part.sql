PREPARE phrase_list_by_ids_word_part (int, int, int, int) AS
    SELECT s.word_id,
           u.word_id AS user_word_id,
           s.user_id,
           s.values,
           CASE
               WHEN (u.word_name <> '' IS NOT TRUE)
                   THEN s.word_name
               ELSE u.word_name END                                               AS word_name,
           CASE WHEN (u.plural <> '' IS NOT TRUE) THEN s.plural ELSE u.plural END AS plural,
           CASE
               WHEN (u.description <> '' IS NOT TRUE)
                   THEN s.description
               ELSE u.description END                                             AS description,
           CASE
               WHEN (u.word_type_id IS NULL)
                   THEN s.word_type_id
               ELSE u.word_type_id END                                            AS word_type_id,
           CASE WHEN (u.view_id IS NULL) THEN s.view_id ELSE u.view_id END        AS view_id,
           CASE WHEN (u.excluded IS NULL) THEN s.excluded ELSE u.excluded END     AS excluded,
           CASE
               WHEN (u.share_type_id IS NULL)
                   THEN s.share_type_id
               ELSE u.share_type_id END                                           AS share_type_id,
           CASE
               WHEN (u.protection_type_id IS NULL)
                   THEN s.protection_type_id
               ELSE u.protection_type_id END                                      AS protection_type_id
    FROM words s
             LEFT JOIN user_words u ON s.word_id = u.word_id AND u.user_id = $4
    WHERE word_id IN [$1,$2,$3];