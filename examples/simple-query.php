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
 
require_once realpath(dirname(__FILE__) . '/../src/Livere/autoload.php');

$client = new Livere_Client();
$client_id = "<YOUR_CLIENT_ID>"; // Change this line.
$client_secret = "<YOUR_CLIENT_SECRET>"; // Change this line.

// Warn if the API key isn't changed.
if (strpos($client_id, "<") !== false || strpos($client_secret, "<") !== false) {
  echo 'Not set your api info';
  exit;
}
$client->setClientId($client_id);
$client->setClientSecret($client_secret);

$service = new Livere_Service_Count($client);

$refer = 'www.bloter.net/archives/244922';
$optParams = array();
$result = $service->page->get($refer, $optParams);

echo $result->count;