<?php

namespace TimewebAutoupdateIP\Service;

use GuzzleHttp\ClientInterface;

class Timeweb
{

  protected $client;
  protected $token;

  protected $baseUri = "https://api.timeweb.cloud";

  public function __construct(ClientInterface $client, String $token)
  {
    $this->client = $client;
    $this->token = $token;
  }

  // public function getRemoteDomainList()
  // {
  //   $requestConfig = $this->makeRequestConfig("/api/v1/domains");

  //   $response = $this->client->get($requestConfig["url"], [
  //     "headers" => $requestConfig["headers"]
  //   ]);

  //   $domainResponse = json_decode($response->getBody()->getContents(), true);

  //   $domains = [];
  //   foreach ($domainResponse["domains"] as $domain) {

  //     $domains[] = [
  //       "id" => $domain["id"],
  //       "fqdn" => $domain["fqdn"]
  //     ];

  //     if (is_array($domain["subdomains"]) && sizeof($domain["subdomains"]) > 0) {
  //       foreach ($domain["subdomains"] as $subdomain) {
  //         $domains[] = [
  //           "id" => $subdomain["id"],
  //           "fqdn" => $subdomain["fqdn"]
  //         ];
  //       }
  //     }
  //   }

  //   return $domains;
  // }

  public function getDnsRecordsByFQDN($fqdn)
  {
    $requestConfig = $this->makeRequestConfig("/api/v1/domains/" . $fqdn . "/dns-records/");
    $response = $this->client->get($requestConfig["url"], [
      "headers" => $requestConfig["headers"]
    ]);
    $jsonResponse = json_decode($response->getBody()->getContents(), true);
    return $jsonResponse["dns_records"];
  }

  protected function makeRequestConfig($url)
  {
    return [
      "url" => $this->baseUri . $url,
      "headers" => [
        "Content-Type" =>  "application/json",
        "Authorization" => "Bearer " . $this->token
      ]
    ];
  }

  public function updateARecord($fqdn, $id, $ip)
  {
    $requestConfig = $this->makeRequestConfig("/api/v1/domains/" . $fqdn . "/dns-records/" . $id . "/");
    $this->client->patch($requestConfig["url"], [
      "headers" => $requestConfig["headers"],
      "body" => json_encode([
        "type" => "A",
        "value" => $ip
      ])
    ]);
  }
}
