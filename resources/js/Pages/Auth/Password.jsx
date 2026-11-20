import { Link, useForm, usePage } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import PublicLayout from '../Public/Layout';

export default function Password() {
    const { auth, flash } = usePage().props;
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const fieldClass = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/15';

    return (
        <PublicLayout currentPage="">
            <section className="mx-auto max-w-2xl space-y-6 px-5 py-10 lg:px-8 lg:py-12">
                <header className="flex flex-wrap items-center justify-between gap-4">
                    <div><p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Seguridad de la cuenta</p><h1 className="mt-1 text-3xl font-bold tracking-tight text-[#102b38]">Cambiar contraseña</h1></div>
                    <Link href={auth.user.rol === 'propietario' ? '/propietario/dashboard' : auth.user.rol === 'administrador' ? '/admin/dashboard' : auth.user.rol === 'recepcion' ? '/recepcion/dashboard' : '/veterinario/dashboard'} className="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-white">Volver al panel</Link>
                </header>
                {flash?.success && <p className="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{flash.success}</p>}
                <form onSubmit={(event) => { event.preventDefault(); form.patch('/mi-cuenta/seguridad', { onSuccess: () => form.reset() }); }} className="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div className="mb-2 flex items-start gap-3">
                        <span className="grid size-11 shrink-0 place-items-center rounded-2xl bg-teal-50 text-teal-800"><KeyRound size={21} /></span>
                        <div><h2 className="text-lg font-bold text-[#102b38]">Actualiza tus credenciales</h2><p className="mt-1 text-sm text-slate-600">Usa una contraseña nueva de al menos 12 caracteres.</p></div>
                    </div>
                    {[
                        ['current_password', 'Contraseña actual'],
                        ['password', 'Nueva contraseña'],
                        ['password_confirmation', 'Confirmar nueva contraseña'],
                    ].map(([name, label]) => (
                        <label className="block text-sm font-medium text-slate-700" key={name}>{label}
                            <input className={fieldClass} type="password" autoComplete={name === 'current_password' ? 'current-password' : 'new-password'} value={form.data[name]} onChange={(event) => form.setData(name, event.target.value)} required />
                            {form.errors[name] && <span className="mt-1 block text-xs text-rose-700">{form.errors[name]}</span>}
                        </label>
                    ))}
                    <button className="rounded-xl bg-teal-800 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-900 disabled:opacity-60" type="submit" disabled={form.processing}>{form.processing ? 'Guardando…' : 'Actualizar contraseña'}</button>
                </form>
            </section>
        </PublicLayout>
    );
}
