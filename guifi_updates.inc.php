<?php

// --
// This new release can be installed in a clean database.
// But updates 1 and 2 are necessary if it is installed
// on the old database that contains data for migrating purposes.
// Please, skip updates 1 and 2 if you work on a clean database.
// These updates will be commentED when the new release is published.
// --
// During development period, please create updates indicating the number of SVN revision.
// (Ex: guifi_update_XXX) Where XXX equals the number of revision.
// We move these updates to updates 1 and 2 when the process is necessary.
// --

//
// Database Updates!
//
/*
function guifi_update_1() {
  $items = array();

// Drop obsolete tables
    $items[] = update_sql("DROP TABLE `guifi_blocks`");

// Update some fields with a new names , types and lengths.

    $items[] = update_sql("ALTER TABLE `guifi_model_specs`
                           CHANGE `mid` `mid` INT( 11 ) NOT NULL AUTO_INCREMENT ,
                           CHANGE model model VARCHAR( 40 ) NOT NULL DEFAULT '' ,
                           CHANGE `tipus` `tipus` VARCHAR( 10 ) NULL COMMENT 'Extern, PCI, PCMCIA' ,
                           CHANGE `radiodev_max` `radiodev_max` TINYINT( 2 ) NOT NULL DEFAULT '1' ,
                           CHANGE `modes` `modes` VARCHAR( 20 ) NOT NULL DEFAULT '802.11bg' COMMENT '802.11bg,802.11b,802.11a,802.11abg,WiMax,802.11n' ,
                           CHANGE `AP` `AP` VARCHAR( 5 ) NULL COMMENT 'Yes, No' ,
                           CHANGE `virtualAP` `virtualAP` VARCHAR( 5 ) NOT NULL DEFAULT 'no' COMMENT 'Yes, No' ,
                           CHANGE `WDS` `WDS` VARCHAR( 5 ) NULL COMMENT 'Si,No,Hack' ,
                           CHANGE `bridge` `bridge` VARCHAR( 5 ) NULL COMMENT 'Si,No,Hack' ,
                           CHANGE `client` `client` VARCHAR( 5 ) NULL COMMENT 'Si,No,Hack' ,
                           CHANGE `antenes` `antenes` VARCHAR( 5 ) NULL DEFAULT '2' COMMENT '2,1,0' ,
                           CHANGE `router` `router` VARCHAR( 5 ) NULL DEFAULT NULL COMMENT 'Si,No' ,
                           CHANGE `firewall` `firewall` VARCHAR( 5 ) NULL DEFAULT NULL COMMENT 'Si,No' ,
                           CHANGE `QoS` `QoS` VARCHAR( 5 ) NULL DEFAULT NULL COMMENT 'Si,No,Hack' ,
                           CHANGE `snmp` `snmp` VARCHAR( 5 ) NULL DEFAULT NULL COMMENT 'Si,No,Hack' ,
                           CHANGE `hack` `hack` VARCHAR( 5 ) NULL DEFAULT NULL COMMENT 'Si,No' ,
                           CHANGE `interfaces` `interfaces` VARCHAR( 240 ) NULL ,
                           CHANGE `supported` `supported` VARCHAR( 25 ) NOT NULL DEFAULT 'Yes'");

    $items[] = update_sql("ALTER TABLE `guifi_location`
                           CHANGE `nick` `nick` VARCHAR(40) NOT NULL DEFAULT '',
                           CHANGE `contact` `notification` VARCHAR( 1024 ) NOT NULL,
                           CHANGE `stable` `stable` VARCHAR(25) NOT NULL DEFAULT 'Yes',
                           CHANGE `graph_server` `graph_server` INT( 11 ) NOT NULL DEFAULT '0' COMMENT 'Foreign key to guifi_services (type SNPGraph)'");

    $items[] = update_sql("ALTER TABLE `guifi_devices`
                           CHANGE `nick` `nick` VARCHAR( 40 ) NOT NULL ,
                           CHANGE `contact` `notification` VARCHAR( 1024 ) NOT NULL,
                           CHANGE `graph_server` `graph_server` INT( 11 ) NOT NULL DEFAULT '0' COMMENT 'Foreign key to guifi_services (type SNPGraph)'");

    $items[] = update_sql("ALTER TABLE `guifi_interfaces`
                           CHANGE `device_id` `device_id` INT( 11 ) NOT NULL");

    $items[] = update_sql("ALTER TABLE `guifi_ipv4`
                           CHANGE `id` `id` INT( 11 ) NOT NULL");

    $items[] = update_sql("ALTER TABLE `guifi_links`
                           CHANGE `id` `id` INT( 11 ) NOT NULL ,
                           CHANGE `nid` `nid` INT( 11 ) NOT NULL ,
                           CHANGE `device_id` `device_id` INT( 11 ) NOT NULL ,
                           CHANGE `interface_id` `interface_id` INT( 11 ) NOT NULL ,
                           CHANGE `ipv4_id` `ipv4_id` INT( 11 ) NOT NULL");

    $items[] = update_sql("ALTER TABLE `guifi_manufacturer`
                           CHANGE `fid` `fid` INT( 11 ) NOT NULL AUTO_INCREMENT ,
                           CHANGE `nom` `nom` VARCHAR( 40 ) NOT NULL DEFAULT ''");

    $items[] = update_sql("ALTER TABLE `guifi_networks`
                           CHANGE `base` `base` VARCHAR( 255 ) NOT NULL DEFAULT '' ,
                           CHANGE `zone` `zone` INT( 10 ) UNSIGNED NOT NULL ,
                           CHANGE `network_type` `network_type` VARCHAR( 10 ) NOT NULL DEFAULT 'public'");

    $items[] = update_sql("ALTER TABLE `guifi_permission`
                           CHANGE `perm` `perm` LONGTEXT NULL DEFAULT NULL");

    $items[] = update_sql("ALTER TABLE `guifi_radios`
                           CHANGE `nid` `nid` INT( 11 ) NOT NULL ,
                           CHANGE `model_id` `model_id` INT( 10 ) NOT NULL ,
                           CHANGE `ssid` `ssid` VARCHAR( 20 ) NOT NULL DEFAULT '',
                           CHANGE `antmode` `antenna_mode` VARCHAR( 1024 ) NOT NULL ,
                           CHANGE `clients_accepted` `clients_accepted` VARCHAR( 5 ) NULL DEFAULT 'Yes' COMMENT 'Yes,No'");

    $items[] = update_sql("ALTER TABLE `guifi_services`
                           CHANGE `nick` `nick` VARCHAR( 40 ) NOT NULL DEFAULT '' ,
                           CHANGE `service_type` `service_type` VARCHAR( 40 ) NOT NULL DEFAULT '',
                           CHANGE `contact` `notification` VARCHAR( 1024 ) NOT NULL");

    $items[] = update_sql("ALTER TABLE `guifi_types`
                           CHANGE `type` `type` VARCHAR( 15 ) NOT NULL ,
                           CHANGE `text` `text` VARCHAR( 15 ) NOT NULL ,
                           CHANGE `description` `description` LONGTEXT NOT NULL ,
                           CHANGE `relations` `relations` LONGTEXT NULL DEFAULT NULL");

    $items[] = update_sql("ALTER TABLE `guifi_users`
                           CHANGE `firstname` `firstname` VARCHAR( 60 ) NOT NULL DEFAULT '' ,
                           CHANGE `lastname` `lastname` VARCHAR( 60 ) NOT NULL DEFAULT '' ,
                           CHANGE `username` `username` VARCHAR( 40 ) NOT NULL DEFAULT '' ,
                           CHANGE `password` `password` VARCHAR( 128 ) NOT NULL DEFAULT '',
                           CHANGE `email` `notification` VARCHAR( 1024 ) NULL DEFAULT NULL");


    $items[] = update_sql("ALTER TABLE `guifi_zone`
                           CHANGE `title` `title` VARCHAR( 255 ) NOT NULL DEFAULT '',
                           CHANGE `image` `image` VARCHAR( 255 ) NOT NULL DEFAULT '',
                           CHANGE `notification` `notification` VARCHAR( 1024 ) NOT NULL ,
                           CHANGE `local` `local` VARCHAR( 5 ) NULL DEFAULT 'Yes' COMMENT 'Yes,No',
                           CHANGE `graph_server` `graph_server` INT( 11 ) NOT NULL DEFAULT '0' COMMENT 'Foreign key to guifi_services (type SNPGraph)'");

// Update fileds with the new types
    $items[] = update_sql("UPDATE node SET type = REPLACE (type,'guifi-zone', 'guifi_zone')");
    $items[] = update_sql("UPDATE node SET type = REPLACE (type,'guifi-node', 'guifi_node')");
    $items[] = update_sql("UPDATE node SET type = REPLACE (type,'guifi-service', 'guifi_service')");
    $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE(extra,'s:11:\"NanoStation\"', 's:9:\"AirOsv221\"')");

    $query = db_query("
      SELECT *
      FROM {guifi_radios}
      WHERE protocol = '802.11abg'
    ");
    while ($protocol = db_fetch_object($query)) {
      if ($protocol->channel < 5000)
        $items[] = update_sql(sprintf("UPDATE {guifi_radios} SET protocol = REPLACE (protocol, '802.11abg', '802.11b') WHERE id = %d AND channel= %d", $protocol->id, $protocol->channel));
    }
    $items[] = update_sql("UPDATE {guifi_radios} SET protocol = REPLACE (protocol, '802.11g+', '802.11g')");
    $items[] = update_sql("UPDATE {guifi_radios} SET protocol = REPLACE (protocol, '802.11abg', '802.11a')");
    $items[] = update_sql("UPDATE {guifi_radios} SET protocol = REPLACE (protocol, '802.11bg', '802.11b')");

// Set abbreviated nick to zones without nick.
    $q = db_query('SELECT id, title, nick FROM {guifi_zone}');
      while ($z = db_fetch_object($q)) {
        if (empty($z->nick)) {
          $nick = guifi_abbreviate($z->title);
          $items[] = update_sql(
            sprintf("UPDATE {guifi_zone} SET nick='%s' WHERE id=%d",
            $nick,$z->id)
            );
        }
      }

  return $items;
}

//
// Database New entries!
//
function guifi_update_2() {

  // changes @ guifi_zones
  $items = array();

  db_add_field($items,'guifi_zone', 'zone_mode',
    array('type' => 'varchar', 'length' => 25, 'not null' => TRUE, 'default' => 'infrastructure', 'COMMENT' => 'infrastructure/ad-hoc'));
  db_add_field($items, 'guifi_zone', 'proxy_id',
    array('type' => 'int', 'length' => '11', 'not null' => TRUE, 'default' => '0', 'COMMENT' => 'Foreign key to guifi_services (type Proxy)'));
  db_add_field($items, 'guifi_zone', 'voip_id',
    array('type' => 'int', 'length' => '11', 'not null' => TRUE, 'default' => '0', 'COMMENT' => 'Foreign key to guifi_services (type VoIP)'));
  db_drop_field($items,'guifi_zone', 'mrtg_servers');
  db_drop_field($items,'guifi_zone', 'image');
  db_drop_field($items,'guifi_zone', 'map_poly');
  db_drop_field($items,'guifi_zone', 'map_coord');

  // changes @ guifi_users
  db_add_field($items, 'guifi_users', 'status',
    array('type' => 'varchar', 'length' => 25, 'not null' => TRUE, 'default' => 'new', 'COMMENT' => 'pending/approved/rejected'));
  db_add_field($items, 'guifi_users', 'content_filters',
    array('type' => 'text', 'size' => 'big', 'not null' => FALSE, 'default' => NULL));
  $items[] = update_sql("UPDATE {guifi_users} SET status = 'Approved'");

  // changes @ guifi_devices
  db_add_field($items, 'guifi_devices', 'last_online',
    array('type' => 'int', 'disp-width' => 11, 'not null' => TRUE, 'default' => 0, 'COMMENT' => 'Last time that this device has been seen online'));
  db_add_field($items, 'guifi_devices', 'last_flag',
    array('type' => 'varchar', 'length' => '40', 'not null' => TRUE, 'default' => 'N/A', 'COMMENT' => 'N/A, Online, Offline...'));
  db_add_field($items, 'guifi_devices', 'ly_availability',
    array('type' => 'numeric', 'precision' => '10', 'scale' => '6', 'not null' => FALSE, 'default' => NULL));

  // changes @ guifi_radios
  db_add_field($items, 'guifi_radios', 'ly_bytes_in',
    array('type' => 'numeric', 'precision' => 25,  'scale' => 0, 'not null' => FALSE, 'default' => NULL));
  db_add_field($items, 'guifi_radios', 'ly_bytes_out',
    array('type' => 'numeric', 'precision' => 25, 'scale' => 0, 'not null' => FALSE, 'default' => NULL));

  db_drop_field($items,'guifi_devices', 'url_mrtg_server');

  return $items;
}
*/

function guifi_update_540() {
  $items = array();

  db_change_field($items, 'guifi_radios', 'ssid', 'ssid',
    array('type' => 'varchar', 'length' => '128', 'not null' => TRUE, 'default' => ''));
  return $items;
}

function guifi_update_556() {

  // Data cleaning for backward compatibility purposes.
  // All notifications will be validated
  // In the meantime, empty notifications will remain empty, just leaving the
  // code commented for further reference

  $items = array();

  $tables = array('guifi_zone', 'guifi_location', 'guifi_devices', 'guifi_services', 'guifi_users');

  foreach ($tables as $table)  {
    $qmails = db_query('SELECT * FROM {%s}', $table);
    while ($amails = db_fetch_object($qmails)) {
      // validate the email
      if (!empty($amails->notification))
        $dmails = guifi_notification_validate($amails->notification);

      // if mails empty or not valid, take from uid or user_created
//      if (empty($dmails)) {
//        if (!isset($amails->nid))
//          $amails->nid = $amails->id;
//        $n = node_load(array('nid' => $amails->nid));
//        $u = user_load($n->uid);
//        $dmails = $u->mail;
//      } // if was no mail

      // there was changes? then save the change
      if ($dmails != $amails->notification) {
        $items[] = update_sql(sprintf(
            'UPDATE {%s} SET notification = "%s" WHERE id = %d',
            $table, $dmails, $amails->id)
          );
      }

    } // foreach row

  } // foreach table

  return $items;
}

function guifi_update_652() {
  db_add_field($items, 'guifi_devices', 'last_stats',
    array('type' => 'int', 'disp-width' => 11, 'not null' => TRUE, 'default' => 0, 'comment' => 'Last time that this device has been loaded with statistics'));
  db_add_field($items, 'guifi_devices', 'latency_avg',
    array('type' => 'int', 'disp-width' => 11, 'not null' => TRUE, 'default' => 0, 'comment' => 'Average latency'));
  db_add_field($items, 'guifi_devices', 'latency_max',
    array('type' => 'int', 'disp-width' => 11, 'not null' => TRUE, 'default' => 0, 'comment' => 'Maximum latency'));
  db_change_field($items, 'guifi_radios', 'ly_bytes_in', 'ly_mb_in',
    array('type' => 'numeric', 'disp-width' => 25, 'not null' => FALSE, 'default' => NULL));
  db_change_field($items, 'guifi_radios', 'ly_bytes_out', 'ly_mb_out',
    array('type' => 'numeric', 'disp-width' => 25, 'not null' => FALSE, 'default' => NULL));
}

function guifi_update_661() {
  $items = array();

  db_drop_field($items, 'guifi_networks', 'valid');

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('1', 'adhoc', 'OLSR', 'ad-hoc mesh - OLSR', 'kamikaze|freifunk-OLSR');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('2', 'adhoc', 'OLSR-NG', 'ad-hoc mesh - OLSR-NG', 'kamikaze');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('3', 'adhoc', 'BATMAN', 'ad-hoc mesh - BATMAN', 'kamikaze|freifunk-BATMAN');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('4', 'adhoc', 'RouterOS', 'ad-hoc mesh - RouterOS', 'RouterOSv3.x');");

  return $items;
}

function guifi_update_664() {
  $items = array();

  db_add_field($items, 'guifi_ipv4', 'ipv4_type',
    array('type' => 'int', 'disp-width' => 11, 'not null' => TRUE, 'default' => 0, 'comment' => 'Address type'));
  db_add_field($items, 'guifi_ipv4', 'zone_id',
    array('type' => 'int', 'disp-width' => 11, 'not null' => TRUE, 'default' => 0, 'comment' => 'Address type'));

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('1', 'ipv4_types', '1', 'public', '');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('2', 'ipv4_types', '2', 'backbone', '');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('3', 'ipv4_types', '3', 'ad-hoc mesh - OLSR', 'kamikaze|freifunk-OLSR');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('4', 'ipv4_types', '4', 'ad-hoc mesh - BATMAN', 'kamikaze|freifunk-BATMAN');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('5', 'ipv4_types', '5', 'ad-hoc mesh - BMX', 'kamikaze');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('6', 'ipv4_types', '6', 'ad-hoc mesh - RouterOS', 'RouterOSv3.x');");

  $sql = db_query(
    'SELECT a.*, l.zone_id ' .
    'FROM {guifi_ipv4} a, {guifi_interfaces} i, {guifi_devices} d, {guifi_location} l ' .
    'WHERE a.interface_id=i.id AND i.device_id=d.id AND d.nid=l.id');
  $pmin = ip2long('10.0.0.0');
  $pmax = ip2long('11.0.0.0');
  while ($ipv4 = db_fetch_object($sql)) {
    // Set network type
    $daddr = ip2long($ipv4->ipv4);
    if ($ipv4->ipv4_type == 0) {
       (($daddr >= $pmin) and ($daddr < $pmax)) ? $type = 1 : $type = 2;
    }

    update_sql(sprintf('UPDATE {guifi_ipv4} SET ipv4_type=%s, zone_id=%d WHERE id=%d AND interface_id=%d',
      $type, $ipv4->zone_id, $ipv4->id, $ipv4->interface_id));

  }

  return $items;
}

function guifi_update_667() {
  $items = array();

  $items[] = update_sql("DELETE FROM {guifi_types} WHERE type='adhoc'");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('3', 'adhoc', 'OLSR', 'ad-hoc mesh - OLSR', 'kamikaze|freifunk-OLSR');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('4', 'adhoc', 'BATMAN', 'ad-hoc mesh - BATMAN', 'kamikaze|freifunk-BATMAN');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('5', 'adhoc', 'BMX', 'ad-hoc mesh - BMX', 'kamikaze|freifunk-BATMAN|freifunk-BMX');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('6', 'adhoc', 'RouterOS', 'ad-hoc mesh - RouterOS', 'RouterOSv3.x');");

  return $items;
}

function guifi_update_700() {
  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_manufacturer} (fid, nom, url) VALUES ('12', 'Gateworks', 'http://www.gateworks.com'),('13', 'Asus', 'http://www.asus.com');");
  $items[] = update_sql("INSERT INTO {guifi_model_specs} (mid, fid, model, tipus, radiodev_max, modes, AP, virtualAP, WDS, bridge, client, connector, antenes, router, firewall, QoS, snmp, hack, interfaces, url, comentaris, supported) VALUES
  ('34', '10', 'NanoStation Loco2', 'Extern', '1', '400', '802.11bg', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'Wan', 'http://www.ubnt.com/products/loco.php', 'Permet Firmwares de tercers', 'Yes'),
  ('35', '10', 'NanoStation Loco5', 'Extern', '1', '400', '802.11a', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'Wan', 'http://www.ubnt.com/products/loco.php', 'Permet Firmwares de tercers', 'Yes'),
  ('36', '10', 'Bullet2', 'Extern', '1', '400', '802.11bg', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'Wan', 'http://www.ubnt.com/products/loco.php', 'Permet Firmwares de tercers', 'Yes'),
  ('37', '10', 'Bullet5', 'Extern', '1', '400', '802.11a', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'Wan', 'http://www.ubnt.com/products/loco.php', 'Permet Firmwares de tercers', 'Yes'),
  ('38', '10', 'RouterStation', 'Extern', '4', '400', '802.11abg', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'Wan', 'http://ubnt.com/products/ls5.php', 'Permet Firmwares de tercers', 'Yes'),
  ('39', '12', 'Avila GW2348-4', 'Extern', '4', '400', '802.11abg', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'Wan', 'http://www.gateworks.com/products/avila/gw2348-4.php', 'Permet Firmwares de tercers', 'Yes'),
  ('40', '13', 'Asus WL-500xx', 'Extern', '1', '400', '802.11bg', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'Wan', 'http://asus.com/products.aspx?l1=29&l2=172&l3=743&l4=60&model=1277&modelmenu=1', 'Permet Firmwares de tercers', 'Yes');");

  $items[] = update_sql("UPDATE {guifi_types} SET relations = 'WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Meraki/Fonera|Wrap|Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|NanoStation2|NanoStation5|NanoStation Loco2|NanoStation Loco5|Bullet2|Bullet5|RouterStation|Avila GW2348-4|Asus WL-500xx' WHERE id = 8 AND type = 'firmware'");
  $items[] = update_sql("UPDATE {guifi_types} SET relations = 'WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Asus WL-500xx|WHR-HP-G54, WHR-G54S|NanoStation2|NanoStation5|NanoStation Loco2|NanoStation Loco5|Bullet2|Bullet5|RouterStation|Avila GW2348-4' WHERE id = 4 AND type = 'firmware'");
  $items[] = update_sql("UPDATE {guifi_types} SET relations = 'NanoStation2|NanoStation5|NanoStation Loco2|NanoStation Loco5|Bullet2|Bullet5' WHERE id = 11 AND type = 'firmware'");
  $items[] = update_sql("UPDATE {guifi_types} SET relations = 'NanoStation2|NanoStation5|NanoStation Loco2|NanoStation Loco5|Bullet2|Bullet5' WHERE id = 13 AND type = 'firmware'");

  return $items;
}

function guifi_update_736() {
  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_manufacturer} (fid ,nom ,url) VALUES ('14', 'Pcengines', 'http://www.pcengines.ch');");
  $items[] = update_sql("INSERT INTO {guifi_model_specs} (mid ,fid ,model ,tipus ,radiodev_max ,potencia_max ,modes ,AP ,virtualAP ,WDS ,bridge ,client ,connector ,antenes ,router ,firewall ,QoS ,snmp ,hack ,interfaces ,url ,comentaris ,supported) VALUES
  (41, 14, 'Alix1', 'Extern', 5, 400, '802.11bg', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'wLan/Lan', 'http://www.pcengines.ch/alix1d.htm', 'Permet Firmwares de tercers', 'Yes'),
  (42, 14, 'Alix2', 'Extern', 2, 400, '802.11bg', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'wLan/Lan|ether2', 'http://www.pcengines.ch/alix2d.htm', 'Permet Firmwares de tercers', 'Yes'),
  (43, 14, 'Alix3', 'Extern', 2, 400, '802.11bg', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'wLan/Lan', 'http://www.pcengines.ch/alix3d.htm', 'Permet Firmwares de tercers', 'Yes'),
  (44, 8, 'Routerboard 800', NULL, 8, 400, '802.11abg', 'Si', 'Yes', 'Si', 'Si', 'Si', 'N-Female', '2', 'Si', 'Si', 'Si', 'Si', 'No', 'wLan/Lan|ether2|ether3|ether4|ether5|ether6|ether7|ether8|ether9', 'http://www.routerboard.com', NULL,'Yes');");

  $items[] = update_sql("UPDATE {guifi_types} SET relations = 'WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Meraki/Fonera|Wrap|Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|NanoStation2|NanoStation5|NanoStation Loco2|NanoStation Loco5|Bullet2|Bullet5|RouterStation|Avila GW2348-4|Asus WL-500xx|Alix1|Alix2|Alix3' WHERE id = 8 AND type = 'firmware'");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('14', 'firmware', 'RouterOSv4.x', 'RouterOS 4.x from Mikrotik', 'Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|Routerboard 600|Routerboard 411|Routerboard 333|Routerboard 433|Routerboard 800');");

  return $items;
}

function guifi_update_743() {
  $items = array();

  $items[] = update_sql("CREATE TABLE IF NOT EXISTS `guifi_api_tokens` (
	  `uid` int(8) unsigned NOT NULL,
	  `token` varchar(255) NOT NULL,
	  `created` timestamp NULL DEFAULT NULL,
	  `rand_key` int(6) NOT NULL,
	  PRIMARY KEY (`uid`)
	) ENGINE=MyISAM");
  return $items;
}

function guifi_update_768() {
  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_model_specs} (mid ,fid ,model ,tipus ,radiodev_max ,potencia_max ,modes ,AP ,virtualAP ,WDS ,bridge ,client ,connector ,antenes ,router ,firewall ,QoS ,snmp ,hack ,interfaces ,url ,comentaris ,supported) VALUES
   (45, 10, 'AirMaxM2 Rocket/Nano/Loco', 'Extern', 1, 100, '802.11bg', 'Yes', 'No', 'Si', 'Si','Si', NULL, '1', 'Si', 'Si', NULL, 'Si', 'Si', 'eth0|ath0', 'http://ubnt.com/airmax', 'Permet Firmwares de tercers','Yes'),
   (46, 10, 'AirMaxM5 Rocket/Nano/Loco', 'Extern', 1, 100, '802.11a', 'Yes', 'No', 'si', 'Si', 'Si', NULL, '1', 'Si', 'Si', NULL, 'Si', 'Si', 'eth0|ath0', 'http://ubnt.com/airmax', 'Permet Firmwares de tercers','Yes'),
   (47, 10, 'AirMaxM2 Bullet/PwBrg/AirGrd/NanoBr', 'Extern', 1, 100, '802.11bg', 'Yes', 'No', 'Si', 'Si','Si', NULL, '1', 'Si', 'Si', NULL, 'Si', 'Si', 'eth0|ath0', 'http://ubnt.com/airmax', 'Permet Firmwares de tercers','Yes'),
   (48, 10, 'AirMaxM5 Bullet/PwBrg/AirGrd/NanoBr', 'Extern', 1, 100, '802.11a', 'Yes', 'No', 'si', 'Si', 'Si', NULL, '1', 'Si', 'Si', NULL, 'Si', 'Si', 'eth0|ath0', 'http://ubnt.com/airmax', 'Permet Firmwares de tercers','Yes');");

   $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('15', 'firmware', 'AirOsv52', 'Ubiquti AirOs 5.2','AirMaxM2 Rocket/Nano/Loco|AirMaxM5 Rocket/Nano/Loco|AirMaxM2 Bullet/PwBrg/AirGrd/NanoBr|AirMaxM5 Bullet/PwBrg/AirGrd/NanoBr');");
   $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('16', 'firmware', 'RouterOSv4.7+', 'RouterOS 4.7 or newer from Mikrotik', 'Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|Routerboard 600|Routerboard 411|Routerboard 333|Routerboard 433|Routerboard 800');");
   $items[] = update_sql("UPDATE {guifi_types} SET text = 'RouterOSv4.0+' , description = 'RouterOS 4.0 to 4.6 from Mikrotik' WHERE id = '14' AND type = 'firmware'");
  return $items;
}

function guifi_update_808() {
  $items = array();
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('26', 'service', 'LDAP', 'LDAP Server');");
  return $items;
}

function guifi_update_849() {
  $items = array();
  $items[] = update_sql("ALTER TABLE {guifi_dns_domains} ADD public VARCHAR( 5 ) NOT NULL DEFAULT 'yes' AFTER type;");
  return $items;
}

function guifi_update_851() {
  $items = array();
  $items[] = update_sql("INSERT INTO {guifi_manufacturer} VALUES (15, 'Setup Informatica', 'http://www.setup.cat');");
  $items[] = update_sql("INSERT INTO {guifi_model_specs} (mid ,fid ,model ,tipus ,radiodev_max ,potencia_max ,modes ,AP ,virtualAP ,WDS ,bridge ,client ,connector ,antenes ,router ,firewall ,QoS ,snmp ,hack ,interfaces ,url ,comentaris ,supported) VALUES
  (49, 15, 'GuifiStation2', 'Extern', 1, 100, '802.11bg', 'Yes', 'No', 'si', 'Si', 'Si', NULL, '1', 'Si', 'Si', NULL, 'Si', 'Si', 'eth0|ath0', 'http://tienda.setupinformatica.com/wac/detalle.php?codigo=CPEGS2', 'Permet Firmwares de tercers', 'Yes'),
  (50, 15, 'GuifiStation5', 'Extern', 1, 100, '802.11a', 'Yes', 'No', 'si', 'Si', 'Si', NULL, '1', 'Si', 'Si', NULL, 'Si', 'Si', 'eth0|ath0', 'http://tienda.setupinformatica.com/wac/detalle.php?codigo=CPEGS5', 'Permet Firmwares de tercers', 'Yes');");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('17', 'firmware', 'GSOS', 'GuifiStationOS from Setup Informatica', 'GuifiStation2|GuifiStation5');");
  return $items;
}

function guifi_update_852() {
   $items = array();
   $items[] = update_sql("UPDATE {guifi_types} SET text = 'GuifiStationOS1.0' , description = 'GuifiStationOS v1.0 from Setup Informatica' WHERE id = '17' AND type = 'firmware'");
  return $items;
}

function guifi_update_873() {
   $items = array();
   $items[] = update_sql("ALTER TABLE {guifi_dns_domains} ADD externalmx VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER allow");
  return $items;
}

function guifi_update_874() {
   $items = array();
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 532' WHERE mid =19");
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 133C' WHERE mid =20");
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 133' WHERE mid =21");
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 112' WHERE mid =22");
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 153' WHERE mid =23");
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 600' WHERE mid =27");
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 333' WHERE mid =28");
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 411' WHERE mid =29");
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 433' WHERE mid =31");
   $items[] = update_sql("UPDATE {guifi_model_specs} SET model = 'Routerboard 800' WHERE mid =44");
   $items[] = update_sql("UPDATE {guifi_types} SET `relations` = 'Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net' WHERE id = 6 AND type = 'firmware'");
   $items[] = update_sql("UPDATE {guifi_types} SET `relations` = 'WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Wrap|Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153' WHERE id = 7 AND type = 'firmware'");
   $items[] = update_sql("UPDATE {guifi_types} SET `relations` = 'WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Meraki/Fonera|Wrap|Routerboard 532|Routerboard 133|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|NanoStation2|NanoStation5|NanoStation Loco2|NanoStation Loco5|Bullet2|Bullet5|RouterStation|Avila GW2348-4|Asus WL-500xx|Alix1|Alix2|Alix3' WHERE id = 8 AND type = 'firmware'");
   $items[] = update_sql("UPDATE {guifi_types} SET `relations` = 'Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|Routerboard 600|Routerboard 411|Routerboard 333|Routerboard 433' WHERE id = 10 AND type = 'firmware'");
   $items[] = update_sql("UPDATE {guifi_types} SET `relations` = 'Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|Routerboard 600|Routerboard 411|Routerboard 333|Routerboard 433|Routerboard 800' WHERE id = 14 AND type = 'firmware'");
   $items[] = update_sql("UPDATE {guifi_types} SET `relations` = 'Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|Routerboard 600|Routerboard 411|Routerboard 333|Routerboard 433|Routerboard 800' WHERE id = 16 AND type = 'firmware'");
  return $items;
}

function guifi_update_891() {
   $items = array();
   $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('18', 'firmware', 'gsffirm', 'Firmware de GràciaSenseFils MANET', 'Alix2|Alix3|WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Asus WL-500xx|WHR-HP-G54, WHR-G54S|NanoStation2|NanoStation5|NanoStation Loco2|NanoStation Loco5|Bullet2|Bullet5|RouterStation|RouterStationPro|Meraki/Fonera');");
   $items[] = update_sql("INSERT INTO {guifi_model_specs} (mid ,fid ,model ,tipus ,radiodev_max ,potencia_max ,modes ,AP ,virtualAP ,WDS ,bridge ,client ,connector ,antenes ,router ,firewall ,QoS ,snmp ,hack ,interfaces ,url ,comentaris ,supported) VALUES
                                        (51, 10, 'RouterStationPro', 'Extern', 4, 400, '802.11abg', 'No', 'No', 'No', 'No', 'Si', 'RP-SMA', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'Wan', 'http://ubnt.com/rspro', 'Permet Firmwares de tercers', 'Yes');");
   $items[] = update_sql("UPDATE {guifi_types} SET relations = 'WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Asus WL-500xx|WHR-HP-G54, WHR-G54S|NanoStation2|NanoStation5|NanoStation Loco2|NanoStation Loco5|Bullet2|Bullet5|RouterStation|RouterStationPro|Avila GW2348-4' WHERE id = 4 AND type = 'firmware'");
   $items[] = update_sql("UPDATE {guifi_types} SET relations = 'WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Meraki/Fonera|Wrap|Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|NanoStation2|NanoStation5|NanoStation Loco2|NanoStation Loco5|Bullet2|Bullet5|RouterStation|RouterStationPro|Avila GW2348-4|Asus WL-500xx|Alix1|Alix2|Alix3' WHERE id = 8 AND type = 'firmware'");
  return $items;
}

function guifi_update_893() {
  $items = array();
  $items[] = update_sql("ALTER TABLE {guifi_dns_domains} ADD defipv6 VARCHAR( 128 ) NOT NULL AFTER defipv4;");
  $items[] = update_sql("ALTER TABLE {guifi_dns_hosts} ADD ipv6 VARCHAR( 128 ) NOT NULL AFTER ipv4;");
  return $items;
}

function guifi_update_894() {
  $items = array();
  $items[] = update_sql("UPDATE {guifi_types} SET text = 'DD-WRTv23' , description = 'DD-WRTv23Beta2 from BrainSlayer' WHERE id = '4' AND type = 'firmware'");
  return $items;
}

function guifi_update_895() {
  $items = array();
  $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE(extra,'s:6:\"DD-WRT\"', 's:9:\"DD-WRTv23\"')");
  return $items;
}

function guifi_update_896() {
  $items = array();
  $items[] = update_sql("ALTER TABLE {guifi_devices} ADD logserver VARCHAR( 60 ) NOT NULL AFTER graph_server;");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('27', 'service', 'LogServer', 'Ip logs server');");
  return $items;
}

function guifi_update_900() {
 $items = array();
 $items[] = update_sql("ALTER TABLE {guifi_model_specs} ADD etherdev_max tinyint(2) NOT NULL DEFAULT 1 AFTER radiodev_max");
 $items[] = update_sql("ALTER TABLE {guifi_interfaces} ADD etherdev_counter tinyint(2) AFTER radiodev_counter");
 $items[] = update_sql("ALTER TABLE {guifi_radios} ADD etherdev_counter tinyint(2) AFTER radiodev_counter");
 $items[] = update_sql("INSERT INTO {guifi_model_specs} (mid ,fid ,model ,tipus ,radiodev_max , etherdev_max, potencia_max ,modes ,AP ,virtualAP ,WDS ,bridge ,client ,connector ,antenes ,router ,firewall ,QoS ,snmp ,hack ,interfaces ,url ,comentaris ,supported) VALUES
                                      (52, 8, 'Routerboard 750/750G', 'Extern', '0', '5', '400', '802.11abg', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'None', '0', 'Yes','Yes', 'Yes', 'Yes', 'Yes', 'ether1|ether2|ether3|ether4|ether5', 'http://www.routerboard.com/pricelist.php?showProduct=90', 'Permet Firmwares de tercers', 'Yes');");
 $items[] = update_sql("UPDATE {guifi_types} SET `relations` = 'Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|Routerboard 600|Routerboard 411|Routerboard 333|Routerboard 433|Routerboard 800|Routerboard 750/750G' WHERE id = 16 AND type = 'firmware'");
 $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('19', 'firmware', 'RouterOSv5.x', 'RouterOS 5.x or newer from Mikrotik', 'Routerboard 532|Routerboard 133C|Routerboard 133|Routerboard 112|Routerboard 153|Supertrasto guifiBUS guifi.net|Routerboard 600|Routerboard 411|Routerboard 333|Routerboard 433|Routerboard 800|Routerboard 750/750G');");
  return $items;
}

function guifi_update_901() {
   $items = array();
   $items[] = update_sql("ALTER TABLE {guifi_dns_domains} ADD externalns VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER externalmx");
  return $items;
}

function guifi_update_902() {
   $items = array();
   $items[] = update_sql("ALTER TABLE {guifi_model_specs} ADD notification varchar(1024) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER supported");
   $items[] = update_sql("ALTER TABLE {guifi_model_specs} ADD user_created mediumint(9) NOT NULL AFTER notification");
   $items[] = update_sql("ALTER TABLE {guifi_model_specs} ADD user_changed mediumint(9) NOT NULL AFTER user_created");
   $items[] = update_sql("ALTER TABLE {guifi_model_specs} ADD timestamp_created int(11) NOT NULL AFTER user_changed");
   $items[] = update_sql("ALTER TABLE {guifi_model_specs} ADD timestamp_changed int(11) NOT NULL AFTER timestamp_created");
   $items[] = update_sql("ALTER TABLE {guifi_manufacturer} ADD notification varchar(1024) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER url");
   $items[] = update_sql("ALTER TABLE {guifi_manufacturer} ADD user_created mediumint(9) NOT NULL AFTER notification");
   $items[] = update_sql("ALTER TABLE {guifi_manufacturer} ADD user_changed mediumint(9) NOT NULL AFTER user_created");
   $items[] = update_sql("ALTER TABLE {guifi_manufacturer} ADD timestamp_created int(11) NOT NULL AFTER user_changed");
   $items[] = update_sql("ALTER TABLE {guifi_manufacturer} ADD timestamp_changed int(11) NOT NULL AFTER timestamp_created");
   $items[] = update_sql("ALTER TABLE {guifi_model_specs}
  DROP potencia_max,
  DROP modes,
  DROP WDS,
  DROP bridge,
  DROP connector,
  DROP antenes,
  DROP router,
  DROP firewall,
  DROP QoS,
  DROP snmp,
  DROP hack");

   $items[] = update_sql("DELETE FROM {guifi_manufacturer} WHERE fid = 1");
   $items[] = update_sql("DELETE FROM {guifi_manufacturer} WHERE fid = 3");
   $items[] = update_sql("DELETE FROM {guifi_manufacturer} WHERE fid = 4");
   $items[] = update_sql("DELETE FROM {guifi_manufacturer} WHERE fid = 5");
   $items[] = update_sql("DELETE FROM {guifi_manufacturer} WHERE fid = 6");
   $items[] = update_sql("DELETE FROM {guifi_manufacturer} WHERE fid = 7");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 2");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 3");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 4");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 5");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 6");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 7");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 8");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 10");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 11");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 12");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 13");
   $items[] = update_sql("DELETE FROM {guifi_model_specs WHERE mid = 14");

   $items[] = update_sql("ALTER TABLE {guifi_manufacturer} CHANGE nom name VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''");
   $items[] = update_sql("ALTER TABLE {guifi_model_specs} CHANGE tipus type VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Extern, PCI, PCMCIA'");
   $items[] = update_sql("ALTER TABLE {guifi_model_specs} CHANGE comentaris comments VARCHAR( 240 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");

   $items[] = update_sql("UPDATE {guifi_model_specs} SET mid = '3', fid = '1' WHERE mid =99;");
   $items[] = update_sql("UPDATE {guifi_manufacturer} SET fid = '99' WHERE fid =1;");
   $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE( extra,'8:\"model_id\";s:1:\"2\"', '8:\"model_id\";s:1:\"3\"')");
   $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE( extra,'8:\"model_id\";s:1:\"4\"', '8:\"model_id\";s:1:\"3\"')");
   $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE( extra,'8:\"model_id\";s:1:\"5\"', '8:\"model_id\";s:1:\"3\"')");
   $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE( extra,'8:\"model_id\";s:1:\"6\"', '8:\"model_id\";s:1:\"3\"')");
   $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE( extra,'8:\"model_id\";s:1:\"8\"', '8:\"model_id\";s:1:\"3\"')");
   $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE( extra,'8:\"model_id\";s:2:\"10\"', '8:\"model_id\";s:1:\"3\"')");
   $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE( extra,'8:\"model_id\";s:2:\"13\"', '8:\"model_id\";s:1:\"3\"')");
   $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE( extra,'8:\"model_id\";s:2:\"14\"', '8:\"model_id\";s:1:\"3\"')");
   $items[] = update_sql("UPDATE {guifi_devices} SET extra = REPLACE( extra,'8:\"model_id\";s:2:\"99\"', '8:\"model_id\";s:1:\"3\"')");
  return $items;
}

function guifi_update_903() {

  $items = array();

  // creació de la taula de firmwares
  $items[] = update_sql("CREATE TABLE IF NOT EXISTS `guifi_firmware` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(45) NOT NULL,
  `descripcio` varchar(45) NOT NULL,
  `relations` varchar(45) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT '0',
  `notification` varchar(1024) NOT NULL,
  `user_created` mediumint(9) NOT NULL,
  `user_changed` mediumint(9) NOT NULL,
  `timestamp_created` int(11) NOT NULL,
  `timestamp_changed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Firmwares'");

  // insert de firmwares
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (1,'n/a','not available','Other',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (2,'Alchemy','Alchemy from sveasoft','WRT54Gv1-4|WRT54GSv1-2',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (3,'Talisman','Talisman from sveasoft','WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (4,'DD-WRTv23','DD-WRTv23Beta2 from BrainSlayer','WRT54Gv1-4|WHR-HP-G54, WHR-G54S|WRT54GL|WRT54',1,'webmestre@guifi.net',541,541,0,1336245714);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (5,'DD-guifi','DD-guifi from Miquel Martos','WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|WHR-',1,'webmestre@guifi.net',541,541,0,1336245706);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (6,'RouterOSv2.9','RouterOS 2.9 from Mikrotik','Routerboard 532|Routerboard 133C|Routerboard ',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (7,'whiterussian','OpenWRT-whiterussian','WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Wrap',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (8,'kamikaze','OpenWRT kamikaze','WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|Rout',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (9,'Freifunk-BATMAN','OpenWRT-Freifunk-v1.6.16 with B.A.T.M.A.N','WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|WHR-',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (10,'RouterOSv3.x','RouterOS 3.x from Mikrotik','Routerboard 532|Routerboard 133C|Routerboard ',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (11,'AirOsv221','Ubiquti AirOs 2.2.1','NanoStation2|NanoStation5|NanoStation Loco2|N',0,'webmestre@guifi.net',541,541,0,1328962781);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (12,'Freifunk-OLSR','OpenWRT-Freifunk-v1.6.16 with OLSR','WRT54Gv1-4|WRT54GL|WRT54GSv1-2|WRT54GSv4|WHR-',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (13,'AirOsv30','Ubiquti AirOs 3.0','NanoStation2|NanoStation5|NanoStation Loco2|N',1,'webmestre@guifi.net',541,541,0,1336245700);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (14,'RouterOSv4.0+','RouterOSv4.0+','Routerboard 532|Routerboard 133C|Routerboard ',1,'webmestre@guifi.net',541,541,0,1336245758);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (15,'AirOsv52','Ubiquti AirOs 5.2','AirMaxM2 Rocket/Nano/Loco|AirMaxM5 Rocket/Nan',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (16,'RouterOSv4.7+','RouterOS 4.7 and newer 4.x ','Routerboard 532|Routerboard 133C|Routerboard ',1,'webmestre@guifi.net',541,541,0,1336245724);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (17,'GuifiStationOS1.0','GuifiStationOS v1.0 from Setup Informatica','GuifiStation2|GuifiStation5',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (18,'gsffirm','Firmware de GràciaSenseFils MANET','WRT54Gv1-4|WHR-HP-G54, WHR-G54S|WRT54GL|WRT54',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (19,'RouterOSv5.x','RouterOS 5.x or newer from Mikrotik','Routerboard 532|Routerboard 133C|Routerboard ',1,'webmestre@guifi.net',541,541,0,1336245762);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (20,'AirOsv3.6+','Ubiquti AirOs 3.6+','NanoStation2|NanoStation5|NanoStation Loco2|N',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_firmware} (id,nom,descripcio,relations,enabled,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (21,'DD-WRTv24preSP2','DD-WRTv24preSP2 from BrainSlayer','AirMaxM2 Bullet/PwBrg/AirGrd/NanoBr|AirMaxM5 ',1,'webmestre@guifi.net',541,541,0,1336245719);");

  // creació de la taula de parametres de parametres dels firmwares
  $items[] = update_sql("CREATE TABLE IF NOT EXISTS `guifi_parametresFirmware` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '  ',
  `fid` int(11) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `notification` varchar(1024) NOT NULL,
  `user_created` mediumint(9) NOT NULL,
  `user_changed` mediumint(9) NOT NULL,
  `timestamp_created` int(11) NOT NULL,
  `timestamp_changed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Firmware Parameters'");

  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (2,13,2,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (25,13,5,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (11,13,6,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (26,11,2,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (27,11,5,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (28,11,6,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (29,11,7,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (30,13,7,'webmestre@guifi.net',541,0,1328978022,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (31,13,11,'webmestre@guifi.net',541,0,1328978238,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (32,13,1,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (33,13,3,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (34,13,4,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (35,13,8,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (36,13,9,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (37,13,10,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (38,13,12,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (39,13,13,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (40,13,14,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (41,13,15,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (42,13,16,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (43,13,18,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (44,13,19,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (45,13,20,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (46,13,21,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (47,13,22,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (48,13,23,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (49,13,24,'webmestre@guifi.net',541,0,1328979536,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (50,5,20,'webmestre@guifi.net',541,0,1329161702,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (51,5,21,'webmestre@guifi.net',541,0,1329161702,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (52,5,22,'webmestre@guifi.net',541,0,1329161702,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (53,5,23,'webmestre@guifi.net',541,0,1329161702,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (54,15,19,'webmestre@guifi.net',541,0,1329263969,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (55,5,1,'webmestre@guifi.net',541,0,1329872883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (56,5,5,'webmestre@guifi.net',541,0,1329934265,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (57,5,25,'webmestre@guifi.net',541,0,1329934265,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (58,5,4,'webmestre@guifi.net',541,0,1329934265,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (59,5,2,'webmestre@guifi.net',541,0,1329934265,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (60,5,3,'webmestre@guifi.net',541,0,1329934265,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (61,5,24,'webmestre@guifi.net',541,0,1329934265,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (62,5,6,'webmestre@guifi.net',541,0,1329934756,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (63,16,23,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (64,16,22,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (65,16,21,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (66,16,20,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (67,16,5,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (68,16,6,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (69,16,4,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (70,16,2,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (71,16,3,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (72,16,1,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (73,16,24,'webmestre@guifi.net',541,0,1330123306,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (74,16,8,'webmestre@guifi.net',541,0,1330124856,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (75,16,27,'webmestre@guifi.net',541,0,1330124856,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (76,16,28,'webmestre@guifi.net',541,0,1330126007,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (77,16,29,'webmestre@guifi.net',541,0,1330126611,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (78,16,32,'webmestre@guifi.net',541,0,1330126992,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (79,16,9,'webmestre@guifi.net',541,0,1330126992,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (80,16,30,'webmestre@guifi.net',541,0,1330126992,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (81,16,31,'webmestre@guifi.net',541,0,1330127476,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (82,14,23,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (83,14,22,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (84,14,21,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (85,14,20,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (86,14,8,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (87,14,5,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (88,14,32,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (89,14,31,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (90,14,9,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (91,14,29,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (92,14,30,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (93,14,28,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (94,14,6,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (95,14,4,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (96,14,2,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (97,14,3,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (98,14,27,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (99,14,24,'webmestre@guifi.net',541,0,1330459516,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (101,19,23,'webmestre@guifi.net',541,0,1331383109,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (100,19,18,'webmestre@guifi.net',541,0,1331383109,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (102,14,7,'webmestre@guifi.net',541,0,1331407815,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (103,14,33,'webmestre@guifi.net',541,0,1331410222,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (104,14,34,'webmestre@guifi.net',541,0,1331410222,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (105,14,35,'webmestre@guifi.net',541,0,1331410598,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (106,16,35,'webmestre@guifi.net',541,0,1331410764,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (107,19,35,'webmestre@guifi.net',541,0,1331410776,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (108,16,36,'webmestre@guifi.net',541,0,1331597801,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (109,19,36,'webmestre@guifi.net',541,0,1331597808,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (110,19,22,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (111,19,7,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (112,19,21,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (113,19,20,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (114,19,5,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (115,19,33,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (116,19,34,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (117,19,6,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (118,19,27,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (119,19,24,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (120,19,8,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (121,19,32,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (122,19,31,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (123,19,9,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (124,19,29,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (125,19,30,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (126,19,28,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (127,19,4,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (128,19,2,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (129,19,3,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (130,19,1,'webmestre@guifi.net',541,0,1331677498,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (157,4,23,'webmestre@guifi.net',541,0,1332284532,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (158,4,22,'webmestre@guifi.net',541,0,1332284532,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (133,4,21,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (134,4,20,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (135,4,5,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (136,4,6,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (137,4,35,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (138,4,25,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (139,4,4,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (140,4,2,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (141,4,3,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (142,4,1,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (143,4,27,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (144,4,24,'webmestre@guifi.net',541,0,1332271883,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (145,21,23,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (146,21,22,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (147,21,21,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (148,21,20,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (149,21,5,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (150,21,6,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (151,21,25,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (152,21,4,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (153,21,2,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (154,21,3,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (155,21,1,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (156,21,24,'webmestre@guifi.net',541,0,1332272787,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (159,5,39,'webmestre@guifi.net',541,0,1332286129,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (160,4,39,'webmestre@guifi.net',541,0,1332286355,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (161,21,39,'webmestre@guifi.net',541,0,1332286487,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (162,21,11,'webmestre@guifi.net',541,0,1332286488,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresFirmware} (id,fid,pid,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (163,14,36,'webmestre@guifi.net',541,0,1332454981,0);");


  // creació de la taula de parametres generics
  $items[] = update_sql("CREATE TABLE IF NOT EXISTS `guifi_parametres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(45) DEFAULT NULL,
  `default_value` varchar(45) DEFAULT NULL,
  `origen` varchar(80) DEFAULT NULL,
  `dinamic` tinyint(1) DEFAULT '0',
  `notification` varchar(1024) NOT NULL,
  `user_created` mediumint(9) NOT NULL,
  `user_changed` mediumint(9) NOT NULL,
  `timestamp_created` int(11) NOT NULL,
  `timestamp_changed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Generic Parameters'
  ");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (1,'wireless1ssid',NULL,'linkedto_ssid',1,'webmestre@guifi.net',541,541,0,1336845453);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (2,'wanipv4',NULL,'ipv4_ip',1,'webmestre@guifi.net',541,541,0,1336845749);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (3,'wannetmask',NULL,'radios_1_interfaces_1_ipv4_0_links_1_interface_ipv4_ipv4',1,'webmestre@guifi.net',541,541,0,1336845774);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (4,'wangateway',NULL,'radios_1_interfaces_1_ipv4_0_links_1_interface_ipv4_ipv4',1,'webmestre@guifi.net',541,541,0,1336845729);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (5,'primarydns',NULL,'zone_dns_servers',1,'webmestre@guifi.net',541,541,0,1332285385);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (6,'secondarydns',NULL,'secondarydns',1,'webmestre@guifi.net',541,541,0,1332285289);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (7,'devnick',NULL,'device_name',1,'webmestre@guifi.net',541,541,0,1332285366);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (8,'locnick',NULL,'node_name',1,'webmestre@guifi.net',541,541,0,1332285374);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (9,'radio1ieee_mode',NULL,'radio_mode.1',1,'webmestre@guifi.net',541,541,0,1336845811);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (10,'radio1rate.max',NULL,'radio.1.rate.max 10',1,'webmestre@guifi.net',541,541,0,1336845830);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (11,'radio1txpower','6','radio.1.txpower',0,'webmestre@guifi.net',541,541,0,1332283508);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (12,'radio1acktimeout',NULL,'radio.1.acktimeout 12',1,'webmestre@guifi.net',541,541,0,1336845784);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (13,'radio1rx_antenna',NULL,'radio.1.rx_antenna 13',1,'webmestre@guifi.net',541,541,0,1336845835);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (14,'radio1tx_antenna',NULL,'radio.1.tx_antenna 14',1,'webmestre@guifi.net',541,541,0,1336845880);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (15,'radio1ext_antenna',NULL,'radio.1.ext_antenna 15',1,'webmestre@guifi.net',541,541,0,1336845803);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (16,'radio1mcastrate',NULL,'radio.1.mcastrate 16',1,'webmestre@guifi.net',541,541,0,1336845818);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (19,'prova1',NULL,'prova2',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (18,'antennaselection',NULL,'antenna.selection 18',1,'webmestre@guifi.net',541,541,0,1336845909);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (20,'firmware_version',NULL,'firmware_version',1,'webmestre@guifi.net',541,541,0,1329162058);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (21,'firmware_name',NULL,'firmware_name',1,'webmestre@guifi.net',541,541,0,1329162065);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (22,'device_name',NULL,'node_name',1,'webmestre@guifi.net',541,541,0,1332285362);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (23,'device_id','aaaaa','device_id',1,'webmestre@guifi.net',541,541,0,1332285354);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (24,'zone_ntp_servers',NULL,'zone_ntp_servers',1,'webmestre@guifi.net',541,541,0,1332285275);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (25,'txpwr',NULL,NULL,0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (26,'nombre',NULL,'origen',1,'',541,0,1329501088,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (27,'zone_id',NULL,'zone_id',1,'',541,541,1330124406,1332285208);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (28,'radio1ssid',NULL,'radio_ssid.1',1,'',541,541,1330125885,1336845946);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (29,'radio1order',NULL,'radio_order.1',1,'',541,541,1330126599,1336845938);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (30,'radio1protocol',NULL,'radio_protocol.1',1,'',541,541,1330126785,1336845824);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (31,'radio1channel',NULL,'radio_channel.1',1,'',541,541,1330126858,1336845798);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (32,'radio1antenna_gain',NULL,'radio_antenna_gain.1',1,'',541,541,1330126897,1336845792);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (33,'routerOS_24_band_value','2ghz-b','',0,'',541,541,1331409135,1331409200);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (34,'routerOS_5_band_value','5ghz','',0,'',541,541,1331409239,1331409274);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (35,'snmp_contact','guifi@guifi.net','',0,'',541,0,1331410572,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (36,'firmware_description','firmware_description','firmware_description',1,'',541,541,1331597765,1332450428);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (37,'zone_ternary_dns','','zone_ternary_dns',1,'',541,0,1332286041,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (38,'ternary_dns','','zone_ternary_dns',1,'',541,0,1332286090,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametres} (id,nom,default_value,origen,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (39,'ternarydns','','zone_ternary_dns',1,'',541,0,1332286115,0);");


  // creació de la taula de característiques
  $items[] = update_sql("CREATE TABLE IF NOT EXISTS `guifi_caracteristica` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(45) NOT NULL,
  `tipus` enum('bool','numeric','text','rang') NOT NULL,
  `notification` varchar(1024) NOT NULL,
  `user_created` mediumint(9) NOT NULL,
  `user_changed` mediumint(9) NOT NULL,
  `timestamp_created` int(11) NOT NULL,
  `timestamp_changed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Characteristics'
  ");

  $items[] = update_sql("INSERT INTO {guifi_caracteristica} (id,nom,tipus,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (1,'max_radios','numeric','webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_caracteristica} (id,nom,tipus,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (2,'maxethernet','numeric','webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_caracteristica} (id,nom,tipus,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (4,'interface_names','text','''webmestre@guifi.net''',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_caracteristica} (id,nom,tipus,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (3,'access_point','bool','''webmestre@guifi.net''',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_caracteristica} (id,nom,tipus,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (5,'vlan','bool','''webmestre@guifi.net''',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_caracteristica} (id,nom,tipus,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (6,'station','bool','''webmestre@guifi.net''',541,0,0,0);");

  // creació de la taula de UnSolclics
  $items[] = update_sql("CREATE TABLE IF NOT EXISTS `guifi_configuracioUnSolclic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mid` int(11) NOT NULL,
  `fid` int(11) NOT NULL,
  `enabled` tinyint(1) unsigned zerofill DEFAULT '0',
  `plantilla` text,
  `tipologia` tinyint(1) unsigned zerofill NOT NULL DEFAULT '0',
  `notification` varchar(1024) NOT NULL,
  `user_created` mediumint(9) NOT NULL,
  `user_changed` mediumint(9) NOT NULL,
  `timestamp_created` int(11) NOT NULL,
  `timestamp_changed` int(11) NOT NULL,
  `template_file` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='UnSolclic Configurations'
  ");

  // creació de la taula de Parametres dels UnSolclics
  $items[] = update_sql("CREATE TABLE IF NOT EXISTS `guifi_parametresConfiguracioUnsolclic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `uscid` int(11) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `dinamic` tinyint(1) unsigned zerofill NOT NULL DEFAULT '0',
  `notification` varchar(1024) NOT NULL,
  `user_created` mediumint(9) NOT NULL,
  `user_changed` mediumint(9) DEFAULT NULL,
  `timestamp_created` int(11) DEFAULT NULL,
  `timestamp_changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Parameters forUnSolclic Configurations'
  ");

  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (1,1,1,'',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (2,2,1,'',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (3,3,1,'',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (4,4,1,'',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (5,5,1,'',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (6,6,1,'',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (7,7,1,'',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (8,8,1,'',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (9,9,19,'b',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (10,10,19,'11M',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (11,11,19,'6',0,'webmestre@guifi.net',541,541,0,1332284043);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (12,12,19,'45',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (13,13,19,'2',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (14,14,19,'2',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (15,15,19,'disabled',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (16,16,19,'11',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (19,9,2,'b',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (18,18,1,'Main/Internal - Vertical',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (20,10,2,'11M',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (21,11,2,'6',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (22,12,2,'45',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (23,13,2,'2',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (24,14,2,'2',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (25,15,2,'disabled',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (26,16,2,'11',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (27,18,2,'Main/Internal - Vertical',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (125,22,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (124,23,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (33,1,19,'',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (34,2,19,'',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (57,4,33,'wan.gateway',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (35,3,19,'',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (36,4,19,'',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (37,5,19,'',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (38,6,19,'',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (39,8,19,'locnick',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (60,7,33,'devnick',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (59,6,33,'secondarydns',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (58,5,33,'primarydns',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (44,7,19,'',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (54,1,33,'wireless.1.ssid',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (55,2,33,'wan.ipv4',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (56,3,33,'wan.netmask',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (61,8,33,'locnick',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (68,24,33,'',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (64,20,33,'firmware_name',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (65,21,33,'locnick',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (66,22,33,'devnick',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (67,23,33,'locnick',1,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (69,25,33,'28',0,'webmestre@guifi.net',541,0,0,0);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (70,19,34,'',1,'webmestre@guifi.net',541,NULL,1329333997,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (71,2,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (72,5,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (73,6,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (74,7,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (75,11,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (76,1,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (77,3,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (78,4,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (79,8,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (80,9,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (81,10,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (82,12,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (83,13,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (84,14,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (85,15,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (86,16,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (87,18,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (88,19,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (89,20,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (90,21,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (91,22,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (92,23,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (93,24,35,'',1,'webmestre@guifi.net',541,NULL,1329501151,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (94,20,20,'',1,'webmestre@guifi.net',541,NULL,1329862310,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (95,21,20,'',1,'webmestre@guifi.net',541,NULL,1329862310,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (96,22,20,'',1,'webmestre@guifi.net',541,NULL,1329862310,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (97,23,20,'',1,'webmestre@guifi.net',541,NULL,1329862310,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (255,18,25,'',1,'webmestre@guifi.net',541,NULL,1331383109,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (105,21,23,'',1,'webmestre@guifi.net',541,NULL,1329870861,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (104,20,23,'',1,'webmestre@guifi.net',541,NULL,1329870861,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (256,23,25,'',1,'webmestre@guifi.net',541,NULL,1331383109,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (107,23,23,'',1,'webmestre@guifi.net',541,NULL,1329870861,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (108,1,23,'',0,'webmestre@guifi.net',541,NULL,1329872883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (109,1,20,'',1,'webmestre@guifi.net',541,NULL,1329872883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (110,5,23,'',0,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (111,5,20,'',1,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (112,25,23,'',0,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (113,25,20,'28',0,'webmestre@guifi.net',541,541,1329934265,1331676576);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (114,4,23,'',0,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (115,4,20,'',1,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (116,2,23,'',0,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (117,2,20,'',1,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (118,3,23,'',0,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (119,3,20,'',1,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (120,24,23,'',0,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (121,24,20,'',1,'webmestre@guifi.net',541,NULL,1329934265,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (122,6,23,'',1,'webmestre@guifi.net',541,NULL,1329934756,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (123,6,20,'',1,'webmestre@guifi.net',541,NULL,1329934756,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (126,21,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (127,20,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (128,5,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (129,6,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (130,4,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (131,2,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (132,3,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (133,1,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (134,24,6,'',1,'webmestre@guifi.net',541,NULL,1330123306,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (135,8,6,'',1,'webmestre@guifi.net',541,NULL,1330124856,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (136,27,6,'',1,'webmestre@guifi.net',541,NULL,1330124856,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (137,28,6,'',1,'webmestre@guifi.net',541,NULL,1330126007,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (138,29,6,'',1,'webmestre@guifi.net',541,NULL,1330126611,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (139,32,6,'',1,'webmestre@guifi.net',541,NULL,1330126992,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (140,9,6,'',1,'webmestre@guifi.net',541,NULL,1330126992,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (141,30,6,'',1,'webmestre@guifi.net',541,NULL,1330126992,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (142,31,6,'',1,'webmestre@guifi.net',541,NULL,1330127476,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (143,23,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (144,22,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (145,21,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (146,20,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (147,8,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (148,5,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (149,32,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (150,31,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (151,9,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (152,29,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (153,30,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (154,28,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (155,6,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (156,4,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (157,2,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (158,3,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (159,27,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (160,24,24,'',1,'webmestre@guifi.net',541,NULL,1330459516,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (161,2,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (162,5,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (163,6,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (164,7,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (165,11,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (166,1,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (167,3,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (168,4,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (169,8,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (170,9,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (171,10,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (172,12,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (173,13,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (174,14,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (175,15,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (176,16,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (177,18,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (178,19,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (179,20,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (180,21,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (181,22,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (182,23,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (183,24,29,'',1,'webmestre@guifi.net',541,NULL,1331077406,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (282,4,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (281,28,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (280,30,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (279,29,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (278,9,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (277,31,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (276,32,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (275,8,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (274,24,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (273,27,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (272,6,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (271,34,25,'5ghz',0,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (270,33,25,'2ghz-b',0,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (269,5,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (268,20,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (267,21,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (266,7,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (265,22,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (264,36,25,'firmware_description',1,'webmestre@guifi.net',541,NULL,1331597808,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (263,36,6,'firmware_description',1,'webmestre@guifi.net',541,NULL,1331597801,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (262,35,25,'guifi@guifi.net',0,'webmestre@guifi.net',541,NULL,1331410776,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (261,35,6,'guifi@guifi.net',0,'webmestre@guifi.net',541,NULL,1331410764,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (260,35,24,'guifi@guifi.net',0,'webmestre@guifi.net',541,NULL,1331410598,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (258,33,24,'2.4ghz-b',0,'webmestre@guifi.net',541,541,1331410222,1331410432);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (232,2,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (233,5,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (234,6,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (235,7,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (236,11,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (237,1,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (238,3,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (239,4,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (240,8,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (241,9,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (242,10,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (243,12,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (244,13,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (245,14,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (246,15,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (247,16,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (248,18,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (249,19,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (250,20,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (251,21,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (252,22,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (253,23,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (254,24,30,'',1,'webmestre@guifi.net',541,NULL,1331162924,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (259,34,24,'5ghz',0,'webmestre@guifi.net',541,NULL,1331410222,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (257,7,24,'',1,'webmestre@guifi.net',541,NULL,1331407815,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (283,2,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (284,3,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (285,1,25,'',1,'webmestre@guifi.net',541,NULL,1331677498,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (312,23,22,'aaaaa',1,'webmestre@guifi.net',541,NULL,1332284532,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (313,22,22,'',1,'webmestre@guifi.net',541,NULL,1332284532,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (288,21,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (289,20,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (290,5,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (291,6,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (292,35,22,'guifi@guifi.net',0,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (293,25,22,'28',0,'webmestre@guifi.net',541,541,1332271883,1332272960);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (294,4,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (295,2,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (296,3,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (297,1,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (298,27,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (299,24,22,'',1,'webmestre@guifi.net',541,NULL,1332271883,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (300,23,21,'aaaaa',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (301,22,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (302,21,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (303,20,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (304,5,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (305,6,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (306,25,21,'28',0,'webmestre@guifi.net',541,541,1332272787,1332273042);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (307,4,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (308,2,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (309,3,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (310,1,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (311,24,21,'',1,'webmestre@guifi.net',541,NULL,1332272787,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (314,39,23,'',1,'webmestre@guifi.net',541,NULL,1332286129,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (315,39,20,'',1,'webmestre@guifi.net',541,NULL,1332286129,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (316,39,22,'',1,'webmestre@guifi.net',541,NULL,1332286355,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (317,39,21,'',1,'webmestre@guifi.net',541,NULL,1332286488,NULL);");
  $items[] = update_sql("INSERT INTO {guifi_parametresConfiguracioUnsolclic} (id,pid,uscid,valor,dinamic,notification,user_created,user_changed,timestamp_created,timestamp_changed) VALUES (318,36,24,'firmware_description',1,'webmestre@guifi.net',541,NULL,1332454981,NULL);");

  // claus foranies de la taula device a model i firmware
  $items[] = update_sql("ALTER TABLE {guifi_devices} ADD COLUMN usc_id INT(11) NOT NULL  AFTER extra;");
  $items[] = update_sql("ALTER TABLE {guifi_devices} ADD COLUMN mid INT(11) NOT NULL  AFTER usc_id;");
  $items[] = update_sql("ALTER TABLE {guifi_devices} ADD COLUMN fid INT(11) NOT NULL  AFTER `mid`;");

  guifi_update_devices_fromExtraInfo();

  return $items;
}

function guifi_update_904() {

  // inicialitzacio de totes les combinacions de models-firmwares (com a no validades)
  $items[] = update_sql("insert into {guifi_configuracioUnSolclic}(mid, fid, enabled, tipologia, notification, user_created) values
  (40,4,0,0,'webmestre@guifi.net',541),
  (40,21,0,0,'webmestre@guifi.net',541),
  (40,18,0,0,'webmestre@guifi.net',541),
  (40,8,0,0,'webmestre@guifi.net',541),
  (15,5,0,0,'webmestre@guifi.net',541),
  (15,4,0,0,'webmestre@guifi.net',541),
  (15,21,0,0,'webmestre@guifi.net',541),
  (15,9,0,0,'webmestre@guifi.net',541),
  (15,12,0,0,'webmestre@guifi.net',541),
  (15,18,0,0,'webmestre@guifi.net',541),
  (56,21,0,0,'webmestre@guifi.net',541),
  (39,4,0,0,'webmestre@guifi.net',541),
  (39,21,0,0,'webmestre@guifi.net',541),
  (39,8,0,0,'webmestre@guifi.net',541),
  (16,5,0,0,'webmestre@guifi.net',541),
  (16,4,0,0,'webmestre@guifi.net',541),
  (16,21,0,0,'webmestre@guifi.net',541),
  (16,9,0,0,'webmestre@guifi.net',541),
  (16,12,0,0,'webmestre@guifi.net',541),
  (16,18,0,0,'webmestre@guifi.net',541),
  (16,8,0,0,'webmestre@guifi.net',541),
  (16,3,0,0,'webmestre@guifi.net',541),
  (17,2,0,0,'webmestre@guifi.net',541),
  (17,5,0,0,'webmestre@guifi.net',541),
  (17,4,0,0,'webmestre@guifi.net',541),
  (17,21,0,0,'webmestre@guifi.net',541),
  (17,9,0,0,'webmestre@guifi.net',541),
  (17,12,0,0,'webmestre@guifi.net',541),
  (17,18,0,0,'webmestre@guifi.net',541),
  (17,8,0,0,'webmestre@guifi.net',541),
  (17,3,0,0,'webmestre@guifi.net',541),
  (17,7,0,0,'webmestre@guifi.net',541),
  (16,7,0,0,'webmestre@guifi.net',541),
  (18,5,0,0,'webmestre@guifi.net',541),
  (18,4,0,0,'webmestre@guifi.net',541),
  (18,21,0,0,'webmestre@guifi.net',541),
  (18,9,0,0,'webmestre@guifi.net',541),
  (18,12,0,0,'webmestre@guifi.net',541),
  (18,18,0,0,'webmestre@guifi.net',541),
  (18,8,0,0,'webmestre@guifi.net',541),
  (18,3,0,0,'webmestre@guifi.net',541),
  (18,7,0,0,'webmestre@guifi.net',541),
  (1,2,0,0,'webmestre@guifi.net',541),
  (1,5,0,0,'webmestre@guifi.net',541),
  (1,4,0,0,'webmestre@guifi.net',541),
  (1,21,0,0,'webmestre@guifi.net',541),
  (1,9,0,0,'webmestre@guifi.net',541),
  (1,12,0,0,'webmestre@guifi.net',541),
  (1,18,0,0,'webmestre@guifi.net',541),
  (1,8,0,0,'webmestre@guifi.net',541),
  (1,3,0,0,'webmestre@guifi.net',541),
  (1,7,0,0,'webmestre@guifi.net',541),
  (30,21,0,0,'webmestre@guifi.net',541),
  (30,18,0,0,'webmestre@guifi.net',541),
  (30,8,0,0,'webmestre@guifi.net',541),
  (19,8,0,0,'webmestre@guifi.net',541),
  (19,6,0,0,'webmestre@guifi.net',541),
  (19,10,0,0,'webmestre@guifi.net',541),
  (19,14,0,0,'webmestre@guifi.net',541),
  (19,16,0,0,'webmestre@guifi.net',541),
  (19,19,0,0,'webmestre@guifi.net',541),
  (19,7,0,0,'webmestre@guifi.net',541),
  (21,8,0,0,'webmestre@guifi.net',541),
  (21,6,0,0,'webmestre@guifi.net',541),
  (21,10,0,0,'webmestre@guifi.net',541),
  (21,14,0,0,'webmestre@guifi.net',541),
  (21,16,0,0,'webmestre@guifi.net',541),
  (21,19,0,0,'webmestre@guifi.net',541),
  (21,7,0,0,'webmestre@guifi.net',541),
  (20,8,0,0,'webmestre@guifi.net',541),
  (20,6,0,0,'webmestre@guifi.net',541),
  (20,10,0,0,'webmestre@guifi.net',541),
  (20,14,0,0,'webmestre@guifi.net',541),
  (20,16,0,0,'webmestre@guifi.net',541),
  (20,19,0,0,'webmestre@guifi.net',541),
  (20,7,0,0,'webmestre@guifi.net',541),
  (22,8,0,0,'webmestre@guifi.net',541),
  (22,6,0,0,'webmestre@guifi.net',541),
  (22,10,0,0,'webmestre@guifi.net',541),
  (22,14,0,0,'webmestre@guifi.net',541),
  (22,16,0,0,'webmestre@guifi.net',541),
  (22,19,0,0,'webmestre@guifi.net',541),
  (22,7,0,0,'webmestre@guifi.net',541),
  (23,8,0,0,'webmestre@guifi.net',541),
  (23,6,0,0,'webmestre@guifi.net',541),
  (23,10,0,0,'webmestre@guifi.net',541),
  (23,14,0,0,'webmestre@guifi.net',541),
  (23,16,0,0,'webmestre@guifi.net',541),
  (23,19,0,0,'webmestre@guifi.net',541),
  (23,7,0,0,'webmestre@guifi.net',541),
  (24,8,0,0,'webmestre@guifi.net',541),
  (24,6,0,0,'webmestre@guifi.net',541),
  (24,10,0,0,'webmestre@guifi.net',541),
  (24,14,0,0,'webmestre@guifi.net',541),
  (24,16,0,0,'webmestre@guifi.net',541),
  (24,19,0,0,'webmestre@guifi.net',541),
  (24,7,0,0,'webmestre@guifi.net',541),
  (27,10,0,0,'webmestre@guifi.net',541),
  (27,14,0,0,'webmestre@guifi.net',541),
  (27,16,0,0,'webmestre@guifi.net',541),
  (27,19,0,0,'webmestre@guifi.net',541),
  (28,10,0,0,'webmestre@guifi.net',541),
  (28,14,0,0,'webmestre@guifi.net',541),
  (28,16,0,0,'webmestre@guifi.net',541),
  (28,19,0,0,'webmestre@guifi.net',541),
  (29,10,0,0,'webmestre@guifi.net',541),
  (29,14,0,0,'webmestre@guifi.net',541),
  (29,16,0,0,'webmestre@guifi.net',541),
  (29,19,0,0,'webmestre@guifi.net',541),
  (31,10,0,0,'webmestre@guifi.net',541),
  (31,14,0,0,'webmestre@guifi.net',541),
  (31,16,0,0,'webmestre@guifi.net',541),
  (31,19,0,0,'webmestre@guifi.net',541),
  (55,19,0,0,'webmestre@guifi.net',541),
  (54,16,0,0,'webmestre@guifi.net',541),
  (54,19,0,0,'webmestre@guifi.net',541),
  (52,16,0,0,'webmestre@guifi.net',541),
  (52,19,0,0,'webmestre@guifi.net',541),
  (44,14,0,0,'webmestre@guifi.net',541),
  (44,16,0,0,'webmestre@guifi.net',541),
  (44,19,0,0,'webmestre@guifi.net',541),
  (53,16,0,0,'webmestre@guifi.net',541),
  (53,19,0,0,'webmestre@guifi.net',541),
  (41,21,0,0,'webmestre@guifi.net',541),
  (41,8,0,0,'webmestre@guifi.net',541),
  (42,21,0,0,'webmestre@guifi.net',541),
  (42,18,0,0,'webmestre@guifi.net',541),
  (42,8,0,0,'webmestre@guifi.net',541),
  (43,21,0,0,'webmestre@guifi.net',541),
  (43,18,0,0,'webmestre@guifi.net',541),
  (43,8,0,0,'webmestre@guifi.net',541),
  (50,17,0,0,'webmestre@guifi.net',541),
  (49,17,0,0,'webmestre@guifi.net',541),
  (46,15,0,0,'webmestre@guifi.net',541),
  (46,21,0,0,'webmestre@guifi.net',541),
  (47,15,0,0,'webmestre@guifi.net',541),
  (47,21,0,0,'webmestre@guifi.net',541),
  (25,11,0,0,'webmestre@guifi.net',541),
  (25,20,0,0,'webmestre@guifi.net',541),
  (25,13,0,0,'webmestre@guifi.net',541),
  (25,4,0,0,'webmestre@guifi.net',541),
  (25,21,0,0,'webmestre@guifi.net',541),
  (25,18,0,0,'webmestre@guifi.net',541),
  (25,8,0,0,'webmestre@guifi.net',541),
  (26,11,0,0,'webmestre@guifi.net',541),
  (26,20,0,0,'webmestre@guifi.net',541),
  (26,13,0,0,'webmestre@guifi.net',541),
  (26,4,0,0,'webmestre@guifi.net',541),
  (26,21,0,0,'webmestre@guifi.net',541),
  (26,18,0,0,'webmestre@guifi.net',541),
  (26,8,0,0,'webmestre@guifi.net',541),
  (34,11,0,0,'webmestre@guifi.net',541),
  (34,20,0,0,'webmestre@guifi.net',541),
  (34,13,0,0,'webmestre@guifi.net',541),
  (34,4,0,0,'webmestre@guifi.net',541),
  (34,21,0,0,'webmestre@guifi.net',541),
  (34,18,0,0,'webmestre@guifi.net',541),
  (34,8,0,0,'webmestre@guifi.net',541),
  (35,11,0,0,'webmestre@guifi.net',541),
  (35,20,0,0,'webmestre@guifi.net',541),
  (35,13,0,0,'webmestre@guifi.net',541),
  (35,4,0,0,'webmestre@guifi.net',541),
  (35,21,0,0,'webmestre@guifi.net',541),
  (35,18,0,0,'webmestre@guifi.net',541),
  (35,8,0,0,'webmestre@guifi.net',541),
  (36,11,0,0,'webmestre@guifi.net',541),
  (36,20,0,0,'webmestre@guifi.net',541),
  (36,13,0,0,'webmestre@guifi.net',541),
  (36,4,0,0,'webmestre@guifi.net',541),
  (36,9,0,0,'webmestre@guifi.net',541),
  (36,18,0,0,'webmestre@guifi.net',541),
  (36,8,0,0,'webmestre@guifi.net',541),
  (37,11,0,0,'webmestre@guifi.net',541),
  (37,20,0,0,'webmestre@guifi.net',541),
  (37,13,0,0,'webmestre@guifi.net',541),
  (37,4,0,0,'webmestre@guifi.net',541),
  (37,21,0,0,'webmestre@guifi.net',541),
  (37,18,0,0,'webmestre@guifi.net',541),
  (37,8,0,0,'webmestre@guifi.net',541),
  (51,4,0,0,'webmestre@guifi.net',541),
  (51,21,0,0,'webmestre@guifi.net',541),
  (51,18,0,0,'webmestre@guifi.net',541),
  (51,8,0,0,'webmestre@guifi.net',541),
  (38,4,0,0,'webmestre@guifi.net',541),
  (38,21,0,0,'webmestre@guifi.net',541),
  (38,18,0,0,'webmestre@guifi.net',541),
  (38,8,0,0,'webmestre@guifi.net',541),
  (48,15,0,0,'webmestre@guifi.net',541),
  (48,21,0,0,'webmestre@guifi.net',541),
  (45,15,0,0,'webmestre@guifi.net',541),
  (45,21,0,0,'webmestre@guifi.net',541);");

  return $items;
}

function guifi_update_905() {
   $items = array();
   $items[] = update_sql("ALTER TABLE {guifi_configuracioUnSolclic} ADD snmp_id varchar(32) NOT NULL AFTER enabled");
  return $items;
}

function guifi_update_906() {
   $items = array();
   $items[] = update_sql("ALTER TABLE guifi_location ADD INDEX lat_index    USING BTREE (lat);");
   $items[] = update_sql("ALTER TABLE guifi_location ADD INDEX lon_index    USING BTREE (lon);");
   $items[] = update_sql("ALTER TABLE guifi_location ADD INDEX zcs_index    USING BTREE (zone_id, timestamp_created, status_flag);");
   $items[] = update_sql("ALTER TABLE guifi_location ADD INDEX year_index   USING BTREE (timestamp_created);");
   $items[] = update_sql("ALTER TABLE guifi_location ADD INDEX status_index USING BTREE (status_flag);");
   $items[] = update_sql("ALTER TABLE guifi_devices  ADD INDEX nid_index    USING BTREE (nid);");
   $items[] = update_sql("ALTER TABLE guifi_links    ADD INDEX device_index USING BTREE (device_id);");
   $items[] = update_sql("ALTER TABLE guifi_services ADD INDEX zone_index   USING BTREE (zone_id);");
   $items[] = update_sql("ALTER TABLE guifi_zone     ADD INDEX master_index USING BTREE (master);");
   return $items;
}

function guifi_update_907() {
  $items = array();
  $items[] = update_sql("ALTER TABLE guifi_links ADD INDEX nid_index USING BTREE (nid);");
  return $items;
}

function guifi_update_908() {
  $items = array();
  $items[] = update_sql("ALTER TABLE {guifi_dns_hosts} CHANGE `counter` `counter` INT( 11 ) NOT NULL;");
  return $items;
}

function guifi_update_909() {
  $items = array();
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('9', 'device', 'confine', 'Node Confine/Clommunity');");
  return $items;
}

function guifi_update_910() {
  $items = array();
  // Remove 'zone_mode' field
  $items[] = update_sql("ALTER TABLE {guifi_zone} DROP `zone_mode`;");
  // Add "Mesh" radio and IPv4 types
  $items = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('5', 'mode', 'mesh', 'Mesh radio', NULL);");
  $items = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('7', 'ipv4_types', '7', 'mesh', NULL);");
  // Add reserved IPv4 type
  $items = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('8', 'ipv4_types', '8', 'reserved', NULL);");
  return $items;
}

function guifi_update_911() {
  // Add new status Inactive
  $items = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('7', 'status', 'Inactive', 'Inactive', NULL);");
  return $items;
}

function guifi_update_1000() {

  $items = array();
  db_add_field($items,'guifi_zone','host_nodes',
      array('type' => 'int', 'size' => 'small', 'not null'  => FALSE, 'default' => 0));

  db_query("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('6', 'link', 'xPON', 'Passive Splitted Fiber Optics (xPON) link', '');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('7', 'link', 'DuplexFO', 'Duplex P2P link', '');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('8', 'link', 'SimplexFO', 'Simplex P2P link', '');");

  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('10', 'device', 'onu', 'xPON ONU User Unit (Final)');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('11', 'device', 'olt', 'xPON OLT Concentrator (Terminal)');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('12', 'device', 'splitter', 'xPON Splitter');");

  return $items;
}

function guifi_update_1002() {

  $items = array();
  // --
  // -- maintenance types
  // --
  update_sql("DELETE FROM `guifi_types` WHERE `type` = 'maintainer'");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('1', 'maintainer', 'Volunteer', 'Volunteer');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('2', 'maintainer', 'FO', 'Fibre Optics - Professional');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('3', 'maintainer', 'Wireless', 'Wireless - Professional');");

  // --
  // -- SLA types
  // --
  update_sql("DELETE FROM `guifi_types` WHERE `type` = 'sla'");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('1', 'sla', 'none',  'Best effort');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('2', 'sla', '8x5',   '8x5: Labour times and days');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('3', 'sla', 'dayx5', 'Dx5: Daylight - Labour days');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('4', 'sla', '8x7',   '8x7: Labour times - all days');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('5', 'sla', 'dayx7', 'Dx7: Daylight - All days');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('6', 'sla', '24x5',  '24x5: All times - Labour days');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('7', 'sla', '24x7',  '24x7: All times - days');");

  return $items;
}

function guifi_update_1003() {

  $items = array();

  db_create_table($items, 'guifi_maintainers',
    array(
    'fields' => array(
      'id' => array('type' => 'int', 'not null'  => TRUE, 'default' => 0, 'disp-width' => '11'),
      'supplier_id' => array('type' => 'int', 'unsigned' => TRUE, 'not null'  => TRUE, 'disp-width' => '11', 'default' => 0, 'comment' => 'Foreign key to supplier'),
      'subject_id' => array('type' => 'int', 'unsigned' => TRUE, 'not null'  => TRUE, 'disp-width' => '11', 'default' => 0, 'comment' => 'Foreign key to subject maintained (zone/node/device...)'),
      'subject_type' => array('type' => 'varchar', 'length' => 15, 'not null'  => TRUE, 'default' => '', 'comment' => 'subject type (values zone, node, device....)'),
      'commitment' => array('type' => 'varchar', 'length' => 15, 'not null'  => TRUE, 'default' => '', 'comment' => 'type of commitment: volunteer, FO, Wireless...'),
      'sla' => array('type' => 'varchar', 'length' => 15, 'not null'  => TRUE, 'default' => '', 'comment' => 'SLA: bone, 24x7, 8x5...'),
      'sla_resp' => array('type' => 'int', 'size'=>'small','not null'  => FALSE, 'comment' => 'Objective for response time' ),
      'sla_fix' => array('type' => 'int', 'size'=>'small','not null'  => FALSE,  'comment' => 'Objective for solving time' ),
      'weight' => array('type' => 'int', 'size'=>'small','not null'  => FALSE),
      'comment' => array('type' => 'text', 'not null'  => false),
      'user_created' => array('type' => 'int', 'size' => 'medium', 'not null' => TRUE, 'default' => 0),
      'user_changed' => array('type' => 'int', 'size' => 'medium',' not null' => FALSE),
      'timestamp_created' => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
      'timestamp_changed' => array('type' => 'int', 'not null' => FALSE),
      ),
    'primary key' => array('id'),
    )
  );

  return $items;

}

function guifi_update_1006() {

  $items = array();

  db_create_table($items, 'guifi_funders',
    array(
    'fields' => array(
      'id' => array('type' => 'int', 'not null'  => TRUE, 'default' => 0, 'disp-width' => '11'),
      'supplier_id' => array('type' => 'int', 'unsigned' => TRUE, 'not null'  => false, 'disp-width' => '11', 'default' => 0, 'comment' => 'Foreign key to supplier'),
      'user_id' => array('type' => 'int', 'size' => 'medium', 'unsigned' => TRUE, 'not null'  => false, 'disp-width' => '11', 'default' => 0, 'comment' => 'Foreign key to userid'),
      'comment' => array('type' => 'text', 'not null'  => TRUE),
//      'removed' => array('type' => 'int', 'size'=>'small','not null'  => TRUE, 'default' => FALSE),
      'subject_id' => array('type' => 'int', 'unsigned' => TRUE, 'not null'  => TRUE, 'disp-width' => '11', 'default' => 0, 'comment' => 'Foreign key to subject maintained (node/device...)'),
      'subject_type' => array('type' => 'varchar', 'length' => 15, 'not null'  => TRUE, 'default' => '', 'comment' => 'subject type (values node, device....)'),
      'weight' => array('type' => 'int', 'size'=>'small','not null'  => FALSE),
      'user_created' => array('type' => 'int', 'size' => 'medium', 'not null' => TRUE, 'default' => 0),
      'user_changed' => array('type' => 'int', 'size' => 'medium',' not null' => FALSE),
      'timestamp_created' => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
      'timestamp_changed' => array('type' => 'int', 'not null' => FALSE),
      ),
    'primary key' => array('id'),
    )
  );

  return $items;

}

function guifi_update_1007() {

  $items = array();

  db_add_field($items,'guifi_radios','fund_required',
      array('comment' => 'Null n/d, free, yes', 'type' => 'varchar', 'length' => '5', 'not null' => FALSE));
  db_add_field($items,'guifi_radios','fund_amount',
      array('type' => 'numeric', 'not null'  => TRUE, 'default' => 0, 'precision' => '10', 'scale' => '2'));
  db_add_field($items,'guifi_radios','fund_currency',
      array('type' => 'varchar', 'length' => '10', 'not null'  => TRUE, 'default' => 'Euros'));

  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('1', 'contribution', '',  'Not defined, contact admins');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('2', 'contribution', 'free',   'Free - No funding required');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('3', 'contribution', 'yes', 'Funding required');");

  return $items;

}

function guifi_update_1008() {

  $items = array();


  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('13', 'device', 'fomconv', 'FO Media Converter');");

  return $items;

}

function guifi_update_1009() {

  $items = array();


  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('14', 'device', 'torpedo', 'Torpedo - Joint enclosure');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('15', 'device', 'switch', 'Switch');");
  db_query("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('16', 'device', 'rack', 'Rack');");

  return $items;

}

function guifi_update_1013() {

  $items = array();

  $items[] = update_sql("RENAME TABLE {guifi_model} TO {guifi_model_specs};");

  return $items;

}

function guifi_update_1018() {

  $items = array();

  $items[] = update_sql("ALTER TABLE guifi_model_specs " .
  		"ADD COLUMN model_class VARCHAR(240) DEFAULT 'wireless|router' COMMENT 'device category (radio, router, switch...)' NOT NULL AFTER model, " .
  		"ADD COLUMN optoports_max TINYINT(2) DEFAULT 0 COMMENT 'device category (radio, router, switch...)' NOT NULL AFTER etherdev_max, " .
  		"ADD COLUMN rackeable TINYINT(2) DEFAULT 0 COMMENT 'Rackeable (>0 true, # of Us))' NOT NULL AFTER optoports_max, " .
  		"ADD COLUMN opto_interfaces VARCHAR(240) NULL COMMENT 'map to ethernet interafaces' AFTER interfaces;");

  return $items;

}

function guifi_update_1019() {

  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES " .
  		"('1', 'fo_port', 'LX', '10km 1310nm SM duplex')," .
  		"('2', 'fo_port', 'EX', '40km 1310nm SM duplex')," .
  		"('3', 'fo_port', 'XD', '40km 1550nm SM duplex')," .
  		"('4', 'fo_port', 'ZX', '80km 1550nm SM duplex')," .
  		"('5', 'fo_port', 'EZX', '120km 1550nm SM duplex')," .
  		"('6', 'fo_port', 'BX', '10km 1550nm SM single fiber')," .
  		"('7', 'fo_port', 'CWDM', 'Various wavelengths single fiber')," .
  		"('8', 'fo_port', 'SFSW', 'CWDM coupled Single wavelength')," .
  		"('9', 'fo_port', 'SX', '850nm MM duplex')" .
  		";");

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES " .
  		"('1', 'fo_conn', 'fusion', 'fusion')," .
  		"('2', 'fo_conn', 'SC', 'SC connector')," .
  		"('3', 'fo_conn', 'LC', 'LC Connector')" .
  		";");

  return $items;

}

function guifi_update_1020() {

  $items = array();


  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES " .
  		"('17', 'device', 'PPanel','Patch panel');");

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES " .
  		"('1', 'model_class', 'wireless', 'wireless')," .
  		"('2', 'model_class', 'router', 'router')," .
  		"('3', 'model_class', 'fiber', 'fiber optics')," .
  		"('4', 'model_class', 'switch', 'switch or hub')" .
  		";");

  return $items;

}

function guifi_update_1025() {

  $items = array();

  $items[] = update_sql("UPDATE {guifi_types} SET text=LOWER(text) WHERE text IN ('OLT','ONU','FOMConv','PPanel','Switch','Rack','Torpedo');");
  $items[] = update_sql("UPDATE {guifi_devices} SET type=LOWER(type) WHERE type IN ('OLT','ONU','FOMConv','PPanel','Switch','Rack','Torpedo');");

  return $items;

}

function guifi_update_1027() {

  $items = array();

  $items[] = update_sql("DELETE FROM {guifi_types} WHERE type = 'fo_port'");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES " .
  		"('1', 'fo_port', 'LX',  'LX 10km 1310nm SM duplex')," .
  		"('2', 'fo_port', 'EX',  'EX 40km 1310nm SM duplex')," .
  		"('3', 'fo_port', 'XD',  'XD 40km 1550nm SM duplex')," .
  		"('4', 'fo_port', 'ZX',  'ZX 80km 1550nm SM duplex')," .
  		"('5', 'fo_port', 'EZX', 'EZX 120km 1550nm SM duplex')," .
  		"('6', 'fo_port', 'BX',  'BX 10km 1550nm SM single')," .
  		"('7', 'fo_port', 'CWDM','CWDM Various Wavelengths')," .
  		"('8', 'fo_port', 'SFSW','SFSW Single Wlength to CWDM')," .
  		"('9', 'fo_port', 'SX',  'SX 850nm MM duplex')" .
  		";");

  return $items;

}

function guifi_update_1030() {

  $items = array();

  $items[] = update_sql("ALTER TABLE {guifi_interfaces} " .
  		"ADD COLUMN connector_type VARCHAR(10) DEFAULT NULL COMMENT 'connector type (RJ45,FO LX,SC...)' AFTER mac," .
  		"ADD COLUMN vlan VARCHAR(10) DEFAULT NULL COMMENT 'vlan (if have)'," .
  		"ADD COLUMN comments VARCHAR(64) DEFAULT NULL COMMENT 'Additional info/comments'," .
  		"ADD COLUMN connto_did MEDIUMINT(9) DEFAULT NULL COMMENT 'connected to (device)'," .
  		"ADD COLUMN connto_iid MEDIUMINT(9) DEFAULT NULL COMMENT 'connected to (interface)'");
  return $items;

}

function guifi_update_1031() {

  $items = array();

  $items[] = update_sql("ALTER TABLE {guifi_interfaces} " .
  		"ADD COLUMN interface_class VARCHAR(40) DEFAULT NULL COMMENT 'radio, ethernet, bridge, bonding, vlan, wds/p2p, virtualAP, tunnels ...' AFTER etherdev_counter," .
  		"ADD COLUMN related_interfaces VARCHAR(120) DEFAULT NULL COMMENT 'FK to parent interfaces (vlans, bondings, bridges...)' AFTER interface_type");

  return $items;

}

function guifi_update_1032() {

  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES " .
  		"('18', 'device', 'ups', 'Uninterrumpible Power Supply (UPS)')," .
  		"('19', 'device', 'generator', 'Power generator source')," .
  		"('20', 'device', 'solar', 'Solar Panel')," .
  		"('21', 'device', 'battery', 'Battery')," .
  		"('22', 'device', 'breaker','Circuit breakers / Surge protectors')");

  return $items;

}

function guifi_update_1035() {

  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES " .
  		"('1', 'vlan', 'vlan',    'Virtual Lan','all')," .
  		"('2', 'vlan', 'vap',     'Virtual AP','radio')," .
        "('3', 'vlan', 'wds/p2p', 'WDS PtP link','all')," .
        "('4', 'vlan', 'vrrp',    'VRRP','all')," .

        "('1', 'tunnel', 'pptp', 'PPTP','all')," .
  		"('2', 'tunnel', 'sstp', 'SSTP','all')," .
  		"('3', 'tunnel', 'sstp', 'SSTP','all')," .
        "('4', 'tunnel', 'pppoe', 'PPPoE','all')," .
        "('5', 'tunnel', 'ovpn', 'Open VPN','all')," .
        "('6', 'tunnel', 'eoip', 'EoIP','all')," .
        "('7', 'tunnel', 'ipip', 'IPIP','all')," .
        "('8', 'tunnel', 'gre', 'GRE','all')," .

  		"('1', 'aggregation', 'bridge', 'Bridge','all')," .
  		"('2', 'aggregation', '802.3ad', 'Bonding 802.3ad','all')," .
  		"('3', 'aggregation', 'abackup', 'Bonding Active Backup','all')," .
        "('4', 'aggregation', 'balb', 'Bonding Balanced Alb','all')," .
        "('5', 'aggregation', 'brr', 'Bonding Balanced RR','all')," .
        "('6', 'aggregation', 'btlb', 'Bonding Balanced TLB','all')," .
        "('7', 'aggregation', 'bxor', 'Bonding Balanced XOR','all')," .
        "('8', 'aggregation', 'bbroadcast', 'Bonding Broadcast','all'),".
        "('9', 'aggregation', 'ndual', 'NStreme Dual','radio')");

  return $items;

}

function guifi_update_1036() {
  $items = array();

  db_add_field($items,'guifi_firmware','managed',
    array('type' => 'varchar', 'length' => '100'));

  return $items;
}

function guifi_update_1037() {
  $items = array();

  db_add_field($items,'guifi_devices','mainipv4',
    array('type' => 'varchar', 'length' => '20'));
  db_add_field($items,'guifi_radios','mac',
    array('type' => 'varchar', 'length' => '20'));

  return $items;
}

function guifi_update_1038() {
  $items = array();

  $items[] = update_sql("DELETE FROM {guifi_types} WHERE id = 3 AND type = 'vlan'");
  $items[] = update_sql("UPDATE {guifi_types} SET id = 3 WHERE id = 4 AND type = 'vlan'");

  return $items;
}

function guifi_update_1039() {
  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('7', 'protocol', '802.11ac', '802.11ac')");
  $items[] = update_sql("UPDATE {guifi_types} SET relations = REPLACE (relations,'802.11a|802.11n', '802.11a|802.11n|802.11ac')");

  return $items;
}

function guifi_update_1040() {
  $items = array();

  $items[] = update_sql("ALTER TABLE guifi_model_specs " .
  		"ADD COLUMN winterfaces VARCHAR(240) NULL COMMENT 'wireless interafaces' AFTER interfaces;");

  return $items;
}

function guifi_update_1041() {
  $items = array();

  $items[] = update_sql("UPDATE {guifi_types} SET id = 4 WHERE id = 3 AND type = 'vlan'");
  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES ('3', 'vlan', 'wds/p2p', 'WDS PtP link', 'all')");

  return $items;
}

function guifi_update_1042() {
  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES ('23', 'device', 'cloudy', 'Server with Cloudy distribution based on Debian GNU/Linux')");

  return $items;
}

function guifi_update_1043() {
  $items = array();

  $items[] = update_sql("ALTER TABLE guifi_radios ADD COLUMN chbandwith VARCHAR(8) NOT NULL DEFAULT '20Mhz' COMMENT 'Channel Bandwith' AFTER channel;");

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES " .
                "('1', 'chbandwith', '5Mhz', '5Mhz')," .
                "('2', 'chbandwith', '7Mhz', '7Mhz')," .
                "('3', 'chbandwith', '8Mhz', '8Mhz')," .
                "('4', 'chbandwith', '10Mhz', '10Mhz')," .
                "('5', 'chbandwith', '14Mhz', '14Mhz')," .
                "('6', 'chbandwith', '20Mhz', '20Mhz')," .
                "('7', 'chbandwith', '28Mhz', '28Mhz')," .
                "('8', 'chbandwith', '30Mhz', '30Mhz')," .
                "('9', 'chbandwith', '40Mhz', '40Mhz')," .
                "('10', 'chbandwith', '50Mhz', '50Mhz')," .
                "('11', 'chbandwith', '60Mhz', '60Mhz')," .
                "('12', 'chbandwith', '70Mhz', '70Mhz')," .
                "('13', 'chbandwith', '80Mhz', '80Mhz');");

  return $items;
}

function guifi_update_1044() {
  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description) VALUES " .

                "('14', 'chbandwith', '160Mhz', '160Mhz');");

  return $items;
}

function guifi_update_1045() {
  $items = array();

  $items[] = update_sql("INSERT INTO {guifi_types} (id, type, text, description, relations) VALUES " .
                "('104', 'channel', '5745', '149 - 5745MHz', '802.11a|802.11n|802.11ac')," .
                "('105', 'channel', '5765', '153 - 5765MHz', '802.11a|802.11n|802.11ac')," .
                "('106', 'channel', '5785', '157 - 5785MHz', '802.11a|802.11n|802.11ac')," .
                "('107', 'channel', '5805', '161 - 5805MHz', '802.11a|802.11n|802.11ac')," .
                "('108', 'channel', '5825', '165 - 5825MHz', '802.11a|802.11n|802.11ac');");

  return $items;
}

function guifi_update_1046() {

  $items = array();

  $items[] = update_sql("ALTER TABLE {guifi_links} " .
    "ADD COLUMN hybrid BOOLEAN NOT NULL DEFAULT '0' AFTER flag");

  $items[] = update_sql("UPDATE {guifi_links} SET hybrid = FALSE");

  return $items;
}

function guifi_update_1047() {

  $items = array();

  $items[] = update_sql("ALTER TABLE {guifi_location} " .
    		"ADD COLUMN location_type VARCHAR(10) NOT NULL DEFAULT 'node' AFTER zone_description," .
    		"ADD COLUMN project_id INT(11) DEFAULT NULL AFTER status_flag;");

  $items[] = update_sql("ALTER TABLE guifi_location ADD INDEX project_index    USING BTREE (project_id);");

  return $items;

}

?>
