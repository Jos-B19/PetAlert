import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    HeartPulse,
    Leaf,
    PawPrint,
    ShieldCheck,
    Siren,
    Sparkles,
    Stethoscope,
    Utensils,
} from 'lucide-react';
import PublicLayout from './Layout';

const topics = [
    {
        id: 'cuidado',
        title: 'Cuidado de mascotas',
        icon: HeartPulse,
        description: 'Las rutinas de cuidado ayudan a reconocer cambios y a mantener un entorno seguro y estable.',
        recommendations: [
            'Mantén agua limpia y un espacio cómodo, protegido y adecuado para la especie.',
            'Adapta la actividad diaria a la edad, condición y necesidades de cada mascota.',
            'Observa cambios en el apetito, el comportamiento o la movilidad y coméntalos con un veterinario.',
        ],
    },
    {
        id: 'urgencias',
        title: 'Urgencias veterinarias',
        icon: Siren,
        description: 'Algunas señales requieren atención profesional inmediata y no deben manejarse solo en casa.',
        recommendations: [
            'Busca atención veterinaria inmediata ante dificultad para respirar, colapso, sangrado abundante o deterioro rápido.',
            'Si sospechas intoxicación, conserva el envase o la información del producto para compartirla con el profesional.',
            'No administres medicamentos ni provoques el vómito sin indicación veterinaria.',
        ],
    },
    {
        id: 'vacunacion',
        title: 'Vacunación',
        icon: ShieldCheck,
        description: 'El plan de vacunación depende de la especie, edad, antecedentes y valoración profesional.',
        recommendations: [
            'Solicita al veterinario un esquema individual y conserva el registro de cada aplicación.',
            'Consulta antes de iniciar o modificar el calendario, especialmente si desconoces los antecedentes.',
            'Pregunta cuándo corresponde el siguiente refuerzo según el plan indicado para tu mascota.',
        ],
    },
    {
        id: 'alimentacion',
        title: 'Alimentación',
        icon: Utensils,
        description: 'Una alimentación apropiada debe considerar la especie, etapa de vida y condición de salud.',
        recommendations: [
            'Elige alimentos formulados para la especie y etapa de vida, siguiendo la orientación profesional.',
            'Mantén agua fresca disponible y realiza cualquier cambio de dieta de manera gradual.',
            'Consulta antes de ofrecer suplementos o alimentos para personas.',
        ],
    },
    {
        id: 'higiene',
        title: 'Higiene y aseo',
        icon: Sparkles,
        description: 'El aseo regular permite cuidar la piel, el pelaje, la boca y las uñas con productos adecuados.',
        recommendations: [
            'Utiliza productos diseñados para mascotas y apropiados para su especie.',
            'Acostumbra a la mascota al cepillado y a la revisión de forma tranquila y progresiva.',
            'Pide orientación profesional si observas irritación, dolor o cambios persistentes en la piel.',
        ],
    },
    {
        id: 'prevencion',
        title: 'Prevención de enfermedades',
        icon: Stethoscope,
        description: 'Los controles veterinarios permiten revisar la salud y acordar medidas preventivas individualizadas.',
        recommendations: [
            'Programa controles preventivos de acuerdo con la recomendación de tu veterinario.',
            'Pregunta qué medidas de vacunación y control de parásitos son adecuadas para tu mascota.',
            'Registra cambios y antecedentes que puedan ayudar durante la consulta.',
        ],
    },
    {
        id: 'bienestar',
        title: 'Bienestar animal',
        icon: Leaf,
        description: 'El bienestar incluye seguridad, descanso, actividad y oportunidades de interacción positiva.',
        recommendations: [
            'Ofrece un lugar de descanso y un entorno protegido de peligros previsibles.',
            'Proporciona juego, exploración e interacción acordes con la especie y el temperamento.',
            'Respeta las señales de incomodidad y busca ayuda profesional ante cambios conductuales importantes.',
        ],
    },
];

const services = [
    {
        title: 'Registro de mascotas',
        icon: HeartPulse,
        description: 'Organización de información de identificación y cuidado dentro de los módulos internos.',
    },
    {
        title: 'Urgencias y seguimiento',
        icon: Siren,
        description: 'Registro y consulta de situaciones de urgencia por parte del personal autorizado.',
    },
    {
        title: 'Atención veterinaria',
        icon: Stethoscope,
        description: 'Registro de atenciones y evolución clínica dentro del sistema protegido.',
    },
    {
        title: 'Historial clínico',
        icon: BookOpen,
        description: 'Consulta de información clínica para usuarios internos con permisos correspondientes.',
    },
];

function ServiceCard({ service }) {
    const Icon = service.icon;

    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <span className="grid size-12 place-items-center rounded-2xl bg-teal-50 text-teal-800">
                <Icon size={23} />
            </span>
            <h3 className="mt-5 text-lg font-semibold text-[#102b38]">{service.title}</h3>
            <p className="mt-2 text-sm leading-6 text-slate-600">{service.description}</p>
        </article>
    );
}

function TopicCard({ topic }) {
    const Icon = topic.icon;

    return (
        <article id={topic.id} className="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <span className="grid size-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-800">
                <Icon size={23} />
            </span>
            <h2 className="mt-5 text-xl font-semibold text-[#102b38]">{topic.title}</h2>
            <p className="mt-2 text-sm leading-6 text-slate-600">{topic.description}</p>
            <a
                href={`#${topic.id}-recomendaciones`}
                className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-teal-800 hover:text-teal-950"
            >
                Consultar recomendaciones <ArrowRight size={16} />
            </a>
            <div id={`${topic.id}-recomendaciones`} className="mt-5 border-t border-slate-100 pt-4">
                <h3 className="text-sm font-semibold text-slate-800">Recomendaciones generales</h3>
                <ul className="mt-3 grid gap-2">
                    {topic.recommendations.map((recommendation) => (
                        <li key={recommendation} className="flex gap-2 text-sm leading-6 text-slate-600">
                            <span className="mt-2 size-1.5 shrink-0 rounded-full bg-teal-600" />
                            <span>{recommendation}</span>
                        </li>
                    ))}
                </ul>
            </div>
        </article>
    );
}

function HomePage() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const accountHref = {
        administrador: '/admin/dashboard',
        recepcion: '/recepcion/dashboard',
        veterinario: '/veterinario/dashboard',
        propietario: '/propietario/dashboard',
    }[user?.rol] || '/register';
    const accountAction = user ? (user.rol === 'propietario' ? 'Mi cuenta' : 'Ir al panel interno') : 'Crear cuenta';

    return (
        <>
            <section className="overflow-hidden bg-[#eff7f5]">
                <div className="mx-auto grid max-w-7xl items-center gap-10 px-5 py-14 md:py-20 lg:grid-cols-[1.05fr_.95fr] lg:px-8">
                    <div>
                        <span className="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-white/80 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-teal-900">
                            <PawPrint size={15} /> Cuidado responsable en Ipiales
                        </span>
                        <h1 className="mt-6 max-w-2xl text-4xl font-bold leading-tight tracking-tight text-[#102b38] sm:text-5xl">
                            Cada mascota merece cuidado y atención.
                        </h1>
                        <p className="mt-5 max-w-xl text-base leading-7 text-slate-600 sm:text-lg">
                            Pet Alert Ipiales reúne información útil sobre bienestar animal y herramientas internas
                            para organizar el seguimiento veterinario.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link
                                href="/servicios"
                                className="inline-flex items-center gap-2 rounded-xl bg-[#102b38] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#174653]"
                            >
                                Conoce los servicios <ArrowRight size={17} />
                            </Link>
                            <Link
                                href={user ? accountHref : '/register'}
                                className="rounded-xl border border-teal-800/20 bg-white px-5 py-3 text-sm font-semibold text-teal-900 hover:bg-teal-50"
                            >
                                {accountAction}
                            </Link>
                        </div>
                    </div>
                    <div className="relative">
                        <img
                            src="/images/pet-alert-dashboard.jpg"
                            alt="Mascota acompañada durante su cuidado"
                            className="h-72 w-full rounded-3xl object-cover shadow-md sm:h-96"
                        />
                        <div className="absolute -bottom-4 left-4 right-4 rounded-2xl border border-white/70 bg-white/95 p-4 shadow-md sm:left-auto sm:right-6 sm:max-w-xs">
                            <p className="text-sm font-semibold text-[#102b38]">Información clara, cuidado cercano</p>
                            <p className="mt-1 text-xs leading-5 text-slate-600">
                                Contenido general para acompañar, no sustituir, la valoración veterinaria.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-5 py-16 lg:px-8">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Servicios</p>
                        <h2 className="mt-2 text-3xl font-bold tracking-tight text-[#102b38]">Herramientas para el cuidado</h2>
                    </div>
                    <Link href="/servicios" className="inline-flex items-center gap-2 text-sm font-semibold text-teal-800">
                        Ver servicios <ArrowRight size={16} />
                    </Link>
                </div>
                <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {services.map((service) => <ServiceCard key={service.title} service={service} />)}
                </div>
            </section>

            <section className="bg-white">
                <div className="mx-auto max-w-7xl px-5 py-16 lg:px-8">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Aprende y cuida</p>
                            <h2 className="mt-2 text-3xl font-bold tracking-tight text-[#102b38]">Información veterinaria</h2>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                                Orientación general sobre cuidado, prevención y bienestar animal.
                            </p>
                        </div>
                        <Link
                            href="/informacion-veterinaria"
                            className="inline-flex items-center gap-2 text-sm font-semibold text-teal-800"
                        >
                            Explorar los temas <ArrowRight size={16} />
                        </Link>
                    </div>
                    <div className="mt-8 grid gap-5 md:grid-cols-3">
                        {topics.slice(0, 3).map((topic) => <TopicCard key={topic.id} topic={topic} />)}
                    </div>
                </div>
            </section>

            <section className="mx-auto grid max-w-7xl gap-8 px-5 py-16 md:grid-cols-[.8fr_1.2fr] md:items-center lg:px-8">
                <div className="grid aspect-[4/3] place-items-center rounded-3xl bg-[#dff2ee] text-[#14545a]">
                    <div className="text-center">
                        <PawPrint className="mx-auto" size={54} strokeWidth={1.4} />
                        <p className="mt-3 text-sm font-semibold">Bienestar animal</p>
                    </div>
                </div>
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Sobre Pet Alert</p>
                    <h2 className="mt-2 text-3xl font-bold tracking-tight text-[#102b38]">Una plataforma pensada para acompañar el cuidado</h2>
                    <p className="mt-4 text-base leading-7 text-slate-600">
                        Pet Alert Ipiales ofrece contenido educativo abierto al público y un espacio interno para
                        organizar información de mascotas y atenciones veterinarias por usuarios autorizados.
                    </p>
                    <Link
                        href="/nosotros"
                        className="mt-6 inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-[#102b38] hover:bg-slate-100"
                    >
                        Conoce más <ArrowRight size={16} />
                    </Link>
                </div>
            </section>

            <section className="bg-[#eaf4f1]">
                <div className="mx-auto flex max-w-7xl flex-col gap-5 px-5 py-12 md:flex-row md:items-center md:justify-between lg:px-8">
                    <div>
                        <h2 className="text-2xl font-bold text-[#102b38]">
                            {user ? 'Accede a tu espacio' : '¿Quieres crear una cuenta de propietario?'}
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            {user
                                ? 'Continúa al área correspondiente a tu cuenta.'
                                : 'Registra tus datos personales para consultar tu perfil.'}
                        </p>
                    </div>
                    <Link href={user ? accountHref : '/register'} className="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-800 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-900">
                        {accountAction} <ArrowRight size={16} />
                    </Link>
                </div>
            </section>
        </>
    );
}

function AboutPage() {
    return (
        <section className="mx-auto max-w-5xl px-5 py-16 lg:px-8">
            <p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Nosotros</p>
            <h1 className="mt-2 text-4xl font-bold tracking-tight text-[#102b38]">Cuidado informado para la comunidad</h1>
            <div className="mt-8 grid gap-6 md:grid-cols-2">
                <article className="rounded-2xl bg-white p-7 shadow-sm">
                    <h2 className="text-xl font-semibold text-[#102b38]">Nuestro propósito</h2>
                    <p className="mt-3 leading-7 text-slate-600">
                        Pet Alert Ipiales es una plataforma para compartir información general de bienestar animal y
                        facilitar la organización del seguimiento veterinario dentro del sistema.
                    </p>
                </article>
                <article className="rounded-2xl bg-white p-7 shadow-sm">
                    <h2 className="text-xl font-semibold text-[#102b38]">Atención responsable</h2>
                    <p className="mt-3 leading-7 text-slate-600">
                        El contenido educativo no reemplaza el examen, diagnóstico ni tratamiento de un profesional
                        veterinario. Ante signos de urgencia, busca atención profesional.
                    </p>
                </article>
            </div>
        </section>
    );
}

function ServicesPage() {
    return (
        <section className="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            <p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Servicios</p>
            <h1 className="mt-2 text-4xl font-bold tracking-tight text-[#102b38]">Herramientas del sistema</h1>
            <p className="mt-4 max-w-3xl leading-7 text-slate-600">
                Los módulos de gestión clínica son de acceso restringido y están disponibles únicamente para usuarios
                internos con los permisos correspondientes.
            </p>
            <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {services.map((service) => <ServiceCard key={service.title} service={service} />)}
            </div>
            <Link href="/informacion-veterinaria" className="mt-8 inline-flex items-center gap-2 font-semibold text-teal-800">
                Consultar información veterinaria <ArrowRight size={16} />
            </Link>
        </section>
    );
}

function InformationPage() {
    return (
        <section className="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            <p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Guía educativa</p>
            <h1 className="mt-2 text-4xl font-bold tracking-tight text-[#102b38]">Información veterinaria</h1>
            <p className="mt-4 max-w-3xl rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                Esta información es general y educativa. No sustituye una valoración veterinaria, un diagnóstico ni un
                tratamiento profesional.
            </p>
            <div className="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                {topics.map((topic) => <TopicCard key={topic.id} topic={topic} />)}
            </div>
        </section>
    );
}

function ContactPage() {
    return (
        <section className="mx-auto max-w-5xl px-5 py-16 lg:px-8">
            <p className="text-sm font-semibold uppercase tracking-wider text-teal-800">Contacto</p>
            <h1 className="mt-2 text-4xl font-bold tracking-tight text-[#102b38]">Estamos construyendo este espacio</h1>
            <div className="mt-8 rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                <p className="leading-7 text-slate-600">
                    Los canales oficiales de contacto de Pet Alert Ipiales no están configurados en esta versión del
                    sitio. No publicamos teléfonos, correos ni direcciones que no hayan sido confirmados.
                </p>
                <Link
                    href="/informacion-veterinaria"
                    className="mt-6 inline-flex items-center gap-2 font-semibold text-teal-800"
                >
                    Ver información veterinaria <ArrowRight size={16} />
                </Link>
            </div>
        </section>
    );
}

export default function Site({ page = 'home' }) {
    const pages = {
        home: <HomePage />,
        about: <AboutPage />,
        services: <ServicesPage />,
        information: <InformationPage />,
        contact: <ContactPage />,
    };

    return <PublicLayout currentPage={page}>{pages[page] ?? pages.home}</PublicLayout>;
}
