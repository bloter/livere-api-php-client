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

/**
 * Service definition for Count (v1).
 */
class Livere_Service_Count extends Livere_Service
{

    public $page;

    /**
     * Constructs the internal representation of the Count service.
     *
     * @param Livere_Client $client
     */
    public function __construct(Livere_Client $client)
    {
        parent::__construct($client);
        $this->rootUrl = 'https://livere.me/';
        $this->servicePath = 'v1/count/';
        $this->version = 'v1';
        $this->serviceName = 'count';
		
		$this->page = new Livere_Service_Count_Page_Resource(
			$this,
			$this->serviceName,
			'page',
			array(
				'methods' => array(
					'get' => array(
						'path' => 'page',
						'httpMethod' => 'GET',
						'parameters' => array(
							'refer' => array(
								'location' => 'query',
								'type' => 'string',
								'required' => true,
							)
						)						
					)
				)
			)
		);
    }
		 

}

class Livere_Service_Count_Page_Resource extends Livere_Service_Resource
{
	public function get($refer, $optParams = array())
	{
        $params = array('refer' => $refer);
        $params = array_merge($params, $optParams);
        return $this->call('get', array($params), "Livere_Service_Count_Page");
	}
}

class Livere_Service_Count_Page extends Livere_Model
{
    protected $internal_gapi_mappings = array(
    );
    public $repSeq;
    public $count;
    
    public function setRepSeq($repSeq)
    {
        $this->repSeq = $repSeq;
    }
    public function getRepSeq()
    {
        return $this->repSeq;
    }
}

