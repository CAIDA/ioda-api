<?php

namespace App\Utils;


use Symfony\Component\Serializer\Annotation\Groups;

class QueryTime
{
    // TODO: actually parse the relative times if/when we need them in
    // TODO: charthouse. for now it is sufficient to pass them along to graphite

    /**
     * @var bool
     * @Groups({"public"})
     */
    protected $relative;

    /**
     * @var string
     * @Groups({"public"})
     */
    protected $relativeTime;

    /**
     * @var \DateTime
     * @Groups({"public"})
     */
    protected $absoluteTime;

    /**
     * @var string
     * @Groups({"public"})
     */
    protected $rawTime;

    /**
     * QueryTime constructor.
     * @param null|string $time
     */
    public function __construct(?string $time)
    {
        $this->rawTime = $time;

        if (!$time) {
            // treat as 'now'
            $this->setRelativeTime('now');
            return;
        }

        if (ctype_digit($time)) {
            // assume epoch time
            $dt = new \DateTime();
            $dt->setTimestamp((int)$time);
            $this->setAbsoluteTime($dt);
            return;
        }

        // is it a relative time?
        if (strtolower($time) == 'now') {
            $this->setRelativeTime('now');
            return;
        }
        if ($time[0] == '-' || $time[0] == '+') {
            $this->setRelativeTime($time);
            return;
        }

        // otherwise, assume an RFC3339 string (e.g., 2018-09-14T00:00:00Z)
        $dt = \DateTime::createFromFormat(\DateTime::RFC3339, $time);
        if ($dt === false) {
            throw new \InvalidArgumentException("Could not parse time: '$time'");
        }
        $this->setAbsoluteTime($dt);
        return;
    }

    private function setRelativeTime(string $relTime)
    {
        $this->relative = true;
        $this->relativeTime = $relTime;
    }

    private function setAbsoluteTime(\DateTime $absTime)
    {
        $this->relative = false;
        $this->absoluteTime = $absTime;
    }

    public function __toString()
    {
        return $this->relative ? $this->relativeTime : $this->absoluteTime->format(DATE_ATOM);
    }

    public function isRelative(): bool
    {
        return $this->relative;
    }

    public function getRelativeTime(): ?string
    {
        return $this->relativeTime;
    }

    public function getAbsoluteTime(): ?\DateTime
    {
        return $this->absoluteTime;
    }

    /**
     * @Groups({"public"})
     */
    public function getEpochTime(): ?int
    {
        return $this->relative ? null : $this->absoluteTime->getTimestamp();
    }

    public function getRawTime(): string
    {
        return $this->rawTime;
    }

    public function getGraphiteTime(): string
    {
        return $this->relative ? $this->relativeTime : $this->absoluteTime->getTimestamp();
    }
}
