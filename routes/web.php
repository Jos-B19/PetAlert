<?php

use App\Http\Controllers\AdministracionMedicamentoController;
use App\Http\Controllers\AtencionController;
use App\Http\Controllers\AtencionMedicamentoController;
use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvolucionClinicaController;
use App\Http\Controllers\HistorialClinicoController;
use App\Http\Controllers\MascotaController;
use App\Http\Controllers\MedicamentoController;
use App\Http\Controllers\PropietarioController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ReportesPdfController;
use App\Http\Controllers\UrgenciaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/nosotros', [PublicSiteController::class, 'about'])->name('public.about');
Route::get('/servicios', [PublicSiteController::class, 'services'])->name('public.services');
Route::get('/informacion-veterinaria', [PublicSiteController::class, 'information'])->name('public.information');
Route::get('/contacto', [PublicSiteController::class, 'contact'])->name('public.contact');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/mi-cuenta', [AccountController::class, 'show'])
        ->middleware('role:propietario')
        ->name('account.show');
    Route::middleware('role:propietario')->group(function () {
        Route::get('/propietario/dashboard', [AccountController::class, 'show'])->name('owner.dashboard');
        Route::get('/mi-cuenta/perfil', [AccountController::class, 'editProfile'])->name('account.profile.edit');
        Route::get('/mi-cuenta/mascotas/create', [AccountController::class, 'createPet'])->name('account.pets.create');
        Route::post('/mi-cuenta/mascotas', [AccountController::class, 'storePet'])->name('account.pets.store');
        Route::get('/mi-cuenta/mascotas/{pet}', [AccountController::class, 'showPet'])->whereNumber('pet')->name('account.pets.show');
        Route::get('/mi-cuenta/mascotas/{pet}/edit', [AccountController::class, 'editPet'])->whereNumber('pet')->name('account.pets.edit');
        Route::put('/mi-cuenta/mascotas/{pet}', [AccountController::class, 'updatePet'])->whereNumber('pet')->name('account.pets.update');
        Route::get('/mi-cuenta/seguimiento', [AccountController::class, 'trackingIndex'])->name('account.tracking.index');
        Route::get('/mi-cuenta/seguimiento/{pet}', [AccountController::class, 'trackingShow'])->whereNumber('pet')->name('account.tracking.show');
        Route::get('/mi-cuenta/seguimiento/{pet}/pdf', [AccountController::class, 'trackingPdf'])->whereNumber('pet')->name('account.tracking.pdf');
        Route::get('/mi-cuenta/seguimiento/{pet}/historial', [AccountController::class, 'trackingHistory'])->whereNumber('pet')->name('account.tracking.history');
        Route::post('/mi-cuenta/urgencias', [AccountController::class, 'storeUrgency'])->name('account.urgencies.store');
        Route::get('/mi-cuenta/urgencias/{urgency}', [AccountController::class, 'showUrgency'])->whereNumber('urgency')->name('account.urgencies.show');
        Route::patch('/mi-cuenta/perfil', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    });
    Route::get('/mi-cuenta/seguridad', [AccountController::class, 'editPassword'])->name('password.edit');
    Route::patch('/mi-cuenta/seguridad', [AccountController::class, 'updatePassword'])->name('password.update');

    Route::middleware('role:administrador,recepcion,veterinario')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'redirectForRole'])->name('dashboard');
    });
    Route::get('/admin/dashboard', DashboardController::class)
        ->middleware('role:administrador')
        ->name('admin.dashboard');
    Route::get('/recepcion/dashboard', [DashboardController::class, 'reception'])
        ->middleware('role:recepcion')
        ->name('reception.dashboard');
    Route::get('/veterinario/dashboard', [DashboardController::class, 'veterinarian'])
        ->middleware('role:veterinario')
        ->name('veterinarian.dashboard');

    Route::middleware('role:administrador,recepcion')->group(function () {
        Route::resource('owners', PropietarioController::class)->only(['create', 'store']);
        Route::resource('pets', MascotaController::class)->only(['create', 'store']);
    });
    Route::middleware('role:administrador')->group(function () {
        Route::resource('owners', PropietarioController::class)->only(['edit', 'update', 'destroy']);
        Route::resource('pets', MascotaController::class)->only(['edit', 'update', 'destroy']);
    });

    Route::middleware('role:administrador,recepcion,veterinario')->group(function () {
        Route::get('/urgencies', [UrgenciaController::class, 'index'])->name('urgencies.index');
        Route::get('/urgencies/{urgency}', [UrgenciaController::class, 'show'])->whereNumber('urgency')->name('urgencies.show');
    });
    Route::middleware('role:administrador,veterinario')->group(function () {
        Route::get('/histories', [HistorialClinicoController::class, 'index'])->name('histories.index');
        Route::get('/histories/{pet}', [HistorialClinicoController::class, 'show'])->name('histories.show');
    });

    Route::middleware('role:administrador,recepcion')->group(function () {
        Route::get('/urgencies/create', [UrgenciaController::class, 'create'])->name('urgencies.create');
        Route::post('/urgencies', [UrgenciaController::class, 'store'])->name('urgencies.store');
        Route::patch('/urgencies/{urgency}/assignment', [UrgenciaController::class, 'assign'])->name('urgencies.assign');
    });

    Route::middleware('role:administrador,veterinario')->group(function () {
        Route::put('/urgencies/{urgency}', [UrgenciaController::class, 'update'])->name('urgencies.update');
        Route::post('/urgencies/{urgency}/discharge', [UrgenciaController::class, 'discharge'])->name('urgencies.discharge');

        Route::resource('attentions', AtencionController::class)->except(['destroy']);
        Route::post('/attentions/{attention}/medications', [AtencionMedicamentoController::class, 'store'])->name('attentions.medications.store');
        Route::post('/attention-medications/{attentionMedication}/administrations', [AdministracionMedicamentoController::class, 'store'])->name('attention-medications.administrations.store');
        Route::resource('evolutions', EvolucionClinicaController::class)->only(['index', 'create', 'store', 'show']);
        Route::put('/histories/{pet}', [HistorialClinicoController::class, 'update'])->name('histories.update');
    });

    Route::middleware('role:administrador')->group(function () {
        Route::get('/medications', [MedicamentoController::class, 'index'])->name('medications.index');
        Route::post('/medications', [MedicamentoController::class, 'store'])->name('medications.store');
    });

    Route::middleware('role:administrador,veterinario,recepcion')->group(function () {
        Route::resource('owners', PropietarioController::class)->only(['index', 'show']);
        Route::resource('pets', MascotaController::class)->only(['index', 'show']);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/owners/{owner}/toggle-status', [PropietarioController::class, 'toggleStatus'])->name('owners.toggle-status');
    });

    Route::middleware('role:administrador,recepcion,veterinario')
        ->prefix('reports')
        ->name('reports.')
        ->group(function () {
            Route::get('/', [ReportesController::class, 'index'])->name('index');
            Route::get('/pdf', [ReportesPdfController::class, 'index'])->name('pdf');
            Route::get('/ingresos', [ReportesController::class, 'ingresos'])->name('ingresos');
            Route::get('/ingresos/pdf', [ReportesPdfController::class, 'ingresos'])->name('ingresos.pdf');
            Route::get('/ocupacion', [ReportesController::class, 'ocupacion'])->name('ocupacion');
            Route::get('/ocupacion/pdf', [ReportesPdfController::class, 'ocupacion'])->name('ocupacion.pdf');
        });

    Route::middleware('role:administrador,veterinario')
        ->prefix('reports')
        ->name('reports.')
        ->group(function () {
            Route::get('/medicamentos', [ReportesController::class, 'medicamentos'])->name('medicamentos');
            Route::get('/medicamentos/pdf', [ReportesPdfController::class, 'medicamentos'])->name('medicamentos.pdf');
            Route::get('/resultados', [ReportesController::class, 'resultados'])->name('resultados');
            Route::get('/resultados/pdf', [ReportesPdfController::class, 'resultados'])->name('resultados.pdf');
        });
});
