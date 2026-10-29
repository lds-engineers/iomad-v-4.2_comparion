<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_user_registration
 * @category    string
 * @copyright   2023 suneet Sharma <suneet@lds-international.in>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'User Registration/SSO';
$string['sso_shared_secret'] = 'SSO Wstoken';
$string['sso_shared_secret_desc'] = 'A secret key shared between Moodle and WordPress used to securely authenticate SSO requests.';

$string['sso_registration_disable'] = 'Deshabilitar el registro SSO';
$string['sso_registration_disable_desc'] = 'Cuando está habilitada, las cuentas de usuario no se crearán mediante SSO.';

$string['register_your_account'] = 'REGISTRA TU CUENTA';
$string['choose_account_type'] = 'ELIGE EL TIPO DE CUENTA PARA CREAR';
$string['register_as_individual'] = 'Registrarse como individuo';
$string['register_as_business'] = 'Registrarse como empresa';
$string['individual_text'] = 'Regístrate como usuario individual para inscribirte en cursos para ti u otra persona';
$string['business_text'] = 'Regístrate como empresa para agregar/administrar múltiples empleados, asignar y rastrear su formación';

// individual_registration.php

$string['individual_account_registration'] = 'Formulario de registro - Individual';
$string['individual_account_registration_heading'] = 'Registro de cuenta individual';
$string['login_with_company_url'] = 'Iniciar sesión con la URL de la empresa';
$string['something_wrong'] = '¡Algo salió mal!';
$string['individual_account_title'] = 'Cuenta individual';
$string['individual_account_description'] = 'Regístrate como usuario individual para inscribirte en formación en línea para ti u otra persona. Cuenta individual donde puedes registrarte en cursos individuales o paquetes, descargar e imprimir tus certificados de finalización, o registrarte en eventos en vivo/webinars y cursos presenciales.';
$string['register_as_business_text'] = 'Si deseas registrarte como usuario empresarial y poder comprar y asignar formación a tus empleados, por favor <a href="{$a}/local/user_registration/business_registration.php">haz clic aquí</a>.';

// individual_registrationform.php

$string['select_company'] = 'Seleccione Empresa';
$string['create_account'] = 'Crear una Cuenta';
$string['user_name'] = 'Nombre de Usuario';
$string['missing_user_name'] = 'Falta Nombre de Usuario';
$string['user_name_regex'] = 'Debe contener solo letras (mayúsculas/minúsculas), números, guión bajo, guión o @';
$string['first_name'] = 'Nombre';
$string['missing_first_name'] = 'Falta Nombre';
$string['last_name'] = 'Apellido';
$string['missing_last_name'] = 'Falta Apellido';
$string['email_address'] = 'Correo Electrónico';
$string['missing_email'] = 'Falta Correo Electrónico';
$string['invalid_email'] = 'Formato de correo electrónico inválido';
$string['password'] = 'Contraseña';
$string['missing_password'] = 'Falta Contraseña';
$string['password_min_length'] = 'La contraseña debe tener al menos 6 caracteres';
$string['confirm_password'] = 'Confirmar Contraseña';
$string['missing_confirm_password'] = 'Falta Confirmar Contraseña';
$string['sign_up'] = 'Registrarse';
$string['username_already_registered'] = 'El nombre de usuario ya está registrado';
$string['email_already_registered'] = 'Este correo ya está registrado para su empresa.';
$string['passwords_not_same'] = 'Las contraseñas deben coincidir.';

// // business_registration.php
$string['registration_form_business'] = 'Formulario de Registro Empresarial';
$string['business_account_registration'] = 'Registro de Cuenta Empresarial';
$string['login_with_company_url'] = 'Inicie sesión con la URL de la empresa';
$string['access_denied'] = 'Acceso denegado';
$string['something_wrong'] = '¡Algo salió mal!';
$string['business_account'] = 'Cuenta Empresarial';
$string['business_account_description'] = 'Regístrese como administrador de la empresa y pueda agregar/gestionar múltiples empleados, comprar cursos en bloque, asignar y realizar un seguimiento de la capacitación de los empleados, incluido usted mismo. Puede comprar y asignar cursos individuales o en paquete, descargar e imprimir Certificados de Finalización, o inscribir a su equipo en eventos en vivo, seminarios web y cursos presenciales.';
$string['individual_account_redirect_text'] = 'Si desea registrarse como usuario individual y poder inscribirse en capacitación en línea para usted o otra persona que está registrando, por favor ';
$string['click_here'] = 'haga clic aquí';

// business_registrationform.php
$string['user_company_name'] = 'Nombre de la empresa';
$string['missing_company_name'] = 'Falta nombre de la empresa';
$string['managewhitelabeladmin'] = 'Administrar marca blanca';
$string['createwhitelabeladmin'] = 'Crear administrador de marca blanca';
$string['fullname'] = 'Nombre completo';
$string['email'] = 'Correo electrónico';
$string['createwhitelabeladmin'] = 'Crear cuenta de administrador de marca blanca';
$string['submit'] ='Enviar';
$string['createindividualaccount'] = 'Crear cuenta individual';
$string['createaccountformindividual'] = 'Formulario de Creación de Cuenta - Individual';
$string['createaccountformbusiness'] = 'Formulario de Creación de Cuenta - Negocios';
$string['createbusinessaccount'] = 'Crear cuenta comercial';
$string['missing_company_name'] = 'Nombre de empresa faltante';


