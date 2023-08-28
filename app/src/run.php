<?php

namespace TimewebAutoupdateIP;

require __DIR__ . "/../vendor/autoload.php";

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use TimewebAutoupdateIP\Service\Log;
use TimewebAutoupdateIP\Service\CheckIp;
use TimewebAutoupdateIP\Service\Configuration;
use TimewebAutoupdateIP\Service\Timeweb;


$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
// $dotenv->load();
if (file_exists(__DIR__ . ".env")) {
  $dotenv->load();
}

$configuration = new Configuration();
$client = new Client();
$checkIp = new CheckIp($client);
$timeweb = new Timeweb($client, $configuration->getTimewebToken());

$newIp = $checkIp->getMyIp();

if ($configuration->getLastIp() == $newIp) {
  Log::info("Обновление IP адреса не требуется");
  exit;
}

Log::info("Обнаружен новый IP адрес: " . $newIp);
$configuration->setNewIp($newIp);

$domains = $configuration->getDomains();

if (sizeof($domains) == 0) {
  Log::info("Доменные имена не указаны");
}

foreach ($domains as $domainIndex => $domainInfo) {
  // Загружаем в файл cofnig.json ID записей типа A если они не указаны
  if (!isset($domainInfo["A_id"])) {
    Log::info("Не указан ID записи типа A у домена " . $domainInfo["fqdn"]);
    $records = $timeweb->getDnsRecordsByFQDN($domainInfo["fqdn"]);
    foreach ($records as $record) {
      if ($record["type"] == "A") {
        Log::info("Найдена запись типа A с ID:" . $record["id"] . " и значением:" . $record["data"]["value"]);
        $configuration->setDomainId($domainIndex, $record["id"]);
      }
    }
  }
}

// Обновляем запись типа А у хостера
$domains = $configuration->getDomains();
foreach ($domains as $domainInfo) {
  Log::info("Отправка запроса на обновление IP у домена " . $domainInfo["fqdn"]);
  $timeweb->updateARecord($domainInfo["fqdn"], $domainInfo["A_id"], $newIp);
}

Log::info("Обновление завершено");
