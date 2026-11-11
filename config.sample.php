<?php
/**
 * Plantilla de configuración del formulario de contacto.
 *
 * Copia este archivo como `config.php`, rellena los valores reales y súbelo
 * UNA VEZ al servidor, a la carpeta que contiene public_html (una por encima
 * de la raíz web). Ahí no es accesible desde el navegador y los despliegues
 * desde Git no lo sobrescriben.
 *
 * `config.php` NUNCA se sube al repositorio (está en .gitignore): contiene la
 * contraseña del buzón y el correo destino privado.
 */
return [
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => 465,
    'smtp_user' => 'info@thepetitclubbyblanca.com',
    'smtp_pass' => 'PON_AQUI_LA_CONTRASENA_DEL_BUZON',
    // Correo que RECIBE los formularios (no se muestra en ningún sitio público):
    'dest'      => 'PON_AQUI_EL_CORREO_DESTINO',
];
