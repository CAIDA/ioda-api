<?php

namespace App\Expression;


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
     * @SWG\Parameter(
     *     type="string",
     *     enum={"path"}
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
     * Array of humanized nodes that make up the path
     *
     * @var string[]
     */
    protected $humanNodes;

    /**
     * PathExpression constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        /* TODO: implement the humanizer */
        parent::__construct($this::TYPE);
        $this->setPath($path);
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
        $this->humanNodes = $this->pathNodes; // TODO: $this->humanizer->humanizeFqid($this->pathNodes, true);
    }

    public function getPathNodes()
    {
        return $this->pathNodes;
    }

    public function getHumanNodes()
    {
        return $this->humanNodes;
    }

    public function getCanonicalStr(): string
    {
        return $this->getPath();
    }

    public function getCanonicalHumanized(?AbstractExpression $excludeRoot = null,
                                   ?AbstractExpression $excludeLeaf = null): string
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
            return new PathExpression(implode($this::SEPARATOR, $commonPathNodes));
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
            return new PathExpression($this->getPath());
        }
        if (self::TYPE != $that->getType()) {
            return null;
        }
        /* @var PathExpression $that */
        return $this->getCommonPath($this->getPathNodes(),
                                    $that->getPathNodes(),
                                    true);
    }

    public static function createFromJson(ExpressionFactory $expFactory,
                                          array $json): ?AbstractExpression
    {
        AbstractExpression::checkJsonAttributes("Path", ['path'], $json);
        return new PathExpression($json['path']);
    }

    public static function createFromCanonical(ExpressionFactory $expFactory,
                                               string $expStr): ?AbstractExpression
    {
        // TODO: check for illegal characters in path
        return new PathExpression($expStr);
    }
}
