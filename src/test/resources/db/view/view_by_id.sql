SELECT
    s.view_id,
    u.view_id AS user_view_id,
    s.user_id,
    s.code_id,
    CASE WHEN (u.view_name <> '' IS NOT TRUE) THEN s.view_name    ELSE u.view_name    END AS view_name,
    CASE WHEN (u.comment <> ''   IS NOT TRUE) THEN s.comment      ELSE u.comment      END AS comment,
    CASE WHEN (u.view_type_id    IS     NULL) THEN s.view_type_id ELSE u.view_type_id END AS view_type_id,
    CASE WHEN (u.excluded        IS     NULL) THEN s.excluded     ELSE u.excluded     END AS excluded
FROM views s LEFT JOIN user_views u ON s.view_id = u.view_id
    AND u.user_id = 1
WHERE s.view_id = 2;