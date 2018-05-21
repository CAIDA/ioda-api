<?php

namespace App\Response;

use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class ResponseEnvelope
 * @package App\Response
 */
abstract class ResponseEnvelope
{

    /**
     * @Groups({"all", "public"})
     * @SWG\Property(type="string")
     */
    private $type;

    /**
     * @Groups({"all", "public"})
     * @SWG\Property(type="object")
     */
    private $requestParamters;

    /**
     * @Groups({"all", "public"})
     */
    private $error;

    /**
     * @Groups({"all", "public"})
     * @SWG\Property(type="object")
     */
    private $data;

    public function getType(): string
    {
        return $this->type;
    }

    protected function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getRequestParamters(): array
    {
        return $this->requestParamters;
    }

    protected function setRequestParamters(array $requestParamters): void
    {
        $this->requestParamters = $requestParamters;
    }

    public function getError(): string
    {
        return $this->error;
    }

    protected function setError(string $error): void
    {
        $this->error = $error;
    }

    public function getData(): array
    {
        return $this->data;
    }

    protected function setData(array $data): void
    {
        $this->data = $data;
    }

}
