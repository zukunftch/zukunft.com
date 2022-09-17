PREPARE word_by_not_changed_not_owned (int, int) AS
    SELECT user_id
      FROM user_words
     WHERE word_id = $1
       AND (excluded <> 1 OR excluded is NULL)
       AND user_id <> $2;