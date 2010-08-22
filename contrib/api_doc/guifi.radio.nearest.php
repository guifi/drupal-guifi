<h4 id="method_radio_nearest"><?php _e("guifi.radio.nearest")?></h4>

<p><strong><?php _e("Cerca les ràdios en mode <em><strong>ap</strong></em> més properes a un node del mapa.")?></strong></p>

<h5 id="method_device_nearest_params"><?php _e("Paràmetres")?></h5>

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
			<td>node_id</td>
			<td>integer</td>
			<td><?php _e("Identificador del node al qual volem buscar les ràdios més
			properes per establir-hi un enllaç.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>dmin</td>
			<td>integer</td>
			<td><?php _e("Distància mínima a la qual han d'estar les ràdios retornades.")?></td>
			<td>0</td>
		</tr>
		<tr>
			<td>dmax</td>
			<td>integer</td>
			<td><?php _e("Distància màxima a la qual ha d'estar les ràdios retornades.")?></td>
			<td>15</td>
		</tr>
	</tbody>
</table>

<h5 id="method_radio_nearest_return"><?php _e("Retorn")?></h5>

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
			<td>radios</td>
			<td>array</td>
			<td><?php _e("Matriu d'informació amb totes les possibles ràdios. Com a màxim retornarà 20 ràdios diferents.")?></td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">device_id</dt>
				<dd class="field_type">integer</dd>
				<dd class="field_description" style="width: 545px"><?php _e("ID del dispositiu on és la ràdio.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">radiodev_counter</dt>
				<dd class="field_type">integer</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Posició dins del dispositiu que ocupa la ràdio.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">ssid</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("SSID de la ràdio.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">distance</dt>
				<dd class="field_type">float</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Distància (en km) entre el node i la ràdio.")?></dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>