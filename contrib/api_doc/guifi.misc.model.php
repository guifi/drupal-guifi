<h4 id="method_misc_model"><?php _e("guifi.misc.model")?></h4>
<p><?php _e("Aquest mètode serveix per retornar els diversos tipus de models de
dispositius (o trastos) suportats per guifi.net.")?></p>

<h5 id="method_misc_model_params"><?php _e("Paràmetres")?></h5>

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
			<td>type</td>
			<td>string</td>
			<td><?php _e("Tipus de models a retornar. Possibles valors: <em><strong>Extern</strong></em>,
			<em><strong>PCMCIA</strong></em>, <em><strong>PCI</strong></em>.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>fid</td>
			<td>integer</td>
			<td><?php _e("<a href=\"%s\">ID del fabricant</a> dels models a retornar.", '#method_misc_manufacturer')?></td>
			<td></td>
		</tr>
		<tr>
			<td>supported</td>
			<td>string</td>
			<td><?php _e("Si el model està suportat o no. Possibles valors: <em><strong>Yes</strong></em>
			(se suporta el model), <em><strong>Deprecated</strong></em> (ja no se
			suporta el model).")?></td>
			<td><em>Yes</em></td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_model_return"><?php _e("Retorna")?></h5>

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
			<td>models</td>
			<td>array</td>
			<td><?php _e("Tots els models que concorden amb els paràmetres passats")?></td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">mid</dt>
				<dd class="field_type">integer</dd>
				<dd class="field_description" style="width: 545px"><?php _e("ID del model.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">fid</dt>
				<dd class="field_type">integer</dd>
				<dd class="field_description" style="width: 545px"><?php _e("ID del fabricant del model.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">model</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Nom del model.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">type</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Tipus del model.")?></dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">supported</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px"><?php _e("Si se suporta el model o no.")?></dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_model_list"><?php _e("Llistat")?></h5>
<p><?php _e("Un llistat útil de models de dispositius és el següent:")?></p>
<table class="sample">
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row">mid</th>
			<th scope="row">fid</th>
			<th scope="row">model</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>1</td>
			<td>2</td>
			<td>WRT54Gv1-4</td>
		</tr>
		<tr>
			<td>15</td>
			<td>9</td>
			<td>WHR-HP-G54, WHR-G54S</td>
		</tr>
		<tr>
			<td>16</td>
			<td>2</td>
			<td>WRT54GL</td>
		</tr>
		<tr>
			<td>17</td>
			<td>2</td>
			<td>WRT54GSv1-2</td>
		</tr>
		<tr>
			<td>18</td>
			<td>2</td>
			<td>WRT54GSv4</td>
		</tr>
		<tr>
			<td>19</td>
			<td>8</td>
			<td>Supertrasto RB532 guifi.net</td>
		</tr>
		<tr>
			<td>20</td>
			<td>8</td>
			<td>Supertrasto RB133C guifi.net</td>
		</tr>
		<tr>
			<td>21</td>
			<td>8</td>
			<td>Supertrasto RB133 guifi.net</td>
		</tr>
		<tr>
			<td>22</td>
			<td>8</td>
			<td>Supertrasto RB112 guifi.net</td>
		</tr>
		<tr>
			<td>23</td>
			<td>8</td>
			<td>Supertrasto RB153 guifi.net</td>
		</tr>
		<tr>
			<td>24</td>
			<td>8</td>
			<td>Supertrasto guifiBUS guifi.net</td>
		</tr>
		<tr>
			<td>25</td>
			<td>10</td>
			<td>NanoStation2</td>
		</tr>
		<tr>
			<td>26</td>
			<td>10</td>
			<td>NanoStation5</td>
		</tr>
		<tr>
			<td>27</td>
			<td>8</td>
			<td>Supertrasto RB600 guifi.net</td>
		</tr>
		<tr>
			<td>28</td>
			<td>8</td>
			<td>Supertrasto RB333 guifi.net</td>
		</tr>
		<tr>
			<td>29</td>
			<td>8</td>
			<td>Supertrasto RB411 guifi.net</td>
		</tr>
		<tr>
			<td>30</td>
			<td>11</td>
			<td>Meraki/Fonera</td>
		</tr>
		<tr>
			<td>31</td>
			<td>8</td>
			<td>Supertrasto RB433 guifi.net</td>
		</tr>
		<tr>
			<td>32</td>
			<td>10</td>
			<td>LiteStation2</td>
		</tr>
		<tr>
			<td>33</td>
			<td>10</td>
			<td>LiteStation5</td>
		</tr>
		<tr>
			<td>34</td>
			<td>10</td>
			<td>NanoStation Loco2</td>
		</tr>
		<tr>
			<td>35</td>
			<td>10</td>
			<td>NanoStation Loco5</td>
		</tr>
		<tr>
			<td>36</td>
			<td>10</td>
			<td>Bullet2</td>
		</tr>
		<tr>
			<td>37</td>
			<td>10</td>
			<td>Bullet5</td>
		</tr>
		<tr>
			<td>38</td>
			<td>10</td>
			<td>RouterStation</td>
		</tr>
		<tr>
			<td>39</td>
			<td>12</td>
			<td>Avila GW2348-4</td>
		</tr>
		<tr>
			<td>40</td>
			<td>13</td>
			<td>Asus WL-500xx</td>
		</tr>
	</tbody>
</table>