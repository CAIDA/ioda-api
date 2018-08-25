<?php

namespace App\TimeSeries\Backend;


use App\Expression\AbstractExpression;
use App\Expression\PathExpression;
use App\TimeSeries\TimeSeriesSet;
use Swagger\Annotations\Path;

class GraphiteBackend implements BackendInterface
{
    const GRAPHITE_URL = 'http://charthouse-render.int.limbo.caida.org';
    const QUERY_TIMEOUT = 120;
    const META_CACHE_TIMEOUT = 28800; // 8 hrs
    const MAX_POINTS_PER_SERIES = 4000;

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
        // TODO: here
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
        return $paths;
    }

    public function tsQuery(AbstractExpression $expression,
                            \DateTime $from, \DateTime $until,
                            string $aggrFunc): TimeSeriesSet
    {
        // TODO: Implement tsQuery() method.
        // TODO: unlimit
        // TODO: max points per series
    }
}
