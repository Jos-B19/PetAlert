import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, PawPrint, UserRoundPlus } from 'lucide-react';
import PublicLayout from '../Public/Layout';

const inputClassName = 'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-700 focus:ring-4 focus:ring-teal-700/10';

function Field({ label, name, error, children }) {
    return (
        <label className="grid gap-2 text-sm font-medium text-slate-700" htmlFor={name}>
            {label}
            {children}
            {error && <span className="text-sm font-normal text-rose-700" role="alert">{error}</span>}
        </label>
    );
}

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        nombres: '',
        apellidos: '',
        tipo_documento: '',
        numero_documento: '',
        telefono: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const [confirmationError, setConfirmationError] = useState('');

    function submit(event) {
        event.preventDefault();

        if (data.password !== data.password_confirmation) {
            setConfirmationError('La confirmación de contraseña no coincide.');
            return;
        }

        setConfirmationError('');
        post('/register');
    }

    return (
        <PublicLayout currentPage="">
            <section className="mx-auto max-w-3xl px-5 py-12 lg:px-8">
                <Link href="/" className="inline-flex items-center gap-2 text-sm font-semibold text-teal-800 hover:text-teal-950">
                    <ArrowLeft size={16} /> Volver al inicio
                </Link>
                <div className="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-9">
                    <span className="grid size-12 place-items-center rounded-2xl bg-teal-50 text-teal-800">
                        <UserRoundPlus size={23} />
                    </span>
                    <h1 className="mt-5 text-3xl font-bold tracking-tight text-[#102b38]">Crear cuenta de propietario</h1>
                    <p className="mt-2 max-w-xl text-sm leading-6 text-slate-600">
                        Registra tus datos personales. Esta cuenta solo permite consultar tu perfil y no habilita
                        funciones administrativas ni módulos clínicos internos.
                    </p>

                    <form className="mt-8 grid gap-5" onSubmit={submit}>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field label="Nombres" name="nombres" error={errors.nombres}>
                                <input
                                    id="nombres"
                                    className={inputClassName}
                                    autoComplete="given-name"
                                    maxLength={100}
                                    value={data.nombres}
                                    onChange={(event) => setData('nombres', event.target.value)}
                                    required
                                />
                            </Field>
                            <Field label="Apellidos" name="apellidos" error={errors.apellidos}>
                                <input
                                    id="apellidos"
                                    className={inputClassName}
                                    autoComplete="family-name"
                                    maxLength={100}
                                    value={data.apellidos}
                                    onChange={(event) => setData('apellidos', event.target.value)}
                                    required
                                />
                            </Field>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field label="Tipo de documento" name="tipo_documento" error={errors.tipo_documento}>
                                <select
                                    id="tipo_documento"
                                    className={inputClassName}
                                    value={data.tipo_documento}
                                    onChange={(event) => setData('tipo_documento', event.target.value)}
                                    required
                                >
                                    <option value="">Selecciona una opción</option>
                                    <option value="CC">Cédula de ciudadanía</option>
                                    <option value="CE">Cédula de extranjería</option>
                                    <option value="TI">Tarjeta de identidad</option>
                                    <option value="Pasaporte">Pasaporte</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </Field>
                            <Field label="Número de documento" name="numero_documento" error={errors.numero_documento}>
                                <input
                                    id="numero_documento"
                                    className={inputClassName}
                                    autoComplete="off"
                                    maxLength={30}
                                    value={data.numero_documento}
                                    onChange={(event) => setData('numero_documento', event.target.value)}
                                    required
                                />
                            </Field>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field label="Teléfono" name="telefono" error={errors.telefono}>
                                <input
                                    id="telefono"
                                    className={inputClassName}
                                    type="tel"
                                    inputMode="tel"
                                    autoComplete="tel"
                                    minLength={7}
                                    maxLength={20}
                                    pattern="[0-9]{7,20}"
                                    value={data.telefono}
                                    onChange={(event) => setData('telefono', event.target.value)}
                                    required
                                />
                            </Field>
                            <Field label="Correo electrónico" name="email" error={errors.email}>
                                <input
                                    id="email"
                                    className={inputClassName}
                                    type="email"
                                    autoComplete="email"
                                    maxLength={150}
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    required
                                />
                            </Field>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field label="Contraseña (mínimo 12 caracteres)" name="password" error={errors.password}>
                                <input
                                    id="password"
                                    className={inputClassName}
                                    type="password"
                                    autoComplete="new-password"
                                    minLength={12}
                                    value={data.password}
                                    onChange={(event) => {
                                        setData('password', event.target.value);
                                        if (event.target.value === data.password_confirmation) {
                                            setConfirmationError('');
                                        }
                                    }}
                                    required
                                />
                            </Field>
                            <Field
                                label="Confirmar contraseña"
                                name="password_confirmation"
                                error={confirmationError || errors.password_confirmation}
                            >
                                <input
                                    id="password_confirmation"
                                    className={inputClassName}
                                    type="password"
                                    autoComplete="new-password"
                                    minLength={12}
                                    value={data.password_confirmation}
                                    onChange={(event) => {
                                        setData('password_confirmation', event.target.value);
                                        if (event.target.value === data.password) {
                                            setConfirmationError('');
                                        }
                                    }}
                                    required
                                />
                            </Field>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-2 inline-flex items-center justify-center gap-2 rounded-xl bg-[#102b38] px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#174653] disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <PawPrint size={17} />
                            {processing ? 'Creando cuenta…' : 'Crear cuenta'}
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-slate-600">
                        ¿Ya tienes una cuenta?{' '}
                        <Link href="/login" className="font-semibold text-teal-800 hover:text-teal-950">
                            Inicia sesión
                        </Link>
                    </p>
                </div>
            </section>
        </PublicLayout>
    );
}
