<?php

use Random\RandomException;

/**
 * @throws RandomException
 */
function uuidv4(): string {
    $data = random_bytes(16);

    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // setze version auf 0100 (4)
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // setze bits 6-7 auf 10 (variante 1)

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}



/**

 * Porting of PHP 8.4 function (Polyfill from php.net)

 *

 * @template TValue of mixed

 * @template TKey of array-key

 *

 * @param array<TKey, TValue> $array

 * @param callable(TValue $value, TKey $key): bool $callback

 * @return ?TValue

 *

 * @see https://www.php.net/manual/en/function.array-find.php

 */
function array_find(array $array, callable $callback): mixed

{

    foreach ($array as $key => $value) {

        if ($callback($value, $key)) {

            return $value;

        }

    }



    return null;

}