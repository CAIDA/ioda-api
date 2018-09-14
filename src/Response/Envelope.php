<?php

namespace App\Response;

use App\Utils\QueryTime;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * Envelope constructor.
     * @param string $type
     * @param string $paramsIn
     * @param RequestParameter[] $requestParameters
     * @param Request $request
     */
    public function __construct(string $type,
                                string $paramsIn,
                                array $requestParameters,
                                Request $request)
    {
        $this->setType($type);
        $this->requestParameters = [];
        try {
            $this->processRequestParameters($paramsIn, $requestParameters, $request);
        } catch (\InvalidArgumentException $ex) {
            $this->setError($ex->getMessage());
        }
        $this->setMetadata(
            new EnvelopeMetadata($request->server->get('REQUEST_TIME')));
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

    /**
     * @param string $paramsIn
     * @param RequestParameter[] $requestParameters
     * @param Request $request
     */
    private function processRequestParameters(string $paramsIn,
                                              array $requestParameters,
                                              Request $request)
    {
        if ($paramsIn == 'body') {
            if ($request->getContentType() != 'json') {
                $this->setError("Request type must be json, not '".$request->getContentType()."'");
                return;
            }
            $content = $request->getContent();
            if (!$content) {
                $this->setError("Request body was empty");
                return;
            }
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->setError('Invalid JSON in request: ' . json_last_error_msg());
                return;
            }
            $bag = new ParameterBag($data);
        } else {
            $bag = $request->query;
        }
        foreach ($requestParameters as $p) {
            // if parameter wasn't given, either error out or return the default
            if (!$bag->has($p->name)) {
                if ($p->required) {
                    $this->setError("Missing required request parameter '" . $p->name . "'");
                    $this->setRequestParameter($p->name, null);
                } else {
                    $this->setRequestParameter($p->name, $p->default);
                }
                continue;
            }

            // we are sure the parameter was given, so just parse the
            // value according to the type
            if ($p->type == RequestParameter::STRING ||
                $p->type == RequestParameter::MIXED) {
                $parsedVal = $bag->get($p->name);
            } elseif ($p->type == RequestParameter::BOOL) {
                $parsedVal = $bag->get($p->name);
                // allow "&param" to be treated as true
                if ($parsedVal === "" || $parsedVal === null) {
                    $parsedVal = true;
                } else {
                    $parsedVal = $bag->getBoolean($p->name);
                }
            } elseif ($p->type == RequestParameter::INTEGER) {
                $parsedVal = $bag->getInt($p->name);
            } elseif ($p->type == RequestParameter::ARRAY) {
                $parsedVal = $bag->get($p->name);
                if (!is_array($parsedVal)) {
                    $parsedVal = [$parsedVal];
                }
            } elseif ($p->type == RequestParameter::DATETIME) {
                $dateStr = $bag->get($p->name);
                $parsedVal = new QueryTime($dateStr);
            } else {
                throw new \InvalidArgumentException("Unexpected parameter type $p->type");
            }

            $this->setRequestParameter($p->name, $parsedVal);
        }
    }

    public function getRequestParameters(): ?array
    {
        return $this->requestParameters;
    }

    private function setRequestParameter(string $name, $value): void
    {
        $this->requestParameters[$name] = $value;
    }

    public function getParam(string $param)
    {
        return $this->requestParameters[$param];
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

/** Represents a parameter that can be provided in the request */
class RequestParameter
{
    const STRING = 'string';
    const BOOL = 'bool';
    const INTEGER = 'integer';
    const ARRAY = 'array';
    const MIXED = 'mixed';
    const DATETIME = 'date-time';

    const TYPES = [
        RequestParameter::STRING,
        RequestParameter::BOOL,
        RequestParameter::INTEGER,
        RequestParameter::ARRAY,
        RequestParameter::MIXED,
        RequestParameter::DATETIME,
    ];

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var mixed
     */
    public $default;

    /**
     * @var boolean
     */
    public $required;

    public function __construct(string $name, string $type, $default, bool $required=false)
    {
        $this->name = $name;

        if (!in_array($type, RequestParameter::TYPES)) {
            throw new \InvalidArgumentException("Invalid parameter type");
        }
        $this->type = $type;
        $this->default = $default;
        $this->required = $required;
    }
}

class EnvelopeMetadata
{
    /**
     * @SWG\Property(type="string", format="date-time")
     * @Groups({"public"})
     */
    private $requestTime;

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

    /**
     * @SWG\Property(type="string", format="date-time")
     * @Groups({"public"})
     */
    public function getResponseTime(): string
    {
        return gmdate('c', time());
    }
}
