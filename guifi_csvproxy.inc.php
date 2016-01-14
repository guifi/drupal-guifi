<?php
/**
 * guifi_csvproxy
**/



function guifi_csvproxy($zoneid,$action = 'help') {
  // load nodes and zones in memory for faster execution
  $searchStatusFlag = 'Working';
  $searchServiceType = 'Proxy';

  $subzoneIds = implode(guifi_zone_childs($zoneid), ',');
  $sql_services = sprintf("SELECT
                             s.nick,
                             s.extra,
                             z.title as ZoneTitle
                           FROM  guifi_services s,
                                 guifi_zone z
                           WHERE
                             s.status_flag = '%s'
                             AND s.zone_id = z.id
                             AND s.service_type = '%s'
                             AND (s.zone_id = %s or s.zone_id in (%s))
                             ORDER BY ZoneTitle asc, s.timestamp_created ASC",
                  $searchStatusFlag,
                  $searchServiceType,
                  $zoneid,
                  $subzoneIds);
  drupal_set_header('Content-Type: text/csv; charset=utf-8');
  $qservices = db_query($sql_services);
  while ($service = db_fetch_object($qservices)) {
    if (!empty($service->extra)) {
      $extraData = unserialize($service->extra);
      if (isset($extraData['proxy'])
          && isset($extraData['port'])
          && ($extraData['proxy']!='')
          && ($extraData['port']!='')
         ) {
        echo $service->nick. ";".$extraData['proxy'] .";". $extraData['port']. "\n";
      }
    }
  }
}
  return;

?>
