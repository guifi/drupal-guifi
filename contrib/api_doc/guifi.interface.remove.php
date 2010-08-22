<h4 id="method_interface_remove"><?php _e("guifi.interface.remove")?></h4>

<p><strong><?php _e("Esborra una interfície de ràdio de la xarxa.")?></strong></p>

<div class="alert">
<h6><?php _e("Vés amb compte")?></h6>
<p><?php _e("Aquesta acció no té marxa enrere, i una interfície esborrada deixarà de
mostrar-se a la pàgina web de guifi.net.")?></p>
</div>

<p><?php _e("L'API només suporta gestionar interfícies de ràdio sense fils")?>.</p>
<p><?php _e("No es poden esborrar interfícies del tipus wLan/Lan, així com tampoc
hi pot haver cap ràdio sense interfícies. Les úniques interfícies que es
poden treure són les de tipus <em><strong>wLan</strong></em>, les que
afegeixen rangs d'IP per clients.")?></p>
<p><?php _e("Queda per implementar el suport de les connexions per cable.")?></p>

<h5 id="method_interface_remove_params"><?php _e("Paràmetres")?></h5>

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
			<td>interface_id</td>
			<td>integer</td>
			<td><?php _e("ID de la interfície a esborrar.")?></td>
			<td></td>
		</tr>
	</tbody>
</table>