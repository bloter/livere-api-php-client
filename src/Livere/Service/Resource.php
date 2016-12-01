<?php
/*
 * Copyright 2016 Bloter and Media Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (!class_exists('Livere_Client')) {
    require_once dirname(__FILE__) . '/../autoload.php';
}

class Livere_Service_Resource
{
    /** @var string $rootUrl */
    private $rootUrl;

    /** @var Livere_Client $client */
    private $client;

    /** @var string $serviceName */
    private $serviceName;

    /** @var string $servicePath */
    private $servicePath;

    /** @var string $resourceName */
    private $resourceName;

    /** @var array $methods */
    private $methods;

    public function __construct($service, $serviceName, $resourceName, $resource)
    {
        $this->rootUrl = $service->rootUrl;
        $this->client = $service->getClient();
        $this->servicePath = $service->servicePath;
        $this->serviceName = $serviceName;
        $this->resourceName = $resourceName;
        $this->methods = is_array($resource) && isset($resource['methods']) ?
            $resource['methods'] :
            array($resourceName => $resource);
    }
    
    public function call($name, $arguments, $expected_class = null)
    {
        if (! isset($this->methods[$name])) {
            $this->client->getLogger()->error(
                'Service method unknown',
                array(
                    'service' => $this->serviceName,
                    'resource' => $this->resourceName,
                    'method' => $name
                )
            );
            
            throw new Livere_Exception("Unknown function: " . "{$this->serviceName}->{$this->resourceName}->{$name}()");
        }
        $method = $this->methods[$name];
        $parameters = $arguments[0];
        
        // postBody is a special case since it's not defined in the discovery
        // document as parameter, but we abuse the param entry for storing it.
        $postBody = null;
        if (isset($parameters['postBody'])) {
            if ($parameters['postBody'] instanceof Livere_Model) {
                // In the cases the post body is an existing object, we want
                // to use the smart method to create a simple object for
                // for JSONification.
                $parameters['postBody'] = $parameters['postBody']->toSimpleObject();
            } else if (is_object($parameters['postBody'])) {
                // If the post body is another kind of object, we will try and
                // wrangle it into a sensible format.
                $parameters['postBody'] = $this->convertToArrayAndStripNulls($parameters['postBody']);
            }
            $postBody = json_encode($parameters['postBody']);
            if ($postBody === false && $parameters['postBody'] !== false) {
                throw new Livere_Exception("JSON encoding failed. Ensure all strings in the request are UTF-8 encoded.");
            }
            unset($parameters['postBody']);
        }
        
        // TODO: optParams here probably should have been
        // handled already - this may well be redundant code.
        if (isset($parameters['optParams'])) {
            $optParams = $parameters['optParams'];
            unset($parameters['optParams']);
            $parameters = array_merge($parameters, $optParams);
        }
                
        if (!isset($method['parameters'])) {
            $method['parameters'] = array();
        }
        
        foreach ($parameters as $key => $val) {
            if ($key != 'postBody' && ! isset($method['parameters'][$key])) {
                $this->client->getLogger()->error(
                    'Service parameter unknown',
                    array(
                        'service' => $this->serviceName,
                        'resource' => $this->resourceName,
                        'method' => $name,
                        'parameter' => $key
                    )
                );
                throw new Livere_Exception("($name) unknown parameter: '$key'");
            }
        }
        
        foreach ($method['parameters'] as $paramName => $paramSpec) {
            if (isset($paramSpec['required']) && $paramSpec['required'] && !isset($parameters[$paramName])) {
                $this->client->getLogger()->error(
                    'Service parameter missing',
                    array(
                        'service' => $this->serviceName,
                        'resource' => $this->resourceName,
                        'method' => $name,
                        'parameter' => $paramName
                    )
                );
                
                throw new Livere_Exception("($name) missing required param: '$paramName'");
            }
            
            if (isset($parameters[$paramName])) {
                $value = $parameters[$paramName];
                $parameters[$paramName] = $paramSpec;
                $parameters[$paramName]['value'] = $value;
                unset($parameters[$paramName]['required']);
            } else {
                // Ensure we don't pass nulls.
                unset($parameters[$paramName]);
            }
        }

        $client_id = 
        $client_secret = $this->client->getClassConfig($this->client->getAuth(), 'client_secret');
        
        
        $parameters['id'] = array(
            'location' => 'query',
            'type' => 'string',
            'value' => $this->client->getClassConfig($this->client->getAuth(), 'client_id')
        );
        
        $parameters['token'] = array(
            'location' => 'query',
            'type' => 'string',
            'value' => $this->client->getClassConfig($this->client->getAuth(), 'client_secret')
        );
        
        $this->client->getLogger()->info(
            'Service Call',
            array(
                'service' => $this->serviceName,
                'resource' => $this->resourceName,
                'method' => $name,
                'arguments' => $parameters,
            )
        );
        
        $url = Livere_Http_REST::createRequestUri(
            $this->servicePath,
            $method['path'],
            $parameters
        );
        
        $httpRequest = new Livere_Http_Request(
            $url,
            $method['httpMethod'],
            null,
            $postBody
        );
        
        if ($this->rootUrl) {
            $httpRequest->setBaseComponent($this->rootUrl);
        } else {
            $httpRequest->setBaseComponent($this->client->getBasePath());
        }
        
        if ($postBody) {
            $contentTypeHeader = array();
            $contentTypeHeader['content-type'] = 'application/json; charset=UTF-8';
            $httpRequest->setRequestHeaders($contentTypeHeader);
            $httpRequest->setPostBody($postBody);
        }
           
        $httpRequest = $this->client->getAuth()->sign($httpRequest);
        $httpRequest->setExpectedClass($expected_class);
        
        //var_dump($httpRequest);
        
        return $this->client->execute($httpRequest);        
    }

    protected function convertToArrayAndStripNulls($o)
    {
        $o = (array) $o;
        foreach ($o as $k => $v) {
            if ($v === null) {
                unset($o[$k]);
            } elseif (is_object($v) || is_array($v)) {
                $o[$k] = $this->convertToArrayAndStripNulls($o[$k]);
            }
        }
        return $o;
    }
}
