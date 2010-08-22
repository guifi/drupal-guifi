<h3 id="working_errors"><?php _e("Codis d'error")?></h3>

<p><?php _e("A l'utilitzar l'API de guifi.net és possible que hi hagi crides mal
formulades, que l'API no entén, o que no es poden realitzar.")?></p>

<p><?php _e("Per saber si s'està en una d'aquestes crides, l'API retorna un camp
de dades especial, <em><strong>errors</strong></em>, tal com s'explica a
<a href=\"%s\">respostes de l'API</a>.", '#working_responses')?></p>

<p><?php _e("Aquests errors estan conformats pels següents camps:")?></p>

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
			<td><?php _e("Identificador del codi d'error.")?></td>
		</tr>
		<tr>
			<td>str</td>
			<td>string</td>
			<td><?php _e("Cadena de caràcters explicativa del codi d'error (i unívoca al codi d'error).")?></td>
		</tr>
		<tr>
			<td>extra</td>
			<td>string</td>
			<td><?php _e("Cadena de caràcters opcional, per definir més concretament
			l'error. Un mateix codi d'error pot venir amb diferents valors al
			camp extra.")?></td>
		</tr>
	</tbody>
</table>

<p><?php _e("A continuació hi ha un llistat amb els possibles valors que poden tenir aquests errors.")?></p>

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
			<td>400</td>
			<td>Request is not well-formatted: input command is empty or invalid</td>
		</tr>
		<tr>
			<td>401</td>
			<td>Request is not valid: input command is not implemented</td>
		</tr>
		<tr>
			<td>402</td>
			<td>Request is not valid: some mandatory fields are missing</td>
		</tr>
		<tr>
			<td>403</td>
			<td>Request is not valid: some input data is incorrect</td>
		</tr>
		<tr>
			<td>404</td>
			<td>Request is not valid: operation is not allowed</td>
		</tr>
		<tr>
			<td>500</td>
			<td>Request could not be completed. The object was not found</td>
		</tr>
		<tr>
			<td>501</td>
			<td>You don't have the required permissions</td>
		</tr>
		<tr>
			<td>502</td>
			<td>The given Auth token is invalid</td>
		</tr>
	</tbody>
</table>