<?php
/**
 * This software is Copyright (c) 2013 The Regents of the University of
 * California. All Rights Reserved. Permission to copy, modify, and distribute this
 * software and its documentation for academic research and education purposes,
 * without fee, and without a written agreement is hereby granted, provided that
 * the above copyright notice, this paragraph and the following three paragraphs
 * appear in all copies. Permission to make use of this software for other than
 * academic research and education purposes may be obtained by contacting:
 *
 * Office of Innovation and Commercialization
 * 9500 Gilman Drive, Mail Code 0910
 * University of California
 * La Jolla, CA 92093-0910
 * (858) 534-5815
 * invent@ucsd.edu
 *
 * This software program and documentation are copyrighted by The Regents of the
 * University of California. The software program and documentation are supplied
 * "as is", without any accompanying services from The Regents. The Regents does
 * not warrant that the operation of the program will be uninterrupted or
 * error-free. The end-user understands that the program was developed for research
 * purposes and is advised not to rely exclusively on the program for any reason.
 *
 * IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE TO ANY PARTY FOR
 * DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING LOST
 * PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS DOCUMENTATION, EVEN IF
 * THE UNIVERSITY OF CALIFORNIA HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE. THE UNIVERSITY OF CALIFORNIA SPECIFICALLY DISCLAIMS ANY WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE. THE SOFTWARE PROVIDED HEREUNDER IS ON AN "AS
 * IS" BASIS, AND THE UNIVERSITY OF CALIFORNIA HAS NO OBLIGATIONS TO PROVIDE
 * MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
 */

namespace App\TimeSeries\Backend\Graphite\Expression;

use App\TimeSeries\Backend\Graphite\Expression\Humanize\Humanizer;

class ExpressionFactory
{
    private $expressionClasses = [
        ConstantExpression::TYPE => 'App\TimeSeries\Backend\Graphite\Expression\ConstantExpression',
        FunctionExpression::TYPE => 'App\TimeSeries\Backend\Graphite\Expression\FunctionExpression',
        PathExpression::TYPE => 'App\TimeSeries\Backend\Graphite\Expression\PathExpression',
    ];

    private $humanizer;

    public function __construct(Humanizer $humanizer)
    {
        $this->humanizer = $humanizer;
    }

    public function getHumanizer(): ?Humanizer {
        return $this->humanizer;
    }

    /**
     * Create a new instance of this object from a de-serialized JSON object
     *
     * @param $json
     *
     * @throws ParsingException
     * @return AbstractExpression
     */
    public function createFromJson($json): AbstractExpression
    {
        if (!is_array($json)) {
            throw new ParsingException("Expression must be an object");
        }
        // recursively parse the json
        if (!array_key_exists('type', $json)) {
            throw new ParsingException(
                'Missing type parameter');
        }
        if (!array_key_exists($json['type'], $this->expressionClasses)) {
            throw new ParsingException("Invalid expression type: '" . $json['type']."'");
        }
        // we hand off the outer object to the appropriate implementation
        // if it has child objects, it will in turn call their createFromJson
        // methods
        return call_user_func([$this->expressionClasses[$json['type']], 'createFromJson'],
                             $this, $json);
    }

    /**
     * Create a new instance of this object from a "canonical" graphite-style
     * expression string
     *
     * @param string $expStr
     *
     * @throws ParsingException
     * @return AbstractExpression|AbstractExpression[]
     */
    public function createFromCanonical(string $expStr)
    {
        if ($expStr == null || ($expStr = trim($expStr)) == '') {
            return null;
        }
        $chunks = [];
        $carry = '';
        foreach (explode(',', $expStr) as $t) {
            $carry .= ((strlen($carry) ? ',' : '') . $t);
            // Remove quoted parts
            $noQuotesArr = [];
            foreach (explode('"', $carry) as $i => $_) {
                if ($i % 2 == 0) {
                    $noQuotesArr[] = $_;
                }
            }
            $noQuotes = implode('', $noQuotesArr);
            unset($noQuotesArr);
            if (strlen(trim($carry)) &&
                substr_count($noQuotes, '(') == substr_count($noQuotes, ')') &&
                substr_count($carry, '"') % 2 == 0
            ) {
                $chunks[] = $carry;
                $carry = '';
            }
        }
        if (strlen($carry)) {
            throw new ParsingException("Malformed expression: '$carry'");
        }
        if (count($chunks) > 1) { // list of items, so recurs
            /* we have a list of expressions. technically this shouldn't be
               allowed, but it makes things easier for the function parser */
            $exps = [];
            foreach ($chunks as $chunk) {
                $exps[] = $this->createFromCanonical($chunk);
            }
            return $exps;
        }
        $expStr = $chunks[0];
        $firstCh = substr($expStr, 0, 1);
        $lastCh = substr($expStr, -1, 1);
        if ($firstCh == '"' || $lastCh == '"' || is_numeric($expStr)) { // String
            /* still has quote marks */
            $type = ConstantExpression::TYPE;
        } elseif (substr_count($expStr, '(') != 0 ||
                  substr_count($expStr, ')') != 0
        ) { // Function
            $type = FunctionExpression::TYPE;
        } else { // only option left, must be a path
            $type = PathExpression::TYPE;
        }
        $expression = call_user_func([$this->expressionClasses[$type], 'createFromCanonical'],
                                     $this, $expStr);
        return $expression;
    }
}
