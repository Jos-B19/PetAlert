import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { LoaderCircle, PawPrint, ShieldCheck } from 'lucide-react';

export default function Login({ redirect = '' }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
        redirect,
    });
    const [passwordVisible, setPasswordVisible] = useState(false);

    function submit(event) {
        event.preventDefault();
        post('/login');
    }

    return (
        <main className="login-page">
            <div className="login-card">
                <section className="login-visual">
                    <img src="/images/pet-alert-login.jpg" alt="Perro acompañado por su cuidador" />
                    <div className="login-image-shade" />
                    <div className="login-brand">
                        <span className="brand-mark"><PawPrint size={23} /></span>
                        <strong>Pet Alert <small>IPIALES</small></strong>
                    </div>
                    <div className="login-quote">
                        <span>“</span>
                        <h2>Cada vida merece una oportunidad.</h2>
                        <p>Una red de cuidado para las mascotas de nuestra comunidad.</p>
                    </div>
                </section>
                <section className="login-form-side">
                    <div className="login-form-content">
                        <div className="login-mobile-mark"><span className="brand-mark"><PawPrint size={23} /></span></div>
                        <p className="eyebrow">BIENVENIDO</p>
                        <h1>Iniciar sesión</h1>
                        <p className="login-description">Ingresa tus credenciales para acceder al sistema.</p>
                        <Link className="text-sm font-semibold text-teal-700 hover:text-teal-900" href="/">
                            Volver al inicio
                        </Link>
                        <form onSubmit={submit}>
                            <label className="form-field">
                                <span>Correo electrónico</span>
                                <input
                                    type="email"
                                    autoComplete="username"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    aria-invalid={Boolean(errors.email)}
                                    aria-describedby={errors.email ? 'login-email-error' : undefined}
                                    required
                                />
                                {errors.email && <small className="field-error" id="login-email-error">{errors.email}</small>}
                            </label>
                            <label className="form-field">
                                <span>Contraseña</span>
                                <div className="password-input">
                                    <input
                                        type={passwordVisible ? 'text' : 'password'}
                                        autoComplete="current-password"
                                        value={data.password}
                                        onChange={(event) => setData('password', event.target.value)}
                                        aria-invalid={Boolean(errors.password)}
                                        aria-describedby={errors.password ? 'login-password-error' : undefined}
                                        required
                                    />
                                    <button type="button" onClick={() => setPasswordVisible(!passwordVisible)}>
                                        {passwordVisible ? 'Ocultar' : 'Mostrar'}
                                    </button>
                                </div>
                                {errors.password && <small className="field-error" id="login-password-error">{errors.password}</small>}
                            </label>
                            <label className="remember-field">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(event) => setData('remember', event.target.checked)}
                                />
                                <span>Mantener la sesión iniciada</span>
                            </label>
                            <button className="login-submit" type="submit" disabled={processing}>
                                {processing && <LoaderCircle className="spin" size={17} />}
                                {processing ? 'Ingresando...' : 'Iniciar sesión'}
                            </button>
                        </form>
                        <p className="mt-6 text-center text-sm text-slate-600">
                            ¿No tienes una cuenta?{' '}
                            <Link className="font-semibold text-teal-700 hover:text-teal-900" href="/register">
                                Regístrate
                            </Link>
                        </p>
                        <p className="login-pending"><ShieldCheck size={15} /> Acceso exclusivo para usuarios autorizados.</p>
                    </div>
                    <footer className="login-footer">Pet Alert Ipiales · Cuidado y atención responsable</footer>
                </section>
            </div>
        </main>
    );
}
