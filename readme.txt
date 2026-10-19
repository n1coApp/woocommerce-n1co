=== Payment Gateway for n1co on WooCommerce===
Contributors: n1co,felipe
Requires at least: 5.0
Tested up to: 5.0.1
Requires PHP: 5.2.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==
Integra el pago por medio de la plataforma de n1co, acepta pagos con tarjeta Visa, Mastercard, American Express para sitios de comercio electrónico que se encuentren desarrollados en WordPress a través de la solución de WooCommerce. Este complemento permite al cliente realizar pagos con tarjeta de débito o crédito de una manera fácil y sencilla para el comercio y sus clientes.

n1co está disponible para las tiendas de los siguientes paises:

* Guatemala
* El Salvador
* Honduras
* Nicaragua
* Costa Rica


== Installation ==

Descarga el plugin de la Pasarela de Pago n1co desde nuestro sitio web o el repositorio de plugins de WordPress.
En el panel de administración de tu tienda WooCommerce, navega hasta "Plugins" > "Añadir nuevo".
Haz clic en el botón "Subir plugin" y selecciona el archivo ZIP descargado.
Haz clic en "Instalar ahora" y luego en "Activar" para activar el plugin de la Pasarela de Pago N1CO.

== Configuration ==

•   En el panel de administración de tu tienda WooCommerce, ve a "WooCommerce" > "Ajustes" > "Pagos".
•   En la pestaña "Pasarela de Pago", encontrarás la lista de métodos de pago disponibles. Activa "n1co" para habilitar la Pasarela de Pago n1co.
•   Haz clic en el enlace "Configurar" junto a "n1co" para acceder a la configuración específica.
•   Completa los siguientes campos con los datos generados desde el portal n1c0:
        •   “Título”: Título que los clientes verán durante el proceso de pago, puede dejar el texto predeterminado o dejarlo en blanco
        •   “Descripción”: Descripción que los clientes verán durante el proceso de pago
        •   “Debug Log”: Casilla de verificación para activar o desactivar el log de transacciones de la pasarela de pago de n1co, se recomienda activarlo únicamente si n1co lo solicita
        •   “Modo de registro n1co”: defina la forma quiere que sus clientes realicen el pago por medio de n1co, por medio un redireccionar a una nueva pestaña del navegador o por medio de un iframe dentro de su tienda
        •    “Dirección de link de pago”: Link de pago permanente generado desde el portal n1co ( https://portal.n1co.shop ) Al momento de crear tu link de pago en tu portal n1co ( https://portal.n1co.shop ), recueda crear los campos personalizados order_id y callbackurl, además tu link de pago debe ser de tipo permanente y de monto variable
        •   “Token”: Coloca el token que te proporciono el KAM de tu cuenta asociado a tu url webhook que te muestra la configuración del plugin en el paso 1 y que debes de proporcionar (	https:/tuempresa.com/wc-api/n1co_webhook )
•   Guarda los cambios.
•   Realiza cualquier configuración adicional según las instrucciones proporcionadas por n1co.

Para el correcto funcionamiento del webhook es necesario configurar Ajustes->Enlaces Permanente como “Nombre de la entrada”

== Support and Additional Documentation ==

Si tienes alguna pregunta o encuentras problemas durante la configuración o uso de la Pasarela de Pago n1co, te recomendamos consultar los siguientes recursos:
Página de soporte de n1co: https://www.n1co.com/support
Documentación y guías de n1co: https://www.n1co.com/docs
Foros de soporte de WooCommerce y WordPress.

Correo electrónico: integraciones@asesoresenweb.com
WhatsApp: ​https://wa.me/50235353683


== Changelog ==
= v1.0.00 =
* Versión inicial 2023

