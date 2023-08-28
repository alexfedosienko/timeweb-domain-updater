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

  public function addDomain($domainName)
  {
    foreach ($this->data["domains"] as $domain) {
      if ($domain["fqdn"] == $domainName) {
        return false;
      }
    }

    $this->data["domains"][] = [
      "fqdn" => $domainName
    ];

    $this->data["last-ip"] = "127.0.0.1";
    $this->save();
    return true;
  }

  public function removeDomain($domainName)
  {
    $this->data["domains"] = array_filter($this->data["domains"], function ($item) use ($domainName) {
      if ($item["fqdn"] != $domainName) {
        return $item;
      }
    });
    $this->save();
  }

  public function export()
  {
    return file_get_contents($this->configFileName);
  }

  public function import($config)
  {
    try {
      $configData = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
      $configData["last-ip"] = "127.0.0.1";
      $this->data = $configData;
      $this->save();
      Log::info("Импорт успешно выполнен");
    } catch (\Throwable $th) {
      Log::info("Файл конфигурации не валидный");
    }
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
