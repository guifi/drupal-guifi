TRUNCATE `access`;
TRUNCATE `cache`;
TRUNCATE `cache_block`;
TRUNCATE `cache_filter`;
TRUNCATE `cache_form`;
TRUNCATE `cache_menu`;
TRUNCATE `cache_page`;
TRUNCATE `cache_update`;
TRUNCATE `sessions`;
TRUNCATE `watchdog`;

-- 
-- Dumping data for table `node`
-- 

INSERT INTO `node` (`nid`, `vid`, `type`, `language`, `title`, `uid`, `status`, `created`, `changed`, `comment`, `promote`, `moderate`, `sticky`, `tnid`, `translate`) VALUES 
(1, 1, 'page', '', 'Portada', 1, 1, 1209392700, 1209392860, 0, 1, 0, 0, 0, 0),
-- The World
(2, 2, 'guifi_zone', '', 'Guifi.net World', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
-- Countries
(3, 3, 'guifi_zone', '', 'Catalunya', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(4, 4, 'guifi_zone', '', 'Andorra', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
-- SubZones
(5, 5, 'guifi_zone', '', 'Osona', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(6, 6, 'guifi_zone', '', 'Lluçanes', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(7, 7, 'guifi_zone', '', 'Parròquia Andorra la Vella', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(8, 8, 'guifi_zone', '', 'Parròquia Canillo', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
-- Cities
(9, 9, 'guifi_zone', '', 'Vic', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(10, 10, 'guifi_zone', '', 'Torelló', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(11, 11, 'guifi_zone', '', 'Seva', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(12, 12, 'guifi_zone', '', 'Prats de Lluçanes', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(13, 13, 'guifi_zone', '', 'Lluçà', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(14, 14, 'guifi_zone', '', 'Andorra la Vella', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(15, 15, 'guifi_zone', '', 'Sant Julià de Llòria', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(16, 16, 'guifi_zone', '', 'Canillo', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
-- Nodes
(17, 17, 'guifi_node', '', 'VicPlaçaMajor', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(18, 18, 'guifi_node', '', 'VicZonaNord', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(19, 19, 'guifi_node', '', 'TorellóEstació', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(20, 20, 'guifi_node', '', 'TorellóDiposits', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(21, 21, 'guifi_node', '', 'SevaCentre', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(22, 22, 'guifi_node', '', 'AndorraCentre', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(23, 23, 'guifi_node', '', 'AndorraEst', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0),
(24, 24, 'guifi_node', '', 'CanilloPalauDelGel', 1, 1, 1211229461, 1211229461, 2, 1, 0, 0, 0, 0);

-- 
-- Dumping data for table `url_alias`
-- 

INSERT INTO `url_alias` (`pid`, `src`, `dst`, `language`) VALUES 
(1, 'node/1', 'Portada', ''),
(2, 'node/2', 'guifi_zones', ''),
(3, 'node/3', 'catalunya', ''),
(4, 'node/4', 'andorra', ''),
(5, 'node/5', 'osona', ''),
(6, 'node/6', 'llucanes', ''),
(7, 'node/7', 'parroquiandorra', ''),
(8, 'node/8', 'parroquiacanillo', ''),
(9, 'node/9', 'vic', ''),
(10, 'node/10', 'torello', ''),
(11, 'node/11', 'seva', ''),
(12, 'node/12', 'pratsdellucanes', ''),
(13, 'node/13', 'lluca', ''),
(14, 'node/14', 'andorralavella', ''),
(15, 'node/15', 'stjuliadelloria', ''),
(16, 'node/16', 'canillo', ''),
(17, 'node/17', 'vicplaca', ''),
(18, 'node/18', 'viczonanord', ''),
(19, 'node/19', 'torelloestacio', ''),
(20, 'node/20', 'torellodiposits', ''),
(21, 'node/21', 'sevacentre', ''),
(22, 'node/22', 'andorracentre', ''),
(23, 'node/23', 'andorraest', ''),
(24, 'node/24', 'canillopalau', '');

-- 
-- Dumping data for table `variable`
-- 

UPDATE `variable` SET `name` = 'site_frontpage', `value` = 's:7:"Portada";' WHERE  CONVERT(`variable`.`name` USING utf8) = 'site_frontpage';

-- 
-- Dumping data for table `guifi_zone`
-- 

INSERT INTO `guifi_zone` (`id`, `title`, `nick`, `body`, `master`, `time_zone`, `dns_servers`, `ntp_servers`, `mrtg_servers`, `graph_server`, `image`, `map_coord`, `map_poly`, `homepage`, `notification`, `ospf_zone`, `minx`, `miny`, `maxx`, `maxy`, `local`, `nodexchange_url`, `refresh`, `remote_server_id`, `weight`, `valid`, `user_created`, `user_changed`, `timestamp_created`, `timestamp_changed`) VALUES 
(2, 'Guifi.net World', 'world', 'the world xD', 0, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', -106.171875, -56.559482, 62.578125, 82.765373, 'Yes', NULL, NULL, NULL, 0, 0, 0, 1, 1211229461, 0),
(3, 'Catalunya', 'catalunya', 'Catalunya/País', 2, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 0.098877, 40.354917, 3.680420, 43.028745, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(4, 'Andorra', 'andorra', 'Andorra/País', 2, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 1.407623, 42.426498, 1.804504, 42.658202, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(5, 'Osona', 'osona', 'La comarca d''Osona!', 3, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 2.062683, 41.787697, 2.427979, 42.114524, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(6, 'Lluçanes', 'lluçanes', '', 3, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 1.903381, 41.895122, 2.195206, 42.094656, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(7, 'Parròquia Andorra la Vella', 'parandorra', 'Parròquia d''Andorra la Vella', 4, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 1.443329, 42.451835, 1.578598, 42.533856, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(8, 'Parròquia Canillo', 'parcanillo', 'Parroquia de Canillo', 4, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 1.570702, 42.544228, 1.671124, 42.583801, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(9, 'Vic', 'Vic', 'Ciutat de vic', 5, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 2.220612, 41.907643, 2.289963, 41.960256, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(10, 'Torelló', 'torello', 'Vila de Torelló', 5, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 2.242928, 42.037437, 2.279663, 42.057450, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(11, 'Seva', 'seva', '', 5, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 2.264729, 41.830049, 2.290306, 41.842583, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(12, 'Prats de Lluçanes', 'prats', 'Vila Prats de Lluçanès', 6, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '',  2.021914, 41.998922, 2.040710, 42.018756, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(13, 'Lluçà', 'lluça', 'Vila de Lluçà', 6, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 1.994019, 42.024814, 2.091522, 42.076565, 'Yes', NULL, NULL, NULL, 0, 0, 0, 1, 1211229461, 0),
(14, 'Andorra la Vella', 'andlavella', 'Ciutat Andorra la Vella', 7, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 1.506500, 42.497415, 1.552935, 42.517094, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(15, 'Sant Julià de Llòria', 'andstjulia', 'Poble de Sant Julià de Llòria', 7, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 1.485729, 42.459750, 1.496716, 42.470676, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0),
(16, 'Canillo', 'canillo', 'Poble de Canillo', 8, '+01 2 2', '', '', '', '0', '', '', '', '', 'zonestest@guifitest.xd', '', 1.591451, 42.563654, 1.602502, 42.568506, 'Yes', NULL, NULL, NULL, 0, 0, 0, 0, 1211229461, 0);

-- 
-- Dumping data for table `node_revisions`
-- 

INSERT INTO `node_revisions` (`nid`, `vid`, `uid`, `title`, `body`, `teaser`, `log`, `timestamp`, `format`) VALUES 
(1, 1, 1, 'Portada', 'This is the front page!\r\n\r\nNow, you can browse all Zones and nodes examples:\r\n <a href="guifi_zones">Guifi.net World</a>\r\n<ul>\r\n   <li><a  href="catalunya">Catalunya</a>\r\n         <ul>\r\n            <li><a  href="osona">Osona</a>\r\n                   <ul>\r\n                         <li><a  href="vic">Vic</a>\r\n                              <ul>\r\n                                 <li><a  href="vicplaca">NODE VicPlaçaMajor</a></li>\r\n                                 <li><a  href="viczonanord">NODE VicZonaNord</a></li>\r\n                               </ul>\r\n                         </li>\r\n                         <li><a  href="torello">Torelló</a>\r\n                              <ul>\r\n                                 <li><a  href="torelloestacio">NODE TorelloEstació</a></li>\r\n                                 <li><a  href="torellodiposits">NODE TorelloDiposits</a></li>\r\n                               </ul>\r\n                         </li>\r\n                          <li><a  href="seva">Seva</a>\r\n                              <ul>\r\n                                 <li><a  href="sevacentre">NODE SevaCentre</a></li>\r\n                               </ul>\r\n                         </li>\r\n                   </ul>\r\n            </li>\r\n            <li><a  href="lluçanes">Lluçanès</a>\r\n                  <ul>\r\n                         <li><a  href="prats">Prats de Lluçanès</a></li>\r\n                         <li><a  href="lluça">Lluça</a></li>\r\n                   </ul>\r\n            </li>\r\n          </ul>\r\n   </li>\r\n   <li><a  href="andorra">Andorra</a>\r\n         <ul>\r\n            <li><a  href="parroquiandorra">Parròquia d''Andorra la Vella</a>\r\n                   <ul>\r\n                         <li><a  href="andlavella">Andorra la Vella</a>\r\n                              <ul>\r\n                                 <li><a  href="andorracentre">NODE AndorraCentre</a></li>\r\n                                 <li><a  href="andorraest">NODE AndorraEst</a></li>\r\n                               </ul>\r\n                         </li>\r\n                         <li><a  href="andstjulia">Sant Julià de Lòria</a></li>\r\n                   </ul>\r\n            </li>\r\n            <li><a  href="parroquiacanillo">Parròquia de Canillo</a>\r\n                  <ul>\r\n                         <li><a  href="canillo">Canillo</a>                              \r\n                                <ul>\r\n                                 <li><a  href="canillopalau">NODE CanilloPalauDelGel</a></li>\r\n                               </ul>\r\n                          </li>\r\n                   </ul>\r\n            </li>\r\n          </ul>\r\n      </li>\r\n</ul>\r\n', 'This is the front page!\r\n\r\nNow, you can browse all Zones and nodes examples:\r\n <a  href="guifi_zones">Guifi.net World</a>\r\n<ul>\r\n   <li><a  href="catalunya">Catalunya</a>\r\n         <ul>\r\n            <li><a  href="osona">Osona</a>\r\n                   <ul>\r\n                         <li><a  href="vic">Vic</a>\r\n                              <ul>\r\n                                 <li><a  href="vicplaca">NODE VicPlaçaMajor</a></li>\r\n                                 <li><a  href="viczonanord">NODE VicZonaNord</a></li>\r\n                               </ul>\r\n                         </li>\r\n                         <li><a  href="torello">Torelló</a>\r\n                              <ul>\r\n                                 <li><a  href="torelloestacio">NODE TorelloEstació</a></li>\r\n                                 <li><a  href="torellodiposits">NODE TorelloDiposits</a></li>\r\n                               </ul>\r\n                         </li>\r\n                          <li><a  href="seva">Seva</a>\r\n                              <ul>\r\n                                 <li><a  href="sevacentre">NODE SevaCentre</a></li>\r\n                               </ul>\r\n                         </li>\r\n                   </ul>\r\n            </li>\r\n            <li><a  href="lluçanes">Lluçanès</a>\r\n                  <ul>\r\n                         <li><a  href="prats">Prats de Lluçanès</a></li>\r\n                         <li><a  href="lluça">Lluça</a></li>\r\n                   </ul>\r\n            </li>\r\n          </ul>\r\n   </li>\r\n   <li><a  href="andorra">Andorra</a>\r\n         <ul>\r\n            <li><a  href="parroquiandorra">Parròquia d''Andorra la Vella</a>\r\n                   <ul>\r\n                         <li><a  href="andlavella">Andorra la Vella</a>\r\n                              <ul>\r\n                                 <li><a  href="andorracentre">NODE AndorraCentre</a></li>\r\n                                 <li><a  href="andorraest">NODE AndorraEst</a></li>\r\n                               </ul>\r\n                         </li>\r\n                         <li><a  href="andstjulia">Sant Julià de Lòria</a></li>\r\n                   </ul>\r\n            </li>\r\n            <li><a  href="parroquiacanillo">Parròquia de Canillo</a>\r\n                  <ul>\r\n                         <li><a  href="canillo">Canillo</a>                              \r\n                                <ul>\r\n                                 <li><a  href="canillopalau">NODE CanilloPalauDelGel</a></li>\r\n                               </ul>\r\n                          </li>\r\n                   </ul>\r\n            </li>\r\n          </ul>\r\n      </li>\r\n</ul>\r\n', '', 1209392860, 1),
(2, 2, 1, 'Guifi.net World', 'the world xD', 'the world xD', '', 1211229461, 0),
(3, 3, 1, 'Catalunya', 'Catalunya/País', 'Catalunya/País', '', 1211229461, 0),
(4, 4, 1, 'Andorra', 'Andorra/País', 'Andorra/País', '', 1211229461, 0),
(5, 5, 1, 'Osona', 'La comarca d''Osona!', 'La comarca d''Osona', '', 1211229461, 0),
(6, 6, 1, 'Lluçanes', 'El Lluçanès', 'El Lluçanès', '', 1211229461, 0),
(7, 7, 1, 'Parròquia Andorra la Vella', 'Parròquia d''Andorra la Vella', 'Ciutat Andorra la Vella', '', 1211229461, 0),
(8, 8, 1, 'Parròquia Canillo', 'Parròquia de Canillo', 'Poble de Canillo', '', 1211229461, 0),
(9, 9, 1, 'Vic', 'Ciutat de Vic', 'Ciutat de Vic', '', 1211229461, 0),
(10, 10, 1, 'Torelló', 'Vila de Torelló', 'Vila de Torelló', '', 1211229461, 0),
(11, 11, 1, 'Seva', 'Vila de Seva', 'Vila de Seva', '', 1211229461, 0),
(12, 12, 1, 'Prats de Lluçanes', 'Vila Prats de Lluçanès', 'Vila Prats de Lluçanès', '', 1211229461, 0),
(13, 13, 1, 'Lluçà', 'Vila de Lluçà', 'Vila de Lluçà', '', 1211229461, 0),
(14, 14, 1, 'Andorra la Vella', 'Ciutat Andorra la Vella', 'Ciutat Andorra la Vella', '', 1211229461, 0),
(15, 15, 1, 'Sant Julià de Llòria', 'Poble de Sant Julià de Llòria', 'Poble de Sant Julià de Llòria', '', 1211229461, 0),
(16, 16, 1, 'Canillo', 'Poble de Canillo', 'Poble de Canillo', '', 1211229461, 0),
(17, 17, 1, 'VicPlaçaMajor', 'Node al centre de Vic!', 'Node al centre de Vic!', '', 1211229461, 0),
(18, 18, 1, 'VicZonaNord', 'Node a la Zona nord de Vic', 'Node a la Zona nord de Vic', '', 1211229461, 0),
(19, 19, 1, 'TorellóEstació', 'Node a l''estació de Torello!', 'Node a l''estació de Torello!', '', 1211229461, 0),
(20, 20, 1, 'TorellóDiposits', 'Node als dipòsits de Torelló!', 'Node als dipòsits de Torelló!', '', 1211229461, 0),
(21, 21, 1, 'SevaCentre', 'Node al centre de Seva!', 'Node al centre de Seva!', '', 1211229461, 0),
(22, 22, 1, 'AndorraCentre', 'Node al centre d''Andorra la Vella!', 'Node al centre d''Andorra la Vella!', '', 1211229461, 0),
(23, 23, 1, 'AndorraEst', 'Node a l''est d''Andorra la Vella!', 'Node a l''est d''Andorra la Vella!', '', 1211229461, 0),
(24, 24, 1, 'CanilloPalauDelGel', 'PoliesPortiu d''Andorra!', 'PoliesPortiu d''Andorra!', '', 1211229461, 0);

-- 
-- Dumping data for table `node_comment_statistics`
-- 

INSERT INTO `node_comment_statistics` (`nid`, `last_comment_timestamp`, `last_comment_name`, `last_comment_uid`, `comment_count`) VALUES 
(1, 1211229461, NULL, 1, 0),
(2, 1211229461, NULL, 1, 0),
(3, 1211229461, NULL, 1, 0),
(4, 1211229461, NULL, 1, 0),
(5, 1211229461, NULL, 1, 0),
(6, 1211229461, NULL, 1, 0),
(7, 1211229461, NULL, 1, 0),
(8, 1211229461, NULL, 1, 0),
(9, 1211229461, NULL, 1, 0),
(10, 1211229461, NULL, 1, 0),
(11, 1211229461, NULL, 1, 0),
(12, 1211229461, NULL, 1, 0),
(13, 1211229461, NULL, 1, 0),
(14, 1211229461, NULL, 1, 0),
(15, 1211229461, NULL, 1, 0),
(16, 1211229461, NULL, 1, 0),
(17, 1211229461, NULL, 1, 0),
(18, 1211229461, NULL, 1, 0),
(19, 1211229461, NULL, 1, 0),
(20, 1211229461, NULL, 1, 0),
(21, 1211229461, NULL, 1, 0),
(22, 1211229461, NULL, 1, 0),
(23, 1211229461, NULL, 1, 0),
(24, 1211229461, NULL, 1, 0);
-- 
-- Dumping data for table `guifi_networks`
-- 

INSERT INTO `guifi_networks` (`id`, `base`, `mask`, `zone`, `network_type`, `user_created`, `user_changed`, `timestamp_created`, `timestamp_changed`, `valid`) VALUES 
(1, '10.0.0.0', '255.0.0.0', 2, 'public', 1, 1, 1211229461, 0, 1),
(2, '172.16.0.0', '255.240.0.0', 2, 'backbone', 1, 1, 1211229461, 0, 1),
(3, '10.138.0.0', '255.254.0.0', 3, 'public', 1, 1, 1211229461, 0, 1),
(4, '172.16.0.0', '255.255.0.0', 3, 'backbone', 1, 0, 1211229461, 0, 1),
(5, '10.140.0.0', '255.254.0.0', 4, 'public', 1, 0, 1211229461, 0, 1),
(6, '172.32.0.0', '255.255.0.0', 4, 'backbone', 1, 0, 1211229461, 0, 1),
(7, '10.138.0.0', '255.255.0.0', 5, 'public', 1, 0, 1211229461, 0, 1), -- 10.138.0.0/16 and 172.16.0.0/20
(8, '172.16.0.0', '255.255.240.0', 5, 'backbone', 1, 0, 1211229461, 0, 1), 
(9, '10.138.16.0', '255.255.0.0', 6, 'public', 1, 0, 1211229461, 0, 1),--  10.138.16.0/20 and 172.16.16.0/20
(10, '172.16.16.0', '255.255.240.0', 6, 'backbone', 1, 0, 1211229461, 0, 1),
(11, '10.140.0.0', '255.255.0.0', 7, 'public', 1, 0, 1211229461, 0, 1), -- 10.140.0.0/16 and 172.32.0.0/20
(12, '172.32.0.0', '255.255.240.0', 7, 'backbone', 1, 0, 1211229461, 0, 1), 
(13, '10.140.2.0', '255.255.0.0', 8, 'public', 1, 0, 1211229461, 0, 1),--  10.140.2.0/16 and 172.32.16.0/20
(14, '172.32.16.0', '255.255.240.0', 8, 'backbone', 1, 0, 1211229461, 0, 1),
(15, '10.138.0.0', '255.255.254.0', 9, 'public', 1, 0, 1211229461, 0, 1),--  10.138.0.0/23 and 172.16.0.0/24
(16, '172.16.0.0', '255.255.255.0', 9, 'backbone', 1, 0, 1211229461, 0, 1),
(17, '10.138.2.0', '255.255.254.0', 10, 'public', 1, 0, 1211229461, 0, 1),--  10.138.2.0/23 and 172.16.1.0/24
(18, '172.16.1.0', '255.255.255.0', 10, 'backbone', 1, 0, 1211229461, 0, 1),
(19, '10.138.4.0', '255.255.254.0', 11, 'public', 1, 0, 1211229461, 0, 1),--  10.138.4.0/23 and 172.16.2.0/24
(20, '172.16.2.0', '255.255.255.0', 11, 'backbone', 1, 0, 1211229461, 0, 1),
(21, '10.138.16.0', '255.255.254.0', 12, 'public', 1, 0, 1211229461, 0, 1),--  10.138.16.0/23 and 172.16.16.0/24
(22, '172.16.16.0', '255.255.255.0', 12, 'backbone', 1, 0, 1211229461, 0, 1),
(23, '10.138.18.0', '255.255.254.0', 13, 'public', 1, 0, 1211229461, 0, 1),--  10.138.18.0/23 and 172.16.16.0/24
(24, '172.16.17.0', '255.255.255.0', 13, 'backbone', 1, 0, 1211229461, 0, 1),
(25, '10.140.0.0', '255.255.254.0', 14, 'public', 1, 0, 1211229461, 0, 1),--  10.140.0.0/23 and 172.32.0.0/24
(26, '172.32.0.0', '255.255.255.0', 14, 'backbone', 1, 0, 1211229461, 0, 1),
(27, '10.140.2.0', '255.255.254.0', 15, 'public', 1, 0, 1211229461, 0, 1),--  10.140.2.0/23 and 172.32.1.0/24
(28, '172.32.1.0', '255.255.255.0', 15, 'backbone', 1, 0, 1211229461, 0, 1),
(29, '10.141.0.0', '255.255.254.0', 16, 'public', 1, 0, 1211229461, 0, 1),--  10.141.0.0/23 and 172.32.16.0/24
(30, '172.32.16.0', '255.255.255.0', 16, 'backbone', 1, 0, 1211229461, 0, 1);

--
-- Dumping data for table `guifi_location`
--

INSERT INTO `guifi_location` (`id`, `nick`, `zone_id`, `zone_description`, `lat`, `lon`, `elevation`, `notification`, `status_flag`, `stable`, `graph_server`, `user_created`, `user_changed`, `timestamp_created`, `timestamp_changed`) VALUES
(17, 'VicPlaçaMajor', 9, 'Plaça Major de Vic', 41.930410, 2.254331, 15, 'zonestest@guifitest.xd', 'Planned', 'Yes', '0', 0, NULL, 1211229461, NULL),
(18, 'VicZonaNord', 9, 'Seminari de Vic', 41.936498, 2.256995, 15, 'zonestest@guifitest.xd', 'Planned', 'Yes', '0', 0, NULL, 1211229461, NULL),
(19, 'TorelloEstacio', 10, 'Estació de Renfe', 42.052078, 2.259991, 15, 'zonestest@guifitest.xd', 'Planned', 'Yes', '0', 0, NULL, 1211229461, NULL),
(20, 'TorelloDiposits', 10, 'Zona dipòsits Sud', 42.040936, 2.268148, 15, 'zonestest@guifitest.xd', 'Planned', 'Yes', '0', 0, NULL, 1211229461, NULL),
(21, 'SevaCentre', 11, 'Centre de Seva', 41.837772, 2.282312, 15, 'zonestest@guifitest.xd', 'Planned', 'Yes', '0', 0, NULL, 1211229461, NULL),
(22, 'AndorraCentre', 14, 'Centre d''Andorra la Vella', 42.508006, 1.524305, 15, 'zonestest@guifitest.xd', 'Planned', 'Yes', '0', 0, NULL, 1211229461, NULL),
(23, 'AndorraEst', 14, 'Est d''Andorra la Vella', 42.506867, 1.525520, 15, 'zonestest@guifitest.xd', 'Planned', 'Yes', '0', 0, NULL, 1211229461, NULL),
(24, 'CanilloPalauDelGel', 16, 'Centre del poble', 42.566469, 1.598143, 15, 'zonestest@guifitest.xd', 'Planned', 'Yes', '0', 0, NULL, 1211229461, NULL);

