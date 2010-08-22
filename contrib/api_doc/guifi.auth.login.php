<h4 id="method_auth_login"><?php _e("guifi.auth.login")?></h4>

<p><strong><?php _e("Autentica un usuari contra guifi.net.")?></strong></p>

<h5 id="method_auth_login_params"><?php _e("Paràmetres")?></h5>

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
			<td>username</td>
			<td>string</td>
			<td><?php _e("Nom d'usuari que es vol autenticar.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>password</td>
			<td>string</td>
			<td><?php _e("Contrasenya de l'usuari que es vol autenticar.")?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<h5 id="method_auth_login_return"><?php _e("Retorn")?></h5>
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
			<td>authToken</td>
			<td>string</td>
			<td><?php _e("<a href=\"#auth_token\">Testimoni d'autenticació</a> a utilitzar per futures consultes dins de la mateixa sessió")?></td>
		</tr>
	</tbody>
</table>