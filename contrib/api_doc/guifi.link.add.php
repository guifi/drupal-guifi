<h4 id="method_link_add"><?php _e("guifi.link.add")?></h4>
<p><strong><?php _e("Afegeix un nou enllaç entre dues interfícies de la xarxa.")?></strong></p>
<p><?php _e("Aquest mètode només és vàlid entre interfícies del tipus <strong><em>Wan</em></strong>
i <strong><em>wLan/Lan</em></strong> o <strong><em>wLan</em></strong>
(els anomenats enllaços <strong><em>link2ap</em></strong>); o bé entre
interfícies <em><strong>wds</strong></em> (els anomenats enllaços <em><strong>wds</strong></em>).")?></p>
<p><?php _e("Segons les interfícies que s'introdueixin a aquest mètode, l'API
detecterà automàticament quin tipus d'enllaç nou crear.")?></p>

<h5 id="method_link_add_params"><?php _e("Paràmetres")?></h5>

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
			<td>from_device_id</td>
			<td>integer</td>
			<td><?php _e("Identificador de <a href=\"%s\">dispositiu</a> des d'on fer l'enllaç.", '#method_device')?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>from_radiodev_counter</td>
			<td>integer</td>
			<td><?php _e("Posició de la ràdio dins del dispositiu cap on fer l'enllaç.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>to_device_id</td>
			<td>integer</td>
			<td><?php _e("Identificador de <a href=\"%s\">dispositiu</a> cap on fer l'enllaç.", '#method_device')?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>to_radiodev_counter</td>
			<td>integer</td>
			<td><?php _e("Posició de la ràdio dins del dispositiu cap on fer l'enllaç.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>ipv4</td>
			<td>string</td>
			<td><?php _e("Adreça IP que ha de tenir el dispositiu de l'enllaç amb identificador <em><strong>from_device_id</strong></em>. Aquesta adreça es verificarà, i si no és correcta no s'afegirà l'enllaç.")?></td>
			<td><em><?php _e("Generat automàticament")?></em></td>
		</tr>
		<tr>
			<td>status</td>
			<td>string</td>
			<td><?php _e("Estat del dispositiu. Possibles valors: <em><strong>%s</strong></em>
			(Projectat), <em><strong>%s</strong></em> (Reservat), <em><strong>%s</strong></em>
			(En construcció), <em><strong>%s</strong></em> (En proves), <em><strong>%s</strong></em>
			(Operatiu) i <em><strong>%s</strong></em> (Esborrat).", 'Planned', 'Reserved', 'Building',
			'Testing', 'Working', 'Dropped')?></td>
			<td><em>Working</em></td>
		</tr>
	</tbody>
</table>

<p><?php _e("A més, segons el camp <strong>mode</strong> d'aquest mètode hi ha un
seguit de camps extra que complementen la informació sobre l'enllaç que
s'està afegint. Aquests altres camps estan separats a una segona taula,
especificant sobre quin tipus d'enllaç s'utilitzen.")?></p>

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
			<td colspan="4">mode = link2ap</td>
		</tr>
		
		<tr class="group">
			<td colspan="4">mode = wds</td>
		</tr>
		<tr class="required">
			<td>to_interface_id</td>
			<td>integer</td>
			<td><?php _e("Identificador d'<a href=\"%s\">interfície</a> cap on fer l'enllaç.", '#method_interface')?></td>
			<td></td>
		</tr>
		<tr>
			<td>routing</td>
			<td>string</td>
			<td><?php _e("Tipus d'enrutament aplicat a aquest enllaç. Possibles valors: <em><strong>%s</strong></em>
			(<abbr title=\"Open Shortest Path First\">OSPF</abbr>), <em><strong>%s</strong></em>
			(<abbr title=\"Border Gateway Protocol\">BGP</abbr>), <em><strong>%s</strong></em>
			(estàtic).", 'OSPF', 'BGP', 'Static')?></td>
			<td><em>BGP</em></td>
		</tr>
	</tbody>
</table>

<h5 id="method_link_add_return"><?php _e("Retorn")?></h5>
<p><?php _e("A l'afegir un enllaç a un dispositiu de guifi.net, automàticament es
s'afegeix la configuració IP de l'enllaç.")?></p>

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
			<td>link_id</td>
			<td>integer</td>
			<td><?php _e("Identificador del nou enllaç afegit")?></td>
		</tr>
		<tr class="group returngroup">
			<td>ipv4</td>
			<td>array</td>
			<td><?php _e("Informació sobre la xarxa IPv4 que s'hagi afegit a la interfície.")?></td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">ipv4_type</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 495px"><?php _e("Tipus
				d'adreçament IPv4. Possibles valors: <em><strong>1</strong></em>
				(Adreces públiques), <em><strong>2</strong></em> (Adreces troncals).")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">ipv4</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 495px"><?php _e("Adreça IPv4.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
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
   "link_id" = 21947,
   "ipv4" = array(
      "ipv4_type" = 1
      "ipv4" = "10.145.5.99"
      "netmask" = "255.255.255.224"
      )
   );
</pre></blockquote>
