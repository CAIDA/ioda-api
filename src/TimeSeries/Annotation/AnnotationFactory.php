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

namespace App\TimeSeries\Annotation;


use App\Expression\AbstractExpression;
use App\TimeSeries\Annotation\Provider\AbstractAnnotationProvider;
use App\TimeSeries\Annotation\Provider\AsnAnnotationProvider;
use App\TimeSeries\Annotation\Provider\GeoAnnotationProvider;
use App\TimeSeries\TimeSeriesSummary;

class AnnotationFactory
{
    /**
     * Possibly annotate a given AbstractExpression with meta data.
     * Checks each available annotation provider.
     *
     * @param AbstractExpression $expression
     * @param TimeSeriesSummary $summary
     * @return AbstractAnnotation[]|null
     */
    public static function annotateExpression(AbstractExpression $expression,
                                              $summary = null): ?array
    {
        // TODO find smart way to specify which providers should be searched
        $providers = [
            new GeoAnnotationProvider(),
            new AsnAnnotationProvider(),
        ];
        $anns = [];
        // for each of the annotation providers that we have, check for metadata
        foreach ($providers as $provider) {
            /* @var $provider AbstractAnnotationProvider */
            $anns = array_merge($anns,
                                $provider->annotateExpression($expression,
                                                              $summary));
        }
        if (!count($anns)) {
            // TODO: is this really what we want to do?
            return null;
        }
        return $anns;
    }
}
