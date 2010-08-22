<h4 id="method_misc_manufacturer"><?php _e("guifi.misc.manufacturer")?></h4>
<p><?php _e("Aquest mètode serveix per retornar els diversos fabricants de
dispositius (o trastos) suportats per guifi.net.")?></p>

<h5 id="method_misc_manufacturer_return"><?php _e("Retorna")?></h5>

<p><?php _e("Els camps que retorna aquest mètode en cas d'èxit són els descrits a continuació:")?></p>
<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
		<col class="field_example" />
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
			<td>manufacturers</td>
			<td>array</td>
			<td><?php _e("Tots els fabricants suportats a guifi.net")?></td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">fid</dt>
				<dd class="field_type">integer</dd>
				<dd class="field_description" style="width: 545px"><?php _e("ID del fabricant.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">name</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Nom del fabricant.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">url</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Adreça web del fabricant.")?></dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_manufacturer_list"><?php _e("Llistat")?></h5>
<p><?php _e("Un llistat útil de fabricants de dispositius és el següent:")?></p>
<table class="sample">
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row">fid</th>
			<th scope="row">name</th>
			<th scope="row">url</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>1</td>
			<td>D-Link</td>
			<td>http://www.dlink.com</td>
		</tr>
		<tr>
			<td>2</td>
			<td>Linksys</td>
			<td>http://www.linksys.com</td>
		</tr>
		<tr>
			<td>3</td>
			<td>Conceptronic</td>
			<td>http://www.conceptronic.net/</td>
		</tr>
		<tr>
			<td>4</td>
			<td>US Robotics</td>
			<td>http://www.usr.com</td>
		</tr>
		<tr>
			<td>5</td>
			<td>3Com</td>
			<td>http://www.3com.com</td>
		</tr>
		<tr>
			<td>6</td>
			<td>Zyxel</td>
			<td>http://www.zyxel.com</td>
		</tr>
		<tr>
			<td>7</td>
			<td>Conceptronic</td>
			<td></td>
		</tr>
		<tr>
			<td>8</td>
			<td>Mikrotik</td>
			<td>http://mikrotik.com</td>
		</tr>
		<tr>
			<td>9</td>
			<td>Buffalo</td>
			<td>http://www.buffalotech.com</td>
		</tr>
		<tr>
			<td>10</td>
			<td>Ubiquiti</td>
			<td>http://www.ubnt.com</td>
		</tr>
		<tr>
			<td>11</td>
			<td>Meraki</td>
			<td>http://meraki.com</td>
		</tr>
		<tr>
			<td>12</td>
			<td>Gateworks</td>
			<td>http://www.gateworks.com</td>
		</tr>
		<tr>
			<td>13</td>
			<td>Asus</td>
			<td>http://www.asus.com</td>
		</tr>
		<tr>
			<td>14</td>
			<td>Pcengines</td>
			<td>http://www.pcengines.ch</td>
		</tr>
		<tr>
			<td>99</td>
			<td>Other</td>
			<td></td>
		</tr>
	</tbody>
</table>