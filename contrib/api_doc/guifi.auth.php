<p>
	<?php _e("Per utilitzar l'API de guifi.net es necessita autenticar un usari vàlid registrat de la web de <a href=\"http://www.guifi.net\">guifi.net</a>.")?></p>

<p>
  <?php _e("Per autenticar l'usuari, s'ha de fer servir el mètode <a href=\"#method_auth_login\">guifi.auth.login</a>, descrit anteriorment.")?></p>

<p>
  <?php _e("Els mètodes tenen en compte els permisos de l'usuari autenticat, de manera que si aquest usuari no té permisos per fer una acció en concret, es retornarà un <a href=\"#working_errors\">codi d'error</a>.")?>
</p>

<h3 id="auth_token"><?php _e("Testimoni d'autenticació")?></h3>
<p>
	<?php _e("Un cop feta l'autenticació, el mateix mètode <a href=\"#method_auth_login\">guifi.auth.login</a> retorna un testimoni d'autenticació (<em>auth token</em>), que es pot fer servir per autenticar-se sense haver d'enviar l'usuari i la contrasenya un altre cop.")?>
</p>

<p>
	<?php _e("Per poder fer servir aquest testimoni d'autenticació, cal que a cada futur mètode s'inclogui la següent capçalera HTTP fins de la consulta:")?>
</p>
	
<blockquote><pre>Authorization: GuifiLogin auth=&lt;authToken&gt;</pre></blockquote>

<p>
	<?php _e("on <em><strong>&lt;authToken&gt;</strong></em> és el testimoni d'autenticació retornat pel mètode <a href=\"#method_auth_login_return\">guifi.auth.login</a>.")?>
</p>

<h3 id="auth_expire"><?php _e("Expiració del testimoni d'autenticació")?></h3>
<p>
	<?php _e("Aquest testimoni d'autenticació té una data d'expiració, que és de 12 hores a partir del moment que va ser creada.")?></p>
<p>
	<?php _e("Quan aquest testimoni d'autenticació expiri, o bé el testimoni que s'enviï al servidor sigui invàlid, el servidor retornarà un <a href=\"#working_errors\">codi d'error</a>, que significarà que el client s'ha de tornar a autenticar mitjançant el mètode <a href=\"#method_auth_login\">guifi.auth.login</a>.")?>
</p>