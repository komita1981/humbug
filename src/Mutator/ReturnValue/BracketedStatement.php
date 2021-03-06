<?php
/**
 * Humbug
 *
 * @category   Humbug
 * @package    Humbug
 * @copyright  Copyright (c) 2015 Pádraic Brady (http://blog.astrumfutura.com)
 * @license    https://github.com/padraic/humbug/blob/master/LICENSE New BSD License
 */

namespace Humbug\Mutator\ReturnValue;

use Humbug\Mutator\MutatorAbstract;
use Humbug\Utility\Tokenizer;

class BracketedStatement extends MutatorAbstract
{

    /**
     * Replace any return statement contained in brackets with null (but retain
     * the statement and move to before the return call).
     * This isn't perfect - the statement might evaluate to null anyway.
     *
     * @param array $tokens
     * @param int $index
     * @return array
     */
    public static function getMutation(array &$tokens, $index)
    {
        $replace = [];
        $last = null;
        $tokenCount = count($tokens);
        for ($i=$index+1; $i < $tokenCount; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] == T_WHITESPACE) {
                continue;
            } elseif (!is_array($tokens[$i]) && $tokens[$i] == '(') {
                // collect statement tokens (skipping one whitespace after 'return')
                for ($j=$index+2; $j < $tokenCount; $j++) {
                    if (!is_array($tokens[$j]) && $tokens[$j] == ';') {
                        $last = $j - 1;
                        break;
                    }
                    $replace[$j] = $tokens[$j];
                }
                // replace them all with blanks and set last to 'null'
                foreach ($replace as $k => $t) {
                    if ($k == $last) {
                        $tokens[$k] = [
                            T_STRING,
                            'null'
                        ];
                    } else {
                        $tokens[$k] = '';
                    }
                }
                // shift the instantiation prior to the return statement to
                // preserve instantiation behaviour without overwriting anything
                // and without upsetting line count.
                $replace[] = ';';
                $replace[] = [T_WHITESPACE, ' '];
                $string = ['-1' => Tokenizer::reconstructFromTokens($replace)];
                array_splice($tokens, $index, 0, $string);
                break;
            }
        }
    }

    public static function mutates(array &$tokens, $index)
    {
        $t = $tokens[$index];
        if (is_array($t) && $t[0] == T_RETURN) {
            if (self::getNextToken($tokens, $index, [T_WHITESPACE]) === '(') {
                return true;
            }
        }
        return false;
    }
}
