<?php


namespace App\TimeSeries\Annotation;


class AsnMetaAnnotation extends AbstractMetaAnnotation
{
    public function __construct($fqid, $asn, $asName)
    {
        parent::__construct('asn');
        $this->setFQID($fqid);
        $this->setAttribute('asn', $asn);
        $this->setAttribute('name', $asName);
    }
}
