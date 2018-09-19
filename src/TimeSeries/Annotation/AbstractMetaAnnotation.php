<?php


namespace App\TimeSeries\Annotation;


abstract class AbstractMetaAnnotation extends AbstractAnnotation
{
    public function __construct($type)
    {
        parent::__construct();
        $this->setAttribute('type', $type);
        $this->setFQID('null');
    }

    public function getType(): string
    {
        return 'meta';
    }

    public function setFQID(string $fqid)
    {
        $this->setAttribute('fqid', $fqid);
    }
}
