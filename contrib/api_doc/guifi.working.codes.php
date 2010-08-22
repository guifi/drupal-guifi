<h3 id="working_codes"><?php _e("Codis de resposta")?></h3>

<p><?php _e("Tota crida que es faci a l'API de guifi.net obté un codi de resposta,
tal i com s'explica a <a href=\"\">respostes de l'API</a>.", '#working_responses')?></p>

<p><?php _e("Aquests codis de resposta estan conformats pels següents camps:")?></p>

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
			<td>code</td>
			<td>integer</td>
			<td><?php _e("Identificador del codi de resposta.")?></td>
		</tr>
		<tr>
			<td>str</td>
			<td>string</td>
			<td><?php _e("Cadena de caràcters explicativa del codi de resposta (i unívoca al codi de resposta).")?></td>
		</tr>
	</tbody>
</table>

<p><?php _e("A continuació hi ha un llistat amb els possibles valors que poden tenir aquests codis.")?></p>

<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_description" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row"><?php _e("Codi")?></th>
			<th scope="row"><?php _e("Cadena de caràcters explicativa")?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>200</td>
			<td>Request completed successfully</td>
		</tr>
		<tr>
			<td>201</td>
			<td>Request could not be completed, errors found</td>
		</tr>
	</tbody>
</table>