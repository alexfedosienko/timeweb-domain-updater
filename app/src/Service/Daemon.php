<?php

namespace TimewebAutoupdateIP\Service;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use TimewebAutoupdateIP\Service\Log;
use TimewebAutoupdateIP\Service\CheckIp;
use TimewebAutoupdateIP\Service\Configuration;
use TimewebAutoupdateIP\Service\Timeweb;

class Daemon
{
  protected $configuration = null;
  protected $client = null;
  protected $checkIp = null;
  protected $timeweb = null;

  public function __construct()
  {
    $envFileDir = __DIR__ . "/../../";

    $dotenv = Dotenv::createImmutable($envFileDir);
    // $dotenv->load();
    if (file_exists($envFileDir . ".env")) {
      $dotenv->load();
    }

    $this->configuration = new Configuration();
    $this->client = new Client();
    $this->checkIp = new CheckIp($this->client);
    $this->timeweb = new Timeweb($this->client, $this->configuration->getTimewebToken());
  }

  protected function addDomains($domains)
  {
    $domains = explode(";", $domains);
    foreach ($domains as $domainName) {
      $result = $this->configuration->addDomain($domainName);
      if ($result) {
        Log::info("Домен " . $domainName . " добавлен");
      } else {
        Log::info("Домен " . $domainName . " уже был добавлен");
      }
    }
  }

  protected function work()
  {
    $newIp = $this->checkIp->getMyIp();
    if ($this->configuration->getLastIp() == $newIp) {
      Log::info("Обновление IP адреса не требуется");
      exit;
    }

    Log::info("Обнаружен новый IP адрес: " . $newIp);
    $this->configuration->setNewIp($newIp);

    $domains = $this->configuration->getDomains();

    if (sizeof($domains) == 0) {
      Log::info("Доменные имена не указаны");
    }

    foreach ($domains as $domainIndex => $domainInfo) {
      // Загружаем в файл cofnig.json ID записей типа A если они не указаны
      if (!isset($domainInfo["A_id"])) {
        Log::info("Не указан ID записи типа A у домена " . $domainInfo["fqdn"]);
        $records = $this->timeweb->getDnsRecordsByFQDN($domainInfo["fqdn"]);
        foreach ($records as $record) {
          if ($record["type"] == "A") {
            Log::info("Найдена запись типа A с ID:" . $record["id"] . " и значением:" . $record["data"]["value"]);
            $this->configuration->setDomainId($domainIndex, $record["id"]);
          }
        }
      }
    }

    // Обновляем запись типа А у хостера
    $domains = $this->configuration->getDomains();
    foreach ($domains as $domainInfo) {
      Log::info("Отправка запроса на обновление IP у домена " . $domainInfo["fqdn"]);
      $this->timeweb->updateARecord($domainInfo["fqdn"], $domainInfo["A_id"], $newIp);
    }

    Log::info("Обновление завершено");
  }

  public function run()
  {
    $commands = @$GLOBALS["argv"];
    if (!isset($commands[1])) {
      Log::info("Не указан ACTION");
      die();
    }

    switch ($commands[1]) {
      case "--run":
        $this->work();
        break;
      case "--add":
        if (!isset($commands[2])) {
          Log::info("Не указан список доменов");
          die();
        }
        $this->addDomains($commands[2]);
        break;
      case "--remove":
        if (!isset($commands[2])) {
          Log::info("Не указан домен для удаления");
          die();
        }
        $this->configuration->removeDomain($commands[2]);
        Log::info("Домен " . $commands[2] . " удален");
        break;
      case "--export-config":
        echo "\n\n" . $this->configuration->export() . "\n\n\n";
        break;
      case "--import-config":
        $this->configuration->import($commands[2]);
        break;
      case "--help":
        echo "
--run - Запуск работы приложения
--add - Добавление домена или доменов в формате \"domain.ru;www.domain.ru\"
--remove - Удаление домена
--export-config - Экспорт всей конфигурации приложения
--import-config - Импорт всей конфигурации приложения
--help - Получаение информации о командах\n\n";
        break;
    }
  }
}
