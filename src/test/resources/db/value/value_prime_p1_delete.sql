PREPARE value_prime_p1_delete (smallint, smallint, smallint, smallint) AS
    DELETE FROM values_prime
     WHERE phrase_id_1 = $1
       AND phrase_id_2 = $2
       AND phrase_id_3 = $3
       AND phrase_id_4 = $4;