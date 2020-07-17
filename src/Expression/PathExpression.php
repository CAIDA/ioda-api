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

namespace App\Expression;


use App\TimeSeries\Humanize\Humanizer;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

class PathExpression extends AbstractExpression
{
    const TYPE = 'path';

    // character that separates nodes in the path
    const SEPARATOR = '.';
    // character that separates nodes between which other nodes have been omitted
    const SEPARATOR_SKIP = '.';

    // be careful changing these...
    // because of hax in GeographicAnnotationProvider, ANY character that is
    // present in one of these will be stripped from the ends of dimension names
    const NAME_SEPARATOR = ' · ';
    const NAME_SEPARATOR_SKIP = ' ··· ';//'↛';

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="path"
     * )
     */
    protected $type;

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="dot.separated.metric.path"
     * )
     */
    private $path;

    /**
     * Array of nodes that make up the path
     *
     * @var string[]
     */
    private $pathNodes;

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="Human · Readable · Metric · Path"
     * )
     */
    private $humanName;

    /**
     * Array of humanized nodes that make up the path
     *
     * @var string[]
     */
    protected $humanNodes;

    /**
     * Does this path describe an actual time series metric?
     *
     * @var bool
     * @Groups({"list"})
     */
    protected $leaf;

    /**
     * Number of paths represented by this (wildcard or relative) expression
     *
     * @var integer
     * @Groups({"list"})
     */
    protected $pathCount;

    private $isRelative = false;

    /**
     * PathExpression constructor.
     *
     * @param Humanizer $humanizer
     * @param string $path
     */
    public function __construct(?Humanizer $humanizer, string $path)
    {
        parent::__construct($this::TYPE, $humanizer);
        $this->setPath($path);
        $this->pathCount = 1;
        $this->leaf = false;
    }

    public function setRelative() {
        $this->isRelative = true;
    }

    public static function nodesToString($nodes, $isName)
    {
        if (!count($nodes)) {
            return null;
        }
        $str = '';
        $sep = null;
        $first = true;
        foreach ($nodes as $i => $node) {
            if (!$node) {
                $sep = ($isName) ? PathExpression::NAME_SEPARATOR_SKIP : PathExpression::SEPARATOR_SKIP;
                continue;
            }
            if ($first) {
                $str .= $node;
                $first = false;
            } else {
                $str .= $sep . $node;
            }
            $sep = ($isName) ? PathExpression::NAME_SEPARATOR : PathExpression::SEPARATOR;
        }
        return $str;
    }

    public function getPath(): string
    {
        if (!$this->path) {
            $this->path = $this->nodesToString($this->getPathNodes(), false);
        }
        return $this->path;
    }

    /**
     * Sets the path for this expression
     *
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->pathNodes = explode($this::SEPARATOR, $path);
        $this->path = null; // remove any previously cached string
        $this->humanNodes = $this->humanizer ?
            $this->humanizer->humanizeFqid($this->pathNodes, true) :
            $this->pathNodes;
        $this->humanName = null;
    }

    public function getPathNodes()
    {
        return ($this->isRelative) ? [end($this->pathNodes)] : $this->pathNodes;
    }

    public function getHumanName(): string
    {
        if (!$this->humanName) {
            $this->humanName = $this->nodesToString($this->getHumanNodes(), true);
        }
        return $this->humanName;
    }

    public function getHumanNodes()
    {
        return ($this->isRelative) ? [end($this->humanNodes)] : $this->humanNodes;
    }

    public function isLeaf(): bool
    {
        return $this->leaf;
    }

    public function setLeaf(bool $leaf): void
    {
        $this->leaf = $leaf;
    }

    public function getPathCount(): int
    {
        return $this->pathCount;
    }

    public function setPathCount(int $pathCount): void
    {
        $this->pathCount = $pathCount;
    }

    public function incrementPathCount(): void
    {
        $this->pathCount++;
    }

    public function getCanonicalStr(): string
    {
        return $this->getPath();
    }

    public function getCanonicalHumanized(?AbstractExpression $excludeRoot = null,
                                          ?AbstractExpression $excludeLeaf = null): ?string
    {
        /* TODO: I'm not sure about this logic... need to check */
        $nameNodes = $this->getHumanNodes();
        if ($excludeRoot) {
            $cRoot = $this->getCommonRoot($excludeRoot);
            if ($cRoot) {
                /* @var PathExpression $cRoot */
                $commonPfxNodes = $cRoot->getHumanNodes();
                $nameNodes = [];
                foreach ($this->getHumanNodes() as $i => $thisNode) {
                    if ($i >= count($commonPfxNodes) || $commonPfxNodes[$i] == null) {
                        $nameNodes[] = $thisNode;
                    } else {
                        $nameNodes[] = null;
                    }
                }
                /* TODO: why do we not care about the exclude root in this case? */
                return $this->nodesToString($nameNodes, true);
            }
        }
        if ($excludeLeaf) {
            $cLeaf = $this->getCommonLeaf($excludeLeaf);
            if ($cLeaf) {
                /* @var PathExpression $cLeaf */
                $commonLeafNodes = array_reverse($cLeaf->getHumanNodes());
                $revNameNodes = array_reverse($nameNodes);
                foreach ($commonLeafNodes as $i => $leafNode) {
                    if ($revNameNodes[$i] == $leafNode) {
                        $revNameNodes[$i] = null;
                    }
                }
                $nameNodes = array_reverse($revNameNodes);
            }
        }
        return $this->nodesToString($nameNodes, true);
    }

    private function getCommonPath($nodesA, $nodesB, $reverse = false,
                                   $stopOnMismatch = true)
    {
        if ($reverse) {
            $nodesA = array_reverse($nodesA);
            $nodesB = array_reverse($nodesB);
        }
        $commonPathNodes = [];

        foreach ($nodesA as $i => $thisNode) {
            if ($i < count($nodesB) && $thisNode == $nodesB[$i]) {
                $commonPathNodes[] = $thisNode;
            } else {
                if ($stopOnMismatch) {
                    break;
                } else {
                    $commonPathNodes[] = null;
                }
            }
        }

        if (count($commonPathNodes)) {
            if ($reverse) {
                $commonPathNodes = array_reverse($commonPathNodes);
            }
            return new PathExpression($this->humanizer,
                                      implode($this::SEPARATOR, $commonPathNodes));
        } else {
            return null;
        }
    }

    public function getCommonRoot(?AbstractExpression $that): ?AbstractExpression
    {
        if (!$that || self::TYPE != $that->getType()) {
            return null;
        }
        /* @var PathExpression $that */
        return $this->getCommonPath($this->getPathNodes(),
                                    $that->getPathNodes(),
                                    false, false);
    }

    public function getCommonLeaf(?AbstractExpression $that): ?AbstractExpression
    {
        if (!$that) {
            return new PathExpression($this->humanizer, $this->getPath());
        }
        if (self::TYPE != $that->getType()) {
            return null;
        }
        /* @var PathExpression $that */
        return $this->getCommonPath($this->getPathNodes(),
                                    $that->getPathNodes(),
                                    true);
    }

    public function applyPathWhitelist(array $whitelist): void
    {
        $this->setPath('grep('.$this->getPath().','.implode(',', $whitelist).')');
    }

    /**
     * Generate a list of regexes that can be used as a whitelist for this
     * Path Expression.
     * The special glob wildcard '**' can be used in the last node to allow
     * any subsequent nodes to be matched (whereas '*' only matches one node).
     *
     * @return string[]
     */
    public function generateWhitelist(): array
    {
        // special case for all-data path
        if ($this->getPath() === '**') {
            return ['"^.+$"'];
        }
        $nodes = $this->getPathNodes();
        $wl = [];
        $pwl = null;
        $prevNodes = [];
        foreach ($nodes as $node) {
            $pr = implode('\\.', $prevNodes);
            $recursive = false;
            if ($node === '**') {
                $recursive = true;
                $pr .= '(\\.|$)';
            } else {
                if (count($prevNodes)) {
                    $pr .= '\\.';
                }
                $node = str_replace('*', '[^\.]+', $node);
                $pr .= $node.'$';
            }
            $regex = '"^'.$pr.'"';
            if ($recursive) {
                $wl[] = $regex;
                break;
            } elseif ($pwl) {
                $wl[] = $pwl;
            }
            $pwl = $regex;
            $prevNodes[] = $node;
        }
        return $wl;
    }

    public static function createFromJson(ExpressionFactory $expFactory,
                                          array $json): ?AbstractExpression
    {
        AbstractExpression::checkJsonAttributes("Path", ['path'], $json);
        return new PathExpression($expFactory->getHumanizer(), $json['path']);
    }

    public static function createFromCanonical(ExpressionFactory $expFactory,
                                               string $expStr): ?AbstractExpression
    {
        // TODO: check for illegal characters in path
        return new PathExpression($expFactory->getHumanizer(), $expStr);
    }
}
