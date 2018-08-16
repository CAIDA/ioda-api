<?php

namespace App\Expression;


use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class AbstractExpression
 * @package App\Expression
 */
abstract class AbstractExpression
{
    /**
     * @Groups({"public"})
     */
    protected $type;

    public function __construct(string $type)
    {
        $this->setType($type);
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the full canonical name for this expression
     *
     * This is a human-readable version of the canonical string representation
     * of the expression
     *
     * If $excludeRoot and/or $excludeLeaf are set, the returned string will
     * be the portion of this expression not shared with the root and/or leaf
     *
     * @param AbstractExpression $excludeRoot
     * @param AbstractExpression $excludeLeaf
     *
     * @return string
     */
    abstract public function getCanonicalHumanized(?AbstractExpression $excludeRoot = null,
                                                   ?AbstractExpression $excludeLeaf = null): string;

    /**
     * Flatten the expression into the canonical "graphite" representation
     *
     * @return string
     */
    abstract public function getCanonicalStr(): string;

    /**
     * Get an array of all of the sub-expressions of the given type
     *
     * If the type of this expression matches the type, it will be included in the set.
     * This method should be overridden called recursively by expressions that have sub expressions, and the results merged
     *
     * @param string $type
     *
     * @return AbstractExpression[]
     */
    public function getAllByType(string $type): array
    {
        return ($type == $this->type) ? [$this] : [];
    }

    /**
     * Get the expression that is common between this expression and $that.
     * May return null if there is nothing in common. If $that is null, there
     * is deemed to be nothing in common and null is returned.
     *
     * @param AbstractExpression|null $that
     *
     * @return AbstractExpression|null
     */
    abstract public function getCommonRoot(?AbstractExpression $that): ?AbstractExpression;

    /**
     * Get the expression leaf that is common between this expression and $that.
     * May return null if there is nothing in common.
     * If non-null, the response is guaranteed to be a single (partial)
     * PathExpression, or a ConstantExpression (i.e. an expression without any
     * children)
     * If $that contains more than one leaf expression, there will only be a
     * common leaf if all of its leaves share a common expression
     * If $that is null, then the common leaf of $this is returned
     *
     * @param AbstractExpression|null $that
     *
     * @return AbstractExpression|null
     */
    abstract public function getCommonLeaf(?AbstractExpression $that): ?AbstractExpression;
}
