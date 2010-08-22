<h4 id="method_zone_nearest"><?php _e("guifi.zone.nearest")?></h4>

<p><strong><?php _e("Cerca la zona més propera a un punt del mapa.")?></strong></p>

<h5 id="method_zone_nearest_params"><?php _e("Paràmetres")?></h5>

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
			<td>lat</td>
			<td>float</td>
			<td><?php _e("Coordenada de longitud, en graus decimals, del punt pel qual volem esbrinar la zona més propera.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>lon</td>
			<td>float</td>
			<td><?php _e("Coordenada de latitud, en graus decimals, del punt pel qual volem esbrinar la zona més propera.")?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<h5 id="method_zone_nearest_return"><?php _e("Retorn")?></h5>

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
		<tr class="group">
			<td>nearest</td>
			<td>array</td>
			<td><?php _e("Informació de la zona més propera al punt especificat.")?></td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">zone_id</dt>
				<dd class="field_type">integer</dd>
				<dd class="field_description" style="width: 545px"><?php _e("ID de la zona.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">title</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Nom de la zona.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="group">
			<td>candidates</td>
			<td>array</td>
			<td><?php _e("Matriu d'informació amb totes les possibles zones que també inclouen el punt especificat.")?></td>
		</tr>
		<tr class="subgroup sublevel2">
			<td colspan="3">
			<dl>
				<dt class="field_name">zone_id</dt>
				<dd class="field_type">integer</dd>
				<dd class="field_description" style="width: 545px"><?php _e("ID de la zona.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup sublevel2">
			<td colspan="3">
			<dl>
				<dt class="field_name">title</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Nom de la zona.")?></dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>