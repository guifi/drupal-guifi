<h4 id="method_misc_protocol"><?php _e("guifi.misc.protocol")?></h4>
<p><?php _e("Aquest mètode serveix per retornar els diversos tipus de protocols de
dispositius (o trastos) suportats per guifi.net.")?></p>

<p><?php _e("No té paràmetres d'entrada, només de retorn.")?></p>

<h5 id="method_misc_protocol_return"><?php _e("Retorna")?></h5>

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
			<td>protocols</td>
			<td>array</td>
			<td><?php _e("Protocols suportats de guifi.net retornats")?></td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">title</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Nom del protocol.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">description</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Descripció del protocol.")?></dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_protocol_list"><?php _e("Llistat")?></h5>
<p><?php _e("Un llistat útil de protocols és el següent:")?></p>
<table class="sample">
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row">title</th>
			<th scope="row">description</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>802.11a</td>
			<td>802.11a (1-54Mbps - 5Ghz)</td>
		</tr>
		<tr>
			<td>802.11b</td>
			<td>802.11b (1-11Mbps - 2.4Ghz)</td>
		</tr>
		<tr>
			<td>802.11g</td>
			<td>802.11g (2-54Mbps - 2.4Ghz)</td>
		</tr>
		<tr>
			<td>802.11n</td>
			<td>802.11n - MIMO (1-125Mbps - 2.4/5Ghz)</td>
		</tr>
		<tr>
			<td>WiMAX</td>
			<td>802.16a - WiMAX (1-125Mbps - 2-8Ghz)</td>
		</tr>
		<tr>
			<td>legacy</td>
			<td>legacy/proprietary protocol</td>
		</tr>
	</tbody>
</table>