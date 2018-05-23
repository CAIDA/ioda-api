<?php

namespace App\Response;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

class Envelope
{
    /**
     * @Groups({"all", "public"})
     * @SWG\Property(type="string")
     */
    private $type;

    /**
     * @Groups({"all", "public"})
     * @SWG\Property(ref=@Model(type=EnvelopeMetadata::class, groups={"public"}))
     */
    private $metadata;

    /**
     * @Groups({"all", "public"})
     * @SWG\Property(
     *     type="object",
     *     additionalProperties={
     *         "type": "string"
     *     }
     * )
     */
    private $requestParameters;

    /**
     * @Groups({"all", "public"})
     */
    private $error;

    /**
     * @Groups({"all", "public"})
     * @SWG\Property(type="object")
     */
    private $data;

    public function __construct(string $type, ?array $requestParameters, int $requestTime)
    {
        $this->setType($type);
        $this->setRequestParameters($requestParameters);
        $this->setMetadata(new EnvelopeMetadata($requestTime));
    }

    public function getType(): string
    {
        return $this->type;
    }

    private function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getMetadata(): EnvelopeMetadata
    {
        return $this->metadata;
    }

    private function setMetadata(EnvelopeMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getRequestParameters(): ?array
    {
        return $this->requestParameters;
    }

    private function setRequestParameters(?array $requestParameters): void
    {
        $this->requestParameters = $requestParameters;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(string $error): void
    {
        $this->error = $error;
        $this->data = null;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): void
    {
        $this->data = $data;
        $this->error = null;
    }

}

class EnvelopeMetadata
{
    /**
     * @SWG\Property(type="string", format="date-time")
     * @Groups({"public"})
     */
    private $requestTime;

    /**
     * @SWG\Property(type="string", format="date-time")
     * @Groups({"public"})
     */
    private $responseTime;

    public function __construct($requestTimeEpoch)
    {
        $this->setRequestTime($requestTimeEpoch);
    }

    public function getRequestTime(): string
    {
        return $this->requestTime;
    }

    public function setRequestTime(int $requestTime): void
    {
        $this->requestTime = gmdate('c', $requestTime);
    }

    public function getResponseTime(): string
    {
        return gmdate('c', time());
    }
}