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

class FunctionExpression extends AbstractExpression
{
    const TYPE = 'function';

    /**
     * @Groups({"public"})
     * @SWG\Parameter(
     *     type="string",
     *     enum={"function"}
     * )
     */
    protected $type;

    /**
     * @Groups({"public"})
     */
    protected $func;

    /**
     * @Groups({"public"})
     */
    protected $args;

    /**
     * Constructor
     *
     * @param Humanizer $humanizer
     * @param string $func
     */
    public function __construct(Humanizer $humanizer, string $func)
    {
        parent::__construct($this::TYPE, $humanizer);
        $this->setFunc($func);
    }

    /**
     * Sets the func for this expression
     *
     * @param $func
     */
    public function setFunc(string $func): void
    {
        $this->func = $func;
    }

    /**
     * Gets the func for this expression
     *
     * @return mixed
     */
    public function getFunc(): string
    {
        return $this->func;
    }


    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="sumSeries"
     * )
     */
    public function getHumanName(): string
    {
        return $this->getFunc();
    }

    /**
     * Sets the args for this function
     *
     * @param AbstractExpression[] $args
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    /**
     * Add an argument to this function
     *
     * @param AbstractExpression $arg
     */
    public function addArg(AbstractExpression $arg): void
    {
        if (!isset($this->args)) {
            $this->args = [];
        }
        $this->args[] = $arg;
    }

    /**
     * Gets the args for this function
     *
     * @return AbstractExpression[]
     */
    public function getArgs(): array
    {
        if (!isset($this->args)) {
            $this->args = [];
        }
        return $this->args;
    }

    public function getAllByType(string $type): array
    {
        $res = [];
        if ($type == $this::TYPE) {
            $res[] = $this;
        }
        foreach ($this->getArgs() as $arg) {
            /* @var AbstractExpression $arg */
            $res = array_merge($res, $arg->getAllByType($type));
        }
        return $res;
    }

    public function getCanonicalHumanized(?AbstractExpression $excludeRoot = null,
                                          ?AbstractExpression $excludeLeaf = null): ?string
    {
        // strip the root expression off this expression before using
        if ($excludeRoot) {
            $cRoot = $this->getCommonRoot($excludeRoot);
            if ($cRoot) {
                // same func name, same num args, some may be null or diff
                /* @var FunctionExpression $cRoot */
                $commonArgs = [];
                $cRootArgs = $cRoot->getArgs();
                foreach ($this->getArgs() as $i => $arg) {
                    /* @var AbstractExpression $arg */
                    $exclRoot = ($i < count($cRootArgs)) ? $cRootArgs[$i] : null;
                    $cArgName = $arg->getCanonicalHumanized($exclRoot, $excludeLeaf);
                    if ($cArgName) {
                        $commonArgs[] = $cArgName;
                    }
                }
                return implode(', ', $commonArgs);
            }
        }
        $argCanonNames = [];
        foreach ($this->getArgs() as $arg) {
            /* @var AbstractExpression $arg */
            if ($arg !== null) {
                $argCanonNames[] = $arg->getCanonicalHumanized(null, $excludeLeaf);
            }
        }
        return $this->getFunc() . '(' . implode(', ', $argCanonNames) . ')';
    }

    public function getCanonicalStr(): string
    {
        $str = $this->getFunc() . "(";
        $args = $this->getArgs();
        $argc = count($args);
        for ($i = 0; $i < $argc; $i++) {
            $str .= $args[$i]->getCanonicalStr();
            if ($i < $argc - 1) {
                $str .= ',';
            }
        }
        $str .= ')';
        return $str;
    }

    public function getCommonRoot(?AbstractExpression $that): ?AbstractExpression
    {
        if (!$that) {
            return null;
        }
        if (self::TYPE == $that->getType()) {
            /* @var FunctionExpression $that */
            if ($this->getFunc() == $that->getFunc() &&
                count($this->getArgs()) == count($that->getArgs())
            ) {
                $common = new FunctionExpression($this->humanizer, $this->getFunc());
                $thisArgs = $this->getArgs();
                $thatArgs = $that->getArgs();
                foreach ($thisArgs as $i => $arg) {
                    /* @var AbstractExpression $arg */
                    if ($arg == null) {
                        $common->addArg(null);
                    } else {
                        $common->addArg($arg->getCommonRoot($thatArgs[$i]));
                    }
                }
                return $common;
            }
        }
        return null;
    }

    public function getCommonLeaf(?AbstractExpression $that): ?AbstractExpression
    {
        // collect all the leaves of this function and find what is in common
        // between them and $that
        $common = $that;
        foreach ($this->getAllByType(PathExpression::TYPE) as $leafExp) {
            // update $common with what is in common with $leafExp
            $common = $common->getCommonLeaf($leafExp);
            // if there was nothing in common, then there can be nothing in
            // common. stop looking.
            if (!$common) {
                return null;
            }
        }
        foreach ($this->getAllByType(ConstantExpression::TYPE) as $leafExp) {
            // update $common with what is in common with $leafExp
            $common = $common->getCommonLeaf($leafExp);
            // if there was nothing in common, then there can be nothing in
            // common. stop looking.
            if (!$common) {
                return null;
            }
        }
        return $common;
    }

    public function applyPathWhitelist(array $whitelist): void
    {
        foreach ($this->getArgs() as $arg) {
            $arg->applyPathWhitelist($whitelist);
        }
    }

    public static function createFromJson(ExpressionFactory $expFactory,
                                          array $json): ?AbstractExpression
    {
        AbstractExpression::checkJsonAttributes("Function", ['func', 'args'],
                                                $json);
        // TODO: validate function name/args against the Registry
        $expression = new FunctionExpression($expFactory->getHumanizer(),
                                             $json['func']);
        if (!is_array($json['args'])) {
            throw new ParsingException("Function expression 'args' parameter must be an array");
        }
        foreach ($json['args'] as $arg) {
            try {
                $expression->addArg($expFactory->createFromJson($arg));
            } catch (ParsingException $ex) {
                throw new ParsingException("Argument parsing failed for '".$json['func']."' function: " . $ex->getMessage());
            }
        }
        return $expression;
    }

    public static function createFromCanonical(ExpressionFactory $expFactory,
                                               string $expStr): ?AbstractExpression
    {
        $firstCh = substr($expStr, 0, 1);
        $lastCh = substr($expStr, -1, 1);
        if ($lastCh != ')' ||
            substr_count($expStr, '(') !=
            substr_count($expStr, ')')
        ) {
            throw new ParsingException("Malformed function: '$expStr'");
        }
        if ($firstCh == '(') {
            throw new ParsingException("Missing function name: '$expStr'");
        }
        $pos = strpos($expStr, '(');
        $funcName = substr($expStr, 0, $pos);
        $expression = new FunctionExpression($expFactory->getHumanizer(),
                                             $funcName);
        /* extract arguments as comma-separated string */
        $args = preg_replace('/^.+?\((.*)\)$/', '$1', $expStr);
        $argsExps = $expFactory->createFromCanonical($args);
        if (!is_array($argsExps)) {
            $argsExps = [$argsExps];
        }
        $expression->setArgs($argsExps);
        return $expression;
    }
}
