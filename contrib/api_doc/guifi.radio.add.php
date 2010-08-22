<h4 id="method_radio_add"><?php _e("guifi.radio.add")?></h4>
<p><strong><?php _e("Afegeix una nova ràdio a un dispositiu de la xarxa.")?></strong></p>

<h5 id="method_radio_add_params"><?php _e("Paràmetres")?></h5>

<p><?php _e("L'ordre bàsica d'ús d'aquest mètode conté els camps bàsics descrits a la taula de continuació.")?></p>
<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
		<col class="field_default" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row"><?php _e("Nom")?></th>
			<th scope="row"><?php _e("Tipus")?></th>
			<th scope="row"><?php _e("Descripció")?></th>
			<th scope="row"><?php _e("Per defecte")?></th>
		</tr>
	</thead>
	<tbody>
		<tr class="required">
			<td>mode</td>
			<td>string</td>
			<td><?php _e("Mode de funcionament de la ràdio. Possibles valors: <strong>Mode infrastructura</strong>: <em><strong>%s</strong></em> (AP o AP amb
			WDS), <em><strong>%s</strong></em> (Client sense fils), <em><strong>routedclient</strong></em>
			(Client enrutat); <strong>Mode ad-hoc</strong>: <em><strong>%s</strong></em>
			(Ad-hoc)", 'ap', 'client', 'routedclient', 'ad-hoc')?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>device_id</td>
			<td>integer</td>
			<td><?php _e("Dispositiu on anirà situada aquesta ràdio.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>mac</td>
			<td>string</td>
			<td><?php _e("Adreça MAC de la primera interfície de la ràdio.")?></td>
			<td><em><?php _e("A la primera ràdio es genera a partir de l'adreça MAC del
			dispositiu. Les altres són obligatòries.")?></em></td>
		</tr>
		<tr>
			<td>antenna_angle</td>
			<td>integer</td>
			<td><?php _e("Angle d'obertura de l'antena. Possibles valors: <em><strong>%d</strong></em>
			(original/integrada), <em><strong>%d</strong></em> (yagi/directiva), <em><strong>%d</strong></em>
			(patch 60 graus), <em><strong>%d</strong></em> (patch 90 graus), <em><strong>%d</strong></em>
			(sector 120 graus), <em><strong>%d</strong></em> (omnidirectiva).", 0, 6, 60, 90, 120, 360)?></td>
			<td><em><?php _e("Depèn del mode.")?> ap: 120; client: 30; routedclient: 30; ad-hoc: 360</em></td>
		</tr>
		<tr>
			<td>antenna_gain</td>
			<td>integer</td>
			<td><?php _e("Guany de l'antena. Possibles valors: <em><strong>%s</strong></em>
			(2 dB), <em><strong>%d</strong></em> (8 dB), <em><strong>%d</strong></em>
			(12 dB), <em><strong>%d</strong></em> (14 dB), <em><strong>18</strong></em>
			(18 dB), <em><strong>%d</strong></em> (21 dB), <em><strong>24</strong></em>
			(24 dB), <em><strong>more</strong></em> (més de 24 dB).", 2, 8, 12, 14, 18, 21, 24)?></td>
			<td><em>21</em></td>
		</tr>
		<tr>
			<td>antenna_azimuth</td>
			<td>integer</td>
			<td><?php _e("Azimuth de l'antena d'aquesta ràdio, en graus. Rang de valors: 0 - 360.")?></td>
			<td><em>0</em></td>
		</tr>
		<tr>
			<td>antenna_mode</td>
			<td>string</td>
			<td><?php _e("Connector de la ràdio on està connectada l'antena. El significat
			dels valors depèn del model de dispositiu. Possibles valors: <em><strong>%s</strong></em>
			(Principal, Dret, Intern), <em><strong>%s</strong></em> (Auxiliar, Esquerra, Extern).", 'Main', 'Aux')?></td>
			<td><em>0</em></td>
		</tr>
	</tbody>
</table>

<p><?php _e("A més, segons el camp <strong>mode</strong> d'aquest mètode hi ha un
seguit de camps extra que complementen la informació sobre la ràdio que
s'està afegint. Aquests altres camps estan separats a una segona taula,
especificant sobre quin tipus de ràdio s'utilitzen.")?></p>

<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
		<col class="field_default" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row"><?php _e("Nom")?></th>
			<th scope="row"><?php _e("Tipus")?></th>
			<th scope="row"><?php _e("Descripció")?></th>
			<th scope="row"><?php _e("Per defecte")?></th>
		</tr>
	</thead>
	<tbody>
		<tr class="group">
			<td colspan="4">mode = ap</td>
		</tr>
		<tr>
			<td>ssid</td>
			<td>string</td>
			<td><?php _e("<abbr title=\"Service Set Identifier\">SSID</abbr> de la ràdio.")?></td>
			<td><?php _e("<em>Generat a partir del nom del dispositiu</em>")?></td>
		</tr>
		<tr>
			<td>protocol</td>
			<td>string</td>
			<td><?php _e("<a href=\"%s\">Protocol</a> que utilitza aquesta ràdio.", '#method_misc_protocol')?></td>
			<td><em>802.11b</em></td>
		</tr>
		<tr>
			<td>channel</td>
			<td>string</td>
			<td><?php _e("<a href=\"%s\">Canal de radiofreqüència</a> que
			utilitza aquesta ràdio. Aquest valor depèn del protocol de la ràdio.", '#method_misc_channel')?></td>
			<td><em><?php _e("Automàtic")?></em></td>
		</tr>
		<tr>
			<td>clients_accepted</td>
			<td>string</td>
			<td><?php _e("Si la ràdio accepta clients o no. Possibles valors: <em><strong>Yes</strong></em>
			(Sí), <em><strong>No</strong></em> (No).")?></td>
			<td><em>Yes</em></td>
		</tr>
		<tr class="group">
			<td colspan="4">mode = ad-hoc</td>
		</tr>
		<tr>
			<td>ssid</td>
			<td>string</td>
			<td><?php _e("<abbr title=\"Service Set Identifier\">SSID</abbr> de la ràdio.")?></td>
			<td><?php _e("<em>Generat a partir del nom del dispositiu</em>")?></td>
		</tr>
		<tr>
			<td>protocol</td>
			<td>string</td>
			<td><?php _e("<a href=\"%s\">Protocol</a> que utilitza aquesta ràdio.", '#method_misc_protocol')?></td>
			<td><em>802.11b</em></td>
		</tr>
		<tr>
			<td>channel</td>
			<td>string</td>
			<td><?php _e("<a href=\"%s\">Canal de radiofreqüència</a> que
			utilitza aquesta ràdio. Aquest valor depèn del protocol de la ràdio.", '#method_misc_channel')?></td>
			<td><em><?php _e("Automàtic")?></em></td>
		</tr>
	</tbody>
</table>

<h5 id="method_radio_add_return"><?php _e("Retorn")?></h5>
<p><?php _e("A l'afegir una ràdio a un dispositiu de guifi.net, automàticament es
creen noves <a href=\"%s\">interfícies</a> per aquesta ràdio.", '#method_interface')?></p>
<p><?php _e("El tipus i número d'interfícies dependrà del mode de funcionament de
la pròpia ràdio. En qualsevol cas, aquestes interfícies afegides
automàticament es poden tractar mitjançant el grup de mètodes d'<a href=\"%s\">interfície</a>.", '#method_interface')?></p>

<p><?php _e("Els camps que retorna aquest mètode en cas d'èxit són els descrits a continuació:")?></p>
<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row"><?php _e("Nom")?></th>
			<th scope="row"><?php _e("Tipus")?></th>
			<th scope="row"><?php _e("Descripció")?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>radiodev_counter</td>
			<td>integer</td>
			<td><?php _e("Posició de la ràdio afegida en relació a altres ràdios del mateix dispositiu")?></td>
		</tr>
		<tr class="group returngroup">
			<td>interfaces</td>
			<td>array</td>
			<td><?php _e("Interfícies afegides automàticament a aquesta ràdio.")?></td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">interface_type</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Tipus d'<a href=\"%s\">interfície</a> afegida.", '#method_interface')?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl class="group">
				<dt class="field_name">ipv4</dt>
				<dd class="field_type">array</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Informació sobre les <strong>N</strong> xarxes IPv4 que s'hagin pogut afegir.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup sublevel2">
			<td colspan="3">
			<dl>
				<dt class="field_name">ipv4_type</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 495px"><?php _e("Tipus
				d'adreçament IPv4. Possibles valors: <em><strong>%d</strong></em>
				(Adreces públiques), <em><strong>%d</strong></em> (Adreces troncals).", 1, 2)?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup sublevel2">
			<td colspan="3">
			<dl>
				<dt class="field_name">ipv4</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 495px"><?php _e("Adreça IPv4.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup sublevel2">
			<td colspan="3">
			<dl>
				<dt class="field_name">netmask</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 495px"><?php _e("Màscara de l'adreça IPv4.")?></dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>

<h6><?php _e("Exemple de retorn")?></h6>
<p><?php _e("Per clarificar els conceptes, a continuació hi ha un exemple típic
d'un possible retorn agafat a l'atzar a l'hora d'afegir una ràdio nova.")?></p>

<blockquote><pre>
array(
   "radiodev_counter" = 0,
   "interfaces" = array(
      0 = array(
         "interface_type" = "wds/p2p"
         ),
      1 = array(
         "interface_type" = "wLan/Lan"
         "ipv4" = array(
            0 = array(
               "ipv4_type" = 1
               "ipv4" = "10.145.9.33"
               "netmask" = "255.255.255.224"
               )
            )
         )
      )
   );
</pre></blockquote>
