
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
 * Add page to admin menu.
 *
 * @package   local_wptomoodlemigration
 * @author lds
 * @copyright  2024, Lds <kteamlds.international@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Migración de WordPress a Moodle';
$string['wp_base_url'] = 'URL base';
$string['wp_base_url_heading'] = 'Configurar URL base del cliente';
$string['certificates'] = 'Certificados';
$string['courses'] = 'Cursos';
$string['team_trainee_list'] = 'Mi equipo - Listado de becarios';
$string['orders'] = 'Pedidos';
$string['wishlist'] = 'Lista de deseos';
$string['quizes'] = 'lista de pruebas';
$string['events'] = 'Lista de eventos';

// Uday
$string['companylogo'] = 'Logotipo de la empresa';
$string['companyfavicon'] = 'Favicon';
$string['savechanges'] = 'Guardar';

// edit course feedback section
$string['editreview'] = 'Editar reseña';
$string['rating'] = 'Calificación';
$string['review'] = 'Reseña';
$string['hasreview'] = 'Has Review';
$string['timecreated'] = 'Hora de creación';
$string['pending'] = 'Pendiente';
$string['approved'] = 'Aprobado';
$string['status'] = 'Estado';
$string['invalidrating'] = 'La calificación debe estar entre 1 y 5.';
$string['reviewtoolong'] = 'La reseña es demasiado larga. Se permite un máximo de 1000 caracteres.';
$string['invalidstatus'] = 'Valor de estado no válido.';
$string['invalidhasreview'] = 'Valor no válido para Has Review.';
$string['changessaved'] = 'Comentarios actualizados correctamente.';

// Reply to user
$string['user'] = 'Usuario';
$string['review'] = 'Reseña';
$string['reply'] = 'Respuesta';
$string['submit'] = 'Entregar';
$string['required'] = 'Este campo es obligatorio.';


// $string['managedepartment'] = 'Gestionar departamento';
$string['addnewdepartment'] = 'Agregar nuevo departamento';
$string['enterdepartmentname'] = 'Ingrese el nombre del departamento';
$string['add'] = 'Agregar';
$string['departments'] = 'departamentos';
$string['manage_users'] = 'Gestionar usuarios individuales';
$string['edit'] = 'Editar';
$string['delete'] = 'Eliminar';
$string['dashboard'] = 'Tablero';
$string['myprofile'] = 'Mi Perfil';
$string['shopcourses'] = 'Comprar Cursos';
$string['mycourses'] = 'Mis Cursos';
$string['messages'] = 'Mensajes';
$string['logout'] = 'Cerrar sesión';
$string['managewhitelabeladmin'] = 'Administrar marca blanca';
$string['managebusinessusers'] = 'Gestionar usuarios de negocio';
$string['createuser'] = 'Crear usuario';
$string['businessusers'] = 'Usuarios de negocios';
$string['assignbusinessrole'] = 'Asignar Rol de Negocio';
$string['managedepartments'] = 'Gestionar Departamentos';
$string['manageusers'] = 'Gestionar usuarios';
$string['users'] = 'Usuarios';
$string['bulkupload'] = 'Carga masiva';
$string['enrolledusers'] = 'Usuarios inscritos';
$string['editusers'] = 'Editar usuarios';
$string['uploadusers'] = 'Subir usuarios';
$string['downloadsample'] = 'Descargar muestra';
$string['manageeventcourses'] = 'Gestionar cursos de eventos';
$string['manageseat'] = 'Gestionar Asiento';
$string['eventcourselist'] = 'Lista de cursos de eventos';
$string['coursefeedback'] = 'Retroalimentación del curso';
$string['search'] = 'Buscar';
$string['clear'] = 'claro';
$string['managecourses'] = 'Gestionar cursos';
$string['gradereport'] = 'Informe de calificaciones';
$string['manageevents'] = 'Gestionar eventos';
$string['coupon'] = 'Cupón';
$string['manageseats'] = 'Gestionar Asientos';
$string['bulkuserupload'] = 'Carga masiva de usuarios';
$string['uploadcsvfile'] = 'Subir archivo CSV';
$string['upload'] = 'Subir';
$string['assigndepartmentusers'] = 'Asignar usuarios al departamento: {$a}';
$string['allindividualusers'] = 'Todos los usuarios individuales';
$string['assignedusers'] = 'Usuarios asignados';
$string['remove'] = 'Eliminar';
$string['general'] = 'General';
$string['course'] = 'Curso';
$string['star'] = 'Estrella';
$string['createddate'] = 'Fecha de creación';
$string['givenby'] = 'Dado por';
$string['action'] = 'Acción';
$string['replyto'] = 'Responder a';
$string['edit'] = 'Editar';
$string['delete'] = 'Eliminar';
$string['approve'] = 'Aprobar';
$string['replytoreview'] = 'Responder a la reseña';
$string['firstname'] = 'Nombre';
$string['lastname'] = 'Apellido';
$string['email'] = 'Correo electrónico';
$string['uploadid'] = 'Subir ID';
$string['image'] = 'Imagen';
$string['usercourses'] = 'Cursos del usuario';
$string['editstudent'] = 'Editar estudiante';
$string['studentfirstname'] = 'Nombre del estudiante';
$string['studentlastname'] = 'Apellido del estudiante';
$string['name'] = 'Nombre';
$string['company'] = 'Empresa';
$string['viewdetails'] = 'Ver detalles';
$string['courserectriction'] = 'Restricción del curso';
$string['edituser'] = 'Editar usuario';
$string['viewtraineelist'] = 'Ver lista de aprendices';
$string['managecategory'] = 'Gestionar categoría';
$string['selectbusinessusers'] = 'Seleccionar usuarios empresariales';
$string['username'] = 'Nombre de usuario';
$string['companyname'] = 'Nombre de la empresa';
$string['courseassignement'] = 'Tarea del curso';
$string['assign'] = 'Asignar';
$string['migratelearnpresscourse'] = 'Migrar curso de Learnpress';
$string['migratelearnpressfeedback'] = 'Migrar comentarios de Learnpress';
$string['allcourse'] = 'Todo el curso';
$string['managecompany'] = 'Gestionar la empresa';
$string['selectcompany'] = 'Seleccionar empresa';
$string['managepayment'] = 'Gestionar el pago';
$string['productcategory'] = 'Categoría de producto';
$string['scanfacecourse'] = 'Curso Scanface';
$string['slno'] = 'N.º';
$string['category'] = 'Categoría';
$string['coursename'] = 'Nombre del curso';
$string['enabledisable'] = 'Activar/Desactivar';
$string['save'] = 'Guardar';
$string['emailtemplate'] = 'Plantillas de correo electrónico';
$string['coursefeedback'] = 'Retroalimentación del curso';
$string['selectdepartment'] = 'Seleccionar Departamento';
$string['pleaseselectdepartment'] = 'Por favor, seleccione departamento';