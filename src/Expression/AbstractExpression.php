<?php

namespace App\Expression;


use Symfony\Component\Serializer\Annotation\Groups;

abstract class AbstractExpression
{
    /**
     * @Groups({"public"})
     */
    protected $type;

    /**
     * @Groups({"public"})
     */
    protected $name;

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

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public
    function whitelistPaths($whitelist);

    /**
     * Flatten the expression into a string
     *
     * This string *should* be compatible with graphite's target parameter
     *
     * @return string
     */
    abstract public function __toString(): string;

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
    abstract public function getCanonicalName(AbstractExpression $excludeRoot = null,
                                              AbstractExpression $excludeLeaf = null);

    /**
     * Flatten the expression into a string
     *
     * @return string
     */
    public function getCanonicalStr(): string
    {
        return $this->__toString();
    }

    /**
     * Get an array of all of the sub-expressions of the given type
     *
     * If the type of this expression matches the type, it will be included in the set.
     * This method should be called recursively by expressions that have sub expressions, and the results merged
     *
     * @param string $type
     *
     * @return AbstractExpression[]
     */
    abstract public function getAllByType(string $type): array;

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
