import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Menu, PawPrint, X } from 'lucide-react';

const navigation = [
    { page: 'home', label: 'Inicio', href: '/' },
    { page: 'about', label: 'Nosotros', href: '/nosotros' },
    { page: 'services', label: 'Servicios', href: '/servicios' },
    { page: 'information', label: 'Información veterinaria', href: '/informacion-veterinaria' },
    { page: 'contact', label: 'Contacto', href: '/contacto' },
];

export default function PublicLayout({ children, currentPage }) {
    const [menuOpen, setMenuOpen] = useState(false);
    const { auth } = usePage().props;
    const user = auth?.user;
    const isOwner = user?.rol === 'propietario';
    const dashboardHref = {
        administrador: '/admin/dashboard',
        recepcion: '/recepcion/dashboard',
        veterinario: '/veterinario/dashboard',
        propietario: '/propietario/dashboard',
    }[user?.rol] || '/';

    return (
        <div className="min-h-screen bg-slate-50 text-slate-800">
            <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3 lg:px-8">
                    <Link href="/" className="flex shrink-0 items-center gap-3" aria-label="Pet Alert Ipiales, inicio">
                        <span className="grid size-11 place-items-center rounded-2xl bg-[#a7eee0] text-[#103c43]">
                            <PawPrint size={23} />
                        </span>
                        <span className="flex flex-col leading-tight">
                            <strong className="text-lg font-bold tracking-tight text-[#102b38]">Pet Alert</strong>
                            <span className="text-[10px] font-bold tracking-[0.2em] text-slate-500">IPIALES</span>
                        </span>
                    </Link>

                    <button
                        type="button"
                        className="grid size-11 place-items-center rounded-xl border border-slate-200 text-[#102b38] md:hidden"
                        aria-label={menuOpen ? 'Cerrar menú' : 'Abrir menú'}
                        aria-expanded={menuOpen}
                        onClick={() => setMenuOpen(!menuOpen)}
                    >
                        {menuOpen ? <X size={21} /> : <Menu size={21} />}
                    </button>

                    <nav
                        aria-label="Navegación principal"
                        className={`${menuOpen ? 'flex' : 'hidden'} absolute inset-x-0 top-full flex-col gap-1 border-b border-slate-200 bg-white p-4 shadow-lg md:static md:flex md:flex-row md:items-center md:gap-1 md:border-0 md:bg-transparent md:p-0 md:shadow-none`}
                    >
                        {navigation.map((item) => (
                            <Link
                                key={item.page}
                                href={item.href}
                                aria-current={currentPage === item.page ? 'page' : undefined}
                                onClick={() => setMenuOpen(false)}
                                className={`rounded-xl px-3 py-2 text-sm font-medium transition ${
                                    currentPage === item.page
                                        ? 'bg-teal-50 text-teal-800'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-[#102b38]'
                                }`}
                            >
                                {item.label}
                            </Link>
                        ))}

                        {user ? (
                            <>
                                {isOwner ? (
                                    <Link
                                        href="/propietario/dashboard"
                                        onClick={() => setMenuOpen(false)}
                                        className="rounded-xl px-3 py-2 text-sm font-semibold text-[#102b38] hover:bg-slate-100"
                                    >
                                        Mi panel
                                    </Link>
                                ) : (
                                    <Link
                                        href={dashboardHref}
                                        onClick={() => setMenuOpen(false)}
                                        className="rounded-xl px-3 py-2 text-sm font-semibold text-[#102b38] hover:bg-slate-100"
                                    >
                                        Panel interno
                                    </Link>
                                )}
                                {isOwner && (
                                    <>
                                        <Link href="/mi-cuenta/seguimiento" onClick={() => setMenuOpen(false)} className="rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                            Seguimiento de mis mascotas
                                        </Link>
                                        <Link href="/propietario/dashboard#mis-mascotas" onClick={() => setMenuOpen(false)} className="rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                            Mis mascotas
                                        </Link>
                                        <Link href="/propietario/dashboard#mis-urgencias" onClick={() => setMenuOpen(false)} className="rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                            Mis urgencias
                                        </Link>
                                        <Link href="/propietario/dashboard#registrar-urgencia" onClick={() => setMenuOpen(false)} className="rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                            Registrar urgencia
                                        </Link>
                                    </>
                                )}
                                <Link
                                    href="/mi-cuenta/seguridad"
                                    onClick={() => setMenuOpen(false)}
                                    className="rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                                >
                                    Cambiar contraseña
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    onClick={() => setMenuOpen(false)}
                                    className="rounded-xl bg-[#102b38] px-4 py-2 text-left text-sm font-semibold text-white hover:bg-[#174653]"
                                >
                                    Cerrar sesión
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    onClick={() => setMenuOpen(false)}
                                    className="rounded-xl px-3 py-2 text-sm font-semibold text-[#102b38] hover:bg-slate-100"
                                >
                                    Iniciar sesión
                                </Link>
                                <Link
                                    href="/register"
                                    onClick={() => setMenuOpen(false)}
                                    className="rounded-xl bg-[#102b38] px-4 py-2 text-sm font-semibold text-white hover:bg-[#174653]"
                                >
                                    Registrarse
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main>{children}</main>

            <footer className="bg-[#102b38] text-slate-200">
                <div className="mx-auto grid max-w-7xl gap-8 px-5 py-10 md:grid-cols-[1fr_auto] md:items-center lg:px-8">
                    <div className="flex items-start gap-3">
                        <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-white/10 text-[#a7eee0]">
                            <PawPrint size={21} />
                        </span>
                        <div>
                            <p className="font-semibold text-white">Pet Alert Ipiales</p>
                            <p className="mt-1 max-w-xl text-sm leading-6 text-slate-300">
                                Información educativa general. Ante una urgencia o duda sobre la salud de una mascota,
                                consulta con un profesional veterinario.
                            </p>
                        </div>
                    </div>
                    <p className="text-sm text-slate-400">© Pet Alert Ipiales</p>
                </div>
            </footer>
        </div>
    );
}
