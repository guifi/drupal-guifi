<h4 id="method_node_add"><?php _e("guifi.node.add")?></h4>
<p><strong><?php _e("Afegeix un nou node (o localització) guifi.net a la xarxa.")?></strong></p>

<h5 id="method_node_add_params"><?php _e("Paràmetres")?></h5>

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
			<td>title</td>
			<td>string</td>
			<td><?php _e("Nom del lloc del node guifi.net.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>nick</td>
			<td>string</td>
			<td><?php _e("Nom curt del lloc.")?></td>
			<td><em><?php _e("generat automàticament")?></em></td>
		</tr>
		<tr>
			<td>body</td>
			<td>string</td>
			<td><?php _e("Descripció del nou node guifi.net.")?></td>
			<td><em><?php _e("generat automàticament")?></em></td>
		</tr>
		<tr class="required">
			<td>zone_id</td>
			<td>integer</td>
			<td><?php _e("ID de zona on estarà ubicat aquest nou lloc.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>zone_description</td>
			<td>string</td>
			<td><?php _e("Descripció de la zona on està localitzat el nou node guifi.net.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>notification</td>
			<td>string</td>
			<td><?php _e("Adreça electrònica de notificació de canvis del node.")?></td>
			<td><em><?php _e("Adreça electrònica de l'usuari autenticat.")?></em></td>
		</tr>
		<tr class="required">
			<td>lat</td>
			<td>float</td>
			<td><?php _e("Latitud, en graus decimals, de la localització del nou node guifi.net.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>lon</td>
			<td>float</td>
			<td><?php _e("Longitud, en graus decimals, de la localització del nou node guifi.net.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>elevation</td>
			<td>integer</td>
			<td><?php _e("Elevació, en metres, de la localització del nou node guifi.net.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>stable</td>
			<td>string</td>
			<td><?php _e("Serveix el node per expandir la xarxa? Possibles valors: <em><strong>%s</strong></em>
			(Sí), <em><strong>%s</strong></em> (No).", 'Yes', 'No')?></td>
			<td><em>Yes</em></td>
		</tr>
		<tr>
			<td>graph_server</td>
			<td>integer</td>
			<td><?php _e("ID del servidor de gràfiques que recull les dades de
			disponibilitat del dispositiu.")?></td>
			<td><em><?php _e("Agafat de la zona pare")?></em></td>
		</tr>
		<tr>
			<td>status</td>
			<td>string</td>
			<td><?php _e("Estat del dispositiu. Possibles valors: <em><strong>%s</strong></em>
			(Projectat), <em><strong>%s</strong></em> (Reservat), <em><strong>%s</strong></em>
			(En construcció), <em><strong>%s</strong></em> (En proves), <em><strong>%s</strong></em>
			(Operatiu) i <em><strong>%s</strong></em> (Esborrat).", 'Planned', 'Reserved', 'Building',
			'Testing', 'Working', 'Dropped')?></td>
			<td><em>Planned</em></td>
		</tr>
	</tbody>
</table>

<h5 id="method_node_add_return"><?php _e("Retorn")?></h5>

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
			<td>node_id</td>
			<td>integer</td>
			<td><?php _e("ID del nou node guifi.net afegit.")?></td>
		</tr>
	</tbody>
</table>