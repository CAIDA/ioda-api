<?php

namespace App\Expression;


use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

class ConstantExpression extends AbstractExpression
{
    const TYPE = 'constant';

    /**
     * @Groups({"public"})
     * @SWG\Parameter(
     *     type="string",
     *     enum={"constant"}
     * )
     */
    protected $type;

    /**
     * @Groups({"public"})
     */
    private $value;

    /**
     * PathExpression constructor.
     * @param mixed $value
     */
    public function __construct($value)
    {
        parent::__construct($this::TYPE);
        $this->setValue($value);
    }

    /* TODO: this function can actually return an integer, but swagger doesn't seem to handle multiple return types */
    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function getCanonicalStr(): string
    {
        $val = $this->getValue();
        if (is_string($val)) {
            return '"' . $val . '"';
        } else {
            return (string)$val;
        }
    }

    public function getCanonicalHumanized(AbstractExpression $excludeRoot = null,
                                          AbstractExpression $excludeLeaf = null): string
    {
        // if either the root or leaf exclusions match us, then return nothing
        if ($excludeRoot && $this->getCommonRoot($excludeRoot) ||
            $excludeLeaf && $this->getCommonLeaf($excludeLeaf)) {
            return null;
        }
        // otherwise return our "canonical" representation as there is no
        // humanizing to be done with a constant
        return $this->getCanonicalStr();
    }

    public function getCommonRoot(?AbstractExpression $that): ?AbstractExpression
    {
        // if there is no "that" expression, then we can't be in common
        // and since we are a leaf expression, the "that" expression must be of
        // the same type to be in common
        if (!$that || self::TYPE != $that->getType()) {
            return null;
        }
        /* @var ConstantExpression $that */
        // and even if it is another constant, it needs to be the same value
        // TODO: see if we can just return $this to save creating a new object
        return ($this->getValue() == $that->getValue()) ?
            new ConstantExpression($this->getValue()) : null;
    }

    public function getCommonLeaf(?AbstractExpression $that): ?AbstractExpression
    {
        // since we are leaf, common root and common leaf calculations are identical
        // if there is no "that", then consider ourselves in common
        // (AK: i'm not sure why this is the case)
        return ($that) ?
            $this->getCommonRoot($that) : new ConstantExpression($this->getValue());
    }

}
