<h4 id="method_interface_add"><?php _e("guifi.interface.add")?></h4>
<p><strong><?php _e("Afegeix una nova interfície a una ràdio de la xarxa.")?></strong></p>

<p><?php _e("L'API només suporta afegir interfícies de ràdio sense fils per poder
afegir rangs d'adreces IP per clients.</p>
<p>En cas que la ràdio funcioni en mode <em><strong>client</strong></em>
no es podran afegir més interfícies.</p>
<p>Queda per implementar el suport de les connexions per cable.")?></p>

<h5 id="method_interface_add_params"><?php _e("Paràmetres")?></h5>

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
			<td><?php _e("ID del dispositiu on es vol afegir la interfície.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>radiodev_counter</td>
			<td>string</td>
			<td><?php _e("Posició de la ràdio on es vol afegir la interfície respecte
			altres ràdios del mateix dispositiu.")?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<h5 id="method_interface_add_return"><?php _e("Retorn")?></h5>

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
			<td>interface_id</td>
			<td>integer</td>
			<td><?php _e("ID de la interfície afegida")?></td>
		</tr>
		<tr class="group returngroup">
			<td>ipv4</td>
			<td>array</td>
			<td><?php _e("Informació sobre la xarxa IPv4 que s'hagi afegit.")?></td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">ipv4_type</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Tipus
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
				<dd class="field_description" style="width: 545px"><?php _e("Adreça IPv4.")?></dd>
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