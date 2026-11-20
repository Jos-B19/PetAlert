import { useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Activity,
    ChevronRight,
    Clock3,
    FileText,
    HeartPulse,
    LayoutDashboard,
    Menu,
    PawPrint,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
    TriangleAlert,
    UserCog,
    Users,
    X,
} from 'lucide-react';

const navigationByRole = {
    administrador: [
        { title: 'GENERAL', items: [
            { id: 'dashboard', label: 'Panel administrativo', icon: 'grid', href: '/admin/dashboard' },
            { id: 'owners', label: 'Propietarios', icon: 'users', href: '/owners' },
            { id: 'pets', label: 'Mascotas', icon: 'paw', href: '/pets' },
            { id: 'users', label: 'Trabajadores y usuarios', icon: 'user-cog', href: '/users' },
        ] },
        { title: 'ATENCIÓN', items: [
            { id: 'new-emergency', label: 'Registrar urgencia', icon: 'plus', accent: true, href: '/urgencies/create' },
            { id: 'emergencies', label: 'Urgencias', icon: 'alert', href: '/urgencies' },
            { id: 'care', label: 'Atención veterinaria', icon: 'heart', href: '/attentions' },
        ] },
        { title: 'CLÍNICA', items: [
            { id: 'evolution', label: 'Evolución clínica', icon: 'activity', href: '/evolutions' },
            { id: 'history', label: 'Historial clínico', icon: 'file', href: '/histories' },
            { id: 'medications', label: 'Medicamentos', icon: 'file', href: '/medications' },
        ] },
        { title: 'ANÁLISIS', items: [
            { id: 'reports', label: 'Centro de reportes', icon: 'activity', href: '/reports' },
            { id: 'reports-ingresos', label: 'Ingresos', icon: 'paw', href: '/reports/ingresos' },
            { id: 'reports-ocupacion', label: 'Ocupación y permanencia', icon: 'clock', href: '/reports/ocupacion' },
            { id: 'reports-medicamentos', label: 'Medicamentos y tratamientos', icon: 'heart', href: '/reports/medicamentos' },
            { id: 'reports-resultados', label: 'Resultados clínicos', icon: 'file', href: '/reports/resultados' },
        ] },
    ],
    recepcion: [
        { title: 'RECEPCIÓN', items: [
            { id: 'dashboard', label: 'Panel de recepción', icon: 'grid', href: '/recepcion/dashboard' },
            { id: 'owners', label: 'Propietarios', icon: 'users', href: '/owners' },
            { id: 'pets', label: 'Mascotas', icon: 'paw', href: '/pets' },
        ] },
        { title: 'URGENCIAS', items: [
            { id: 'new-emergency', label: 'Registrar urgencia', icon: 'plus', accent: true, href: '/urgencies/create' },
            { id: 'emergencies', label: 'Consultar urgencias', icon: 'alert', href: '/urgencies' },
        ] },
        { title: 'ANÁLISIS', items: [
            { id: 'reports', label: 'Centro de reportes', icon: 'activity', href: '/reports' },
            { id: 'reports-ingresos', label: 'Ingresos', icon: 'paw', href: '/reports/ingresos' },
            { id: 'reports-ocupacion', label: 'Ocupación y permanencia', icon: 'clock', href: '/reports/ocupacion' },
        ] },
    ],
    veterinario: [
        { title: 'ATENCIÓN CLÍNICA', items: [
            { id: 'dashboard', label: 'Panel veterinario', icon: 'grid', href: '/veterinario/dashboard' },
            { id: 'emergencies', label: 'Urgencias asignadas', icon: 'alert', href: '/urgencies' },
            { id: 'care', label: 'Atención veterinaria', icon: 'heart', href: '/attentions' },
            { id: 'evolution', label: 'Evolución clínica', icon: 'activity', href: '/evolutions' },
            { id: 'history', label: 'Historial clínico', icon: 'file', href: '/histories' },
        ] },
        { title: 'CONSULTA', items: [
            { id: 'owners', label: 'Propietarios', icon: 'users', href: '/owners' },
            { id: 'pets', label: 'Mascotas', icon: 'paw', href: '/pets' },
        ] },
        { title: 'ANÁLISIS', items: [
            { id: 'reports', label: 'Centro de reportes', icon: 'activity', href: '/reports' },
            { id: 'reports-ingresos', label: 'Ingresos', icon: 'paw', href: '/reports/ingresos' },
            { id: 'reports-ocupacion', label: 'Ocupación y permanencia', icon: 'clock', href: '/reports/ocupacion' },
            { id: 'reports-medicamentos', label: 'Medicamentos y tratamientos', icon: 'heart', href: '/reports/medicamentos' },
            { id: 'reports-resultados', label: 'Resultados clínicos', icon: 'file', href: '/reports/resultados' },
        ] },
    ],
};

const icons = {
    activity: Activity,
    alert: TriangleAlert,
    chevron: ChevronRight,
    clock: Clock3,
    close: X,
    file: FileText,
    grid: LayoutDashboard,
    heart: HeartPulse,
    menu: Menu,
    paw: PawPrint,
    plus: Plus,
    search: Search,
    shield: ShieldCheck,
    trash: Trash2,
    'user-cog': UserCog,
    users: Users,
};

function Icon({ name, size = 20 }) {
    const IconComponent = icons[name] || LayoutDashboard;

    return <IconComponent size={size} strokeWidth={1.8} aria-hidden="true" focusable="false" />;
}

function RoleDashboard({ role, stats, latestUrgencies = [], veterinarians = [] }) {
    const reception = role === 'recepcion';
    const cards = reception
        ? [
            { label: 'Propietarios', value: stats.owners, icon: 'users', href: '/owners', tone: 'teal' },
            { label: 'Mascotas', value: stats.pets, icon: 'paw', href: '/pets', tone: 'blue' },
            { label: 'Urgencias pendientes', value: stats.pendingUrgencies, icon: 'clock', href: '/urgencies?estado=pendiente', tone: 'amber' },
            { label: 'En atención', value: stats.activeUrgencies, icon: 'heart', href: '/urgencies?estado=en_atencion', tone: 'purple' },
            { label: 'En observación', value: stats.observationUrgencies, icon: 'activity', href: '/urgencies?estado=en_observacion', tone: 'blue' },
            { label: 'Finalizadas', value: stats.completedUrgencies, icon: 'shield', href: '/urgencies?estado=finalizada', tone: 'green' },
        ]
        : [
            { label: 'Urgencias asignadas', value: stats.assignedUrgencies, icon: 'alert', href: '/urgencies', tone: 'coral' },
            { label: 'Pendientes', value: stats.pendingUrgencies, icon: 'clock', href: '/urgencies?estado=pendiente', tone: 'amber' },
            { label: 'Mascotas en atención', value: stats.petsInCare, icon: 'paw', href: '/urgencies?estado=en_atencion', tone: 'purple' },
            { label: 'En observación', value: stats.observationUrgencies, icon: 'activity', href: '/urgencies?estado=en_observacion', tone: 'blue' },
            { label: 'Atenciones registradas', value: stats.attentions, icon: 'heart', href: '/attentions', tone: 'teal' },
            { label: 'Evoluciones registradas', value: stats.evolutions, icon: 'activity', href: '/evolutions', tone: 'green' },
        ];

    return (
        <>
            <section className="dashboard-welcome">
                <div className="welcome-copy">
                    <span className="eyebrow">{reception ? 'GESTIÓN DE RECEPCIÓN' : 'ATENCIÓN VETERINARIA'}</span>
                    <h2>{reception ? 'Coordina cada solicitud con claridad.' : 'Tus casos clínicos, en seguimiento.'}</h2>
                    <p>{reception
                        ? 'Registra propietarios, mascotas y urgencias; consulta su estado y asigna un veterinario.'
                        : 'Consulta los casos asignados y continúa su atención clínica con registros vinculados.'}</p>
                </div>
                <div className="welcome-art" aria-hidden="true">
                    <div className="art-glow" />
                    <img src="/images/pet-alert-dashboard.jpg" alt="" />
                    <span className="art-badge"><Icon name={reception ? 'users' : 'heart'} size={18} /> {reception ? 'Atención coordinada' : 'Cuidado profesional'}</span>
                </div>
            </section>
            <section className="stat-grid" aria-label="Resumen del panel">
                {cards.map((card) => (
                    <Link href={card.href} className="stat-card" key={card.label}>
                        <span className={`stat-icon stat-${card.tone}`}><Icon name={card.icon} size={19} /></span>
                        <span className="stat-copy"><small>{card.label}</small><strong>{card.value}</strong></span>
                        <Icon name="chevron" size={16} />
                    </Link>
                ))}
            </section>
            <section className="dashboard-columns">
                <div className="dashboard-section">
                    <div className="section-heading dashboard-heading">
                        <div><p className="eyebrow">{reception ? 'SEGUIMIENTO' : 'ASIGNACIONES'}</p><h2>{reception ? 'Urgencias recientes' : 'Mis urgencias asignadas'}</h2></div>
                        <Link className="text-link" href="/urgencies">Ver todas <Icon name="chevron" size={14} /></Link>
                    </div>
                    {latestUrgencies.length ? (
                        <div className="compact-list">
                            {latestUrgencies.map((urgency) => (
                                <Link href={`/urgencies/${urgency.id}`} className="compact-row" key={urgency.id}>
                                    <span className="list-avatar"><Icon name="paw" size={17} /></span>
                                    <span className="compact-copy"><strong>{urgency.mascota.nombre}</strong><small>{urgency.tipo_urgencia} · {urgency.mascota.propietario.nombres} {urgency.mascota.propietario.apellidos}</small></span>
                                    <StatusBadge value={urgency.estado} />
                                </Link>
                            ))}
                        </div>
                    ) : <EmptyState icon="alert" title={reception ? 'Sin urgencias registradas' : 'Sin urgencias asignadas'} description={reception ? 'Las solicitudes nuevas aparecerán aquí.' : 'Cuando te asignen una urgencia, aparecerá aquí.'} />}
                </div>
                <div className="dashboard-section">
                    <div className="section-heading dashboard-heading">
                        <div><p className="eyebrow">{reception ? 'PERSONAL ACTIVO' : 'HISTORIAL'}</p><h2>{reception ? 'Veterinarios activos' : 'Accesos clínicos'}</h2></div>
                    </div>
                    {reception ? (
                        <>
                            {veterinarians.length ? <div className="compact-list">{veterinarians.map((veterinarian) => (
                                <div className="compact-row" key={veterinarian.id}>
                                    <span className="list-avatar"><Icon name="heart" size={17} /></span>
                                    <span className="compact-copy"><strong>{veterinarian.name}</strong><small>Cuenta activa</small></span>
                                    <StatusBadge value="activo" />
                                </div>
                            ))}</div> : <EmptyState icon="heart" title="Sin veterinarios activos" description="No hay veterinarios activos para asignar en este momento." />}
                            <p className="form-note">El sistema muestra cuentas activas; no hay agenda de turnos para verificar disponibilidad horaria.</p>
                        </>
                    ) : (
                        <div className="compact-list">
                            <Link className="compact-row" href="/attentions"><span className="list-avatar"><Icon name="heart" size={17} /></span><span className="compact-copy"><strong>Atenciones</strong><small>Diagnósticos y tratamientos de tus casos.</small></span><Icon name="chevron" size={16} /></Link>
                            <Link className="compact-row" href="/evolutions"><span className="list-avatar"><Icon name="activity" size={17} /></span><span className="compact-copy"><strong>Evolución clínica</strong><small>Seguimiento longitudinal de la atención.</small></span><Icon name="chevron" size={16} /></Link>
                            <Link className="compact-row" href="/histories"><span className="list-avatar"><Icon name="file" size={17} /></span><span className="compact-copy"><strong>Historiales clínicos</strong><small>Consulta restringida a casos asignados.</small></span><Icon name="chevron" size={16} /></Link>
                        </div>
                    )}
                </div>
            </section>
        </>
    );
}

function EmptyDashboard() {
    return (
        <section className="dashboard-welcome">
            <div className="welcome-copy">
                <span className="eyebrow">CENTRO DE OPERACIONES</span>
                <h2>Todo el cuidado, en un solo lugar.</h2>
                <p>Gestiona la atención de mascotas y coordina las urgencias desde un espacio claro y organizado.</p>
            </div>
            <div className="welcome-art" aria-hidden="true">
                <div className="art-glow" />
                <img
                    src="/images/pet-alert-dashboard.jpg"
                    alt=""
                />
                <span className="art-badge"><Icon name="heart" size={18} /> Cuidado con propósito</span>
            </div>
        </section>
    );
}

function Dashboard({ stats, latestUrgencies, latestPets, activities, auth }) {
    const cards = [
        { label: 'Propietarios', value: stats.owners, icon: 'users', href: '/owners', tone: 'teal' },
        { label: 'Mascotas registradas', value: stats.pets, icon: 'paw', href: '/pets', tone: 'blue' },
        { label: 'Mascotas activas', value: stats.activePets, icon: 'paw', href: '/pets?estado=activo', tone: 'green' },
        { label: 'Mascotas inactivas', value: stats.inactivePets, icon: 'paw', href: '/pets?estado=inactivo', tone: 'slate' },
        { label: 'Urgencias registradas', value: stats.urgencies, icon: 'alert', href: '/urgencies', tone: 'coral' },
        { label: 'Urgencias pendientes', value: stats.pendingUrgencies, icon: 'clock', href: '/urgencies?estado=pendiente', tone: 'amber' },
        { label: 'En atención', value: stats.activeUrgencies, icon: 'heart', href: '/urgencies?estado=en_atencion', tone: 'purple' },
        { label: 'En observación', value: stats.observationUrgencies, icon: 'activity', href: '/urgencies?estado=en_observacion', tone: 'blue' },
        { label: 'Finalizadas', value: stats.completedUrgencies, icon: 'shield', href: '/urgencies?estado=finalizada', tone: 'green' },
        { label: 'Atenciones', value: stats.attentions, icon: 'activity', href: '/attentions', tone: 'blue' },
        { label: 'Trabajadores', value: stats.staff, icon: 'user-cog', href: '/users', tone: 'slate', administratorOnly: true },
        { label: 'Trabajadores activos', value: stats.activeStaff, icon: 'user-cog', href: '/users?estado=activo', tone: 'teal', administratorOnly: true },
    ].filter((card) => (
        !(auth.user.rol === 'recepcion' && card.href.startsWith('/attentions'))
        && !(card.administratorOnly && auth.user.rol !== 'administrador')
    ));

    return (
        <>
            <EmptyDashboard />
            <section className="stat-grid" aria-label="Resumen del sistema">
                {cards.map((card) => (
                    <Link href={card.href} className="stat-card" key={card.label}>
                        <span className={`stat-icon stat-${card.tone}`}><Icon name={card.icon} size={19} /></span>
                        <span className="stat-copy"><small>{card.label}</small><strong>{card.value}</strong></span>
                        <Icon name="chevron" size={16} />
                    </Link>
                ))}
            </section>
            <section className="dashboard-columns">
                <div className="dashboard-section">
                    <div className="section-heading dashboard-heading">
                        <div><p className="eyebrow">ATENCIÓN</p><h2>Últimas urgencias</h2></div>
                        <Link className="text-link" href="/urgencies">Ver todas <Icon name="chevron" size={14} /></Link>
                    </div>
                    {latestUrgencies.length ? (
                        <div className="compact-list">
                            {latestUrgencies.map((urgency) => (
                                <Link href={`/urgencies/${urgency.id}`} className="compact-row" key={urgency.id}>
                                    <span className="list-avatar"><Icon name="paw" size={17} /></span>
                                    <span className="compact-copy"><strong>{urgency.mascota.nombre}</strong><small>{urgency.tipo_urgencia} · {urgency.mascota.propietario.nombres} {urgency.mascota.propietario.apellidos}</small></span>
                                    <StatusBadge value={urgency.estado} />
                                </Link>
                            ))}
                        </div>
                    ) : <EmptyState icon="alert" title="Sin urgencias registradas" description="Las urgencias creadas aparecerán aquí." />}
                </div>
                <div className="dashboard-section">
                    <div className="section-heading dashboard-heading">
                        <div><p className="eyebrow">REGISTROS</p><h2>Últimas mascotas</h2></div>
                        <Link className="text-link" href="/pets">Ver todas <Icon name="chevron" size={14} /></Link>
                    </div>
                    {latestPets.length ? (
                        <div className="compact-list">
                            {latestPets.map((pet) => (
                                <Link href={`/pets/${pet.id}`} className="compact-row" key={pet.id}>
                                    <span className="list-avatar"><Icon name="paw" size={17} /></span>
                                    <span className="compact-copy"><strong>{pet.nombre}</strong><small>{pet.especie}{pet.raza ? ` · ${pet.raza}` : ''} · {pet.propietario.nombres} {pet.propietario.apellidos}</small></span>
                                    <Icon name="chevron" size={16} />
                                </Link>
                            ))}
                        </div>
                    ) : <EmptyState icon="paw" title="Sin mascotas registradas" description="Las mascotas registradas aparecerán aquí." />}
                </div>
            </section>
            <section className="dashboard-section activity-section">
                <div className="section-heading dashboard-heading">
                    <div><p className="eyebrow">SISTEMA</p><h2>Actividad reciente</h2></div>
                </div>
                {activities.length ? (
                    <div className="activity-list">
                        {activities.map((activity, index) => (
                            <div className="activity-row" key={`${activity.type}-${activity.date}-${index}`}>
                                <span className="activity-dot"><Icon name={activity.type === 'urgency' ? 'alert' : activity.type === 'attention' ? 'heart' : 'activity'} size={15} /></span>
                                <span className="compact-copy"><strong>{activity.title}</strong><small>{activity.detail}</small></span>
                                <time>{formatDate(activity.date)}</time>
                            </div>
                        ))}
                    </div>
                ) : <EmptyState icon="file" title="Sin actividad reciente" description="La actividad registrada en el sistema aparecerá aquí." />}
            </section>
        </>
    );
}

function FormField({ label, children, className = '', error }) {
    return (
        <label className={`form-field ${className} ${error ? 'form-field-invalid' : ''}`}>
            <span>{label}</span>
            {children}
            {error && <small className="field-error">{error}</small>}
        </label>
    );
}

function EmptyState({ icon, title, description, action }) {
    return (
        <div className="module-empty">
            <div className="empty-illustration"><Icon name={icon} size={26} /></div>
            <h2>{title}</h2>
            <p>{description}</p>
            {action}
        </div>
    );
}

function StatusBadge({ value }) {
    const labels = {
        activo: 'Activo',
        inactivo: 'Inactivo',
        baja: 'Baja',
        media: 'Media',
        alta: 'Alta',
        critica: 'Crítica',
        pendiente: 'Pendiente',
        en_atencion: 'En atención',
        en_observacion: 'En observación',
        en_proceso: 'En proceso',
        finalizada: 'Finalizada',
        cancelada: 'Cancelada',
    };

    return <span className={`status-badge status-${value}`}>{labels[value] || value}</span>;
}

function formatDate(value, withTime = false) {
    if (! value) return '—';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '—';

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        ...(withTime ? { timeStyle: 'short' } : {}),
    }).format(date);
}

function InputField({ form, name, label, type = 'text', required = false, placeholder, className = '', min, max, step }) {
    return (
        <FormField label={label} error={form.errors[name]} className={className}>
            <input
                type={type}
                name={name}
                value={form.data[name] ?? ''}
                onChange={(event) => form.setData(name, event.target.value)}
                required={required}
                placeholder={placeholder}
                min={min}
                max={max}
                step={step}
            />
        </FormField>
    );
}

function TextareaField({ form, name, label, required = false, rows = 4, className = '' }) {
    return (
        <FormField label={label} error={form.errors[name]} className={className}>
            <textarea name={name} rows={rows} value={form.data[name] ?? ''} onChange={(event) => form.setData(name, event.target.value)} required={required} />
        </FormField>
    );
}

function SelectField({ form, name, label, options, required = false, placeholder = 'Selecciona una opción', disabled = false }) {
    return (
        <FormField label={label} error={form.errors[name]}>
            <select name={name} value={form.data[name] ?? ''} onChange={(event) => form.setData(name, event.target.value)} required={required} disabled={disabled}>
                <option value="">{placeholder}</option>
                {options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
        </FormField>
    );
}

function EntityForm({ form, title, description, onSubmit, submitLabel, cancelHref, children }) {
    return (
        <section className="form-panel entity-form-panel">
            <div className="form-panel-heading">
                <div className="form-heading-icon"><Icon name="file" size={20} /></div>
                <div><h2>{title}</h2><p>{description}</p></div>
            </div>
            <form onSubmit={onSubmit}>
                <div className="form-grid">{children}</div>
                <div className="form-actions">
                    <Link className="secondary-button" href={cancelHref}>Cancelar</Link>
                    <button className="primary-button" type="submit" disabled={form.processing}>
                        {form.processing && <span className="button-spinner" />}
                        {form.processing ? 'Guardando...' : submitLabel}
                    </button>
                </div>
            </form>
        </section>
    );
}

const moduleViews = {
    owners: {
        description: 'Propietarios registrados y sus mascotas asociadas.',
        icon: 'users',
        columns: ['Propietario', 'Documento', 'Contacto', 'Mascotas', 'Urgencias', 'Estado'],
        href: (row) => `/owners/${row.id}`,
        values: (row) => [`${row.nombres} ${row.apellidos}`, `${row.tipo_documento} · ${row.numero_documento}`, row.correo || row.telefono, row.mascotas_count, row.urgencias_count, <StatusBadge value={row.estado} />],
    },
    pets: {
        description: 'Mascotas registradas y su propietario responsable.',
        icon: 'paw',
        columns: ['Mascota', 'Especie', 'Propietario', 'Estado'],
        href: (row) => `/pets/${row.id}`,
        values: (row) => [row.nombre, `${row.especie}${row.raza ? ` · ${row.raza}` : ''}`, `${row.propietario.nombres} ${row.propietario.apellidos}`, <StatusBadge value={row.estado} />],
    },
    urgencies: {
        description: 'Seguimiento de urgencias y su estado de atención.',
        icon: 'alert',
        columns: ['Urgencia', 'Mascota', 'Prioridad', 'Ingreso', 'Estado'],
        href: (row) => `/urgencies/${row.id}`,
        values: (row) => [row.tipo_urgencia, row.mascota.nombre, <StatusBadge value={row.nivel_prioridad} />, formatDate(row.fecha_ingreso, true), <StatusBadge value={row.estado} />],
    },
    attentions: {
        description: 'Atenciones veterinarias asociadas a urgencias reales.',
        icon: 'heart',
        columns: ['Mascota', 'Diagnóstico', 'Veterinario', 'Fecha', 'Estado'],
        href: (row) => `/attentions/${row.id}`,
        values: (row) => [row.urgencia?.mascota?.nombre || '—', row.diagnostico, row.veterinario?.name || '—', formatDate(row.fecha_atencion, true), <StatusBadge value={row.estado} />],
    },
    evolutions: {
        description: 'Evoluciones clínicas registradas durante las atenciones.',
        icon: 'activity',
        columns: ['Mascota', 'Evolución', 'Veterinario', 'Peso', 'Fecha'],
        href: (row) => `/evolutions/${row.id}`,
        values: (row) => [row.atencion?.urgencia?.mascota?.nombre || '—', row.descripcion, row.veterinario?.name || '—', row.peso ? `${row.peso} kg` : '—', formatDate(row.fecha_evolucion, true)],
    },
    histories: {
        description: 'Historial clínico y cronología por mascota.',
        icon: 'file',
        columns: ['Mascota', 'Especie', 'Propietario', 'Actualizado'],
        href: (row) => `/histories/${row.id}`,
        values: (row) => [row.nombre, row.especie, `${row.propietario.nombres} ${row.propietario.apellidos}`, formatDate(row.historial_clinico?.updated_at)],
    },
    users: {
        description: 'Usuarios y roles autorizados para acceder al sistema.',
        icon: 'user-cog',
        columns: ['Trabajador', 'Documento', 'Contacto', 'Cargo', 'Rol', 'Estado'],
        href: (row) => `/users/${row.id}/edit`,
        values: (row) => [<span className="staff-cell"><span className="staff-avatar">{row.name.slice(0, 1).toUpperCase()}</span>{row.name}</span>, `${row.tipo_documento || '—'} · ${row.numero_documento || '—'}`, `${row.email} · ${row.telefono || '—'}`, row.cargo || 'Sin cargo registrado', row.rol, <StatusBadge value={row.estado} />],
    },
};

function Pagination({ records }) {
    if (! records?.links || records.last_page < 2) return null;

    const label = (value) => value.replace('&laquo; Previous', '← Anterior').replace('Next &raquo;', 'Siguiente →');

    return (
        <nav className="pagination" aria-label="Paginación">
            <span>Página {records.current_page} de {records.last_page}</span>
            <div>{records.links.map((link, index) => link.url
                ? <Link key={`${link.label}-${index}`} className={link.active ? 'pagination-active' : ''} href={link.url}>{label(link.label)}</Link>
                : <span key={`${link.label}-${index}`} className="pagination-disabled">{label(link.label)}</span>)}</div>
        </nav>
    );
}

function ModuleList({ moduleKey, records, auth, filters = {}, filterOptions = {}, roles = [] }) {
    const module = moduleViews[moduleKey];
    const rows = records?.data || [];

    return (
        <section className="module-content">
            <div className="module-intro"><p>{module.description}</p></div>
            {['owners', 'pets', 'urgencies', 'users'].includes(moduleKey) && (
                <form className="module-filters" method="get" action={`/${moduleKey === 'owners' ? 'owners' : moduleKey === 'pets' ? 'pets' : moduleKey === 'urgencies' ? 'urgencies' : 'users'}`}>
                    {filters.q && <input type="hidden" name="q" value={filters.q} />}
                    {moduleKey === 'owners' && (
                        <label>Estado
                            <select name="estado" defaultValue={filters.estado || ''}>
                                <option value="">Todos</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option>
                            </select>
                        </label>
                    )}
                    {moduleKey === 'pets' && (
                        <>
                            <label>Especie
                                <select name="especie" defaultValue={filters.especie || ''}>
                                    <option value="">Todas</option>{(filterOptions.species || []).map((species) => <option key={species} value={species}>{species}</option>)}
                                </select>
                            </label>
                            <label>Propietario
                                <select name="propietario_id" defaultValue={filters.propietario_id || ''}>
                                    <option value="">Todos</option>{(filterOptions.owners || []).map((owner) => <option key={owner.id} value={owner.id}>{owner.nombres} {owner.apellidos}</option>)}
                                </select>
                            </label>
                            <label>Estado
                                <select name="estado" defaultValue={filters.estado || ''}>
                                    <option value="">Todos</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option>
                                </select>
                            </label>
                        </>
                    )}
                    {moduleKey === 'urgencies' && (
                        <label>Estado
                            <select name="estado" defaultValue={filters.estado || ''}>
                                <option value="">Todos</option>{['pendiente', 'en_atencion', 'en_observacion', 'finalizada', 'cancelada'].map((status) => <option key={status} value={status}>{status.replace('_', ' ')}</option>)}
                            </select>
                        </label>
                    )}
                    {moduleKey === 'users' && (
                        <>
                            <label>Rol
                                <select name="rol" defaultValue={filters.rol || ''}>
                                    <option value="">Todos</option>{roles.map((role) => <option key={role} value={role}>{role}</option>)}
                                </select>
                            </label>
                            <label>Estado
                                <select name="estado" defaultValue={filters.estado || ''}>
                                    <option value="">Todos</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option>
                                </select>
                            </label>
                        </>
                    )}
                    <button className="secondary-button" type="submit">Aplicar filtros</button>
                </form>
            )}
            <div className="data-panel">
                {rows.length ? (
                    <div className="table-scroll">
                        <table>
                            <thead><tr>{module.columns.map((column) => <th key={column}>{column}</th>)}<th>Acciones</th></tr></thead>
                            <tbody>{rows.map((row) => (
                                <tr key={row.id}>
                                    {module.values(row).map((value, index) => <td key={index}>{index === 0 ? <Link className="table-primary-link" href={module.href(row)}>{value}</Link> : value}</td>)}
                                    <td>
                                        <div className="table-actions">
                                            <Link className="table-icon-button" href={module.href(row)} aria-label={`Ver registro ${row.id}`}><Icon name="file" size={16} /></Link>
                                            {moduleKey === 'users' && (
                                                <>
                                                    <button
                                                        className="table-icon-button"
                                                        disabled={auth.user.id === row.id}
                                                        title={auth.user.id === row.id ? 'No puedes cambiar tu propio estado' : row.estado === 'activo' ? 'Desactivar usuario' : 'Activar usuario'}
                                                        onClick={() => router.patch(`/users/${row.id}/toggle-status`)}
                                                    >
                                                        <Icon name={row.estado === 'activo' ? 'close' : 'activity'} size={16} />
                                                    </button>
                                                    <button
                                                        className="table-icon-button"
                                                        disabled={auth.user.id === row.id}
                                                        title="Eliminar con protección del historial"
                                                        onClick={() => {
                                                            if (window.confirm(`¿Eliminar o desactivar a ${row.name}?`)) {
                                                                router.delete(`/users/${row.id}`);
                                                            }
                                                        }}
                                                    >
                                                        <Icon name="trash" size={16} />
                                                    </button>
                                                </>
                                            )}
                                            {moduleKey === 'owners' && auth.user.rol === 'administrador' && (
                                                <button
                                                    className="table-icon-button"
                                                    title={row.estado === 'activo' ? 'Desactivar propietario' : 'Activar propietario'}
                                                    onClick={() => {
                                                        if (window.confirm(`${row.estado === 'activo' ? '¿Desactivar' : '¿Activar'} a ${row.nombres} ${row.apellidos}?`)) {
                                                            router.patch(`/owners/${row.id}/toggle-status`);
                                                        }
                                                    }}
                                                >
                                                    <Icon name={row.estado === 'activo' ? 'close' : 'activity'} size={16} />
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}</tbody>
                        </table>
                    </div>
                ) : <EmptyState icon={module.icon} title="No hay registros para mostrar" description="Los registros aparecerán cuando se creen en el sistema." />}
                {rows.length > 0 && <Pagination records={records} />}
            </div>
        </section>
    );
}

function OwnerForm({ owner }) {
    const form = useForm(`owner:${owner?.id ?? 'new'}`, {
        nombres: owner?.nombres || '', apellidos: owner?.apellidos || '', tipo_documento: owner?.tipo_documento || '',
        numero_documento: owner?.numero_documento || '', telefono: owner?.telefono || '', correo: owner?.correo || '',
        direccion: owner?.direccion || '',
    });
    const submit = (event) => {
        event.preventDefault();
        owner ? form.put(`/owners/${owner.id}`) : form.post('/owners');
    };

    return (
        <EntityForm form={form} title={owner ? 'Editar propietario' : 'Registrar propietario'} description="Los datos de contacto se validan antes de guardar." onSubmit={submit} submitLabel={owner ? 'Guardar cambios' : 'Registrar propietario'} cancelHref={owner ? `/owners/${owner.id}` : '/owners'}>
            <InputField form={form} name="nombres" label="Nombres" required />
            <InputField form={form} name="apellidos" label="Apellidos" required />
            <InputField form={form} name="tipo_documento" label="Tipo de documento" required />
            <InputField form={form} name="numero_documento" label="Número de documento" required />
            <InputField form={form} name="telefono" label="Teléfono" required />
            <InputField form={form} name="correo" label="Correo electrónico" type="email" />
            <InputField form={form} name="direccion" label="Dirección" className="form-field-wide" />
        </EntityForm>
    );
}

function PetForm({ pet, owners }) {
    const form = useForm(`pet:${pet?.id ?? 'new'}`, {
        propietario_id: pet?.propietario_id || '', nombre: pet?.nombre || '', especie: pet?.especie || '',
        raza: pet?.raza || '', sexo: pet?.sexo || '', fecha_nacimiento: pet?.fecha_nacimiento || '',
        peso: pet?.peso || '', color: pet?.color || '', observaciones: pet?.observaciones || '', estado: pet?.estado || 'activo',
    });
    const submit = (event) => {
        event.preventDefault();
        pet ? form.put(`/pets/${pet.id}`) : form.post('/pets');
    };

    return (
        <EntityForm form={form} title={pet ? 'Editar mascota' : 'Registrar mascota'} description="Cada mascota debe estar asociada a un propietario existente." onSubmit={submit} submitLabel={pet ? 'Guardar cambios' : 'Registrar mascota'} cancelHref={pet ? `/pets/${pet.id}` : '/pets'}>
            <SelectField form={form} name="propietario_id" label="Propietario" required options={owners.map((owner) => ({ value: owner.id, label: `${owner.nombres} ${owner.apellidos} · ${owner.numero_documento}` }))} />
            <InputField form={form} name="nombre" label="Nombre" required />
            <InputField form={form} name="especie" label="Especie" required />
            <InputField form={form} name="raza" label="Raza" />
            <InputField form={form} name="sexo" label="Sexo" />
            <InputField form={form} name="fecha_nacimiento" label="Fecha de nacimiento" type="date" />
            <InputField form={form} name="peso" label="Peso (kg)" type="number" />
            <InputField form={form} name="color" label="Color" />
            <SelectField form={form} name="estado" label="Estado" required options={[{ value: 'activo', label: 'Activo' }, { value: 'inactivo', label: 'Inactivo' }]} />
            <TextareaField form={form} name="observaciones" label="Información adicional" className="form-field-wide" />
        </EntityForm>
    );
}

function EmergencyForm({ urgency, pets = [], veterinarians = [] }) {
    const form = useForm(`urgency:${urgency?.id ?? 'new'}`, {
        mascota_id: urgency?.mascota_id || '', tipo_urgencia: urgency?.tipo_urgencia || '',
        descripcion: urgency?.descripcion || '', nivel_prioridad: urgency?.nivel_prioridad || 'media',
        fecha_ingreso: urgency?.fecha_ingreso ? urgency.fecha_ingreso.slice(0, 16) : '',
        veterinario_id: urgency?.veterinario_id || '', estado: urgency?.estado || 'pendiente',
    });
    const submit = (event) => {
        event.preventDefault();
        urgency ? form.put(`/urgencies/${urgency.id}`) : form.post('/urgencies');
    };

    return (
        <EntityForm form={form} title={urgency ? 'Actualizar urgencia' : 'Registrar urgencia'} description="Relaciona el reporte con una mascota y documenta la situación." onSubmit={submit} submitLabel={urgency ? 'Actualizar urgencia' : 'Registrar urgencia'} cancelHref={urgency ? `/urgencies/${urgency.id}` : '/urgencies'}>
            {!urgency && <SelectField form={form} name="mascota_id" label="Mascota" required options={pets.map((pet) => ({ value: pet.id, label: `${pet.nombre} · ${pet.propietario.nombres} ${pet.propietario.apellidos}` }))} />}
            {!urgency && <InputField form={form} name="tipo_urgencia" label="Tipo de urgencia" required />}
            {!urgency && <SelectField form={form} name="nivel_prioridad" label="Prioridad" required options={['baja', 'media', 'alta', 'critica'].map((value) => ({ value, label: value[0].toUpperCase() + value.slice(1) }))} />}
            {!urgency && <InputField form={form} name="fecha_ingreso" label="Fecha y hora de ingreso" type="datetime-local" required />}
            {!urgency && <SelectField form={form} name="veterinario_id" label="Veterinario asignado" options={veterinarians.map((vet) => ({ value: vet.id, label: vet.name }))} />}
            {!urgency && <TextareaField form={form} name="descripcion" label="Motivo y síntomas" required className="form-field-wide" />}
            {urgency && <SelectField form={form} name="estado" label="Estado" required options={['pendiente', 'en_atencion', 'en_observacion', 'finalizada', 'cancelada'].map((value) => ({ value, label: value.replace('_', ' ') }))} />}
            {urgency && <SelectField form={form} name="veterinario_id" label="Veterinario asignado" options={veterinarians.map((vet) => ({ value: vet.id, label: vet.name }))} />}
        </EntityForm>
    );
}

function AttentionForm({ attention, urgencies = [], veterinarians = [] }) {
    const form = useForm(`attention:${attention?.id ?? 'new'}`, {
        urgencia_id: attention?.urgencia_id || '', veterinario_id: attention?.veterinario_id || '',
        diagnostico: attention?.diagnostico || '', tratamiento: attention?.tratamiento || '',
        procedimientos: attention?.procedimientos || '', observaciones: attention?.observaciones || '',
        fecha_atencion: attention?.fecha_atencion ? attention.fecha_atencion.slice(0, 16) : '',
        estado: attention?.estado || 'en_proceso',
    });
    const submit = (event) => {
        event.preventDefault();
        attention ? form.put(`/attentions/${attention.id}`) : form.post('/attentions');
    };

    return (
        <EntityForm form={form} title={attention ? 'Editar atención' : 'Registrar atención veterinaria'} description="La atención debe corresponder a una urgencia activa." onSubmit={submit} submitLabel={attention ? 'Guardar cambios' : 'Guardar atención'} cancelHref={attention ? `/attentions/${attention.id}` : '/attentions'}>
            {!attention && <SelectField form={form} name="urgencia_id" label="Urgencia y mascota" required options={urgencies.map((item) => ({ value: item.id, label: `${item.mascota.nombre} · ${item.tipo_urgencia} · ${formatDate(item.fecha_ingreso)}` }))} />}
            {!attention && <SelectField form={form} name="veterinario_id" label="Veterinario" options={veterinarians.map((vet) => ({ value: vet.id, label: vet.name }))} />}
            <InputField form={form} name="fecha_atencion" label="Fecha y hora de atención" type="datetime-local" required={!attention} />
            <TextareaField form={form} name="diagnostico" label="Diagnóstico" required className="form-field-wide" />
            <TextareaField form={form} name="tratamiento" label="Tratamiento y medicamentos" className="form-field-wide" />
            <TextareaField form={form} name="procedimientos" label="Procedimientos" className="form-field-wide" />
            <TextareaField form={form} name="observaciones" label="Observaciones" className="form-field-wide" />
            {attention && <SelectField form={form} name="estado" label="Estado de atención" required options={[{ value: 'en_proceso', label: 'En proceso' }, { value: 'finalizada', label: 'Finalizada' }]} />}
        </EntityForm>
    );
}

function EvolutionForm({ attentions = [], veterinarians = [] }) {
    const form = useForm({ atencion_id: '', veterinario_id: '', descripcion: '', tratamiento_aplicado: '', estado_mascota: '', peso: '', fecha_evolucion: '', observaciones: '' });
    const submit = (event) => {
        event.preventDefault();
        form.post('/evolutions');
    };

    return (
        <EntityForm form={form} title="Registrar evolución clínica" description="El registro quedará asociado a una atención en la cronología clínica." onSubmit={submit} submitLabel="Guardar evolución" cancelHref="/evolutions">
            <SelectField form={form} name="atencion_id" label="Atención" required options={attentions.map((item) => ({ value: item.id, label: `${item.urgencia.mascota.nombre} · ${formatDate(item.fecha_atencion)}` }))} />
            <SelectField form={form} name="veterinario_id" label="Veterinario" options={veterinarians.map((vet) => ({ value: vet.id, label: vet.name }))} />
            <InputField form={form} name="fecha_evolucion" label="Fecha y hora" type="datetime-local" required />
            <InputField form={form} name="peso" label="Peso (kg)" type="number" />
            <InputField form={form} name="estado_mascota" label="Estado de la mascota" />
            <TextareaField form={form} name="descripcion" label="Evolución" required className="form-field-wide" />
            <TextareaField form={form} name="tratamiento_aplicado" label="Tratamiento aplicado" className="form-field-wide" />
            <TextareaField form={form} name="observaciones" label="Observaciones" className="form-field-wide" />
        </EntityForm>
    );
}

function UserForm({ managedUser, roles = [], auth }) {
    const self = managedUser?.id === auth.user.id;
    const form = useForm(`user:${managedUser?.id ?? 'new'}`, {
        name: managedUser?.name || '', email: managedUser?.email || '', telefono: managedUser?.telefono || '',
        tipo_documento: managedUser?.tipo_documento || '', numero_documento: managedUser?.numero_documento || '',
        direccion: managedUser?.direccion || '', cargo: managedUser?.cargo || '', rol: managedUser?.rol || '',
        estado: managedUser?.estado || 'activo', password: '', password_confirmation: '',
    });
    const submit = (event) => {
        event.preventDefault();
        managedUser ? form.put(`/users/${managedUser.id}`) : form.post('/users');
    };
    const roleLabels = { administrador: 'Administrador', veterinario: 'Veterinario', recepcion: 'Recepción' };

    return (
        <EntityForm form={form} title={managedUser ? 'Editar trabajador' : 'Registrar trabajador'} description="El acceso y el rol se validan en el servidor; las contraseñas se cifran." onSubmit={submit} submitLabel={managedUser ? 'Guardar cambios' : 'Crear trabajador'} cancelHref="/users">
            <InputField form={form} name="name" label="Nombre" required />
            <InputField form={form} name="email" label="Correo electrónico" type="email" required />
            <InputField form={form} name="telefono" label="Teléfono" required />
            <InputField form={form} name="tipo_documento" label="Tipo de documento" required />
            <InputField form={form} name="numero_documento" label="Número de documento" required />
            <InputField form={form} name="cargo" label="Cargo" required />
            <InputField form={form} name="direccion" label="Dirección" />
            <SelectField form={form} name="rol" label="Rol" required disabled={self} options={roles.map((role) => ({ value: role, label: roleLabels[role] || role }))} />
            <SelectField form={form} name="estado" label="Estado" required disabled={self} options={[{ value: 'activo', label: 'Activo' }, { value: 'inactivo', label: 'Inactivo' }]} />
            {managedUser && self && <p className="form-note form-field-wide">No puedes cambiar tu propio rol ni estado.</p>}
            {!self && <InputField form={form} name="password" label={managedUser ? 'Nueva contraseña (opcional)' : 'Contraseña'} type="password" required={!managedUser} />}
            {!self && <InputField form={form} name="password_confirmation" label="Confirmar contraseña" type="password" required={!managedUser} />}
        </EntityForm>
    );
}

function DetailHeader({ title, subtitle, editHref }) {
    return (
        <div className="detail-header">
            <div><p className="eyebrow">INFORMACIÓN DEL REGISTRO</p><h2>{title}</h2><p>{subtitle}</p></div>
            {editHref && <Link className="secondary-button" href={editHref}>Editar información</Link>}
        </div>
    );
}

function OwnerDetails({ owner, recentUrgencies = [], recentAttentions = [], auth }) {
    const canManage = auth.user.rol !== 'veterinario';
    const canChangeStatus = auth.user.rol === 'administrador';
    const remove = () => {
        if (window.confirm(`¿Eliminar el registro de ${owner.nombres} ${owner.apellidos}?`)) {
            router.delete(`/owners/${owner.id}`);
        }
    };

    return (
        <section className="details-layout">
            <div className="data-panel">
                <DetailHeader title={`${owner.nombres} ${owner.apellidos}`} subtitle={`Documento ${owner.tipo_documento} · ${owner.numero_documento}`} editHref={canManage ? `/owners/${owner.id}/edit` : null} />
                <dl className="detail-grid">
                    <div><dt>Teléfono</dt><dd>{owner.telefono}</dd></div>
                    <div><dt>Correo</dt><dd>{owner.correo || owner.user?.email || 'No registrado'}</dd></div>
                    <div><dt>Dirección</dt><dd>{owner.direccion || 'No registrada'}</dd></div>
                    <div><dt>Mascotas asociadas</dt><dd>{owner.mascotas.length}</dd></div>
                    <div><dt>Urgencias asociadas</dt><dd>{owner.urgencias_count || 0}</dd></div>
                    <div><dt>Estado de cuenta</dt><dd><StatusBadge value={owner.estado} /></dd></div>
                </dl>
                <div className="detail-section-heading"><h3>Mascotas</h3>{canManage && <Link className="text-link" href="/pets/create">Registrar mascota <Icon name="chevron" size={14} /></Link>}</div>
                {owner.mascotas.length ? <div className="compact-list">{owner.mascotas.map((pet) => (
                    <Link className="compact-row" href={`/pets/${pet.id}`} key={pet.id}>
                        <span className="list-avatar"><Icon name="paw" size={17} /></span>
                        <span className="compact-copy"><strong>{pet.nombre}</strong><small>{pet.especie}{pet.raza ? ` · ${pet.raza}` : ''}</small></span>
                        <StatusBadge value={pet.estado} />
                    </Link>
                ))}</div> : <EmptyState icon="paw" title="Sin mascotas asociadas" description="Asocia una mascota para ver su información aquí." action={canManage && <Link className="primary-button" href="/pets/create"><Icon name="plus" size={17} /> Registrar mascota</Link>} />}
                <div className="detail-section-heading"><h3>Urgencias recientes</h3></div>
                {recentUrgencies.length ? <div className="compact-list">{recentUrgencies.map((urgency) => (
                    <Link className="compact-row" href={`/urgencies/${urgency.id}`} key={urgency.id}>
                        <span className="list-avatar"><Icon name="alert" size={17} /></span>
                        <span className="compact-copy"><strong>{urgency.mascota.nombre} · {urgency.tipo_urgencia}</strong><small>{formatDate(urgency.fecha_ingreso, true)} · {urgency.veterinario?.name || 'Sin veterinario asignado'}</small></span>
                        <StatusBadge value={urgency.estado} />
                    </Link>
                ))}</div> : <EmptyState icon="alert" title="Sin urgencias asociadas" description="Las urgencias de este propietario aparecerán aquí." />}
                <div className="detail-section-heading"><h3>Atenciones recientes</h3></div>
                {recentAttentions.length ? <div className="compact-list">{recentAttentions.map((attention) => (
                    <Link className="compact-row" href={`/attentions/${attention.id}`} key={attention.id}>
                        <span className="list-avatar"><Icon name="heart" size={17} /></span>
                        <span className="compact-copy"><strong>{attention.urgencia.mascota.nombre} · {attention.urgencia.tipo_urgencia}</strong><small>{formatDate(attention.fecha_atencion, true)} · {attention.veterinario?.name || 'Sin veterinario'}</small></span>
                        <StatusBadge value={attention.estado} />
                    </Link>
                ))}</div> : <EmptyState icon="heart" title="Sin atenciones registradas" description="Las atenciones veterinarias del propietario aparecerán aquí." />}
                {canChangeStatus && <button className="secondary-button" type="button" onClick={() => {
                    if (window.confirm(`${owner.estado === 'activo' ? '¿Desactivar' : '¿Activar'} la cuenta de este propietario?`)) {
                        router.patch(`/owners/${owner.id}/toggle-status`);
                    }
                }}>{owner.estado === 'activo' ? 'Desactivar cuenta' : 'Activar cuenta'}</button>}
                {canManage && <button className="danger-text-button" type="button" onClick={remove}>Eliminar propietario</button>}
            </div>
        </section>
    );
}

function PetDetails({ pet, auth }) {
    const canManage = auth.user.rol === 'administrador';
    const canViewClinicalDetails = ['administrador', 'veterinario'].includes(auth.user.rol);
    const remove = () => {
        if (window.confirm(`¿Eliminar el registro de ${pet.nombre}?`)) {
            router.delete(`/pets/${pet.id}`);
        }
    };

    return (
        <section className="details-layout">
            <div className="data-panel">
                <DetailHeader title={pet.nombre} subtitle={`${pet.especie}${pet.raza ? ` · ${pet.raza}` : ''}`} editHref={canManage ? `/pets/${pet.id}/edit` : null} />
                <dl className="detail-grid">
                    <div><dt>Propietario</dt><dd><Link href={`/owners/${pet.propietario.id}`}>{pet.propietario.nombres} {pet.propietario.apellidos}</Link></dd></div>
                    <div><dt>Teléfono de contacto</dt><dd>{pet.propietario.telefono || 'No registrado'}</dd></div>
                    {canViewClinicalDetails && <>
                        <div><dt>Sexo</dt><dd>{pet.sexo || 'No registrado'}</dd></div>
                        <div><dt>Fecha de nacimiento</dt><dd>{formatDate(pet.fecha_nacimiento)}</dd></div>
                        <div><dt>Peso</dt><dd>{pet.peso ? `${pet.peso} kg` : 'No registrado'}</dd></div>
                        <div className="detail-wide"><dt>Observaciones</dt><dd>{pet.observaciones || 'Sin observaciones registradas.'}</dd></div>
                    </>}
                    <div><dt>Estado</dt><dd><StatusBadge value={pet.estado} /></dd></div>
                </dl>
                <div className="detail-section-heading"><h3>Atenciones de urgencia recientes</h3>{auth.user.rol !== 'recepcion' && <Link className="text-link" href={`/histories/${pet.id}`}>Ver historial <Icon name="chevron" size={14} /></Link>}</div>
                {pet.urgencias.length ? <div className="compact-list">{pet.urgencias.map((urgency) => (
                    <Link className="compact-row" href={`/urgencies/${urgency.id}`} key={urgency.id}>
                        <span className="list-avatar"><Icon name="alert" size={17} /></span>
                        <span className="compact-copy"><strong>{urgency.tipo_urgencia}</strong><small>{formatDate(urgency.fecha_ingreso, true)}</small></span>
                        <StatusBadge value={urgency.estado} />
                    </Link>
                ))}</div> : <EmptyState icon="alert" title="Sin urgencias registradas" description="Las urgencias asociadas a esta mascota aparecerán aquí." />}
                {canManage && <button className="danger-text-button" type="button" onClick={remove}>Eliminar mascota</button>}
            </div>
        </section>
    );
}

function UrgencyDetails({ urgency, auth, veterinarians = [] }) {
    const form = useForm(`urgency-status:${urgency.id}`, { estado: urgency.estado, veterinario_id: urgency.veterinario_id || '' });
    const assignmentForm = useForm(`urgency-assignment:${urgency.id}`, { veterinario_id: urgency.veterinario_id || '' });
    const dischargeForm = useForm(`urgency-discharge:${urgency.id}`, { fecha_egreso: '', estado_final_mascota: '' });
    const canManage = ['administrador', 'veterinario'].includes(auth.user.rol);
    const canAssign = ['administrador', 'recepcion'].includes(auth.user.rol);
    const submitDischarge = (event) => {
        event.preventDefault();
        dischargeForm.post(`/urgencies/${urgency.id}/discharge`);
    };

    return (
        <section className="details-layout">
            <div className="data-panel">
                <DetailHeader title={urgency.tipo_urgencia} subtitle={`Registrada ${formatDate(urgency.fecha_ingreso, true)}`} />
                <dl className="detail-grid">
                    <div><dt>Mascota</dt><dd><Link href={`/pets/${urgency.mascota.id}`}>{urgency.mascota.nombre}</Link></dd></div>
                    <div><dt>Propietario</dt><dd>{urgency.mascota.propietario.nombres} {urgency.mascota.propietario.apellidos}</dd></div>
                    <div><dt>Prioridad</dt><dd><StatusBadge value={urgency.nivel_prioridad} /></dd></div>
                    <div><dt>Estado</dt><dd><StatusBadge value={urgency.estado} /></dd></div>
                    <div><dt>Registrada por</dt><dd>{urgency.registrado_por?.name || '—'}</dd></div>
                    <div><dt>Veterinario</dt><dd>{urgency.veterinario?.name || 'Sin asignar'}</dd></div>
                    <div><dt>Egreso físico</dt><dd>{formatDate(urgency.fecha_egreso, true)}</dd></div>
                    {urgency.fecha_egreso && <div><dt>Estado final</dt><dd>{urgency.estado_final_mascota || 'Sin registrar'}</dd></div>}
                    {urgency.fecha_egreso && <div><dt>Egreso registrado por</dt><dd>{urgency.egreso_registrado_por?.name || 'Registro histórico sin responsable asociado'}</dd></div>}
                    <div className="detail-wide"><dt>Descripción</dt><dd>{urgency.descripcion}</dd></div>
                </dl>
                {canManage && (
                    <form className="inline-status-form" onSubmit={(event) => { event.preventDefault(); form.put(`/urgencies/${urgency.id}`); }}>
                        <SelectField form={form} name="estado" label="Actualizar estado" required options={['pendiente', 'en_atencion', 'en_observacion', 'finalizada', 'cancelada'].map((value) => ({ value, label: value.replace('_', ' ') }))} />
                        {form.errors.estado && <small className="field-error">{form.errors.estado}</small>}
                        <button className="primary-button" disabled={form.processing}>Guardar estado</button>
                    </form>
                )}
                {canAssign && (
                    <form className="inline-status-form" onSubmit={(event) => { event.preventDefault(); assignmentForm.patch(`/urgencies/${urgency.id}/assignment`); }}>
                        <SelectField form={assignmentForm} name="veterinario_id" label="Asignar veterinario" required options={veterinarians.map((veterinarian) => ({ value: veterinarian.id, label: veterinarian.name }))} />
                        {assignmentForm.errors.veterinario_id && <small className="field-error">{assignmentForm.errors.veterinario_id}</small>}
                        <button className="primary-button" disabled={assignmentForm.processing || veterinarians.length === 0}>Guardar asignación</button>
                    </form>
                )}
                {canManage && urgency.estado === 'finalizada' && !urgency.fecha_egreso && (
                    <form className="inline-status-form" onSubmit={submitDischarge}>
                        <InputField form={dischargeForm} name="fecha_egreso" label="Fecha y hora de salida física" type="datetime-local" required />
                        <InputField form={dischargeForm} name="estado_final_mascota" label="Estado final observado" required />
                        <button className="primary-button" disabled={dischargeForm.processing}>
                            {dischargeForm.processing ? 'Registrando...' : 'Confirmar egreso físico'}
                        </button>
                    </form>
                )}
                {canManage && urgency.estado !== 'finalizada' && !urgency.fecha_egreso && (
                    <p className="form-note">El egreso se registra después de finalizar la atención y confirmar que la mascota salió físicamente de la clínica.</p>
                )}
                {canManage && (
                    <>
                        <div className="detail-section-heading"><h3>Atenciones registradas</h3>
                            {['pendiente', 'en_atencion', 'en_observacion'].includes(urgency.estado) && <Link className="text-link" href="/attentions/create">Registrar atención <Icon name="chevron" size={14} /></Link>}
                        </div>
                        {urgency.atenciones?.length ? <div className="compact-list">{urgency.atenciones.map((attention) => (
                            <Link className="compact-row" href={`/attentions/${attention.id}`} key={attention.id}>
                                <span className="list-avatar"><Icon name="heart" size={17} /></span>
                                <span className="compact-copy"><strong>{attention.diagnostico}</strong><small>{formatDate(attention.fecha_atencion, true)} · {attention.veterinario?.name || 'Sin veterinario'}</small></span>
                                <StatusBadge value={attention.estado} />
                            </Link>
                        ))}</div> : <EmptyState icon="heart" title="Sin atenciones registradas" description="La atención veterinaria asociada se mostrará aquí." />}
                    </>
                )}
            </div>
        </section>
    );
}

function MedicationAdministrationForm({ indication, attentionDate, veterinarians = [], isAdministrator }) {
    const form = useForm(`medication-administration:${indication.id}`, {
        veterinario_id: '',
        dosis_administrada: '',
        cantidad_administrada: '',
        via_administracion: indication.via_administracion || '',
        fecha_hora: '',
        observaciones: '',
    });
    const submit = (event) => {
        event.preventDefault();
        form.post(`/attention-medications/${indication.id}/administrations`);
    };

    return (
        <form className="form-grid" onSubmit={submit}>
            {isAdministrator && <SelectField form={form} name="veterinario_id" label="Veterinario responsable" required options={veterinarians.map((vet) => ({ value: vet.id, label: vet.name }))} />}
            <InputField form={form} name="dosis_administrada" label="Dosis administrada" required />
            <InputField form={form} name="cantidad_administrada" label="Cantidad" type="number" min="0.01" step="0.01" required />
            <InputField form={form} name="via_administracion" label="Vía de administración" required />
            <InputField form={form} name="fecha_hora" label="Fecha y hora" type="datetime-local" min={attentionDate?.slice(0, 16)} required />
            <InputField form={form} name="observaciones" label="Observaciones" />
            <div className="form-actions form-field-wide">
                <button className="primary-button" disabled={form.processing}>
                    {form.processing ? 'Registrando...' : 'Registrar administración'}
                </button>
            </div>
        </form>
    );
}

function AttentionDetails({ attention, availableMedications = [], veterinarians = [], auth }) {
    const indicationForm = useForm(`attention-medication:${attention.id}`, {
        medicamento_id: '',
        veterinario_id: '',
        dosis: '',
        cantidad: '',
        frecuencia: '',
        via_administracion: '',
    });
    const isAdministrator = auth.user.rol === 'administrador';
    const canManageMedication = attention.estado === 'en_proceso'
        && ['pendiente', 'en_atencion', 'en_observacion'].includes(attention.urgencia.estado)
        && !attention.urgencia.fecha_egreso;
    const existingMedicationIds = new Set((attention.medicamentos_indicados || []).map((item) => item.medicamento_id));
    const selectableMedications = availableMedications.filter((item) => !existingMedicationIds.has(item.id));
    const submitIndication = (event) => {
        event.preventDefault();
        indicationForm.post(`/attentions/${attention.id}/medications`);
    };

    return (
        <section className="details-layout">
            <div className="data-panel">
                <DetailHeader title={`Atención de ${attention.urgencia.mascota.nombre}`} subtitle={`Registrada ${formatDate(attention.fecha_atencion, true)}`} editHref={`/attentions/${attention.id}/edit`} />
                <dl className="detail-grid">
                    <div><dt>Urgencia</dt><dd><Link href={`/urgencies/${attention.urgencia.id}`}>{attention.urgencia.tipo_urgencia}</Link></dd></div>
                    <div><dt>Veterinario</dt><dd>{attention.veterinario?.name || '—'}</dd></div>
                    <div><dt>Estado</dt><dd><StatusBadge value={attention.estado} /></dd></div>
                    <div className="detail-wide"><dt>Diagnóstico</dt><dd>{attention.diagnostico}</dd></div>
                    <div className="detail-wide"><dt>Tratamiento</dt><dd>{attention.tratamiento || 'Sin tratamiento registrado.'}</dd></div>
                    <div className="detail-wide"><dt>Procedimientos</dt><dd>{attention.procedimientos || 'Sin procedimientos registrados.'}</dd></div>
                    <div className="detail-wide"><dt>Observaciones</dt><dd>{attention.observaciones || 'Sin observaciones registradas.'}</dd></div>
                </dl>
                <div className="detail-section-heading"><h3>Medicamentos indicados y administraciones</h3></div>
                {attention.medicamentos_indicados?.length ? <div className="compact-list">{attention.medicamentos_indicados.map((indication) => (
                    <div className="compact-row" key={indication.id}>
                        <span className="list-avatar"><Icon name="file" size={17} /></span>
                        <span className="compact-copy">
                            <strong>{indication.medicamento.nombre} · {indication.medicamento.presentacion}</strong>
                            <small>{indication.dosis} · {indication.cantidad} · {indication.frecuencia} · {indication.via_administracion} · Indicado por {indication.veterinario?.name || '—'}</small>
                            {indication.administraciones?.map((administration) => (
                                <small key={administration.id}>
                                    {formatDate(administration.fecha_hora, true)} · {administration.dosis_administrada} · {administration.cantidad_administrada} · {administration.via_administracion} · {administration.veterinario?.name || '—'}
                                </small>
                            ))}
                        </span>
                    </div>
                ))}</div> : <EmptyState icon="file" title="Sin medicamentos estructurados" description="Los medicamentos indicados para esta atención aparecerán aquí." />}
                {canManageMedication && (
                    <>
                        <div className="detail-section-heading"><h3>Indicar medicamento</h3></div>
                        {selectableMedications.length ? (
                            <form className="form-grid" onSubmit={submitIndication}>
                                <SelectField form={indicationForm} name="medicamento_id" label="Medicamento y presentación" required options={selectableMedications.map((item) => ({ value: item.id, label: `${item.nombre} · ${item.presentacion}` }))} />
                                {isAdministrator && <SelectField form={indicationForm} name="veterinario_id" label="Veterinario responsable" required options={veterinarians.map((vet) => ({ value: vet.id, label: vet.name }))} />}
                                <InputField form={indicationForm} name="dosis" label="Dosis indicada" required />
                                <InputField form={indicationForm} name="cantidad" label="Cantidad" type="number" min="0.01" step="0.01" required />
                                <InputField form={indicationForm} name="frecuencia" label="Frecuencia" required />
                                <InputField form={indicationForm} name="via_administracion" label="Vía de administración" required />
                                <div className="form-actions form-field-wide">
                                    <button className="primary-button" disabled={indicationForm.processing}>
                                        {indicationForm.processing ? 'Guardando...' : 'Asociar medicamento'}
                                    </button>
                                </div>
                            </form>
                        ) : <p className="form-note">No hay otros medicamentos activos disponibles en el catálogo.</p>}
                    </>
                )}
                {canManageMedication && attention.medicamentos_indicados?.map((indication) => (
                    <MedicationAdministrationForm
                        key={`administration-form-${indication.id}`}
                        indication={indication}
                        attentionDate={attention.fecha_atencion}
                        veterinarians={veterinarians}
                        isAdministrator={isAdministrator}
                    />
                ))}
                <div className="detail-section-heading"><h3>Evoluciones</h3><Link className="text-link" href="/evolutions/create">Registrar evolución <Icon name="chevron" size={14} /></Link></div>
                {attention.evoluciones_clinicas?.length ? <div className="compact-list">{attention.evoluciones_clinicas.map((evolution) => (
                    <Link className="compact-row" href={`/evolutions/${evolution.id}`} key={evolution.id}>
                        <span className="list-avatar"><Icon name="activity" size={17} /></span>
                        <span className="compact-copy"><strong>{evolution.descripcion}</strong><small>{formatDate(evolution.fecha_evolucion, true)} · {evolution.veterinario?.name || '—'}</small></span>
                        <Icon name="chevron" size={16} />
                    </Link>
                ))}</div> : <EmptyState icon="activity" title="Sin evoluciones clínicas" description="Las evoluciones registradas aparecerán aquí." />}
            </div>
        </section>
    );
}

function EvolutionDetails({ evolution }) {
    return (
        <section className="details-layout">
            <div className="data-panel">
                <DetailHeader title={`Evolución de ${evolution.atencion.urgencia.mascota.nombre}`} subtitle={formatDate(evolution.fecha_evolucion, true)} />
                <dl className="detail-grid">
                    <div><dt>Atención</dt><dd><Link href={`/attentions/${evolution.atencion.id}`}>{evolution.atencion.diagnostico}</Link></dd></div>
                    <div><dt>Veterinario</dt><dd>{evolution.veterinario?.name || '—'}</dd></div>
                    <div><dt>Peso registrado</dt><dd>{evolution.peso ? `${evolution.peso} kg` : 'No registrado'}</dd></div>
                    <div><dt>Estado de la mascota</dt><dd>{evolution.estado_mascota || 'No registrado'}</dd></div>
                    <div className="detail-wide"><dt>Evolución</dt><dd>{evolution.descripcion}</dd></div>
                    <div className="detail-wide"><dt>Tratamiento aplicado</dt><dd>{evolution.tratamiento_aplicado || 'No registrado'}</dd></div>
                    <div className="detail-wide"><dt>Observaciones</dt><dd>{evolution.observaciones || 'No registradas'}</dd></div>
                </dl>
                <p className="form-note">Este registro clínico es histórico y no se puede modificar ni eliminar.</p>
            </div>
        </section>
    );
}

function MedicationCatalog({ medications = [] }) {
    const form = useForm({ nombre: '', presentacion: '' });
    const submit = (event) => {
        event.preventDefault();
        form.post('/medications', {
            onSuccess: () => form.reset(),
        });
    };

    return (
        <section className="details-layout">
            <div className="data-panel">
                <DetailHeader title="Catálogo de medicamentos" subtitle="Administra las presentaciones disponibles para las atenciones clínicas." />
                <form className="form-grid" onSubmit={submit}>
                    <InputField form={form} name="nombre" label="Nombre del medicamento" required />
                    <InputField form={form} name="presentacion" label="Presentación" required />
                    <div className="form-actions form-field-wide">
                        <button className="primary-button" disabled={form.processing}>
                            {form.processing ? 'Guardando...' : 'Agregar al catálogo'}
                        </button>
                    </div>
                </form>
                <div className="detail-section-heading"><h3>Medicamentos disponibles</h3></div>
                {medications.length ? <div className="compact-list">{medications.map((medication) => (
                    <div className="compact-row" key={medication.id}>
                        <span className="list-avatar"><Icon name="file" size={17} /></span>
                        <span className="compact-copy">
                            <strong>{medication.nombre} · {medication.presentacion}</strong>
                            <small>Administraciones registradas: {medication.administraciones_count}</small>
                        </span>
                        <StatusBadge value={medication.activo ? 'activo' : 'inactivo'} />
                    </div>
                ))}</div> : <EmptyState icon="file" title="Catálogo vacío" description="Agrega medicamentos y sus presentaciones para poder relacionarlos con atenciones." />}
            </div>
        </section>
    );
}

function HistoryDetails({ pet, history, timeline, auth }) {
    const form = useForm(`history:${pet.id}`, {
        antecedentes: history?.antecedentes || '', alergias: history?.alergias || '',
        enfermedades_previas: history?.enfermedades_previas || '', vacunas: history?.vacunas || '',
        observaciones: history?.observaciones || '',
    });
    const submit = (event) => {
        event.preventDefault();
        form.put(`/histories/${pet.id}`);
    };

    return (
        <section className="history-layout">
            <div className="data-panel">
                <DetailHeader title={`Historial de ${pet.nombre}`} subtitle={`${pet.especie} · ${pet.propietario.nombres} ${pet.propietario.apellidos}`} />
                {['administrador', 'veterinario'].includes(auth.user.rol) && (
                    <form className="history-form" onSubmit={submit}>
                        <div className="form-grid">
                            <TextareaField form={form} name="antecedentes" label="Antecedentes" />
                            <TextareaField form={form} name="alergias" label="Alergias" />
                            <TextareaField form={form} name="enfermedades_previas" label="Enfermedades previas" />
                            <TextareaField form={form} name="vacunas" label="Vacunas" />
                            <TextareaField form={form} name="observaciones" label="Observaciones" className="form-field-wide" />
                        </div>
                        <div className="form-actions"><button className="primary-button" disabled={form.processing}>{form.processing ? 'Guardando...' : 'Guardar historial'}</button></div>
                    </form>
                )}
            </div>
            <div className="data-panel timeline-panel">
                <div className="detail-section-heading"><h3>Cronología clínica</h3></div>
                {timeline.length ? <div className="timeline-list">{timeline.map((item, index) => {
                    const record = item.record;
                    const href = item.type === 'urgencia' ? `/urgencies/${record.id}` : item.type === 'atencion' ? `/attentions/${record.id}` : `/evolutions/${record.id}`;
                    const summary = item.type === 'urgencia' ? record.tipo_urgencia : item.type === 'atencion' ? record.diagnostico : record.descripcion;
                    const label = item.type === 'urgencia' ? 'Urgencia' : item.type === 'atencion' ? 'Atención' : 'Evolución';
                    return <Link className="timeline-item" href={href} key={`${item.type}-${record.id}-${index}`}><span className="timeline-mark"><Icon name={item.type === 'urgencia' ? 'alert' : item.type === 'atencion' ? 'heart' : 'activity'} size={17} /></span><span><small>{label} · {formatDate(item.date, true)}</small><strong>{summary}</strong></span></Link>;
                })}</div> : <EmptyState icon="file" title="Sin actividad clínica" description="No hay urgencias, atenciones o evoluciones registradas para esta mascota." />}
            </div>
        </section>
    );
}

function PetAlert({ reportContent = null }) {
    const page = usePage();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const logout = useForm({});
    const props = page.props;
    const {
        auth,
        screen = 'dashboard',
        stats,
        latestUrgencies = [],
        latestPets = [],
        activities = [],
        owners,
        owner,
        recentUrgencies = [],
        recentAttentions = [],
        pets,
        pet,
        urgencies,
        urgency,
        attentions,
        attention,
        evolutions,
        evolution,
        histories,
        history,
        timeline = [],
        users,
        managedUser,
        medications = [],
        availableMedications = [],
        veterinarians = [],
        filters = {},
        filterOptions = {},
        errors = {},
        roles = [],
        flash = {},
    } = props;
    const activePage = screen.split('.')[0];
    const reportNavItem = {
        'reports.ingresos': 'reports-ingresos',
        'reports.ocupacion': 'reports-ocupacion',
        'reports.medicamentos': 'reports-medicamentos',
        'reports.resultados': 'reports-resultados',
    }[screen];
    const activeNavItem = reportNavItem || {
        dashboard: 'dashboard',
        owners: 'owners',
        pets: 'pets',
        'urgencies.create': 'new-emergency',
        urgencies: 'emergencies',
        attentions: 'care',
        evolutions: 'evolution',
        histories: 'history',
        medications: 'medications',
        users: 'users',
        reports: 'reports',
    }[screen === 'urgencies.create' ? screen : activePage] || activePage;
    const titles = {
        dashboard: auth.user.rol === 'administrador' ? 'Panel administrativo' : auth.user.rol === 'recepcion' ? 'Panel de recepción' : 'Panel veterinario',
        owners: screen.endsWith('.create') ? 'Registrar propietario' : screen.endsWith('.edit') ? 'Editar propietario' : 'Propietarios',
        pets: screen.endsWith('.create') ? 'Registrar mascota' : screen.endsWith('.edit') ? 'Editar mascota' : 'Mascotas',
        urgencies: screen.endsWith('.create') ? 'Registrar urgencia' : screen.endsWith('.edit') ? 'Actualizar urgencia' : 'Urgencias activas',
        attentions: screen.endsWith('.create') ? 'Registrar atención' : screen.endsWith('.edit') ? 'Editar atención' : 'Atención veterinaria',
        evolutions: screen.endsWith('.create') ? 'Registrar evolución' : 'Evolución clínica',
        histories: 'Historial clínico',
        users: screen.endsWith('.create') ? 'Crear usuario' : screen.endsWith('.edit') ? 'Editar usuario' : 'Usuarios',
        medications: 'Catálogo de medicamentos',
    };
    const reportTitles = {
        'reports.index': 'Centro de Reportes',
        'reports.ingresos': 'Ingresos de mascotas',
        'reports.ocupacion': 'Ocupación y permanencia',
        'reports.medicamentos': 'Medicamentos y tratamientos',
        'reports.resultados': 'Resultados clínicos',
    };
    const title = reportTitles[screen] || titles[activePage] || 'Pet Alert Ipiales';
    const pageContent = reportContent ?? (() => {
        if (screen === 'dashboard.admin' || screen === 'dashboard') return <Dashboard stats={stats} latestUrgencies={latestUrgencies} latestPets={latestPets} activities={activities} auth={auth} />;
        if (screen === 'dashboard.reception') return <RoleDashboard role="recepcion" stats={stats} latestUrgencies={latestUrgencies} veterinarians={veterinarians} />;
        if (screen === 'dashboard.veterinarian') return <RoleDashboard role="veterinario" stats={stats} latestUrgencies={latestUrgencies} />;
        if (screen === 'owners.index') return <ModuleList moduleKey="owners" records={owners} auth={auth} filters={filters} />;
        if (screen === 'owners.create') return <OwnerForm />;
        if (screen === 'owners.edit') return <OwnerForm owner={owner} />;
        if (screen === 'owners.show') return <OwnerDetails owner={owner} recentUrgencies={recentUrgencies} recentAttentions={recentAttentions} auth={auth} />;
        if (screen === 'pets.index') return <ModuleList moduleKey="pets" records={pets} auth={auth} filters={filters} filterOptions={filterOptions} />;
        if (screen === 'pets.create') return <PetForm owners={owners || []} />;
        if (screen === 'pets.edit') return <PetForm pet={pet} owners={owners || []} />;
        if (screen === 'pets.show') return <PetDetails pet={pet} auth={auth} />;
        if (screen === 'urgencies.index') return <ModuleList moduleKey="urgencies" records={urgencies} auth={auth} filters={filters} />;
        if (screen === 'urgencies.create') return <EmergencyForm pets={pets} veterinarians={veterinarians} />;
        if (screen === 'urgencies.edit') return <EmergencyForm urgency={urgency} veterinarians={veterinarians} />;
        if (screen === 'urgencies.show') return <UrgencyDetails urgency={urgency} auth={auth} veterinarians={veterinarians} />;
        if (screen === 'attentions.index') return <ModuleList moduleKey="attentions" records={attentions} auth={auth} />;
        if (screen === 'attentions.create') return <AttentionForm urgencies={urgencies} veterinarians={veterinarians} />;
        if (screen === 'attentions.edit') return <AttentionForm attention={attention} />;
        if (screen === 'attentions.show') return <AttentionDetails attention={attention} availableMedications={availableMedications} veterinarians={veterinarians} auth={auth} />;
        if (screen === 'medications.index') return <MedicationCatalog medications={medications} />;
        if (screen === 'evolutions.index') return <ModuleList moduleKey="evolutions" records={evolutions} auth={auth} />;
        if (screen === 'evolutions.create') return <EvolutionForm attentions={attentions} veterinarians={veterinarians} />;
        if (screen === 'evolutions.show') return <EvolutionDetails evolution={evolution} />;
        if (screen === 'histories.index') return <ModuleList moduleKey="histories" records={pets} />;
        if (screen === 'histories.show') return <HistoryDetails pet={pet} history={history} timeline={timeline} auth={auth} />;
        if (screen === 'users.index') return <ModuleList moduleKey="users" records={users} auth={auth} filters={filters} roles={roles} />;
        if (screen === 'users.create') return <UserForm roles={roles} auth={auth} />;
        if (screen === 'users.edit') return <UserForm managedUser={managedUser} roles={roles} auth={auth} />;

        return <EmptyState icon="alert" title="Página no disponible" description="La ruta solicitada no corresponde a una vista configurada." />;
    })();

    const createActions = {
        dashboard: ['administrador', 'recepcion'].includes(auth.user.rol) ? { href: '/urgencies/create', label: 'Nueva urgencia' } : null,
        owners: screen === 'owners.index' && auth.user.rol !== 'veterinario' ? { href: '/owners/create', label: 'Nuevo propietario' } : null,
        pets: screen === 'pets.index' && auth.user.rol !== 'veterinario' ? { href: '/pets/create', label: 'Nueva mascota' } : null,
        urgencies: screen === 'urgencies.index' && auth.user.rol !== 'veterinario' ? { href: '/urgencies/create', label: 'Nueva urgencia' } : null,
        attentions: screen === 'attentions.index' && auth.user.rol !== 'recepcion' ? { href: '/attentions/create', label: 'Nueva atención' } : null,
        evolutions: screen === 'evolutions.index' && auth.user.rol !== 'recepcion' ? { href: '/evolutions/create', label: 'Nueva evolución' } : null,
        users: screen === 'users.index' ? { href: '/users/create', label: 'Crear usuario' } : null,
    }[activePage];
    const searchPath = ['owners.index', 'pets.index', 'urgencies.index', 'attentions.index', 'histories.index', 'users.index'].includes(screen)
        ? page.url.split('?')[0]
        : null;

    return (
        <div className="app-shell">
            {sidebarOpen && <button className="sidebar-backdrop" aria-label="Cerrar menú" onClick={() => setSidebarOpen(false)} />}
            <aside className={`sidebar ${sidebarOpen ? 'sidebar-open' : ''}`}>
                <div className="brand">
                    <div className="brand-mark"><Icon name="paw" size={23} /></div>
                    <div className="brand-name"><strong>Pet Alert</strong><span>IPIALES</span></div>
                    <button className="icon-button mobile-close" aria-label="Cerrar menú" onClick={() => setSidebarOpen(false)}><Icon name="close" /></button>
                </div>
                <div className="clinic-switcher">
                    <div className="clinic-avatar"><Icon name="shield" size={19} /></div>
                    <div><strong>Red de atención</strong><span>Ipiales, Nariño</span></div>
                    <Icon name="chevron" size={17} />
                </div>
                <nav className="side-nav" aria-label="Navegación principal">
                    {(navigationByRole[auth.user.rol] || []).map((group) => (
                        <div className="nav-group" key={group.title}>
                            <p className="nav-heading">{group.title}</p>
                            {group.items.map((item) => (
                                    <Link
                                        className={`nav-item ${activeNavItem === item.id ? 'nav-item-active' : ''} ${item.accent ? 'nav-item-accent' : ''}`}
                                        key={item.id}
                                        href={item.href}
                                        onClick={() => setSidebarOpen(false)}
                                        aria-current={activeNavItem === item.id ? 'page' : undefined}
                                    >
                                        <Icon name={item.icon} size={19} /><span>{item.label}</span>
                                        {item.accent && <span className="nav-pulse" />}
                                    </Link>
                                ))}
                        </div>
                    ))}
                </nav>
                <div className="sidebar-bottom">
                    <Link href="/mi-cuenta/seguridad" className="text-sm font-semibold text-slate-300 hover:text-white">Cambiar contraseña</Link>
                    <div className="support-card">
                        <div className="support-icon"><Icon name="heart" size={17} /></div>
                        <p><strong>¿Necesitas ayuda?</strong><span>Estamos aquí para apoyarte.</span></p>
                    </div>
                    <button className="account-link" onClick={() => logout.post('/logout')} disabled={logout.processing}>
                        <div className="account-avatar">{auth.user.name.slice(0, 1).toUpperCase()}</div>
                        <span><strong>{auth.user.name}</strong><small>{auth.user.rol}</small></span>
                        <span className="logout-label">Salir</span>
                    </button>
                </div>
            </aside>

            <main className="main-area">
                <header className="topbar">
                    <button className="icon-button mobile-menu" aria-label="Abrir menú" onClick={() => setSidebarOpen(true)}><Icon name="menu" /></button>
                    <div className="breadcrumb"><span>Pet Alert</span><Icon name="chevron" size={14} /><strong>{title}</strong></div>
                    <div className="topbar-actions">
                        {searchPath && (
                            <form className="search-box" method="get" action={searchPath}>
                                <Icon name="search" size={18} />
                                <input type="search" name="q" defaultValue={filters.q || ''} placeholder="Buscar en el módulo..." aria-label="Buscar en el módulo" />
                                <button type="submit" aria-label="Buscar"><Icon name="chevron" size={15} /></button>
                            </form>
                        )}
                    </div>
                </header>
                <div className="page-content">
                    <div className="page-title-row">
                        <div>
                            <p className="eyebrow">PET ALERT IPIALES</p>
                            <h1>{title}</h1>
                            {screen === 'reports.index' && (
                                <p className="mt-2 text-sm text-slate-500">Análisis y estadísticas de Pet Alert Ipiales.</p>
                            )}
                        </div>
                        {createActions && (
                            <Link className="primary-button" href={createActions.href}>
                                <Icon name="plus" size={19} /> {createActions.label}
                            </Link>
                        )}
                    </div>
                    {flash.success && <div className="flash-message flash-success" role="status">{flash.success}</div>}
                    {flash.error && <div className="flash-message flash-error" role="alert">{flash.error}</div>}
                    {Object.keys(errors).length > 0 && <div className="flash-message flash-error" role="alert">{Object.values(errors).join(' ')}</div>}
                    {pageContent}
                </div>
            </main>
        </div>
    );
}

export default PetAlert;
