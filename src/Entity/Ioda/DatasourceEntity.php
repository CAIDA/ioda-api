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

namespace App\Entity\Ioda;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

class DatasourceEntity
{

    /**
     * All available down-sample steps an datasource can use.
     */
    const ALL_STEPS = [
        60, 120, 300, 900, 1800, // minute-level [1, 2, 5, 15, 30]
        3600, 7200, 21600, 43200,  //hour-level [1, 2, 6, 12]
        86400, 172800, //day-level [1, 2]
        604800, 1209600, 2419200, //week-level [1, 2, 4]
        31536000, 63072000, 315360000, //year-level [1, 2, 10]
    ];

    /**
     * Array of steps this datasource can down-sample to, including the native step.
     * @var int[]
     */
    private $downsampleSteps;

    /**
     * Constructor
     * @param string $datasource
     * @param string $name
     * @param string $units
     * @param int $nativeStep
     * @param string $backend
     */
    public function __construct(string $datasource, string $name, string $units, int $nativeStep, string $backend)
    {
        $this->datasource = $datasource;
        $this->name = $name;
        $this->units = $units;
        $this->backend = $backend;
        $this->nativeStep = $nativeStep;
        $this->downsampleSteps = array_filter($this::ALL_STEPS, function ($v){return $v >= $this->nativeStep;});
    }


    //////////////////////////
    //////////////////////////
    // VARIABLE DEFINITIONS //
    //////////////////////////
    //////////////////////////

    /**
     * @Groups({"public"})
     * @var string
     */
    private $datasource;

    /**
     * @Groups({"public"})
     * @var string
     */
    private $name;

    /**
     * @Groups({"public"})
     * @var string
     */
    private $units;

    /**
     * Native step for this data source, in seconds.
     * @var int
     */
    private $nativeStep;


    /**
     * @var string
     */
    private $backend;

    /////////////////////
    /////////////////////
    // GETTERS SETTERS //
    /////////////////////
    /////////////////////

    /**
     * @param string $datasource
     * @return DatasourceEntity
     */
    public function setDatasource(string $datasource): DatasourceEntity
    {
        $this->datasource = $datasource;
        return $this;
    }

    /**
     * @return string
     */
    public function getDatasource(): string
    {
        return $this->datasource;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return DatasourceEntity
     */
    public function setName(string $name): DatasourceEntity
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnits(): string
    {
        return $this->units;
    }

    /**
     * @param string $units
     * @return DatasourceEntity
     */
    public function setUnits(string $units): DatasourceEntity
    {
        $this->units = $units;
        return $this;
    }

    /**
     * @return int
     */
    public function getNativeStep(): int
    {
        return $this->nativeStep;
    }

    public function getDownsampleSteps(): array {
        return $this->downsampleSteps;
    }

    /**
     * @return string
     */
    public function getBackend(): string
    {
        return $this->backend;
    }
}
