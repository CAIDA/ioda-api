<?php


namespace App\TimeSeries\Annotation;


class GeoJoinAnnotation extends AbstractJoinAnnotation
{
    public function __construct(string $db, string $table, string $column,
                                string $id, bool $default = true)
    {
        parent::__construct('geo', $db, $table, $column, $id);
        $this->setDefault($default);
    }

    public function setDimension($id, $name)
    {
        $this->setAttribute(
            'dimension',
            [
                'id' => $id,
                'name' => $name
            ]
        );
    }

    public function getDimension()
    {
        return $this->getAttribute('dimension');
    }

    public function setDefault($isDefault)
    {
        $this->setAttribute('default', $isDefault == true);
    }
}
