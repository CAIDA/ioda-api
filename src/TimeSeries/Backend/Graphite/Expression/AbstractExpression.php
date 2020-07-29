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
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class AbstractExpression
 * @package App\Expression
 */
abstract class AbstractExpression
{
    /**
     * @Groups({"public"})
     * TODO: separate public group into query/response
     */
    protected $type;

    protected $humanizer;

    public function __construct(string $type, ?Humanizer $humanizer)
    {
        $this->setType($type);
        $this->humanizer = $humanizer;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    abstract public function getHumanName(): string;

    /**
     * @param string $typeName
     * @param string[] $params
     * @param array $json

     * @throws ParsingException
     */
    protected static function checkJsonAttributes(string $typeName,
                                                  array $params, array $json): void
    {
        foreach ($params as $param) {
            if (!array_key_exists($param, $json)) {
                throw new ParsingException(
                    "$typeName expression is missing '$param' attribute");
            }
        }
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
                                                   ?AbstractExpression $excludeLeaf = null): ?string;

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

    /**
     * Wrap all PathExpressions contained in this Expression in a grep function.
     *
     * @param string[] $whitelist
     */
    abstract public function applyPathWhitelist(array $whitelist): void;

    /**
     * Factory method for creating Expression instances from a deserialized
     * JSON object
     *
     * Expression types that have child expression should use the provided
     * factory instance to instantiate appropriate sub-expressions.
     *
     * @param ExpressionFactory $expFactory
     * @param array $json
     * @return AbstractExpression|null
     *@throws ParsingException|InvalidArgumentException
     */
    abstract public static function createFromJson(ExpressionFactory $expFactory,
                                                   array $json): ?AbstractExpression;

    /**
     * Factory method for creating Expression instances from a "canonical"
     * graphite-style expression string
     *
     * Expression types that have child expression should use the provided
     * factory instance to instantiate appropriate sub-expressions.
     *
     * @param ExpressionFactory $expFactory
     * @param string $expStr
     * @return AbstractExpression|null
     *@throws ParsingException|InvalidArgumentException
     */
    abstract public static function createFromCanonical(ExpressionFactory $expFactory,
                                                        string $expStr): ?AbstractExpression;
}
