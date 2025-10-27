<?php
// Este archivo no es ejecutable; sirve como guion técnico totalmente comentado.
// Su objetivo es documentar el plan de trabajo del reto paso a paso.

// 1. Modelo de datos propuesto (Doctrine/SQL).
//    Entidades y relaciones mínimas para cumplir el reto.
//    - User: representa a la persona que registra tareas.
//    - Project: representa un proyecto al que pueden pertenecer varios usuarios.
//    - UserProjectRate: tabla puente N:M entre User y Project que guarda la tarifa del usuario en ese proyecto.
//    - Task: representa una tarea registrada por un usuario en un proyecto con duración/fecha y valor aplicado.
//
//    Decisiones de diseño:
//    - La tarifa puede cambiar en el tiempo. Para que el historial no cambie retroactivamente, la "tarifa aplicada"
//      se copia en cada Task al momento de crearla (desnormalización controlada).
//    - Así, si mañana el usuario cambia de tarifa, las tareas antiguas conservan su valor correcto.
//
// 2. Campos clave por entidad (versión inicial mínima):
//    - User: id, name, email (único), createdAt.
//    - Project: id, name, createdAt.
//    - UserProjectRate: id, user_id (FK), project_id (FK), rate (decimal), currency (string), createdAt.
//    - Task: id, user_id (FK), project_id (FK), title, notes (opcional), date, duration_minutes (entero),
//            applied_rate (decimal), amount (decimal), createdAt.
//
// 3. Reglas básicas:
//    - Una Task debe pertenecer a un User y a un Project válidos.
//    - Para calcular amount de una Task: amount = duration_minutes / 60 * applied_rate.
//    - applied_rate se toma de UserProjectRate vigente al registrar la tarea (o por simplicidad, la única definida).
//
// 4. API REST mínima solicitada:
//    - GET /api/users/{id}/tasks
//      Parámetros opcionales de filtro: from (YYYY-MM-DD), to (YYYY-MM-DD), project_id, page, limit.
//      Respuesta: lista paginada con cada tarea incluyendo: task_id, task_title, date, project_id, project_name,
//                 duration_minutes, applied_rate, amount.
//      Orden por defecto: date DESC.
//      Validaciones: id debe ser entero > 0; rangos de fechas válidos; project_id entero si viene.
//      Errores: 400 para parámetros inválidos, 404 si el usuario no existe.
//
// 5. Vista web mínima:
//    - Ruta web: GET /tasks/user/{id}
//    - Plantilla Twig que hace fetch a /api/users/{id}/tasks, muestra tabla con columnas:
//      Fecha, Tarea, Proyecto, Duración (min), Tarifa, Importe.
//    - Filtros simples en frontend: fecha desde/hasta y proyecto (opcional).
//
// 6. Seguridad básica para la prueba:
//    - Sanitización/validación de entradas en el controlador API (id, fechas, project_id, paginación).
//    - Manejo de errores con códigos HTTP correctos y mensajes claros.
//    - Sin autenticación obligatoria para la prueba, a menos que se pida. Si se desea, se puede agregar Bearer opcional.
//
// 7. Plan de implementación incremental (pasos siguientes):
//    Paso 2: Crear entidades y migraciones (User, Project, UserProjectRate, Task) y relaciones.
//    Paso 3: Semillas de datos de ejemplo (Fixtures) para validar rápido la API y la vista.
//    Paso 4: Repositorio/consulta eficiente para listar tareas de un usuario con joins y paginación.
//    Paso 5: Controlador API + normalización/serialización de respuesta + validaciones.
//    Paso 6: Vista Twig + JavaScript (fetch) + render de tabla + filtros básicos.
//    Paso 7: Pruebas manuales y checklist de aceptación + notas de rendimiento.
//    Paso 8: Guion del video técnico (≤ 5 min) y empaquetado final (README con instrucciones).
//
// 8. Notas SQL (MySQL y PostgreSQL) que aplicaremos en Paso 2:
//    - Los decimales se almacenarán como DECIMAL(10,2) en MySQL y NUMERIC(10,2) en PostgreSQL.
//    - duration_minutes es INT para simplificar cálculos.
//    - Índices: FK habituales y un índice por (user_id, project_id, date) en Task para acelerar listados.
//
// 9. Riesgos y cómo los mitigamos:
//    - Tarifas que cambian: mitigado con applied_rate en Task para preservar histórico.
//    - Paginación: evitamos respuestas enormes con limit y page; protegemos el backend.
//    - Validaciones: evitamos SQL innecesario si los parámetros son inválidos, devolviendo 400 temprano.
//
// 10. Criterio de “hecho” global del reto:
//     - DB creada y migraciones aplicadas.
//     - Endpoint GET /api/users/{id}/tasks funcional con filtros y paginación.
//     - Vista web que consume la API y muestra tabla con datos reales.
//     - Video corto explicando arquitectura, diseño y flujo.
