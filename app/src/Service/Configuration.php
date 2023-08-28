<?php

namespace TimewebAutoupdateIP\Service;

class Configuration
{

  protected $configFileName;
  protected $data;

  public function __construct(String $configFileName = "/../../data/config.json")
  {
    $this->configFileName = __DIR__ . $configFileName;
    if (!file_exists($this->configFileName)) {
      Log::info("Конфигурационный файл не найден");

      $this->data = [
        "domains" => [],
        "last-ip" => "127.0.0.1"
      ];

      $this->save();
    } else {
      $this->data = json_decode(file_get_contents($this->configFileName), true);
    }
  }

  public function getDomains()
  {
    if (!isset($this->data["domains"])) return [];
    return $this->data["domains"];
  }

  public function setDomainId($domainIndex, $id)
  {
    $this->data["domains"][$domainIndex]["A_id"] = $id;
    $this->save();
  }

  public function getLastIp()
  {
    if (!isset($this->data["last-ip"])) return "";
    return $this->data["last-ip"];
  }

  public function setNewIp(String $ip)
  {
    $this->data["last-ip"] = $ip;
    $this->save();
  }

  protected function save()
  {
    file_put_contents($this->configFileName, json_encode($this->data));
  }

  public function getTimewebToken()
  {
    if (!isset($_ENV["TIMEWEB_TOKEN"])) {
      throw new \Exception("Не указан токен timeweb.cloud");
    }
    return $_ENV["TIMEWEB_TOKEN"];
  }
}
