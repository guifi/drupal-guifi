<h4 id="method_radio_update"><?php _e("guifi.radio.update")?></h4>
<p><strong><?php _e("Afegeix una nova ràdio a un dispositiu de la xarxa.")?></strong></p>

<h5 id="method_radio_update_params"><?php _e("Paràmetres")?></h5>

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
			<td>device_id</td>
			<td>integer</td>
			<td><?php _e("Dispositiu on està situada aquesta ràdio.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>radiodev_counter</td>
			<td>string</td>
			<td><?php _e("Posició de la ràdio per actualitzar en relació a altres ràdios del mateix dispositiu.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>antenna_angle</td>
			<td>integer</td>
			<td><?php _e("Angle d'obertura de l'antena. Possibles valors: <em><strong>%d</strong></em>
			(original/integrada), <em><strong>%d</strong></em> (yagi/directiva), <em><strong>%d</strong></em>
			(patch 60 graus), <em><strong>%d</strong></em> (patch 90 graus), <em><strong>%d</strong></em>
			(sector 120 graus), <em><strong>%d</strong></em> (omnidirectiva).", 0, 6, 60, 90, 120, 360)?></td>
			<td><em><?php _e("Depèn del mode.")?> ap: 120; client: 30; routedclient: 30; ad-hoc: 360</em></td>
			<td></td>
		</tr>
		<tr>
			<td>antenna_gain</td>
			<td>integer</td>
			<td><?php _e("Guany de l'antena. Possibles valors: <em><strong>%s</strong></em>
			(2 dB), <em><strong>%d</strong></em> (8 dB), <em><strong>%d</strong></em>
			(12 dB), <em><strong>%d</strong></em> (14 dB), <em><strong>18</strong></em>
			(18 dB), <em><strong>%d</strong></em> (21 dB), <em><strong>24</strong></em>
			(24 dB), <em><strong>more</strong></em> (més de 24 dB).", 2, 8, 12, 14, 18, 21, 24)?></td>
			<td></td>
		</tr>
		<tr>
			<td>antenna_azimuth</td>
			<td>integer</td>
			<td><?php _e("Azimuth de l'antena d'aquesta ràdio, en graus. Rang de valors: 0 - 360.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>antenna_mode</td>
			<td>string</td>
			<td><?php _e("Connector de la ràdio on està connectada l'antena. El significat
			dels valors depèn del model de dispositiu. Possibles valors: <em><strong>%s</strong></em>
			(Principal, Dret, Intern), <em><strong>%s</strong></em> (Auxiliar, Esquerra, Extern).", 'Main', 'Aux')?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<p><?php _e("A més, segons el camp <strong>mode</strong> que tingui aquesta hi ha
un seguit de camps extra que complementen la informació sobre la ràdio
que s'està editant. Aquests altres camps estan separats a una segona
taula, especificant sobre quin tipus de ràdio s'utilitzen.")?></p>

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