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
     * @var string
     */
    private $value;

    /**
     * PathExpression constructor.
     * @param Humanizer $humanizer
     * @param mixed $value
     */
    public function __construct(Humanizer $humanizer, $value)
    {
        parent::__construct($this::TYPE, $humanizer);
        $this->setValue($value);
    }

    /**
     * @Groups({"public"})
     */
    public function getHumanName(): string
    {
        return $this->getValue();
    }

    /**
     * @return integer|string
     */
    public function getValue()
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
                                          AbstractExpression $excludeLeaf = null): ?string
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
            new ConstantExpression($this->humanizer, $this->getValue()) : null;
    }

    public function getCommonLeaf(?AbstractExpression $that): ?AbstractExpression
    {
        // since we are leaf, common root and common leaf calculations are identical
        // if there is no "that", then consider ourselves in common
        // (AK: i'm not sure why this is the case)
        return ($that) ?
            $this->getCommonRoot($that) :
            new ConstantExpression($this->humanizer, $this->getValue());
    }

    public function applyPathWhitelist(array $whitelist): void
    {
        // cannot apply path whitelists to a constant
        return;
    }

    public static function createFromJson(ExpressionFactory $expFactory,
                                          array $json): ?AbstractExpression
    {
        AbstractExpression::checkJsonAttributes("Constant", ['value'], $json);
        return new ConstantExpression($expFactory->getHumanizer(),
                                      $json['value']);
    }

    public static function createFromCanonical(ExpressionFactory $expFactory,
                                               string $expStr): ?AbstractExpression
    {
        $valid = 0;
        $quoteCnt = substr_count($expStr, '"');
        if ($quoteCnt == 0 && is_numeric($expStr)) {
            $valid = 1;
            $expStr = (float)$expStr;
        } elseif ($quoteCnt == 2) {
            $firstCh = substr($expStr, 0, 1);
            $lastCh = substr($expStr, -1, 1);
            if ($firstCh != '"' || $lastCh != '"') {
                throw new ParsingException("Malformed string constant: '$expStr'");
            }
            $expStr = str_replace('"', '', $expStr);
            $valid = 1;
        }
        if ($valid) {
            return new ConstantExpression($expFactory->getHumanizer(), $expStr);
        } else {
            throw new ParsingException("Malformed constant: '$expStr'");
        }
    }
}
