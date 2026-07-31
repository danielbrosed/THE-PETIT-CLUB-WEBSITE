<?php
/**
 * Copia este archivo como `config.php` EN EL SERVIDOR (junto a enviar.php)
 * y rellena los valores reales. `config.php` NUNCA debe subirse al
 * repositorio (está en .gitignore): contiene la contraseña del buzón
 * y el correo destino privado.
 */
return [
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => 465,
    'smtp_user' => 'info@thepetitclubbyblanca.com',
    'smtp_pass' => 'PON_AQUI_LA_CONTRASENA_DEL_BUZON',
    // Correo que RECIBE los formularios (no se muestra en ningún sitio público):
    'dest'      => 'PON_AQUI_EL_CORREO_DESTINO',
];
