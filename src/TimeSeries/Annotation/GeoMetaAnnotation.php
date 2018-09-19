<?php


namespace App\TimeSeries\Annotation;


class GeoMetaAnnotation extends AbstractMetaAnnotation
{
    const LEVELS = [
        'continent',
        'country',
        'region',
        'county'
    ];

    public function __construct(string $provider, string $providerName)
    {
        parent::__construct('geo');
        $this->setAttribute('provider',
                            ['id' => $provider, 'name' => $providerName]);
        $this->setNativeLevel(null); // Vasco wants attributes to always be present
        foreach (GeoMetaAnnotation::LEVELS as $level) {
            $this->setAttribute($level, null);
        }
    }

    public function setGeoLevel(string $level, string $code, string $name)
    {
        if (!in_array($level, GeoMetaAnnotation::LEVELS)) {
            throw new \InvalidArgumentException("Invalid geo level '$level'");
        }
        $this->setAttribute($level, ['id' => $code, 'name' => $name]);
    }

    public function setNativeLevel($level)
    {
        if (!in_array($level, GeoMetaAnnotation::LEVELS)) {
            throw new \InvalidArgumentException("Invalid geo level '$level'");
        }
        $this->setAttribute('nativeLevel', $level);
    }
}
