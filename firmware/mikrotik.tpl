# Generat per a:
# {{ firmware_name }}
:log info "Unsolclic for {{ dev.id }}-{{ dev.nick }} going to be executed."
#
# Configuration for {{ firmware_name }} and newer 4.x
# Trasto: {{  dev.id  }}-{{ dev.nick }}
#
# Methods to upload/execute this script:
# 1.-As a script. Upload this output as a script either with:
#     a.Winbox (with Linux, wine required)
#     b.Terminal (telnet, ssh...)
#    Then execute the script with:
#      > /system script run script_name
# 2.-Fitxer importat:
#     Desa aquesta "sortida" a un fitxer, després puja'l al router
#     fent servir FTP amb un nom de l'estil "script_name.rsc".
#     (note, l'extensió ".rsc" es un requisit)
#     Executa el fitxer importat amb la comanda:
#      > /import script_name
# 3.-Telnet copia i enganxar:
#     Open a terminal session, and cut&paste this output
#     directly on the terminal input.
#
# Notes:
# -routing-test package is required if you use RouterOSv2.9 , be sure you have it enabled at system packages
# -wlans should be enabled manually, be sure to set the correct antenna (a or b)
#   according in how did you connect the cable to the miniPCI. Keep the
#   power at the minimum possible and check the channel.
# -The script doesn't reset the router, you might have to do it manually
# -You must have write access to the router
# -MAC access (winbox, MAC telnet...) method is recommended
#   (the script reconfigures some IP addresses, so communication can be lost)
# -No changes are done in user passwords on the device
# -A Read Only guest account with no password will be created to allow guest access
#   to the router with no danger of damage but able to see the config.
# -Be sure that all packages are activated.
# -Don't run the script from telnet and being connected through an IP connection at
#   the wLan/Lan interface: This interface will be destroyed during the script. 
#
/ system identity set name={{ dev.nick }}
#
# DNS (client & server cache) zone: {{ all.zone_id }}
{% if all.secondarydns %}
/ip dns set servers={{ all.zone_dns_servers }},{{ all.secondarydns }} allow-remote-requests=yes
{% else %}
/ip dns set servers={{ all.zone_dns_servers }} allow-remote-requests=yes
{% endif %}
:delay 1
#
# NTP (client & server cache) zone:  {{ all.zone_id }}
{% if all.zone_ntp_servers %}
/system ntp client set enabled=yes mode=unicast primary-ntp={{ all.zone_ntp_servers }} secondary-ntp={{ all.zone_ntp_servers }}
{% else %}
/system ntp client set enabled=yes mode=unicast primary-ntp={{ all.zone_ntp_servers }}
{% endif %}
:delay 1
#
# Bandwidth-server
/ tool bandwidth-server set enabled=yes authenticate=no allocate-udp-ports-from=2000
#
# SNMP
/snmp set contact="guifi@guifi.net" enabled=yes location="{{ all.node_name }}"
#
# Guest user
/user
:foreach i in [find group=read] do={/user remove $i;} 
add name="guest" group=read address=0.0.0.0/0 comment="" disabled=no
#
# Graphing
/tool graphing interface add
{% if dev.logserver %}
# Ip for ServerLogs
/system logging
:foreach i in [/system logging find action=remote]
do={/system logging remove $i }
:foreach i in [/system logging action find name=guifi]
do=[/system logging action remove $i]
/system logging action add name={{ dev.nick }} target=remote remote={{ dev.logserver }}:514 src-address={{ dev.radios[0].ipv4}}
/system logging add action=guifi_remot topics=critical
/system logging add action=guifi_remot topics=account
{% endif %}
{% if dev.mode != 'client' %}
# Remove current wLan/Lan bridge if exists
:foreach i in [/interface bridge find name=wLan/Lan] \
do={:foreach i in [/interface bridge port find bridge=wLan/Lan] \
do={/interface bridge port remove $i; \
:foreach i in [/ip address find interface=wLan/Lan] \
do={/ip address remove $i;};};
/interface bridge remove $i;}
# Construct main bridge on wlan1 & ether1
/ interface bridge
add name="wLan/Lan"
/ interface bridge port
add interface=ether1 bridge=wLan/Lan
add interface=wlan1 bridge=wLan/Lan
{% endif %}

{% if dev.radios %}
  {% for radio in dev.radios %}
# Radio#: {{ radio.radiodev_counter }} {{ radio.ssid }}
{% spaceless %}
/interface wireless set wlan{{ radio.radiodev_counter+1 }} name="wlan{{ radio.radiodev_counter+1 }}" \
radio-name="{{ radio.ssid }}" mode={{ radio.mode }}-bridge ssid="guifi.net-{{ radio.ssid }}" \
{% if radio.mode == 'ap' %}
   {% set mode = 'ap-bridge' %}
    {% if radio.channel != 5000 %}
      {% set band = '2ghz-b' %}
      {% set chwidth = 'channel-width=20mhz' %}
    {% else %}
      {% set band = '5ghz-a' %}
      {% set chwidth = 'channel-width=20mhz' %}
    {% endif %}
{% elseif (radio.mode == 'client' or radio.mode == 'clientrouted') %}
   {% set mode = 'station' %}
    {% if radio.channel != 5000 %}
      {% set band = '2ghz-b' %}
      {% set chwidth = 'channel-width=20mhz' %}
    {% else %}
      {% set band = '5ghz-a' %}
      {% set chwidth = 'channel-width=20mhz' %}
    {% endif %}
{% endif %}
band="{{ band }}" {{ chwidth }} \
frequency-mode=regulatory-domain country=spain antenna-gain={{ radio.antenna_gain }} \
    {% set channel = radio.channel %}
    {% if radio.channel != 0 and radio.channel != 5000 %}
      {% if radio.channel < 20 %}
        {% set channel = 2047 + (radio.channel * 5) %}
      {% endif %}
      frequency={{ channel }}
    {% endif %}
    {% if ((radio.1.band == '5ghz' or '5ghz-a') and (channel == 5000 )) or
         ((radio.1.band == '2.4ghz-b' or '2ghz-b') and (channel == 0 )) %}
      dfs-mode=radar-detect \ 
    {% else %}
      dfs-mode=none \ 
    {% endif %}
    {% if radio.antenna_mode =='' %}
      wds-mode=static wds-default-bridge=none wds-default-cost=100 \
    {% else %}
      {% if radio.antenna_mode != 'Main'%}
         {% set antenna_mode = 'ant-b' %}
      {% else %}
         {% set antenna_mode = 'ant-b' %}
      {% endif %}
       antenna-mode={{ antenna_mode }} wds-mode=static wds-default-bridge=none wds-default-cost=100 \ 
    {% endif %}
      wds-cost-range=50-150 wds-ignore-ssid=yes hide-ssid=no
    {% for interface in radio.interfaces %}
:delay 1
# Type: {{ interface.interface_type }}
      {% if interface.interface_type == 'wds/p2p' %}
# Remove all existing wds interfaces
:foreach i in [/interface wireless wds find master-interface=wlan{{ radio.radiodev_counter+1 }}] \ 
do={:foreach n in [/interface wireless wds get $i name] \ 
do={:foreach inum in [/ip address find interface=$n] \ 
do={/ip address remove $inum;};}; \ 
/interface wireless wds remove $i;}
      {% else %}
          {% if interface.ipv4 %}
             {% for ipv4_id,ipv4 in interface.ipv4 %}
                {% if interface.interface_type == 'wds/p2p' %}
                  {% set iname = interface.interface_type %}
                  {% set ospf_routerid = ipv4 %}
                {% else  %}
                  {% set iname = 'wlan' ~ radio.radiodev_counter+1 %}
                {% endif %}
/ip address
                {% if interface.interface_type == 'Wan'%}
:foreach i in [find interface={{ iname }}] do={remove $i}
                {% endif  %}
:foreach i in [find address="{{ ipv4.ipv4 }}/maskbits"] do={remove $i}
/ ip address add address={{ ipv4.ipv4 }}/maskbits network={{'network'}} broadcast={{'broadcast'}} interface={{iname}} disabled=no
/ routing bgp network
:foreach i in [/routing bgp network find network={{'netid'}}/{{'maskbits'}}] do={/routing bgp network remove $i;}
add network={{ 'netid' }}/{{ 'maskbits' }} disabled=no
                {% if dev.mode != 'client' %}
/ routing ospf interface
:foreach i in [/routing ospf interface find interface={{ iname }}] do={/routing ospf interface remove $i;}
add interface={{ iname }}
/ routing ospf network
:foreach i in [/routing ospf network find network={{'netid'}}/{{'maskbits'}}] do={/routing ospf network remove $i;}
add network={{'netid'}}/{{'maskbits'}} area={{ 'ospf_name' }} disabled=no
                {% else %}
/ routing ospf interface
:foreach i in [/routing ospf interface find interface={{ iname }}] do={/routing ospf interface remove $i;}
add interface={{ iname }}
/ routing ospf network
:foreach i in [/routing ospf network find network={{'netid'}}/{{'maskbits'}}] do={/routing ospf network remove $i;}
add network={{'netid'}}/{{'maskbits'}} area={{ 'ospf_name' }} disabled=yes
                {% endif %}
                {% if interface.interface_type == 'HotSpot' %}
# 
# HotSpot 
/interface wireless
:foreach i in [find name=hotspot{{ radio.id+1 }}] do={remove $i}
add name="hotspot{{ radio.id+1 }}" arp=enabled master-interface=wlan{{ radio.id+1 }} ssid="guifi.net-{{ hotspot_ssid}}" disabled="no"
/ip address');
:foreach i in [find address="192.168.{{ radio.id+100 }}.1/24"] do={remove $i}
/ip address add address=192.168.{{ radio.id+100 }}.1/24 interface=hotspot{{ radio.id+1 }} disabled=no
/ip pool
:foreach i in [find name=hs-pool-{{ radio.id+100 }}] do={remove $i}
add name="hs-pool-{{ radio.id+100 }}" ranges=192.168.{{ radio.id+100 }}.2-192.168.{{ radio.id+100 }}.254
/ip dhcp-server
:foreach i in [find name=hs-dhcp-{{ radio.id+100 }}] do={remove $i}
add name="hs-dhcp-{{ radio.id+100 }}" interface=hotspot{{ radio.id+1 }} lease-time=1h address-pool=hs-pool-{{ radio.id+100 }} bootp-support=static authoritative=after-2sec-delay disabled=no
/ip dhcp-server network
:foreach i in [find address="192.168.{{ radio.id+100 }}.0/24"] do={remove $i}
add address=192.168.{{ radio.id+100 }}.0/24 gateway=192.168.{{ radio.id+100 }}.1 domain=guifi.net comment=dhcp-{{ radio.id }}
/ip hotspot profile
:foreach i in [find name=hsprof{{ radio.id+1 }}] do={remove $i}
add name="hsprof{{ radio.id }}" hotspot-address=192.168.{{ radio.id+100 }}.1 dns-name="guests.guifi.net" html-directory=hotspot smtp-server=0.0.0.0 login-by=http-pap,trial split-user-domain=no trial-uptime=30m/1d trial-user-profile=default use-radius=no
/ip hotspot user profile
set default name="default" advertise-url=http://guifi.net/trespassos/
/ip hotspot
:foreach i in [find name=hotspot{{ radio.id+1 }}] do={remove $i}
add name="hotspot{{ radio.id+1 }}" interface=hotspot{{ radio.id+1 }} address-pool=hs-pool-{{ radio.id+100 }} profile=hsprof{{ radio.id+1 }} idle-timeout=5m keepalive-timeout=none addresses-per-mac=2 disabled=no
end of HotSpot
                {% endif %} {# end hotspot #}
:delay 1
                {% if interface.interface_type != 'HotSpot' and interface.interface_type != 'Wan' %}
                  {% if mode == 'ap-bridge' %}
                    {% set maxip = 'netstart+1' %}
                    {# comprovar if de miktorik.php linia 377 #}
                    {% set dhcpDisabled = 'no' %}
#
# DHCP
:foreach i in [/ip pool find name=dhcp-{{ iname }}] do={/ip pool remove $i;}
/ip pool add name=dhcp-{{ iname }} ranges={{ maxip }}-{{ netend }}
:foreach i in [/ip dhcp-server find name=dhcp-{{ iname }}] do={/ip dhcp-server remove $i;}
/ip dhcp-server add name=dhcp-{{ iname }} interface={{ iname }} address-pool=dhcp-{{ iname }} disabled={{ dhcpDisabled }}
:foreach i in [/ip dhcp-server network find address="{{'netid'}}/{{'maskbits'}}"] do={/ip dhcp-server network remove $i;}
/ip dhcp-server network add address={{'netid'}}/{{'maskbits'}} gateway={{'netstart'}} domain=guifi.net comment=dhcp-{{ iname }}
/ip dhcp-server lease
:foreach i in [find comment=""] do={remove $i;}
:delay 1
                    {% if ipv4.links %}
                      {% for link_id, link in ipv4.links %}
                        {% if link.interface.ipv4.ipv4 %}
                          {% set maxip = link.interface.ipv4.ipv4 +1  %}
                        {% endif %}
                        {% if not link.interface.mac %}
                          {% set rmac = 'ff:ff:ff:ff:ff:ff'  %}
                        {% endif %}
add address={{ link.interface.ipv4.ipv4 }} mac-address={{ rmac }} client-id={{ device_name }} server=dhcp-{{ iname }}
                      {% endfor %}
                    {% endif %}
                  {% endif %} 
                {% endif %} 
             {% endfor %}
          {% endif  %}
      {% endif  %}  
    {% endfor %}
{% endspaceless %}
  {% endfor %} {# end for radio #}
{% endif %}
{% if firewall %}
{% endif %}
#
# Routed device
#
# Other cable connections
{% if dev.interfaces %}
  {% for interface_i, interface in dev.interfaces %}
    {% if interface.interface_type == 'vlan' %}
        {% set iname = 'wLan/Lan' %}
    {% elseif interface.interface_type == 'vlan2' %}
        {% set iname = 'ether2' %}
    {% elseif interface.interface_type == 'vlan3' %}
        {% set iname = 'ether3' %}
    {% elseif interface.interface_type == 'vlan4' %}
        {% set iname = 'wLan/Lan' %}
    {% elseif interface.interface_type == 'Wan' %}
        {% set iname = 'wLan/Lan' %}
    {% else %}
        {% set iname = interface.interface_type  %}
    {% endif %}
  {% endfor %}
{% endif  %}
#
# Internal addresses NAT
#:foreach i in [/ip firewall nat find src-address="172.16.0.0/12"] do={/ip firewall nat remove $i;}
#:foreach i in [/ip firewall nat find src-address="192.168.0.0/16"] do={/ip firewall nat remove $i;}
#/ip firewall nat
{% if firmware_name == 'RouterOSv2.9' %}
add chain=srcnat src-address="192.168.0.0/16" dst-address=!192.168.0.0/16 action=src-nat to-addresses={{ 'ospf_routerid' }} to-ports=0-65535 comment="" disabled=no
{% else %}
add chain=srcnat src-address="192.168.0.0/16" dst-address=!192.168.0.0/16 action=src-nat to-addresses={{ 'ospf_routerid' }} comment="" disabled=no
{% endif  %}
# 
# Enrutament BGP
# BGP & OSPF Filters
:foreach i in [/routing filter find chain=ospf-in] do={/routing filter remove $i;}
:foreach i in [/routing filter find chain=ospf-out] do={/routing filter remove $i;}
:foreach i in [/routing filter find chain=ebgp-in] do={/routing filter remove $i;}
:foreach i in [/routing filter find chain=ebgp-out] do={/routing filter remove $i;}
/ routing filter");
add action=discard chain=ebgp-in comment="1. Discard insert non 10.x routes from BGP peer" disabled=no invert-match=no prefix=!10.0.0.0/8 prefix-length=!8-32
add action=discard chain=ebgp-out comment="2. Discard send non 10.x routes to BGP peer" disabled=no invert-match=no prefix=!10.0.0.0/8 prefix-length=!8-32
add action=accept chain=ospf-in comment="3. Accept insert 10.x routes from OSPF neighbor" disabled=no invert-match=no prefix=10.0.0.0/8 prefix-length=8-32
add action=accept chain=ospf-in comment="4. Accept insert 172.x routes from OSPF neighbor" disabled=no invert-match=no prefix=172.16.0.0/12 prefix-length=8-32
add action=discard chain=ospf-in comment="5. Discard insert non 10.x and 172.x from OSPF neighbor" disabled=no invert-match=no
add action=accept chain=ospf-out comment="6. Allow send 10.x routes to OSPF neighbor" disabled=no invert-match=no prefix=10.0.0.0/8 prefix-length=8-32
add action=accept chain=ospf-out comment="7. Allow send 172.x routes to OSPF neighbor" disabled=no invert-match=no prefix=172.16.0.0/12 prefix-length=8-32
add action=discard chain=ospf-out comment="8. Discard send non 10.x and 172.x to OSPF neighbor" disabled=no invert-match=no
#
#BGP instance
/ routing bgp instance
set default name="default" as={{ dev.id }} router-id={{ 'ospf_routerid' }} \ 
{% if firmware_name == 'RouterOSv4.0+' or firmware_name == 'RouterOSv4.7+' or firmware_name == 'RouterOSv5.x' %}
redistribute-connected=no redistribute-static=no redistribute-rip=no
{% else %}
redistribute-connected=yes redistribute-static=yes redistribute-rip=yes \ 
{% endif  %}
redistribute-ospf=yes redistribute-other-bgp=yes out-filter=ebgp-out \ 
client-to-client-reflection=yes comment="" disabled=no
#
# OSPF Routing
{% if firmware_name == 'RouterOSv2.9' or firmware_name == 'RouterOSv3.x' or firmware_name == 'RouterOSv5.x' %}
/routing ospf set router-id={{ 'ospf_routerid' }} distribute-default=never redistribute-connected=no \ 
redistribute-static=no redistribute-rip=no redistribute-bgp=as-type-1
{% else %}
/routing ospf instance set default name=default router-id={{ 'ospf_routerid' }} comment="" disabled=no distribute-default=never \ 
redistribute-bgp=as-type-1 redistribute-connected=no redistribute-other-ospf=no redistribute-rip=no redistribute-static=no in-filter=ospf-in out-filter=ospf-out
{% endif  %}
  // End of Unsolclic
#
#:log info "Unsolclic for {{ dev.id}}-{{ dev-nick }} executed."
/
#final bucles 
