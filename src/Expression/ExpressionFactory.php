<?php

namespace App\Expression;


class ExpressionFactory
{
    protected $expressionClasses = [
        ConstantExpression::TYPE => 'App\Expression\ConstantExpression',
        //FunctionExpression::TYPE => 'App\Expression\FunctionExpression',
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
            throw new ParsingException('Invalid expression type: ' . $json['type']);
        }
        // we hand off the outer object to the appropriate implementation
        // if it has child objects, it will in turn call their createFromJson
        // methods
        return call_user_func([$this->expressionClasses[$json['type']], 'createFromJson'],
                             $this, $json);
    }

    /**
     * Creates an expression object of specified type from the given arguments
     *
     * @param $type
     * @param array $arguments
     *
     * @throws ParsingException|\InvalidArgumentException
     * @return AbstractExpression
     */
    public function createByType(string $type, array $arguments = null)
    {
        if (!array_key_exists($type, $this->expressionClasses)) {
            throw new ParsingException('Invalid expression type: ' .
                                                $type);
        }
        $class = $this->expressionClasses[$type];
        if (!class_exists($class)) {
            throw new \InvalidArgumentException('Invalid expression class: ' . $type);
        }
        if (!isset($arguments)) {
            $arguments = [];
        } elseif (!is_array($arguments)) {
            throw new \InvalidArgumentException('Arguments must be an array, ' .
                                                gettype($arguments) . ' given');
        }

        return new $class(...$arguments);
    }
}
