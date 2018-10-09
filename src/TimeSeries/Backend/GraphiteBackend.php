<?php

namespace App\TimeSeries\Backend;


use App\Expression\AbstractExpression;
use App\Expression\ExpressionFactory;
use App\Expression\ParsingException;
use App\Expression\PathExpression;
use App\TimeSeries\Annotation\AnnotationFactory;
use App\TimeSeries\TimeSeries;
use App\TimeSeries\TimeSeriesSet;
use App\Utils\QueryTime;

class GraphiteBackend extends AbstractBackend
{
    const GRAPHITE_URL = 'http://charthouse-render.int.limbo.caida.org';
    const QUERY_TIMEOUT = 120;
    const META_CACHE_TIMEOUT = 28800; // 8 hrs
    const DATA_CACHE_TIMEOUTS = [
        7200 => 60, // cache last two hours for 1 min
        86400 => 600, // cache last day for 10 min
    ];
    const DATA_CACHE_TIMEOUT_DEFAULT = 3600;
    const MAX_POINTS_PER_SERIES = 4000;
    const MAX_POINTS = 200000;

    const AGGREGATION_FUNCS = [
        'avg',
        'sum',
    ];


    private $expFactory;

    /**
     * Make a query to the graphite backend service.
     *
     * @param string $path
     * @param array $params
     * @return string
     * @throws BackendException
     */
    private function graphiteQuery(string $path, array $params): string
    {
        // TODO: noCache
        $query = http_build_query($params);
        // hax to replace target[0]...target[1]... with []
        $query = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $query);

        $url = GraphiteBackend::GRAPHITE_URL . $path;
        $ch = curl_init($url);
        curl_setopt_array($ch,
                          array(
                              CURLOPT_TIMEOUT => GraphiteBackend::QUERY_TIMEOUT,
                              CURLOPT_RETURNTRANSFER => true,
                              CURLOPT_FAILONERROR => true,
                              CURLOPT_POSTFIELDS => $query,
                          )
        );
        $result = curl_exec($ch);
        if ($result === false || !$result) {
            throw new BackendException(curl_error($ch) . " URL: " . $url . '?' . $query);
        }
        curl_close($ch);
        return $result;
    }

    public function __construct(ExpressionFactory $expFactory)
    {
        $this->expFactory = $expFactory;
    }

    public function pathListQuery(PathExpression $pathExp,
                                  bool $absolute_paths): array
    {
        $result = $this->graphiteQuery(
            '/metrics/find',
            [
                'format' => 'json',
                'query' => $pathExp->getCanonicalStr(),
                'brief' => true,
                'cacheTimeout' => GraphiteBackend::META_CACHE_TIMEOUT,
            ]
        );
        $jsonResult = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BackendException('Invalid JSON from TS backend: ' . json_last_error_msg());
        }
        if (!is_array($jsonResult)) {
            throw new BackendException('Invalid response from TS backend');
        }
        // parse the response and build a tree out of it
        /* @var PathExpression[] */
        $paths = [];
        $pathTree = [];
        foreach ($jsonResult as $node) {
            $np = $node['path'];

            if (array_key_exists($np, $pathTree)) {
                continue;
            } else {
                $pathTree[$np] = true;
            }

            // TODO: need to correctly handle humanization of relative paths
            // TODO: maybe have some way to tell a path expression that it
            // TODO: is relative, so only the last node is used?

            // graphite gives us absolute paths
            if ($absolute_paths) {
                $nodePath = new PathExpression($node['path']);
            } else {
                $pathExp = new PathExpression($np);
                $pn = $pathExp->getPathNodes();
                $nodePath = new PathExpression(end($pn));
            }

            // future-proof so that there can be a leaf and a node with
            // the same name at the same level of the hierarchy
            if ($node['is_leaf']) {
                $key = $nodePath->getCanonicalStr() . '/IS-LEAF-TRUE';
                $nodePath->setLeaf(true);
            } else {
                $key = $nodePath->getCanonicalStr();
            }

            if (array_key_exists($key, $paths)) {
                $paths[$key]->incrementPathCount();
            } else {
                $paths[$key] = $nodePath;
            }
        }
        return array_values($paths);
    }

    public function tsQuery(array $expressions,
                            QueryTime $from, QueryTime $until,
                            string $aggrFunc, bool $annotate): TimeSeriesSet
    {
        // TODO: is unlimit?
        // TODO: is nocache?

        // TODO: carefully check how dbats/graphite/charthouse deal with various functions
        if (!in_array($aggrFunc, GraphiteBackend::AGGREGATION_FUNCS)) {
            throw new BackendException("Invalid aggregation function '$aggrFunc'. ".
                                       "Supported functions are: ".
                                       implode(', ',
                                               GraphiteBackend::AGGREGATION_FUNCS));
        }

        // if until is a relative time, then we want a low cache timeout
        $now = time();
        $timeout = GraphiteBackend::DATA_CACHE_TIMEOUT_DEFAULT;
        if ($until->isRelative()) {
            $t = $now;
        } else {
            $t = $until->getAbsoluteTime()->getTimestamp();
        }
        foreach (GraphiteBackend::DATA_CACHE_TIMEOUTS as $limit => $tout) {
            if ($t >= $now - $limit) {
                // our 'until' time falls inside this limit
                $timeout = $tout;
                break;
            }
        }

        $graphiteExpressions = [];
        foreach ($expressions as $exp) {
            $graphiteExpressions[] = $exp->getCanonicalStr();
        }

        $params = [
            'format' => 'json-internal',
            'target' => $graphiteExpressions,
            'from' => $from->getGraphiteTime(),
            'until' => $until->getGraphiteTime(),
            'cacheTimeout' => $timeout,
            'aggFunc' => $aggrFunc,
        ];
        // TODO check unlimit
        $params['maxDataPoints'] = GraphiteBackend::MAX_POINTS_PER_SERIES;
        // TODO nocache
        $result = $this->graphiteQuery('/render', $params);
        $jsonResult = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BackendException('Invalid JSON from TS backend: ' . json_last_error_msg());
        }
        if (!is_array($jsonResult)) {
            throw new BackendException('Invalid response from TS backend');
        }

        $tss = new TimeSeriesSet();
        $summary = $tss->getSummary();

        // first pass through the response, build expressions and calculate
        // common root/leaves
        /* @var AbstractExpression $commonRoot */
        $commonRoot = null;
        /* @var AbstractExpression $commonLeaf */
        $commonLeaf = null;
        $first = true;
        foreach ($jsonResult as &$element) {
            $exp = null;
            try {
                $exp = $this->expFactory->createFromCanonical($element['name']);
            } catch (ParsingException $ex) {
                // failed to parse the expression, make it a path
                $exp = new PathExpression($element['name']);
            }

            $commonRoot = ($first) ? $exp : $exp->getCommonRoot($commonRoot);
            if ($first) {
                $commonLeaf = $exp;
                $first = false;
            } else {
                if ($commonLeaf) {
                    $commonLeaf = $commonLeaf->getCommonLeaf($exp);
                }
            }

            $newSeries = new TimeSeries($exp);

            $from = new \DateTime();
            $from->setTimestamp((int)$element['start']);
            $newSeries->setFrom($from);

            $until = new \DateTime();
            $until->setTimestamp((int)$element['end']);
            $newSeries->setUntil($until);

            $newSeries->setStep($element['step']);

            $newSeries->setNativeStep(
                array_key_exists('nativeStep', $element) ?
                    $element['nativeStep'] : $element['step']
            );

            $newSeries->setValues($element['values']);

            $tss->addOneSeries($newSeries);
        }
        $summary->setCommonPrefix($commonRoot);
        $summary->setCommonSuffix($commonLeaf);

        // TODO: check unlimit
        // now we should downsample
        $tss->downSample($this::MAX_POINTS, $aggrFunc);

        // take another pass through now that the summary object is ready
        foreach ($tss->getSeries() as &$newSeries) {
            if ($annotate) {
                $newSeries->setAnnotations(
                    AnnotationFactory::annotateExpression($newSeries->getExpression())
                );
            }

            $newSeries->updateContextualName($summary);

            $summary->addStep($newSeries->getStep());
            $summary->addNativeStep($newSeries->getNativeStep(),
                                    $newSeries->getStep());
            $summary->addFrom($newSeries->getFrom());
            $summary->addUntil($newSeries->getUntil());
        }

        return $tss;
    }
}
