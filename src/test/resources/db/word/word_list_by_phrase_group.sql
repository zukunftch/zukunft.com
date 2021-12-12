SELECT
    s.word_id,
    u.word_id AS user_word_id,
    s.user_id,
    s.values,
    CASE WHEN (u.word_name    <> '' IS NOT TRUE) THEN s.word_name    ELSE u.word_name    END AS word_name,
    CASE WHEN (u.plural       <> '' IS NOT TRUE) THEN s.plural       ELSE u.plural       END AS plural,
    CASE WHEN (u.description  <> '' IS NOT TRUE) THEN s.description  ELSE u.description  END AS description,
    CASE WHEN (u.word_type_id IS           NULL) THEN s.word_type_id ELSE u.word_type_id END AS word_type_id,
    CASE WHEN (u.excluded     IS           NULL) THEN s.excluded     ELSE u.excluded     END AS excluded
FROM words s
         LEFT JOIN user_words u ON s.word_id = u.word_id
    AND u.user_id = 1
WHERE s.word_id IN ( SELECT word_id
                     FROM phrase_group_word_links
                     WHERE phrase_group_id = 1)
ORDER BY s.values DESC, word_name;