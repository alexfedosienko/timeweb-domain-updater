<?php

namespace TimewebAutoupdateIP\Service;

use GuzzleHttp\ClientInterface;

class CheckIp
{

  protected $client;

  public function __construct(ClientInterface $client)
  {
    $this->client = $client;
  }

  public function getMyIp()
  {
    $response = $this->client->get("https://api.ipify.org/", [
      "query" => [
        "format" => "json"
      ],
      "headers" => [
        "content-type" => "application/json"
      ]
    ]);

    $ip = json_decode($response->getBody()->getContents(), true);
    return $ip["ip"];
  }
}
