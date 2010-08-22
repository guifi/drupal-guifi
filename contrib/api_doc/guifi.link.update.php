<h4 id="method_link_update"><?php _e("guifi.link.update")?></h4>
<p><strong><?php _e("Actualitza un nou enllaç de la xarxa.")?></strong></p>

<h5 id="method_link_update_params"><?php _e("Paràmetres")?></h5>

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
			<td>link_id</td>
			<td>integer</td>
			<td><?php _e("ID de l'enllaç a editar.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>ipv4</td>
			<td>string</td>
			<td><?php _e("Adreça IP que ha de tenir el dispositiu de l'enllaç amb identificador <em><strong>from_device_id</strong></em>. Aquesta adreça es verificarà, i si no és correcta no s'afegirà l'enllaç.</td>
			<td><em>Generat automàticament</em>")?></td>
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

<p><?php _e("A més, segons el <strong>mode</strong> de l'enllaç a editar hi ha un
seguit de camps extra que complementen la informació sobre l'enllaç que
s'està editant. Aquests altres camps estan separats a una segona taula,
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