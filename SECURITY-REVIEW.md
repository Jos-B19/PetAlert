# Auditoría de seguridad y autorizaciones por roles

**Proyecto:** Pet Alert Ipiales  
**Fecha:** 19 de noviembre de 2026  
**Alcance:** revisión estática, de solo lectura, de autorizaciones por rol. No se modificaron archivos de la aplicación ni datos.

## Resumen

Se encontró un hallazgo de severidad **MEDIA** en las consultas de mascotas: el listado no aplica alcance por asignación para veterinarios y devuelve modelos sin proyección explícita, incluyendo `observaciones`. La pantalla de mascotas muestra ese campo. En el detalle existe una comprobación de asignación para veterinarios, pero no se observó una restricción equivalente para recepción.

La exposición del campo está confirmada; no se pudo determinar qué información se almacena actualmente en el texto libre `observaciones`. El riesgo de divulgación depende de su contenido y clasificación.

## Hallazgos

| # | Severidad | Archivo | Líneas | Hallazgo | Confianza |
|---|---|---|---|---|---|
| 1 | 🟡 MEDIUM | `app/Http/Controllers/MascotaController.php` | 18-54, 94-120 | El listado de mascotas no limita veterinarios a casos asignados y serializa `observaciones`; el detalle no aplica a recepción una restricción equivalente. Riesgo de exposición de texto libre si contiene datos privados. | 8/10 |

### Evidencia relacionada

- `routes/web.php:114-116`: listado y detalle están disponibles para administrador, veterinario y recepción.
- `app/Http/Controllers/MascotaController.php:18-54`: el índice consulta y pagina mascotas sin alcance por asignación ni selección explícita de campos.
- `app/Http/Controllers/MascotaController.php:94-120`: el detalle verifica la asignación del veterinario, pero no se observó una restricción equivalente para recepción.
- `app/Models/Mascota.php:10-23`: el modelo contiene el atributo `observaciones`.
- `resources/js/Pages/PetAlert.jsx:830-859`: la interfaz muestra `pet.observaciones` a los roles que acceden a la pantalla.

## Permisos observados por rol

- **Administrador:** gestión de usuarios del personal; propietarios y mascotas; urgencias y egresos; acceso clínico; medicamentos y reportes.
- **Veterinario:** acceso a urgencias, atenciones, evoluciones e historiales, con restricciones por asignación observadas en varios endpoints. También puede consultar propietarios y mascotas.
- **Recepción:** gestión y consulta de propietarios y mascotas, registro/asignación de urgencias e informes operativos. No se observaron rutas de edición de evoluciones, atenciones o medicamentos.
- **Propietario:** cuenta, perfil, mascotas y urgencias vinculadas a su cuenta, además del seguimiento privado autorizado. No tiene acceso a módulos administrativos.

El middleware `app/Http/Middleware/EnsureUserHasRole.php:11-20` comprueba que la cuenta esté activa y que el rol pertenezca a los permitidos. No se encontraron Policies o Gates; el control se concentra en middleware y verificaciones de controladores.

## Protecciones verificadas

- El portal del propietario limita consultas a recursos vinculados a su cuenta. Las pruebas en `tests/Feature/OwnerPortalTest.php` y `tests/Feature/OwnerClinicalFollowUpTest.php` cubren propiedad, respuestas y exclusión de campos clínicos privados.
- Se observaron restricciones por asignación para veterinarios en urgencias, atenciones e historiales, entre ellas `UrgenciaController`, `AtencionController` y `HistorialClinicoController`.
- Se observaron controles de integridad clínica en los controladores de urgencias, atenciones y evoluciones. Esta auditoría no ejecutó esas pruebas ni verificó su comportamiento en ejecución.

## Pruebas existentes y brechas

- `tests/Feature/RoleDashboardsTest.php` comprueba que recepción no reciba ciertos datos clínicos y cubre alcance veterinario para urgencias e historiales.
- Las pruebas del portal del propietario cubren aislamiento de sus recursos y privacidad del seguimiento.
- `tests/Feature/OwnersAndPetsTest.php:81-117` cubre el listado de mascotas, pero no la exposición de `observaciones` ni el alcance por rol.
- Falta comprobar por respuesta Inertia/JSON que veterinarios no reciban mascotas no asignadas en `/pets`, y que los campos de `/pets` y `/pets/{id}` sean los autorizados para cada rol, incluida recepción.

No se ejecutaron pruebas: varias usan `RefreshDatabase`, por lo que la revisión se limitó al código fuente para evitar cualquier operación sobre datos.

## Correcciones recomendadas y prioridad

1. **Prioridad 1:** definir si `observaciones` está autorizada para cada rol; aplicar el alcance correspondiente por usuario/asignación y una proyección explícita de campos en servidor para listados y detalles.
2. **Prioridad 2:** agregar pruebas de solicitudes Inertia/JSON para veterinarios con casos asignados y no asignados, recepción y los campos expuestos en `/pets` y `/pets/{id}`.
3. **Prioridad 3:** ampliar la matriz de pruebas de rutas y métodos HTTP para todos los roles en los módulos administrativos y clínicos.

## Limitaciones

- No se inspeccionó contenido real de `observaciones`; no se puede afirmar que existan datos privados almacenados allí.
- La revisión fue estática; no se ejecutó la suite ni se consultó la base de datos.
- El directorio del proyecto no contiene metadatos Git (`.git`); por ello no es posible crear un commit sin inicializar un repositorio, acción que no se realizó.
