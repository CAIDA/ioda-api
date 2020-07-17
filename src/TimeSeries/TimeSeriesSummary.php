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

namespace App\TimeSeries;


use App\Expression\AbstractExpression;
use Symfony\Component\Serializer\Annotation\Groups;

class TimeSeriesSummary
{
    /**
     * @var \DateTime
     * @Groups("public")
     */
    protected $earliestFrom;

    /**
     * @var \DateTime
     * @Groups("public")
     */
    protected $lastUntil;

    /**
     * @var int[]
     * @Groups("public")
     */
    protected $steps;

    /**
     * @var int[]
     * @Groups("public")
     */
    protected $nativeSteps;

    /**
     * @var AbstractExpression
     * TODO: consider a dedicated ExpressionPrefix/Suffix class?
     * @Groups("public")
     */
    protected $commonPrefix;

    /**
     * @var AbstractExpression
     * @Groups("public")
     */
    protected $commonSuffix;

    public function __construct()
    {
        $this->steps = [];
        $this->nativeSteps = [];
    }

    /**
     * @return \DateTime
     */
    public function getEarliestFrom(): ?\DateTime
    {
        return $this->earliestFrom;
    }

    /**
     * @param \DateTime $earliestFrom
     */
    public function setEarliestFrom(\DateTime $earliestFrom): void
    {
        $this->earliestFrom = $earliestFrom;
    }

    /**
     * @param \DateTime $from
     */
    public function addFrom(\DateTime $from): void
    {
        if (!isset($this->earliestFrom) || $from < $this->earliestFrom) {
            $this->earliestFrom = $from;
        }
    }

    /**
     * @return \DateTime
     */
    public function getLastUntil(): ?\DateTime
    {
        return $this->lastUntil;
    }

    /**
     * @param \DateTime $lastUntil
     */
    public function setLastUntil(\DateTime $lastUntil): void
    {
        $this->lastUntil = $lastUntil;
    }

    /**
     * @param \DateTime $until
     */
    public function addUntil(\DateTime $until): void
    {
        if (!isset($this->lastUntil) || $until > $this->lastUntil) {
            $this->lastUntil = $until;
        }
    }

    /**
     * @return int[]
     */
    public function getSteps(): array
    {
        return array_keys($this->steps);
    }

    public function addStep(int $step): void
    {
        $this->steps[$step] = 1;
    }

    /**
     * @return int[]
     */
    public function getNativeSteps(): ?array
    {
        if (count($this->nativeSteps) == 0) {
            return null;
        }
        $result = [];
        $nativeSteps = array_keys($this->nativeSteps);
        foreach ($nativeSteps as $ns) {
            $result[$ns] = array_keys($this->nativeSteps[$ns]);
        }
        return $result;
    }

    public function addNativeStep(int $nativeStep, int $step): void
    {
        if (!array_key_exists((int)$nativeStep, $this->nativeSteps)) {
            $this->nativeSteps[(int)$nativeStep] = [];
        }
        $this->nativeSteps[(int)$nativeStep][(int)$step] = 1;
    }

    /**
     * @return AbstractExpression
     */
    public function getCommonPrefix(): ?AbstractExpression
    {
        return $this->commonPrefix;
    }

    /**
     * @param AbstractExpression $commonPrefix
     */
    public function setCommonPrefix(?AbstractExpression $commonPrefix): void
    {
        $this->commonPrefix = $commonPrefix;
    }

    /**
     * @return AbstractExpression
     */
    public function getCommonSuffix(): ?AbstractExpression
    {
        return $this->commonSuffix;
    }

    /**
     * @param AbstractExpression $commonSuffix
     */
    public function setCommonSuffix(?AbstractExpression $commonSuffix): void
    {
        $this->commonSuffix = $commonSuffix;
    }


}
