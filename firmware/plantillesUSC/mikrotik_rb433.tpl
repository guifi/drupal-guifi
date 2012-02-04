# Generat per a:
# RouterOSv4.7+
:log info "Unsolclic for {device_id}-{device_name} going to be executed."
#
# Configuration for {firmware_name} and newer 4.x
# Trasto: {device_id}-{device_name}
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
/ system identity set name=TRSAjuliol22Rd1
#
# DNS (client & server cache) zone: 8802
/ip dns set servers=10.139.7.162 allow-remote-requests=yes
:delay 1
#
# NTP (client & server cache) zone: 8802
/system ntp client set enabled=yes mode=unicast primary-ntp=10.139.7.162
:delay 1
#
# Bandwidth-server
/ tool bandwidth-server set enabled=yes authenticate=no allocate-udp-ports-from=2000
#
# SNMP
/snmp set contact="guifi@guifi.net" enabled=yes location="TRSAJuliol22"
#
# Guest user
/user
:foreach i in [find group=read] do={/user remove $i;}
add name="guest" group=read address=0.0.0.0/0 comment="" disabled=no
#
# Graphing
/tool graphing interface add
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
:delay 1
#
# Radio#: 0 TRSAjuliol22-LesPlanes
/interface wireless set wlan1 name="wlan1" \
radio-name="TRSAjuliol22-LesPlanes" mode=ap-bridge ssid="guifi.net-TRSAjuliol22-LesPlanes" \
band="5ghz" \
frequency-mode=regulatory-domain country=spain antenna-gain=5 \
frequency=5600 \
dfs-mode=none \
antenna-mode=ant-a wds-mode=static wds-default-bridge=none wds-default-cost=100 \
wds-cost-range=50-150 wds-ignore-ssid=yes hide-ssid=no
:delay 1
# Type: wLan/Lan
/ip address
:foreach i in [find address="10.139.7.161/27"] do={remove $i}
/ ip address add address=10.139.7.161/27 network=10.139.7.160 broadcast=10.139.7.191 interface=wLan/Lan disabled=no
/ routing bgp network
:foreach i in [/routing bgp network find network=10.139.7.160/27] do={/routing bgp network remove $i;}
add network=10.139.7.160/27 disabled=no
/ routing ospf interface
:foreach i in [/routing ospf interface find interface=wLan/Lan] do={/routing ospf interface remove $i;}
add interface=wLan/Lan
/ routing ospf network
:foreach i in [/routing ospf network find network=10.139.7.160/27] do={/routing ospf network remove $i;}
add network=10.139.7.160/27 area=backbone disabled=no
:delay 1
#
# DHCP
:foreach i in [/ip pool find name=dhcp-wLan/Lan] do={/ip pool remove $i;}
/ip pool add name=dhcp-wLan/Lan ranges=10.139.7.167-10.139.7.190
:foreach i in [/ip dhcp-server find name=dhcp-wLan/Lan] do={/ip dhcp-server remove $i;}
/ip dhcp-server add name=dhcp-wLan/Lan interface=wLan/Lan address-pool=dhcp-wLan/Lan disabled=no
:foreach i in [/ip dhcp-server network find address="10.139.7.160/27"] do={/ip dhcp-server network remove $i;}
/ip dhcp-server network add address=10.139.7.160/27 gateway=10.139.7.161 domain=guifi.net comment=dhcp-wLan/Lan
/ip dhcp-server lease
:foreach i in [find comment=""] do={remove $i;}
:delay 1
add address=10.139.7.162 mac-address=00:00:00:00:00:00 client-id=TRSAjuliol22Srvr1 server=dhcp-wLan/Lan
add address=10.139.7.163 mac-address=00:00:00:00:00:00 client-id=TRSAjuliol22DSL1 server=dhcp-wLan/Lan
#
:delay 1
# Type: wds/p2p
# Remove all existing wds interfaces
:foreach i in [/interface wireless wds find master-interface=wlan1] \
do={:foreach n in [/interface wireless wds get $i name] \
do={:foreach inum in [/ip address find interface=$n] \
do={/ip address remove $inum;};}; \
/interface wireless wds remove $i;}
/ interface wireless wds
add name="wds_LPtransvalST1" master-interface=wlan1 wds-address=00:0C:42:3A:6D:53 disabled=no
/ ip address add address=172.25.34.145/30 network=172.25.34.144 broadcast=172.25.34.147 interface=wds_LPtransvalST1 disabled=no comment="wds_LPtransvalST1"
/ routing ospf interface
:foreach i in [/routing ospf interface find interface=wds_LPtransvalST1] do={/routing ospf interface remove $i;}
add interface=wds_LPtransvalST1
/ routing ospf network
:foreach i in [/routing ospf network find network=172.25.34.144/30] do={/routing ospf network remove $i;}
add network=172.25.34.144/30 area=backbone disabled=yes
/ routing bgp peer
:foreach i in [find name=LPtransvalST1] do={/routing bgp peer remove $i;}
add name="LPtransvalST1" instance=default remote-address=172.25.34.146 remote-as=15610 \
multihop=no route-reflect=no ttl=default in-filter=ebgp-in out-filter=ebgp-out disabled=no
#
:delay 1
#
# Radio#: 1 TRSAjuliol225GhzTdP
/interface wireless set wlan2 name="wlan2" \
radio-name="TRSAjuliol225GhzTdP" mode=ap-bridge ssid="guifi.net-TRSAjuliol225GhzTdP" \
band="5ghz" \
frequency-mode=regulatory-domain country=spain antenna-gain=12 \
dfs-mode=radar-detect \
antenna-mode=ant-a wds-mode=static wds-default-bridge=none wds-default-cost=100 \
wds-cost-range=50-150 wds-ignore-ssid=yes hide-ssid=no
:delay 1
# Type: wLan
/ip address
:foreach i in [find address="10.139.60.65/27"] do={remove $i}
/ ip address add address=10.139.60.65/27 network=10.139.60.64 broadcast=10.139.60.95 interface=wlan2 disabled=no
/ routing bgp network
:foreach i in [/routing bgp network find network=10.139.60.64/27] do={/routing bgp network remove $i;}
add network=10.139.60.64/27 disabled=no
/ routing ospf interface
:foreach i in [/routing ospf interface find interface=wlan2] do={/routing ospf interface remove $i;}
add interface=wlan2
/ routing ospf network
:foreach i in [/routing ospf network find network=10.139.60.64/27] do={/routing ospf network remove $i;}
add network=10.139.60.64/27 area=backbone disabled=no
:delay 1
#
# DHCP
:foreach i in [/ip pool find name=dhcp-wlan2] do={/ip pool remove $i;}
/ip pool add name=dhcp-wlan2 ranges=10.139.60.71-10.139.60.94
:foreach i in [/ip dhcp-server find name=dhcp-wlan2] do={/ip dhcp-server remove $i;}
/ip dhcp-server add name=dhcp-wlan2 interface=wlan2 address-pool=dhcp-wlan2 disabled=no
:foreach i in [/ip dhcp-server network find address="10.139.60.64/27"] do={/ip dhcp-server network remove $i;}
/ip dhcp-server network add address=10.139.60.64/27 gateway=10.139.60.65 domain=guifi.net comment=dhcp-wlan2
/ip dhcp-server lease
:foreach i in [find comment=""] do={remove $i;}
:delay 1
#
:delay 1
# Type: wds/p2p
# Remove all existing wds interfaces
:foreach i in [/interface wireless wds find master-interface=wlan2] \
do={:foreach n in [/interface wireless wds get $i name] \
do={:foreach inum in [/ip address find interface=$n] \
do={/ip address remove $inum;};}; \
/interface wireless wds remove $i;}
/ interface wireless wds
add name="wds_TRSTorrePalauRd1" master-interface=wlan2 wds-address=00:0C:42:66:14:80 disabled=no
/ ip address add address=172.30.8.29/30 network=172.30.8.28 broadcast=172.30.8.31 interface=wds_TRSTorrePalauRd1 disabled=no comment="wds_TRSTorrePalauRd1"
/ routing ospf interface
:foreach i in [/routing ospf interface find interface=wds_TRSTorrePalauRd1] do={/routing ospf interface remove $i;}
add interface=wds_TRSTorrePalauRd1
/ routing ospf network
:foreach i in [/routing ospf network find network=172.30.8.28/30] do={/routing ospf network remove $i;}
add network=172.30.8.28/30 area=backbone disabled=no
/ routing bgp peer
:foreach i in [find name=TRSTorrePalauRd1] do={/routing bgp peer remove $i;}
add name="TRSTorrePalauRd1" instance=default remote-address=172.30.8.30 remote-as=15536 \
multihop=no route-reflect=no ttl=default in-filter=ebgp-in out-filter=ebgp-out disabled=yes
#
:delay 1
#
# Radio#: 3 TRSAjuliol22-SocietatWDS
/interface wireless set wlan4 name="wlan4" \
radio-name="TRSAjuliol22-SocietatWDS" mode=ap-bridge ssid="guifi.net-TRSAjuliol22-SocietatWDS" \
band="5ghz" \
frequency-mode=regulatory-domain country=spain antenna-gain=12 \
frequency=5310 \
dfs-mode=none \
antenna-mode=ant-a wds-mode=static wds-default-bridge=none wds-default-cost=100 \
wds-cost-range=50-150 wds-ignore-ssid=yes hide-ssid=no
:delay 1
# Type: wds/p2p
# Remove all existing wds interfaces
:foreach i in [/interface wireless wds find master-interface=wlan4] \
do={:foreach n in [/interface wireless wds get $i name] \
do={:foreach inum in [/ip address find interface=$n] \
do={/ip address remove $inum;};}; \
/interface wireless wds remove $i;}
/ interface wireless wds
add name="wds_TRSASocietatNordRd1" master-interface=wlan4 wds-address=00:0C:42:61:A0:91 disabled=no
/ ip address add address=172.25.32.109/30 network=172.25.32.108 broadcast=172.25.32.111 interface=wds_TRSASocietatNordRd1 disabled=no comment="wds_TRSASocietatNordRd1"
/ routing ospf interface
:foreach i in [/routing ospf interface find interface=wds_TRSASocietatNordRd1] do={/routing ospf interface remove $i;}
add interface=wds_TRSASocietatNordRd1
/ routing ospf network
:foreach i in [/routing ospf network find network=172.25.32.108/30] do={/routing ospf network remove $i;}
add network=172.25.32.108/30 area=backbone disabled=no
/ routing bgp peer
:foreach i in [find name=TRSASocietatNordRd1] do={/routing bgp peer remove $i;}
add name="TRSASocietatNordRd1" instance=default remote-address=172.25.32.110 remote-as=24463 \
multihop=no route-reflect=no ttl=default in-filter=ebgp-in out-filter=ebgp-out disabled=yes
#
:delay 1
#
# Routed device
#
# Altres connexions de cable
/ routing ospf interface
:foreach i in [/routing ospf interface find interface=ether2] do={/routing ospf interface remove $i;}
add interface=ether2
/ routing ospf network
:foreach i in [/routing ospf network find network=172.25.35.188/30] do={/routing ospf network remove $i;}
add network=172.25.35.188/30 area=backbone disabled=no
/ routing bgp peer
:foreach i in [find name=TRSAjuliol22Rd2] do={/routing bgp peer remove $i;}
add name="TRSAjuliol22Rd2" instance=default remote-address=172.25.35.189 remote-as=17131 \
multihop=no route-reflect=no ttl=default in-filter=ebgp-in out-filter=ebgp-out disabled=yes
:foreach i in [/ip address find address="172.25.35.190/30"] do={/ip address remove $i;}
:delay 1
/ ip address add address=172.25.35.190/30 network=172.25.35.188 broadcast=172.25.35.191 interface=ether2 disabled=no comment="TRSAjuliol22Rd2"
#
# Internal addresses NAT
:foreach i in [/ip firewall nat find src-address="172.16.0.0/12"] do={/ip firewall nat remove $i;}
:foreach i in [/ip firewall nat find src-address="192.168.0.0/16"] do={/ip firewall nat remove $i;}
/ip firewall nat
add chain=srcnat src-address="192.168.0.0/16" dst-address=!192.168.0.0/16 action=src-nat to-addresses=10.139.7.161 comment="" disabled=no
#
# Enrutament BGP
# BGP & OSPF Filters
:foreach i in [/routing filter find chain=ospf-in] do={/routing filter remove $i;}
:foreach i in [/routing filter find chain=ospf-out] do={/routing filter remove $i;}
:foreach i in [/routing filter find chain=ebgp-in] do={/routing filter remove $i;}
:foreach i in [/routing filter find chain=ebgp-out] do={/routing filter remove $i;}
/ routing filter
add action=discard chain=ebgp-in comment="1. Discard insert non 10.x routes from BGP peer" disabled=no invert-match=no prefix=!10.0.0.0/8 prefix-length=!8-32
add action=discard chain=ebgp-out comment="2. Discard send non 10.x routes to BGP peer" disabled=no invert-match=no prefix=!10.0.0.0/8 prefix-length=!8-32
add action=accept chain=ospf-in comment="3. Accept insert 10.x routes from OSPF neighbor" disabled=no invert-match=no prefix=10.0.0.0/8 prefix-length=8-32
add action=accept chain=ospf-in comment="4. Accept insert 172.x routes from OSPF neighbor" disabled=no invert-match=no prefix=172.16.0.0/12 prefix-length=8-32
add action=discard chain=ospf-in comment="5. Discard insert non 10.x and 172.x from OSPF neighbor" disabled=no invert-match=no
add action=accept chain=ospf-out comment="6. Allow send 10.x routes to OSPF neighbor" disabled=no invert-match=no prefix=10.0.0.0/8 prefix-length=8-32
add action=accept chain=ospf-out comment="7. Allow send 172.x routes to OSPF neighbor" disabled=no invert-match=no prefix=172.16.0.0/12 prefix-length=8-32
add action=discard chain=ospf-out comment="8. Discard send non 10.x and 172.x to OSPF neighbor" disabled=no invert-match=no
#
# Instància BGP
/ routing bgp instance
set default name="default" as=11111 router-id=10.139.7.161 \
redistribute-connected=no redistribute-static=no redistribute-rip=no \
redistribute-ospf=yes redistribute-other-bgp=yes out-filter=ebgp-out \
client-to-client-reflection=yes comment="" disabled=no
#
# Enrutament OSPF
/routing ospf instance set default name=default router-id=10.139.7.161 comment="" disabled=no distribute-default=never \
redistribute-bgp=as-type-1 redistribute-connected=no redistribute-other-ospf=no redistribute-rip=no redistribute-static=no in-filter=ospf-in out-filter=ospf-out
#
:log info "Unsolclic for 11111-TRSAjuliol22Rd1 executed."
/

