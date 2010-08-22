<h3 id="working_responses"><?php _e("Respostes de l'API")?></h3>

<p><?php _e("Les respostes de l'API de guifi.net vénen donades també pel protocol HTTP.")?></p>

<p><?php _e("La resposta de l'API està al contingut de la resposta HTTP, i ve
formatat. Actualment, l'únic format implementat de la resposta és
mitjançant <a href=\"%s\">JSON</a>.", 'http://en.wikipedia.org/wiki/JSON')?></p>

<p><?php _e("Els camps de resposta definits per la resposta de l'API de guifi.net són els següents:")?></p>

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
			<td>command</td>
			<td>string</td>
			<td><?php _e("Nom del mètode al qual s'està responent.")?></td>
		</tr>
		<tr>
			<td>code</td>
			<td>mixed</td>
			<td><?php _e("<a href=\"%s\">Codi de resposta</a> de l'API.", '#working_codes')?></td>
		</tr>
		<tr>
			<td>responses</td>
			<td>mixed</td>
			<td><?php _e("Resposta que es retorna sobre el mètode que s'utilitza. Correspon
			al <em><strong>retorn</strong></em> de cada <a href=\"%s\">mètode</a>.", '#methods')?></td>
		</tr>
		<tr>
			<td>errors</td>
			<td>mixed</td>
			<td><?php _e("<a href=\"%s\">Codi d'error</a> en la crida a un mètode de l'API.", '#working_errors')?></td>
		</tr>
	</tbody>
</table>

<p><?php _e("Per tant, una possible resposta de l'API de guifi.net (responent a la crida anterior) seria:")?></p>

<blockquote>{"command":"guifi.misc.protocol","code":{"code":200,"str":"Request
completed
successfully"},"responses":{"protocols":[{"title":"802.11a","description":"802.11a
(1-54Mbps - 5Ghz)"},{"title":"802.11b","description":"802.11b (1-11Mbps
- 2.4Ghz)"},{"title":"802.11g","description":"802.11g (2-54Mbps -
2.4Ghz)"},{"title":"802.11n","description":"802.11n - MIMO (1-125Mbps -
2.4\/5Ghz)"},{"title":"WiMAX","description":"802.16a - WiMAX (1-125Mbps
- 2-8Ghz)"},{"title":"legacy","description":"legacy\/proprietary
protocol"}]}}</blockquote>