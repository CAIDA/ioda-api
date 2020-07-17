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

namespace App\TimeSeries\Annotation\Provider;


use App\Expression\AbstractExpression;
use App\Expression\PathExpression;
use App\TimeSeries\Annotation\AbstractAnnotation;
use App\TimeSeries\Annotation\GeoJoinAnnotation;
use App\TimeSeries\Annotation\GeoMetaAnnotation;
use App\TimeSeries\TimeSeriesSummary;
use Swagger\Annotations\Path;

class GeoAnnotationProvider extends AbstractAnnotationProvider
{
    const NETACQ_PFX = 'geo.netacuity.';
    const MAXMIND_PFX = 'geo.maxmind.';
    const PROVIDERS = [
        'netacuity' => 'Net Acuity Edge',
        'maxmind' => 'Maxmind GeoLite',
    ];
    const GEO_DBS = [
        'netacuity' =>
            [
                'continent' => [
                    'db' => 'natural-earth',
                    'table' => 'ne_10m_admin_0.continents.v3.1.0',
                    'col' => 'usercode'
                ],
                'country' => [
                    'db' => 'natural-earth',
                    'table' => 'ne_10m_admin_0.countries.v3.1.0',
                    'col' => 'usercode'
                ],
                'region' => [
                    'db' => 'natural-earth',
                    'table' => 'ne_10m_admin_1.regions.v3.0.0',
                    'col' => 'id'
                ],
                'county' => [
                    'db' => 'gadm',
                    'table' => 'gadm.counties.v2.0',
                    'col' => 'id'
                ],
            ],
        'maxmind' =>
            [
                'continent' => [
                    'db' => 'natural-earth',
                    'table' => 'ne_10m_admin_0.continents.v3.1.0',
                    'col' => 'usercode'
                ],
                'country' => [
                    'db' => 'natural-earth',
                    'table' => 'ne_10m_admin_0.countries.v3.1.0',
                    'col' => 'usercode'
                ],
            ]
    ];
    const GEO_LEVELS = [
        'netacuity' =>
            [
                [
                    'level' => 'continent',
                    'pattern' => '/^[A-Z\?][A-Z\?]$/',
                ],
                [
                    'level' => 'country',
                    'pattern' => '/^[A-Z\?][A-Z\?]$/',
                ],
                [
                    'level' => 'region',
                    'pattern' => '/^\d+$/',
                ],
                [
                    'level' => 'county',
                    'pattern' => '/^\d+$/',
                ],
            ],
        'maxmind' =>
            [
                [
                    'level' => 'continent',
                    'pattern' => '/^[A-Z\?][A-Z1-9\?]$/',
                ],
                [
                    'level' => 'country',
                    'pattern' => '/^[A-Z\?][A-Z1-9\?]$/',
                ],
            ],
    ];

    /**
     * @param string $provider
     * @param string $node
     * @param string $nodeName
     * @param string $pattern
     * @param string $level
     * @param GeoJoinAnnotation $lastAnn
     * @param string $geoStr
     * @param array $geoNameArr
     * @param GeoMetaAnnotation $metaAnn
     *
     * @return GeoJoinAnnotation|null
     */
    private function maybeCreateAnnotation(string $provider, string $node,
                                           string $nodeName, string $pattern,
                                           string $level,
                                           ?GeoJoinAnnotation $lastAnn,
                                           string &$geoStr, array &$geoNameArr,
                                           GeoMetaAnnotation $metaAnn)
    {
        $ann = null;
        if (preg_match($pattern, $node)) {
            if ($lastAnn) {
                $lastAnn->setDefault(false);
            }
            $geoStr .= $node;
            $metaAnn->setFQID($geoStr);
            $geoStr .= '.';
            $geoNameArr[] = $nodeName;
            $ann = new GeoJoinAnnotation(
                GeoAnnotationProvider::GEO_DBS[$provider][$level]['db'],
                GeoAnnotationProvider::GEO_DBS[$provider][$level]['table'],
                GeoAnnotationProvider::GEO_DBS[$provider][$level]['col'],
                $node
            );
            $ann->setDimension($geoStr, $geoNameArr);
            $metaAnn->setGeoLevel($level, $node, $nodeName);
            $metaAnn->setNativeLevel($level);
        }
        return $ann;
    }

    /**
     * @param string $provider
     * @param PathExpression $path
     * @return GeoJoinAnnotation[]
     */
    private function getAnnotationsForProvider(string $provider,
                                               PathExpression $path)
    {
        /* @var $anns GeoJoinAnnotation[] */
        $anns = [];
        $nodes = $path->getPathNodes();
        $nameNodes = $path->getHumanNodes();
        $offset = array_search($provider, $nodes);
        if ($offset === false) {
            throw new \InvalidArgumentException();
        }
        $ann = null;
        $geoStr = implode('.', array_slice($nodes, $offset - 1, 2)) . '.';
        $geoNameArr = [];
        $metaAnn = new GeoMetaAnnotation($provider,
                                         GeoAnnotationProvider::PROVIDERS[$provider]);
        foreach (GeoAnnotationProvider::GEO_LEVELS[$provider] as $check) {
            $offset++;
            if ($offset >= count($nodes)) {
                break; // there is no leaf node
            }
            $ann = $this->maybeCreateAnnotation(
                $provider,
                $nodes[$offset],
                $nameNodes[$offset],
                $check['pattern'],
                $check['level'],
                $ann,
                $geoStr, $geoNameArr,
                $metaAnn
            );
            if ($ann) {
                $anns[] = $ann;
            } else {
                break;
            }
        }
        $anns[] = $metaAnn;
        return $anns;
    }

    /**
     * @param PathExpression $path
     * @return GeoJoinAnnotation[]
     */
    private function getAnnotationsForPath(PathExpression $path)
    {
        if (strpos($path->getPath(), $this::NETACQ_PFX) !== false) {
            return $this->getAnnotationsForProvider('netacuity', $path);
        } else {
            if (strpos($path->getPath(), $this::MAXMIND_PFX) !== false) {
                return $this->getAnnotationsForProvider('maxmind', $path);
            }
        }
        return [];
    }

    private function cleanupName($name)
    {
        $name = trim($name, " ," . PathExpression::NAME_SEPARATOR . PathExpression::NAME_SEPARATOR_SKIP);
        return $name;
    }

    /**
     * Possibly annotate the given series with geographic meta-data
     *
     * @param AbstractExpression $expression
     * @param TimeSeriesSummary $summary
     *
     * @return AbstractAnnotation[]
     */
    public
    function annotateExpression(AbstractExpression $expression,
                                TimeSeriesSummary $summary = null): array
    {
        // look through all PathExpressions in this expression and build
        // annotations for each. if all path expressions have the same set of
        // annotations, then annotate the overall expression, otherwise, nope.
        $anns = [];
        /* @var PathExpression $pe */
        foreach ($expression->getAllByType('path') as $pe) {
            $thisAnns = $this->getAnnotationsForPath($pe);
            if (!count($anns)) {
                $anns = $thisAnns;
            } else {
                if (count(array_intersect($anns, $thisAnns)) != count($anns)) {
                    return [];
                }
            }
        }
        $expStr = $expression->getCanonicalStr();
        // subtract the common prefix and suffix from the expression name
        if (!$summary) {
            $expName = $expression->getCanonicalHumanized();
        } else {
            $expName =
                $expression->getCanonicalHumanized($summary->getCommonPrefix(),
                                                   $summary->getCommonSuffix());
        }
        /** @var GeoJoinAnnotation $ann */
        foreach ($anns as $ann) {
            // we have some annotations, need to update their dimensions
            // removing the current dimension id/name from the expression
            // canonical string / canonical name
            //
            // only join attributes have dimensions
            if ($ann->getType() != 'join') {
                continue;
            }
            $dim = $ann->getDimension();
            $newName = $expName;
            $newId = null;
            $sep = '(' . preg_quote(PathExpression::NAME_SEPARATOR, '/') . '|' .
                   preg_quote(PathExpression::NAME_SEPARATOR_SKIP, '/') . ')?';
            foreach ($dim['name'] as $toRemove) {
                $regex = '/' . $sep . preg_quote($toRemove, '/') . $sep . '/';
                $newName = preg_replace($regex,
                                        PathExpression::NAME_SEPARATOR_SKIP,
                                        $newName);
                $newName = $this->cleanupName($newName);
            }
            // TODO: dimensions are the last hax part of this whole shebang
            if ($newName == "") {
                $newName = null;
            } else {
                $newId = str_replace($dim['id'], '', $expStr);
            }
            $ann->setDimension($newId, $newName);
        }
        return $anns;
    }
}
