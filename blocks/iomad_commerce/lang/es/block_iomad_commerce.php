<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['add_course_to_shop'] = 'Añadir nuevo producto';
$string['add_more_license_blocks'] = 'Añadir otro bloque de licencias';
$string['addnewcourse'] = 'Añadir nuevo producto';
$string['allow_license_blocks'] = 'Permitir bloques de licencias';
$string['allow_license_blocks_help'] = 'Cuando se habilita, un administrador de cliente puede comprar bloques de cursos';
$string['allow_single_purchase'] = 'Permitir compra individual';
$string['allow_single_purchase_help'] = 'Cuando la compra individual está habilitada, los usuarios pueden comprar su propio acceso al curso';
$string['amount'] = 'Cantidad';
$string['blocktitle'] = 'IOMAD Comercio Electrónico';
$string['basket'] = 'Carrito';
$string['basket_1item'] = '<i class="fa fa-shopping-cart"></i>  {$a}';
$string['basket_nitems'] = '<i class="fa fa-shopping-cart"></i> {$a}';
$string['buycourses'] = 'Comprar cursos';
$string['buynow'] = 'Comprar ahora';
$string['categorization'] = 'Categoría';
$string['checkout'] = 'Pagar';
$string['checkoutpreamble'] = '<p></p>';
$string['commerce_admin_email'] = 'Correo electrónico del administrador de comercio';
$string['commerce_admin_email_help'] = 'Dirección de correo del responsable de la tienda.';
$string['commerce_enabled'] = '{$a} habilitado';
$string['commerce_admin_firstname'] = 'Nombre del administrador de comercio';
$string['commerce_admin_firstname_help'] = 'Nombre del responsable de la tienda, usado en los correos electrónicos';
$string['commerce_admin_lastname'] = 'Apellido del administrador de comercio';
$string['commerce_admin_lastname_help'] = 'Apellido del responsable de la tienda, usado en los correos electrónicos';
$string['commerce_default_license_access_length'] = 'Duración por defecto de acceso a la licencia (días)';
$string['commerce_default_license_access_length_help'] = 'Es la cantidad de días que los usuarios tendrán acceso a los cursos comprados desde la tienda externa.';
$string['commerce_admin_default_license_shelf_life'] = 'Duración por defecto de la licencia sin usar (días)';
$string['commerce_admin_default_license_shelf_life_help'] = 'Es el tiempo en días que la licencia puede permanecer sin usar antes de volverse inválida.';
$string['commerce_externalshop_link_timeout'] = 'Segundos antes de que caduque el enlace a la tienda externa';
$string['commerce_externalshop_link_timeout_help'] = 'Es el número de segundos que el enlace a la tienda externa permanecerá válido. Un número alto podría permitir que el enlace sea usado por otra persona.';
$string['commerce_externalshop_url'] = 'URL por defecto de la tienda externa';
$string['commerce_externalshop_url_company'] = 'URL de la tienda externa específica de la empresa';
$string['confirm'] = 'Confirmar';
$string['confirmation'] = 'Su pedido está completo';
$string['Course'] = 'Producto';
$string['courses'] = 'Productos';
$string['course_list_title'] = 'Administrar productos de la tienda';
$string['course_list_title_default'] = 'Administrar plantillas de productos';
$string['course_long_description'] = 'Descripción larga';
$string['course_shop_enabled'] = 'Visible en la tienda';
$string['course_shop_enabled_help'] = 'Marque <b>Sí</b> para mostrar este curso en la tienda';
$string['course_shop_title'] = 'Tienda';
$string['course_short_summary'] = 'Descripción corta';
$string['coursedeletecheckfull'] = '¿Está seguro de que desea eliminar {$a} y su configuración de la tienda?';
$string['courseunavailable'] = 'Este producto no está disponible.';
$string['currency'] = 'Moneda';
$string['decimalnumberonly'] = 'Sólo números decimales, por favor.';
$string['deletecourse'] = 'Eliminar producto de la tienda';
$string['edit_course_shopsettings'] = 'Editar configuración de la tienda para el producto';
$string['edit_invoice'] = 'Editar pedido';
$string['exportproduct'] = 'Exportar producto';
$string['exportproductcheckfull'] = 'Esto guardará el producto seleccionado como plantilla, que luego puede ser asignado a otras empresas.';
$string['importproduct'] = 'Importar producto';
$string['importproductcheckfull'] = 'Esto creará un nuevo producto en la tienda de la empresa actual a partir de esta plantilla. Los cursos que la empresa actual no puede ver serán eliminados del producto, así que revise la configuración después de importar.';
$string['iomad_commerce:add_course'] = 'Añadir un producto a la tienda';
$string['iomad_commerce:addinstance'] = 'Añadir un nuevo bloque de IOMAD Comercio Electrónico';
$string['iomad_commerce:admin_view'] = 'Ver páginas de administración de la tienda';
$string['iomad_commerce:buyinbulk'] = 'Acceso para comprar en bloque en la tienda';
$string['iomad_commerce:buyitnow'] = 'Acceso al botón "Comprar ahora" en la tienda';
$string['iomad_commerce:delete_course'] = 'Eliminar un producto de la tienda';
$string['iomad_commerce:edit_course'] = 'Editar un producto en la tienda';
$string['iomad_commerce:hide_course'] = 'Ocultar un producto en la tienda';
$string['iomad_commerce:manage_default'] = 'Administrar plantillas de productos';
$string['iomad_commerce:myaddinstance'] = 'Añadir un nuevo bloque de IOMAD Comercio Electrónico al panel de usuario';
$string['itemaddedsuccessfully'] = 'Producto creado con éxito';
$string['emptybasket'] = '<i class="fa fa-shopping-cart"></i> 0';
$string['error_duplicateblockstarts'] = 'Número duplicado de licencias.';
$string['error_incompatibletype'] = 'La compra individual sólo se permite para múltiples cursos cuando se trata de un programa de cursos';
$string['error_invalidblockstarts'] = 'Uno o más números de licencias no son válidos.';
$string['error_invalidblockprices'] = 'Uno o más precios no son válidos.';
$string['error_invalidblockvalidlengths'] = 'Una o más duraciones no son válidas.';
$string['error_invalidblockshelflives'] = 'Una o más duraciones de validez no son válidas.';
$string['error_singlepurchaseprice'] = 'El precio de compra individual debe ser mayor que 0.';
$string['error_singlepurchasevalidlength'] = 'La duración válida debe ser mayor que 0.';
$string['filter_by_tag'] = 'Elegir productos por categoría: ';
$string['filtered_by_tag'] = 'Productos dentro de la categoría - {$a}.';
$string['filtered_by_search'] = 'Has buscado: {$a}.';

$string['gotoshop'] = 'Ir a la tienda';
$string['hide'] = 'Ocultar de la tienda';
$string['licenseblock_start'] = 'Desde # de licencias';
$string['licenseblock_price'] = 'Precio por licencia';
$string['licenseblock_shelflife'] = 'Duración de validez (días)';
$string['licenseblock_validlength'] = 'Válido por (días)';
$string['licenseblock_n'] = '{$a} o más licencias';
$string['licenseblocks'] = 'Bloques de licencias';
$string['licenseformempty'] = 'Por favor, introduzca el número de licencias que necesita.';
$string['licenseoptionsavailableforregisteredcompanies'] = 'Opciones de licencias disponibles para empresas registradas, por favor inicie sesión';
$string['loginforlicenseoptions'] = 'Por favor, inicie sesión para ver las opciones de licencias';
$string['managecompanyproducts'] = 'Administrar productos de la empresa';
$string['managedefaultproducts'] = 'Administrar plantillas de productos';
$string['missingshortsummary'] = 'Falta la descripción corta.';
$string['moreinfo'] = 'más información';
$string['multiplecurrencies'] = 'ADVERTENCIA: Ha añadido productos de la tienda con diferentes monedas. No puede completar la transacción.';
$string['name'] = 'Nombre';
$string['nocoursesnotontheshop'] = 'No hay cursos disponibles para añadir a la tienda.';
$string['nocoursesontheshop'] = 'No hay productos en la tienda que coincidan con sus criterios.';
$string['noinvoices'] = 'No hay facturas que coincidan con sus criterios.';
$string['noproviders'] = 'No se han habilitado proveedores de pago. Por favor contacte al administrador del sitio';
$string['notconfigured'] = 'El bloque de comercio electrónico no ha sido configurado. Verifique la página de ajustes del bloque';
$string['opentoallcompanies'] = 'La tienda está disponible para todas las empresas';
$string['opentoallcompanies_help'] = 'Si esto está deshabilitado, el acceso a la tienda se puede activar por empresa a través del panel de control de IOMAD. Si lo habilita por primera vez, deberá activar la tienda para las empresas que lo requieran.';
$string['or'] = 'o';
$string['order'] = 'Pedido';
$string['orders'] = 'Pedidos';
$string['payment_options'] = 'Opciones de pago';
$string['paymentprocessing'] = 'Procesando pago';
$string['paymentprovider'] = 'Proveedor de pago';
$string['payment_provider_disabled'] = '{$a} está deshabilitado';
$string['paymentprovider_enabled'] = 'Habilitar {$a}';
$string['paymentprovider_enabled_help'] = 'Seleccione esto si desea habilitar {$a} como proveedor de pago.';
$string['pluginname'] = 'IOMAD Comercio Electrónico';
$string['postcode'] = 'Código postal';
$string['pp_historic'] = 'PayPal histórico';
$string['pricefrom'] = 'Desde {$a}';
$string['priceoptions'] = 'Opciones de precio';
$string['privacy:metadata'] = 'El bloque de IOMAD Comercio Electrónico solo muestra datos almacenados en otras ubicaciones.';
$string['privacy:metadata:invoice:id'] = 'ID de la tabla {invoice}';
$string['privacy:metadata:invoice:reference'] = 'Referencia de la factura';
$string['privacy:metadata:invoice:userid'] = 'ID de usuario de la factura';
$string['privacy:metadata:invoice:status'] = 'Estado de la factura';
$string['privacy:metadata:invoice:checkout_method'] = 'Método de pago de la factura';
$string['privacy:metadata:invoice:email'] = 'Correo de la factura';
$string['privacy:metadata:invoice:phone1'] = 'Teléfono principal de la factura';
$string['privacy:metadata:invoice:paymentid'] = 'ID de pago Moodle de la factura';
$string['privacy:metadata:invoice:company'] = 'Empresa de la factura';
$string['privacy:metadata:invoice:address'] = 'Dirección de la factura';
$string['privacy:metadata:invoice:city'] = 'Ciudad de la factura';
$string['privacy:metadata:invoice:state'] = 'Estado de la factura';
$string['privacy:metadata:invoice:country'] = 'País de la factura';
$string['privacy:metadata:invoice:postcode'] = 'Código postal de la factura';
$string['privacy:metadata:invoice:firstname'] = 'Nombre de la factura';
$string['privacy:metadata:invoice:lastname'] = 'Apellido de la factura';
$string['privacy:metadata:invoice:date'] = 'Fecha de pago de la factura';
$string['privacy:metadata:invoice'] = 'Metadatos de la factura';
$string['process'] = 'Procesar';
$string['processed'] = 'Procesado';
$string['process_help'] = 'Los artículos con casillas marcadas en la columna "Procesar" se marcarán como "pedido completado" al guardar los cambios.';
$string['productexportedsuccessfully'] = 'El producto se guardó correctamente como plantilla.';
$string['productexportfailed'] = 'Hubo un problema al intentar guardar este producto como plantilla. Si persiste, contacte al administrador del sitio.';
$string['productimportedsuccessfully'] = 'La plantilla se guardó correctamente como producto.';
$string['productimportfailed'] = 'Hubo un problema al intentar guardar esta plantilla como producto. Si persiste, contacte al administrador del sitio.';
$string['purchaser_details'] = 'Comprador';
$string['reference'] = 'Referencia';
$string['remove_filter'] = 'Mostrar todos los productos';
$string['returntoshop'] = 'Continuar comprando';
$string['review'] = 'Revisar su pedido';
$string['search'] = 'Buscar';
$string['selectcoursetoadd'] = 'Seleccione el producto para añadir a la tienda';
$string['select_tag'] = 'Seleccionar categoría';
$string['shop'] = 'Tienda';
$string['shop_title'] = 'Tienda';
$string['show'] = 'Mostrar en la tienda';
$string['single_purchase'] = 'Compra individual';
$string['single_purchase_price'] = 'Precio de compra individual';
$string['single_purchase_price_help'] = 'Precio de una licencia individual';
$string['single_purchase_validlength'] = 'Válido por (días)';
$string['single_purchase_validlength_help'] = 'El usuario estará inscrito en el curso durante estos días desde el primer acceso. Luego será removido automáticamente.';
$string['single_purchase_shelflife'] = 'Duración de validez (días)';
$string['single_purchase_shelflife_help'] = 'El usuario debe inscribirse en el curso dentro de este tiempo o la licencia expirará.';
$string['state'] = 'Estado';
$string['selectastate'] = 'Seleccionar estado';
$string['status'] = 'Estado';
$string['status_b'] = 'Carrito';
$string['status_u'] = 'No pagado';
$string['status_p'] = 'Pagado';
$string['tags'] = 'Categorías';
$string['tags_help'] = 'Categorice este producto añadiendo categorías separadas por comas. Puede seleccionar categorías existentes o añadir nuevas.';
$string['total'] = 'Total';
$string['type_quantity_1_singlepurchase'] = 'Compra individual';
$string['type_quantity_n_singlepurchase'] = 'Compra individual';
$string['type_quantity_1_licenseblock'] = ' licencia';
$string['type_quantity_n_licenseblock'] = ' licencias';

$string['type_checkout_quantity_1_singlepurchase'] = 'Compra individual';
$string['type_checkout_quantity_n_singlepurchase'] = 'Compra individual';
$string['type_checkout_quantity_1_licenseblock'] = '{$a} licencia';
$string['type_checkout_quantity_n_licenseblock'] = '{$a} licencias';
$string['license_singular'] = 'licencia';
$string['license_plural'] = 'licencias';
$string['unitprice'] = 'Precio por licencia';
$string['unprocessed'] = 'No procesado';
$string['unprocesseditems'] = 'Elementos no procesados';
$string['useexternalshop'] = 'Usar una solución de eCommerce externa para compras';
$string['useexternalshop_help'] = 'Habilite esto si tiene una solución de eCommerce externa que cuente con los servicios web necesarios para trabajar con IOMAD.';
$string['value'] = 'Valor';
$string['add_prodcategory'] = 'Seleccionar una categoría de producto';
$string['add_prodcategory_help'] = 'La categoría seleccionada aparecerá para productos tipo paquete en la página de la tienda.';
$string['free_course_help'] = 'Cuando esté marcado, este producto estará disponible gratuitamente para todos los usuarios de esta empresa.';
$string['hide_product_help'] = 'Cuando esté marcado, este producto no será visible en la página de la tienda.';
