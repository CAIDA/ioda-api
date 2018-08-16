<?php

namespace App\Expression;


class ExpressionFactory
{
    protected $expressionClasses = [
        ConstantExpression::TYPE => 'App\Expression\ConstantExpression',
        FunctionExpression::TYPE => 'App\Expression\FunctionExpression',
        PathExpression::TYPE => 'App\Expression\PathExpression',
    ];

    public function __construct()
    {
        // TODO: add humanizer?
    }

    /**
     * Create a new instance of this object from a de-serialized JSON object
     *
     * @param $json
     *
     * @throws ParsingException
     * @return AbstractExpression
     */
    public function createFromJson(array $json): AbstractExpression
    {
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
