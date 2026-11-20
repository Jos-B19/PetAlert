import { useEffect, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    Clock3,
    Download,
    HeartPulse,
    LoaderCircle,
    LogOut,
    PawPrint,
    Plus,
    ShieldCheck,
    Stethoscope,
    UserRound,
} from 'lucide-react';
import PublicLayout from '../Public/Layout';

function formatDate(value, withTime = false) {
    if (!value) return '—';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '—';

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        ...(withTime ? { timeStyle: 'short' } : {}),
    }).format(date);
}

function Status({ value }) {
    const labels = {
        activo: 'Activo',
        pendiente: 'Pendiente',
        en_atencion: 'En atención',
        en_observacion: 'En observación',
        en_proceso: 'En proceso',
        finalizada: 'Finalizada',
        cancelada: 'Cancelada',
    };
    const colors = {
        activo: 'bg-emerald-50 text-emerald-800',
        pendiente: 'bg-amber-50 text-amber-800',
        en_atencion: 'bg-sky-50 text-sky-800',
        en_observacion: 'bg-violet-50 text-violet-800',
        en_proceso: 'bg-sky-50 text-sky-800',
        finalizada: 'bg-slate-100 text-slate-700',
        cancelada: 'bg-rose-50 text-rose-800',
    };

    return <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${colors[value] || 'bg-slate-100 text-slate-700'}`}>{labels[value] || value || '—'}</span>;
}

function PetForm({ pet }) {
    const form = useForm({
        nombre: pet?.nombre || '',
        especie: pet?.especie || '',
        raza: pet?.raza || '',
        sexo: pet?.sexo || '',
        fecha_nacimiento: pet?.fecha_nacimiento || '',
        peso: pet?.peso || '',
        color: pet?.color || '',
        observaciones: pet?.observaciones || '',
    });
    const submit = (event) => {
        event.preventDefault();
        pet ? form.put(`/mi-cuenta/mascotas/${pet.id}`) : form.post('/mi-cuenta/mascotas');
    };
    const fieldClass = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/15';

    return (
        <form onSubmit={submit} className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div className="mb-6 flex items-start gap-3">
                <span className="grid size-11 shrink-0 place-items-center rounded-2xl bg-teal-50 text-teal-800"><PawPrint size={21} /></span>
                <div><h2 className="text-xl font-bold text-[#102b38]">{pet ? 'Editar mascota' : 'Registrar mascota'}</h2><p className="mt-1 text-sm text-slate-600">Completa los datos disponibles para mantener su perfil actualizado.</p></div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                {[
                    ['nombre', 'Nombre', 'text', true],
                    ['especie', 'Especie', 'text', true],
                    ['raza', 'Raza', 'text', false],
                    ['sexo', 'Sexo', 'text', false],
                    ['fecha_nacimiento', 'Fecha de nacimiento', 'date', false],
                    ['peso', 'Peso (kg)', 'number', false],
                    ['color', 'Color', 'text', false],
                ].map(([name, label, type, required]) => (
                    <label className="text-sm font-medium text-slate-700" key={name}>
                        {label}
                        <input
                            className={fieldClass}
                            type={type}
                            name={name}
                            value={form.data[name]}
                            onChange={(event) => form.setData(name, event.target.value)}
                            required={required}
                            min={type === 'number' ? '0.01' : undefined}
                            step={type === 'number' ? '0.01' : undefined}
                        />
                        {form.errors[name] && <span className="mt-1 block text-xs text-rose-700">{form.errors[name]}</span>}
                    </label>
                ))}
                <label className="text-sm font-medium text-slate-700 sm:col-span-2">
                    Información adicional
                    <textarea className={fieldClass} name="observaciones" rows="4" value={form.data.observaciones} onChange={(event) => form.setData('observaciones', event.target.value)} />
                    {form.errors.observaciones && <span className="mt-1 block text-xs text-rose-700">{form.errors.observaciones}</span>}
                </label>
            </div>
            <div className="mt-6 flex flex-wrap gap-3">
                <button className="rounded-xl bg-teal-800 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-900 disabled:opacity-60" type="submit" disabled={form.processing}>
                    {form.processing ? 'Guardando…' : pet ? 'Guardar cambios' : 'Registrar mascota'}
                </button>
                <Link className="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" href={pet ? `/mi-cuenta/mascotas/${pet.id}` : '/mi-cuenta'}>Cancelar</Link>
            </div>
        </form>
    );
}

function EmergencyForm({ activePets }) {
    const form = useForm({ mascota_id: '', tipo_urgencia: '', descripcion: '', nivel_prioridad: 'media' });
    const submit = (event) => {
        event.preventDefault();
        form.post('/mi-cuenta/urgencias');
    };
    const fieldClass = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/15';

    return (
        <form id="registrar-urgencia" onSubmit={submit} className="rounded-3xl border border-rose-100 bg-white p-6 shadow-sm sm:p-8">
            <div className="mb-6 flex items-start gap-3">
                <span className="grid size-11 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-700"><HeartPulse size={21} /></span>
                <div><h2 className="text-xl font-bold text-[#102b38]">Reportar una urgencia</h2><p className="mt-1 text-sm text-slate-600">El equipo veterinario revisará el reporte y actualizará su estado.</p></div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <label className="text-sm font-medium text-slate-700">Mascota
                    <select className={fieldClass} value={form.data.mascota_id} onChange={(event) => form.setData('mascota_id', event.target.value)} required>
                        <option value="">Selecciona una mascota</option>
                        {activePets.map((activePet) => <option value={activePet.id} key={activePet.id}>{activePet.nombre}</option>)}
                    </select>
                    {form.errors.mascota_id && <span className="mt-1 block text-xs text-rose-700">{form.errors.mascota_id}</span>}
                </label>
                <label className="text-sm font-medium text-slate-700">Tipo de urgencia
                    <input className={fieldClass} value={form.data.tipo_urgencia} onChange={(event) => form.setData('tipo_urgencia', event.target.value)} maxLength="100" required />
                    {form.errors.tipo_urgencia && <span className="mt-1 block text-xs text-rose-700">{form.errors.tipo_urgencia}</span>}
                </label>
                <label className="text-sm font-medium text-slate-700">Prioridad percibida
                    <select className={fieldClass} value={form.data.nivel_prioridad} onChange={(event) => form.setData('nivel_prioridad', event.target.value)} required>
                        {['baja', 'media', 'alta', 'critica'].map((priority) => <option value={priority} key={priority}>{priority[0].toUpperCase() + priority.slice(1)}</option>)}
                    </select>
                    {form.errors.nivel_prioridad && <span className="mt-1 block text-xs text-rose-700">{form.errors.nivel_prioridad}</span>}
                </label>
                <label className="text-sm font-medium text-slate-700 sm:col-span-2">Descripción
                    <textarea className={fieldClass} rows="4" value={form.data.descripcion} onChange={(event) => form.setData('descripcion', event.target.value)} maxLength="10000" required />
                    {form.errors.descripcion && <span className="mt-1 block text-xs text-rose-700">{form.errors.descripcion}</span>}
                </label>
            </div>
            <p className="mt-4 text-xs leading-5 text-slate-500">Si la situación representa un riesgo inmediato, contacta directamente a un servicio veterinario de urgencias.</p>
            <button className="mt-5 rounded-xl bg-rose-700 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-800 disabled:opacity-60" type="submit" disabled={form.processing || activePets.length === 0}>
                {form.processing ? 'Enviando…' : 'Enviar reporte'}
            </button>
        </form>
    );
}

function StatCard({ label, value, icon: Icon }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <span className="grid size-10 place-items-center rounded-xl bg-teal-50 text-teal-800"><Icon size={19} /></span>
            <p className="mt-4 text-sm text-slate-600">{label}</p>
            <p className="mt-1 text-2xl font-bold text-[#102b38]">{value}</p>
        </div>
    );
}

function AccountDashboard({ ownerProfile, stats, pets, activePets, urgencies, activities = [] }) {
    const petRows = pets?.data || [];
    const urgencyRows = urgencies?.data || [];

    return (
        <div className="space-y-8">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Mis mascotas" value={stats?.pets || 0} icon={PawPrint} />
                <StatCard label="Urgencias registradas" value={stats?.urgencies || 0} icon={Activity} />
                <StatCard label="Urgencias pendientes" value={stats?.pendingUrgencies || 0} icon={Clock3} />
                <StatCard label="En atención" value={stats?.activeUrgencies || 0} icon={HeartPulse} />
                <StatCard label="En observación" value={stats?.observationUrgencies || 0} icon={Activity} />
            </div>

            <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div><p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Mi perfil</p><h2 className="mt-1 text-xl font-bold text-[#102b38]">{ownerProfile?.nombres} {ownerProfile?.apellidos}</h2></div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Link href="/mi-cuenta/perfil" className="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Editar información</Link>
                        <span className="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800"><ShieldCheck size={15} /> Cuenta {ownerProfile?.estado}</span>
                    </div>
                </div>
                <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Correo electrónico</dt><dd className="mt-1 break-words text-slate-800">{usePage().props.auth?.user?.email}</dd></div>
                    <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Teléfono</dt><dd className="mt-1 text-slate-800">{ownerProfile?.telefono || 'No registrado'}</dd></div>
                    <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Documento</dt><dd className="mt-1 text-slate-800">{ownerProfile?.tipo_documento} · {ownerProfile?.numero_documento}</dd></div>
                </dl>
            </section>

            <section id="mis-mascotas" className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 className="text-xl font-bold text-[#102b38]">Mis mascotas</h2><p className="mt-1 text-sm text-slate-600">Consulta y actualiza la información de tus mascotas.</p></div>
                    <Link href="/mi-cuenta/mascotas/create" className="inline-flex items-center gap-2 rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-900"><Plus size={17} /> Registrar mascota</Link>
                </div>
                {petRows.length ? (
                    <div className="mt-5 grid gap-3 md:grid-cols-2">
                        {petRows.map((pet) => (
                            <Link href={`/mi-cuenta/mascotas/${pet.id}`} className="flex min-w-0 items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:border-teal-300 hover:bg-teal-50/40" key={pet.id}>
                                <span className="grid size-12 shrink-0 place-items-center rounded-2xl bg-teal-50 text-teal-800"><PawPrint size={22} /></span>
                                <span className="min-w-0 flex-1"><strong className="block truncate text-[#102b38]">{pet.nombre}</strong><span className="mt-1 block text-sm text-slate-600">{pet.especie}{pet.raza ? ` · ${pet.raza}` : ''}</span><span className="mt-1 block text-xs text-slate-500">{pet.urgencias_count} urgencias relacionadas</span></span>
                                <ArrowRight size={17} className="shrink-0 text-teal-700" />
                            </Link>
                        ))}
                    </div>
                ) : <p className="mt-5 rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">Aún no tienes mascotas registradas.</p>}
                {pets?.links && pets.last_page > 1 && <div className="mt-5 flex flex-wrap gap-2">{pets.links.map((link, index) => link.url ? <Link key={index} href={link.url} className={`rounded-lg px-3 py-2 text-sm ${link.active ? 'bg-teal-800 text-white' : 'border border-slate-200 text-slate-700'}`}>{link.label.replace('&laquo; Previous', '← Anterior').replace('Next &raquo;', 'Siguiente →')}</Link> : null)}</div>}
            </section>

            {activePets.length ? <EmergencyForm activePets={activePets} /> : (
                <section id="registrar-urgencia" className="rounded-3xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
                    Registra primero una mascota activa para poder reportar una urgencia.
                </section>
            )}

            <section id="mis-urgencias" className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div><h2 className="text-xl font-bold text-[#102b38]">Seguimiento de urgencias</h2><p className="mt-1 text-sm text-slate-600">Consulta el estado y las actualizaciones disponibles.</p></div>
                {urgencyRows.length ? (
                    <div className="mt-5 divide-y divide-slate-100">
                        {urgencyRows.map((urgency) => (
                            <Link href={`/mi-cuenta/urgencias/${urgency.id}`} className="flex flex-wrap items-center gap-3 py-4 first:pt-0 last:pb-0" key={urgency.id}>
                                <span className="grid size-10 place-items-center rounded-xl bg-rose-50 text-rose-700"><HeartPulse size={18} /></span>
                                <span className="min-w-0 flex-1"><strong className="block text-sm text-[#102b38]">{urgency.mascota.nombre} · {urgency.tipo_urgencia}</strong><span className="mt-1 block text-xs text-slate-500">{formatDate(urgency.fecha_ingreso, true)} · Prioridad {urgency.nivel_prioridad}</span></span>
                                <Status value={urgency.estado} /><ArrowRight size={16} className="text-slate-400" />
                            </Link>
                        ))}
                    </div>
                ) : <p className="mt-5 rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">No tienes urgencias registradas.</p>}
                {urgencies?.links && urgencies.last_page > 1 && <div className="mt-5 flex flex-wrap gap-2">{urgencies.links.map((link, index) => link.url ? <Link key={index} href={link.url} className={`rounded-lg px-3 py-2 text-sm ${link.active ? 'bg-teal-800 text-white' : 'border border-slate-200 text-slate-700'}`}>{link.label.replace('&laquo; Previous', '← Anterior').replace('Next &raquo;', 'Siguiente →')}</Link> : null)}</div>}
            </section>

            <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 className="text-xl font-bold text-[#102b38]">Últimas actividades</h2>
                {activities.length ? <div className="mt-4 divide-y divide-slate-100">{activities.map((activity, index) => (
                    <div className="flex items-center gap-3 py-4 first:pt-0 last:pb-0" key={`${activity.type}-${activity.date}-${index}`}>
                        <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-800">{activity.type === 'urgency' ? <HeartPulse size={18} /> : <Activity size={18} />}</span>
                        <span className="min-w-0 flex-1"><strong className="block text-sm text-slate-800">{activity.title}</strong><span className="mt-1 block text-xs text-slate-500">{activity.detail}</span></span>
                        <time className="shrink-0 text-xs text-slate-500">{formatDate(activity.date, true)}</time>
                    </div>
                ))}</div> : <p className="mt-4 rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">Las actividades de tus mascotas aparecerán aquí.</p>}
            </section>
        </div>
    );
}

function PetDetails({ pet, attentions = [] }) {
    return (
        <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-center gap-4"><span className="grid size-14 place-items-center rounded-2xl bg-teal-50 text-teal-800"><PawPrint size={26} /></span><div><h2 className="text-2xl font-bold text-[#102b38]">{pet.nombre}</h2><p className="mt-1 text-sm text-slate-600">{pet.especie}{pet.raza ? ` · ${pet.raza}` : ''}</p></div></div>
                <Link className="rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-900" href={`/mi-cuenta/mascotas/${pet.id}/edit`}>Editar mascota</Link>
            </div>
            <dl className="mt-7 grid gap-5 rounded-2xl bg-slate-50 p-5 sm:grid-cols-2">
                <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Sexo</dt><dd className="mt-1 text-sm text-slate-800">{pet.sexo || 'No registrado'}</dd></div>
                <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Nacimiento</dt><dd className="mt-1 text-sm text-slate-800">{formatDate(pet.fecha_nacimiento)}</dd></div>
                <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Peso registrado</dt><dd className="mt-1 text-sm text-slate-800">{pet.peso ? `${pet.peso} kg` : 'No registrado'}</dd></div>
                <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Color</dt><dd className="mt-1 text-sm text-slate-800">{pet.color || 'No registrado'}</dd></div>
                <div className="sm:col-span-2"><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Información adicional</dt><dd className="mt-1 whitespace-pre-wrap text-sm text-slate-800">{pet.observaciones || 'No registrada'}</dd></div>
            </dl>
            <div className="mt-8"><h3 className="font-bold text-[#102b38]">Urgencias asociadas</h3>
                {pet.urgencias?.length ? <div className="mt-3 space-y-3">{pet.urgencias.map((urgency) => <Link className="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 p-4" href={`/mi-cuenta/urgencias/${urgency.id}`} key={urgency.id}><span className="flex-1 text-sm font-semibold text-slate-800">{urgency.tipo_urgencia} · {formatDate(urgency.fecha_ingreso, true)}</span><Status value={urgency.estado} /></Link>)}</div> : <p className="mt-3 text-sm text-slate-600">No hay urgencias asociadas a esta mascota.</p>}
            </div>
            <div className="mt-8"><h3 className="font-bold text-[#102b38]">Atenciones veterinarias</h3>
                {attentions.length ? <div className="mt-3 space-y-3">{attentions.map((attention) => <div className="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 p-4" key={attention.id}><span className="min-w-0 flex-1"><strong className="block text-sm text-slate-800">{attention.tipo_urgencia}</strong><span className="mt-1 block text-xs text-slate-500">{formatDate(attention.fecha_atencion, true)} · {attention.veterinario || 'Sin veterinario registrado'}</span></span><Status value={attention.estado} /></div>)}</div> : <p className="mt-3 text-sm text-slate-600">No hay atenciones veterinarias registradas.</p>}
            </div>
            <p className="mt-6 text-xs leading-5 text-slate-500">Los diagnósticos y notas clínicas se mantienen en el historial profesional, no visible desde esta cuenta.</p>
        </section>
    );
}

function UrgencyDetails({ urgency, attentions }) {
    return (
        <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div><p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Seguimiento de urgencia</p><h2 className="mt-1 text-2xl font-bold text-[#102b38]">{urgency.tipo_urgencia}</h2><p className="mt-1 text-sm text-slate-600">{urgency.mascota.nombre} · Registrada {formatDate(urgency.fecha_ingreso, true)}</p></div>
                <Status value={urgency.estado} />
            </div>
            <dl className="mt-7 grid gap-5 rounded-2xl bg-slate-50 p-5 sm:grid-cols-2">
                <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Prioridad</dt><dd className="mt-1 text-sm capitalize text-slate-800">{urgency.nivel_prioridad}</dd></div>
                <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Veterinario asignado</dt><dd className="mt-1 text-sm text-slate-800">{urgency.veterinario?.name || 'Pendiente de asignación'}</dd></div>
                <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Última actualización</dt><dd className="mt-1 text-sm text-slate-800">{formatDate(urgency.updated_at, true)}</dd></div>
                <div className="sm:col-span-2"><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Descripción enviada</dt><dd className="mt-1 whitespace-pre-wrap text-sm text-slate-800">{urgency.descripcion}</dd></div>
            </dl>
            <div className="mt-8"><h3 className="font-bold text-[#102b38]">Atenciones registradas</h3>
                {attentions?.length ? <ol className="mt-4 space-y-4 border-l-2 border-teal-100 pl-5">{attentions.map((attention) => <li className="relative rounded-xl border border-slate-200 p-4" key={attention.id}><span className="absolute -left-[1.65rem] top-5 size-3 rounded-full bg-teal-700" /><div className="flex flex-wrap items-center justify-between gap-3"><strong className="text-sm text-slate-800">{formatDate(attention.fecha_atencion, true)}</strong><Status value={attention.estado} /></div><p className="mt-2 text-sm text-slate-600">Veterinario: {attention.veterinario || 'No registrado'}</p></li>)}</ol> : <p className="mt-3 text-sm text-slate-600">Aún no hay atenciones registradas.</p>}
            </div>
            <p className="mt-6 rounded-xl bg-amber-50 p-4 text-xs leading-5 text-amber-900">Las observaciones, diagnósticos y evoluciones clínicas detalladas no se muestran aquí porque el sistema aún no tiene una clasificación explícita de información autorizada para propietarios.</p>
        </section>
    );
}

function TrackingStatus({ value }) {
    const labels = {
        pendiente: 'Pendiente',
        en_atencion: 'En atención',
        en_observacion: 'En observación',
        finalizada: 'Finalizada',
        cancelada: 'Cancelada',
    };
    const colors = {
        pendiente: 'bg-amber-50 text-amber-800 ring-amber-200',
        en_atencion: 'bg-sky-50 text-sky-800 ring-sky-200',
        en_observacion: 'bg-violet-50 text-violet-800 ring-violet-200',
        finalizada: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        cancelada: 'bg-rose-50 text-rose-800 ring-rose-200',
    };

    return (
        <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${colors[value] || 'bg-slate-100 text-slate-700 ring-slate-200'}`}>
            {labels[value] || value || 'Estado no informado'}
        </span>
    );
}

function TrackingIndex({ trackedPets = [] }) {
    return (
        <section className="space-y-5">
            <div className="rounded-3xl border border-teal-100 bg-gradient-to-r from-teal-50 to-white p-5 sm:p-7">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-start gap-3">
                        <span className="grid size-12 shrink-0 place-items-center rounded-2xl bg-teal-800 text-white">
                            <PawPrint size={23} />
                        </span>
                        <div>
                            <p className="text-sm font-semibold text-teal-800">Atención veterinaria activa</p>
                            <h2 className="mt-1 text-xl font-bold text-[#102b38]">Seguimiento de mis mascotas</h2>
                            <p className="mt-1 text-sm leading-6 text-slate-600">
                                Consulta los datos de ingreso, estado y egreso disponibles para tus mascotas.
                            </p>
                        </div>
                    </div>
                    <span className="shrink-0 text-sm font-semibold text-slate-500">
                        {trackedPets.length} {trackedPets.length === 1 ? 'mascota en seguimiento' : 'mascotas en seguimiento'}
                    </span>
                </div>
            </div>

            {trackedPets.length ? (
                <div className="grid gap-4 md:grid-cols-2">
                    {trackedPets.map((pet) => (
                        <article key={pet.id} className="flex min-h-72 flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md sm:p-6">
                            <div className="flex items-start justify-between gap-4">
                                <span className="grid size-14 shrink-0 place-items-center rounded-2xl bg-teal-50 text-teal-800">
                                    <PawPrint size={27} />
                                </span>
                                <TrackingStatus value={pet.estado} />
                            </div>
                            <div className="mt-5">
                                <h3 className="text-xl font-bold text-[#102b38]">{pet.nombre}</h3>
                                <p className="mt-1 text-sm text-slate-600">{pet.especie}{pet.raza ? ` · ${pet.raza}` : ''}</p>
                            </div>
                            <dl className="mt-5 grid gap-3 border-t border-slate-100 pt-5">
                                <div className="flex items-start gap-3">
                                    <CalendarDays size={17} className="mt-0.5 shrink-0 text-teal-700" />
                                    <div>
                                        <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de ingreso</dt>
                                        <dd className="mt-1 text-sm text-slate-800">{formatDate(pet.fecha_ingreso, true)}</dd>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <Activity size={17} className="mt-0.5 shrink-0 text-teal-700" />
                                    <div>
                                        <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Última actualización clínica</dt>
                                        <dd className="mt-1 text-sm text-slate-800">{pet.ultima_actualizacion_clinica ? formatDate(pet.ultima_actualizacion_clinica, true) : 'Sin actualizaciones clínicas registradas'}</dd>
                                    </div>
                                </div>
                            </dl>
                            <Link
                                href={`/mi-cuenta/seguimiento/${pet.id}`}
                                className="mt-5 inline-flex min-h-11 items-center justify-between rounded-xl bg-[#102b38] px-4 text-sm font-semibold text-white transition hover:bg-[#174653]"
                            >
                                Ver seguimiento
                                <ArrowRight size={17} />
                            </Link>
                        </article>
                    ))}
                </div>
            ) : (
                <div className="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center">
                    <span className="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-500"><PawPrint size={23} /></span>
                    <h3 className="mt-4 text-lg font-bold text-[#102b38]">No hay mascotas en seguimiento activo</h3>
                    <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">
                        Las mascotas aparecerán aquí cuando tengan una urgencia activa y todavía no tengan una salida física registrada.
                    </p>
                </div>
            )}
        </section>
    );
}

function TimelineIcon({ type }) {
    const icons = {
        ingreso: CalendarDays,
        egreso: LogOut,
    };
    const Icon = icons[type] || Activity;

    return (
        <span className="relative z-10 grid size-10 shrink-0 place-items-center rounded-full border-4 border-slate-50 bg-teal-700 text-white">
            <Icon size={17} />
        </span>
    );
}

function TimelineEntry({ entry }) {
    const titles = {
        ingreso: 'Ingreso a la clínica',
        egreso: 'Egreso físico registrado',
    };

    return (
        <li className="relative flex gap-4">
            <div className="absolute bottom-[-1.25rem] left-5 top-10 w-px bg-teal-100 last:hidden" />
            <TimelineIcon type={entry.tipo} />
            <article className="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-teal-700">{titles[entry.tipo] || 'Actualización'}</p>
                        <time className="mt-1 block text-sm font-bold text-[#102b38]">{formatDate(entry.fecha, true)}</time>
                    </div>
                    {entry.estado && entry.tipo === 'ingreso' && <TrackingStatus value={entry.estado} />}
                </div>

            </article>
        </li>
    );
}

function ClinicalTimeline({ timeline = [] }) {
    if (!timeline.length) {
        return (
            <div className="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                <Activity size={22} className="mx-auto text-slate-400" />
                <h3 className="mt-3 font-bold text-[#102b38]">No hay fechas de ingreso o egreso disponibles</h3>
                <p className="mt-1 text-sm leading-6 text-slate-600">El detalle de evoluciones, observaciones, tratamientos y medicamentos no se muestra en este seguimiento.</p>
            </div>
        );
    }

    return (
        <ol className="space-y-5">
            {timeline.map((entry, index) => (
                <TimelineEntry entry={entry} key={`${entry.tipo}-${entry.id || entry.evolucion_id || entry.administracion_id || entry.indicacion_id || index}`} />
            ))}
        </ol>
    );
}

function TrackingDetail({ trackingPet, trackingTimeline = null }) {
    const [downloadingPdf, setDownloadingPdf] = useState(false);
    const [pdfError, setPdfError] = useState('');
    const pet = trackingPet?.mascota;
    const timeline = trackingTimeline || trackingPet?.timeline || [];

    if (!pet) {
        return (
            <div className="rounded-3xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
                No fue posible cargar la información de seguimiento. Vuelve al listado e intenta nuevamente.
            </div>
        );
    }

    async function downloadFollowUpPdf() {
        setDownloadingPdf(true);
        setPdfError('');

        try {
            const response = await fetch(`/mi-cuenta/seguimiento/${pet.id}/pdf`, {
                headers: { Accept: 'application/pdf' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                setPdfError(response.status === 403 || response.status === 404
                    ? 'No tienes acceso a este seguimiento.'
                    : 'No fue posible generar el PDF. Intenta nuevamente.');
                return;
            }

            if (!response.headers.get('content-type')?.includes('application/pdf')) {
                setPdfError('Tu sesión pudo haber expirado. Inicia sesión e intenta nuevamente.');
                return;
            }

            const file = await response.blob();
            const url = URL.createObjectURL(file);
            const link = document.createElement('a');
            link.href = url;
            link.download = `seguimiento-${pet.id}.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch {
            setPdfError('Ocurrió un problema de conexión al descargar el PDF. Intenta nuevamente.');
        } finally {
            setDownloadingPdf(false);
        }
    }

    return (
        <div className="space-y-5">
            <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex items-start gap-4">
                        <span className="grid size-14 shrink-0 place-items-center rounded-2xl bg-teal-50 text-teal-800"><PawPrint size={27} /></span>
                        <div>
                            <p className="text-sm font-semibold text-teal-800">Información de seguimiento</p>
                            <h2 className="mt-1 text-2xl font-bold text-[#102b38]">{pet.nombre}</h2>
                            <p className="mt-1 text-sm text-slate-600">{pet.especie}{pet.raza ? ` · ${pet.raza}` : ''}{pet.sexo ? ` · ${pet.sexo}` : ''}</p>
                        </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <TrackingStatus value={pet.estado} />
                        <button
                            type="button"
                            onClick={downloadFollowUpPdf}
                            disabled={downloadingPdf}
                            className="inline-flex min-h-10 items-center gap-2 rounded-xl bg-[#102b38] px-4 text-sm font-semibold text-white transition hover:bg-[#174653] disabled:cursor-wait disabled:opacity-70"
                        >
                            {downloadingPdf ? <LoaderCircle size={16} className="animate-spin" /> : <Download size={16} />}
                            {downloadingPdf ? 'Generando PDF…' : 'Descargar seguimiento PDF'}
                        </button>
                    </div>
                </div>
                {pdfError && <p className="mt-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800" role="alert">{pdfError}</p>}

                <dl className="mt-6 grid gap-4 rounded-2xl bg-slate-50 p-4 sm:grid-cols-2">
                    <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de ingreso</dt><dd className="mt-1 text-sm text-slate-800">{formatDate(trackingPet.ingreso?.fecha, true)}</dd></div>
                    <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Última actualización registrada</dt><dd className="mt-1 text-sm text-slate-800">{trackingPet.ultima_actualizacion_clinica ? formatDate(trackingPet.ultima_actualizacion_clinica, true) : 'Sin actualizaciones registradas'}</dd></div>
                </dl>
            </section>

            {trackingPet.egreso && (
                <section className="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <div className="flex items-start gap-3">
                        <LogOut size={20} className="mt-0.5 shrink-0 text-emerald-800" />
                        <div>
                            <h3 className="font-bold text-emerald-950">Egreso registrado</h3>
                            <p className="mt-1 text-sm text-emerald-900">{formatDate(trackingPet.egreso.fecha, true)}</p>
                        </div>
                    </div>
                </section>
            )}

            <section className="rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sm leading-6 text-sky-950">
                Este seguimiento muestra únicamente datos de identificación y fechas de ingreso, actualización y egreso.
                Las evoluciones, observaciones, tratamientos e información de medicamentos se conservan en el historial profesional
                y no se comparten aquí mientras no exista una clasificación explícita de información autorizada para propietarios.
            </section>

            <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p className="text-sm font-semibold text-teal-800">Fechas de seguimiento</p>
                        <h3 className="mt-1 text-xl font-bold text-[#102b38]">Historial cronológico</h3>
                    </div>
                    <Link
                        href={`/mi-cuenta/seguimiento/${pet.id}/historial`}
                        className="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Ver historial <ArrowRight size={16} />
                    </Link>
                </div>
                <ClinicalTimeline timeline={timeline} />
            </section>
        </div>
    );
}

function TrackingHistory({ trackingPet, trackingTimeline = [] }) {
    const pet = trackingPet?.mascota;

    return (
        <div className="space-y-5">
            <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-start gap-4">
                        <span className="grid size-12 shrink-0 place-items-center rounded-2xl bg-teal-50 text-teal-800"><PawPrint size={23} /></span>
                        <div>
                            <p className="text-sm font-semibold text-teal-800">Historial de seguimiento</p>
                            <h2 className="mt-1 text-xl font-bold text-[#102b38]">{pet?.nombre || 'Mascota'}</h2>
                            {pet && <p className="mt-1 text-sm text-slate-600">{pet.especie}{pet.raza ? ` · ${pet.raza}` : ''}</p>}
                            <p className="mt-2 text-sm leading-6 text-slate-600">Este historial muestra únicamente fechas de ingreso y egreso. Los detalles clínicos, tratamientos y medicamentos no se comparten en este apartado.</p>
                        </div>
                    </div>
                    {pet && (
                        <Link href={`/mi-cuenta/seguimiento/${pet.id}`} className="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <ArrowLeft size={16} /> Ver seguimiento
                        </Link>
                    )}
                </div>
            </section>
            <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <h3 className="mb-5 text-xl font-bold text-[#102b38]">Historial cronológico completo</h3>
                <ClinicalTimeline timeline={trackingTimeline} />
            </section>
        </div>
    );
}

function ProfileForm({ ownerProfile, user }) {
    const form = useForm({
        nombres: ownerProfile?.nombres || '',
        apellidos: ownerProfile?.apellidos || '',
        tipo_documento: ownerProfile?.tipo_documento || '',
        numero_documento: ownerProfile?.numero_documento || '',
        telefono: ownerProfile?.telefono || '',
        email: user?.email || '',
    });
    const fieldClass = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/15';

    return (
        <form onSubmit={(event) => { event.preventDefault(); form.patch('/mi-cuenta/perfil'); }} className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div className="mb-6"><h2 className="text-xl font-bold text-[#102b38]">Información personal</h2><p className="mt-1 text-sm text-slate-600">Actualiza los datos de contacto de tu perfil.</p></div>
            <div className="grid gap-4 sm:grid-cols-2">
                {[
                    ['nombres', 'Nombres', 'text'],
                    ['apellidos', 'Apellidos', 'text'],
                    ['tipo_documento', 'Tipo de documento', 'text'],
                    ['numero_documento', 'Número de documento', 'text'],
                    ['telefono', 'Teléfono', 'tel'],
                    ['email', 'Correo electrónico', 'email'],
                ].map(([name, label, type]) => (
                    <label className="text-sm font-medium text-slate-700" key={name}>{label}
                        <input className={fieldClass} type={type} value={form.data[name]} onChange={(event) => form.setData(name, event.target.value)} required />
                        {form.errors[name] && <span className="mt-1 block text-xs text-rose-700">{form.errors[name]}</span>}
                    </label>
                ))}
            </div>
            <div className="mt-6 flex flex-wrap gap-3">
                <button className="rounded-xl bg-teal-800 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-900 disabled:opacity-60" type="submit" disabled={form.processing}>{form.processing ? 'Guardando…' : 'Guardar cambios'}</button>
                <Link className="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="/propietario/dashboard">Cancelar</Link>
            </div>
        </form>
    );
}

export default function Account({
    screen = 'dashboard',
    ownerProfile,
    stats,
    pets = [],
    activePets = [],
    urgencies = [],
    activities = [],
    pet,
    urgency,
    attentions = [],
    trackedPets = [],
    trackingPet,
    trackingTimeline = [],
}) {
    const { auth, flash } = usePage().props;
    const [navigating, setNavigating] = useState(false);
    const user = auth?.user;
    useEffect(() => {
        const removeStart = router.on('start', () => setNavigating(true));
        const removeFinish = router.on('finish', () => setNavigating(false));

        return () => {
            removeStart();
            removeFinish();
        };
    }, []);
    const title = screen === 'profile.edit' ? 'Editar información personal'
        : screen === 'pets.create' ? 'Registrar mascota'
            : screen === 'pets.edit' ? 'Editar mascota'
                : screen === 'pets.show' ? pet?.nombre
                    : screen === 'urgencies.show' ? 'Detalle de urgencia'
                        : screen === 'tracking.index' ? 'Seguimiento de mis mascotas'
                            : screen === 'tracking.show' ? trackingPet?.mascota?.nombre || 'Seguimiento de mascota'
                                : screen === 'tracking.history' ? 'Historial de seguimiento'
                                    : 'Mi cuenta';

    return (
        <PublicLayout currentPage="">
            {navigating && (
                <div className="fixed right-4 top-20 z-50 flex items-center gap-2 rounded-xl bg-[#102b38] px-4 py-3 text-sm font-semibold text-white shadow-lg" role="status" aria-live="polite">
                    <Activity size={17} className="animate-pulse" />
                    Cargando seguimiento…
                </div>
            )}
            <section className="mx-auto max-w-6xl space-y-6 px-5 py-10 lg:px-8 lg:py-12">
                <header className="flex flex-wrap items-center justify-between gap-4">
                    <div><p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Portal del propietario</p><h1 className="mt-1 text-3xl font-bold tracking-tight text-[#102b38]">{screen === 'dashboard' ? `Hola, ${user?.name}` : title}</h1></div>
                    {screen !== 'dashboard' && <Link href="/mi-cuenta" className="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-white"><ArrowLeft size={16} /> Volver al panel</Link>}
                </header>
                {flash?.success && <p className="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{flash.success}</p>}
                {flash?.error && <p className="rounded-xl bg-rose-50 p-3 text-sm text-rose-800" role="alert">{flash.error}</p>}
                {screen === 'tracking.index' ? <TrackingIndex trackedPets={trackedPets} /> :
                    screen === 'tracking.show' ? <TrackingDetail trackingPet={trackingPet} /> :
                        screen === 'tracking.history' ? <TrackingHistory trackingPet={trackingPet} trackingTimeline={trackingTimeline} /> :
                !ownerProfile ? (
                    <div className="rounded-3xl border border-amber-200 bg-amber-50 p-6 text-sm leading-6 text-amber-900">No encontramos un perfil de propietario vinculado a esta cuenta. Solicita ayuda al personal autorizado.</div>
                ) : screen === 'pets.create' ? <PetForm /> :
                    screen === 'profile.edit' ? <ProfileForm ownerProfile={ownerProfile} user={user} /> :
                    screen === 'pets.edit' ? <PetForm pet={pet} /> :
                        screen === 'pets.show' ? <PetDetails pet={pet} attentions={attentions} /> :
                            screen === 'urgencies.show' ? <UrgencyDetails urgency={urgency} attentions={attentions} /> :
                                <AccountDashboard ownerProfile={ownerProfile} stats={stats} pets={pets} activePets={activePets} urgencies={urgencies} activities={activities} />}
                <Link href="/informacion-veterinaria" className="inline-flex items-center gap-2 text-sm font-semibold text-teal-800 hover:text-teal-950">Consultar información veterinaria <ArrowRight size={16} /></Link>
            </section>
        </PublicLayout>
    );
}
