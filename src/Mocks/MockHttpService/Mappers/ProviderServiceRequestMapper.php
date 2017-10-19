<?php

namespace PhpPact\Mocks\MockHttpService\Mappers;

use PhpPact\Mappers\MatchingRuleMapper;
use PhpPact\Mocks\MockHttpService\Models\ProviderServiceRequest;


class ProviderServiceRequestMapper implements \PhpPact\Mappers\IMapper
{
    /**
     * @param $request
     * @return ProviderServiceRequest
     */
    public function convert($request)
    {
        if (($request instanceof ProviderServiceRequest)) {
            return $request;
        } elseif ($request instanceof \Psr\Http\Message\RequestInterface) {
            $request = $this->httpRequestConvert($request);
        }

        $this->checkExistence($request, "method");
        $this->checkExistence($request, "path");

        $body = false;
        if (property_exists($request, "body")) {
            $contentType = $this->getContentType($request);
            $body = $request->body;

            if (stripos($contentType, "application/json") !== false && !is_string($body)) {
                $body = \json_encode($body);
            }
        }

        if (!isset($request->headers)) {
            $request->headers = null;
        }

        $matchingRulesMapper = new MatchingRuleMapper();
        $matchingRules = $matchingRulesMapper->convert($request);

        $providerServiceRequest = new ProviderServiceRequest($request->method, $request->path, $request->headers, $body, $matchingRules);
        if (isset($request->query)) {
            $providerServiceRequest->setQuery($request->query);
        }

        return $providerServiceRequest;
    }

    private function checkExistence($obj, $attr)
    {
        if (!isset($obj->$attr)) {
            throw new \InvalidArgumentException("$attr was not set");
        }
    }

    /**
     * Mine the headers to pull out the content type
     *
     * @param $request
     * @return bool
     */
    private function getContentType($request)
    {
        $contentTypeStr = "Content-Type";
        if (isset($request->headers) && isset($request->headers->$contentTypeStr)) {
            return $request->headers->$contentTypeStr;
        }

        return false;
    }


    private function httpRequestConvert(\Psr\Http\Message\RequestInterface $request)
    {
        $obj = new \stdClass();
        $headerArray = (array)$request->getHeaders();

        /*
         * Expected format
         [headers] => Array
        (
            [Host] => Array
                (
                    [0] => localhost:1239
                )

            [Date] => Array
                (
                    [0] => Fri, 30 Jun 2017 21:50:19 +0000
                )
        */
        $obj->headers = new \stdClass();
        if (count($headerArray) > 0) {
            foreach ($headerArray as $header_key => $header_value) {
                if (!is_array($header_value)) {
                    throw new \Exception("This was an unexpected case based on the Windwalker implementation.   Make a unit test and pull request.");
                }
                if (count($header_value) > 1) {
                    throw new \Exception("This was an unexpected case based on the Windwalker implementation.   Make a unit test and pull request.");
                }

                $obj->headers->$header_key = array_pop($header_value);
            }
        }

        $obj->method = $request->getMethod();
        $obj->path = $request->getUri()->getPath();

        //@todo flush out query
        $obj->query = $request->getUri()->getQuery();

        $body = (string)$request->getBody();
        if ($body) {
            $obj->body = $body;
        }

        return $obj;
    }
}
