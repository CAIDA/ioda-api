<?php


namespace App\TimeSeries\Annotation;


class AbstractJoinAnnotation extends AbstractAnnotation
{
    public function __construct(string $type, string $db, string $table,
                                string $column = null, string $id = null)
    {
        parent::__construct();
        $this->setAttribute('type', $type);
        $this->setAttribute('db', $db);
        $this->setAttribute('table', $table);
        $this->setAttribute('column', $column);
        $this->setAttribute('id', $id);
    }

    public function getType(): string
    {
        return 'join';
    }
}
