<h4 id="method_misc_firmware"><?php _e("guifi.misc.firmware")?></h4>
<p><?php _e("Aquest mètode serveix per retornar els diversos tipus de firmwares de
dispositius (o trastos) suportats per guifi.net.")?></p>

<h5 id="method_misc_firmware_params"><?php _e("Paràmetres")?></h5>

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
		<tr>
			<td>model_id</td>
			<td>integer</td>
			<td><?php _e("<a href=\"%s\">ID de model</a> que suporta el firmware retornat.", '#method_misc_model')?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_firmware_return"><?php _e("Retorna")?></h5>

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
			<td>firmwares</td>
			<td>array</td>
			<td><?php _e("Firmwares suportats de guifi.net retornats")?></td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">title</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Nom del firmware.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">description</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Descripció del firmware.")?></dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_firmware_list"><?php _e("Llistat")?></h5>
<p><?php _e("Un llistat útil de firmwares de dispositius és el següent:")?></p>
<table class="sample">
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row"><?php _e("Nom del firmware")?></th>
			<th scope="row"><?php _e("Descripció del firmware")?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Alchemy</td>
			<td>Alchemy from sveasoft</td>
		</tr>
		<tr>
			<td>Talisman</td>
			<td>Talisman from sveasoft</td>
		</tr>
		<tr>
			<td>DD-WRT</td>
			<td>DD-WRT from BrainSlayer</td>
		</tr>
		<tr>
			<td>DD-guifi</td>
			<td>DD-guifi from Miquel Martos</td>
		</tr>
		<tr>
			<td>RouterOSv2.9</td>
			<td>RouterOS 2.9 from Mikrotik</td>
		</tr>
		<tr>
			<td>whiterussian</td>
			<td>OpenWRT-whiterussian</td>
		</tr>
		<tr>
			<td>kamikaze</td>
			<td>OpenWRT kamikaze</td>
		</tr>
		<tr>
			<td>Freifunk-BATMAN</td>
			<td>OpenWRT-Freifunk-v1.6.16 with B.A.T.M.A.N</td>
		</tr>
		<tr>
			<td>RouterOSv3.x</td>
			<td>RouterOS 3.x from Mikrotik</td>
		</tr>
		<tr>
			<td>AirOsv221</td>
			<td>Ubiquti AirOs 2.2.1</td>
		</tr>
		<tr>
			<td>Freifunk-OLSR</td>
			<td>OpenWRT-Freifunk-v1.6.16 with OLSR</td>
		</tr>
		<tr>
			<td>AirOsv30</td>
			<td>Ubiquti AirOs 3.0</td>
		</tr>
		<tr>
			<td>RouterOSv4.x</td>
			<td>RouterOS 4.x from Mikrotik</td>
		</tr>
	</tbody>
</table>