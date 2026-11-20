<?php

namespace App\Http\Requests\Reportes;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReporteFiltroRequest extends FormRequest
{
    public const PERIODS = [
        'hoy',
        'ultimos_7_dias',
        'ultimos_30_dias',
        'mes_actual',
        'anio_actual',
        'personalizado',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (
            ! $this->filled('periodo')
            && ($this->filled('fecha_inicio') || $this->filled('fecha_fin'))
        ) {
            $this->merge(['periodo' => 'personalizado']);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'periodo' => ['nullable', 'string', Rule::in(self::PERIODS)],
            'fecha_inicio' => [
                'nullable',
                'required_if:periodo,personalizado',
                'date_format:Y-m-d',
            ],
            'fecha_fin' => [
                'nullable',
                'required_if:periodo,personalizado',
                'date_format:Y-m-d',
                'after_or_equal:fecha_inicio',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periodo.in' => 'El período seleccionado no es válido.',
            'fecha_inicio.required_if' => 'Indica la fecha inicial del período personalizado.',
            'fecha_inicio.date_format' => 'La fecha inicial debe ser una fecha válida con formato AAAA-MM-DD.',
            'fecha_fin.required_if' => 'Indica la fecha final del período personalizado.',
            'fecha_fin.date_format' => 'La fecha final debe ser una fecha válida con formato AAAA-MM-DD.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $this->filled('periodo')
                    && $this->input('periodo') !== 'personalizado'
                    && ($this->filled('fecha_inicio') || $this->filled('fecha_fin'))
                ) {
                    $validator->errors()->add(
                        'periodo',
                        'Usa el período personalizado cuando envíes una fecha inicial y final.',
                    );
                }
            },
        ];
    }

    /**
     * @return array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}
     */
    public function dateRange(): array
    {
        $validated = $this->validated();
        $period = $validated['periodo'] ?? 'mes_actual';

        if ($period === 'personalizado') {
            return [
                'periodo' => $period,
                'desde' => CarbonImmutable::parse($validated['fecha_inicio'])->startOfDay(),
                'hasta' => CarbonImmutable::parse($validated['fecha_fin'])->endOfDay(),
            ];
        }

        $now = CarbonImmutable::now();
        [$from, $to] = match ($period) {
            'hoy' => [$now->startOfDay(), $now],
            'ultimos_7_dias' => [$now->subDays(6)->startOfDay(), $now],
            'ultimos_30_dias' => [$now->subDays(29)->startOfDay(), $now],
            'anio_actual' => [$now->startOfYear(), $now],
            default => [$now->startOfMonth(), $now],
        };

        return [
            'periodo' => $period,
            'desde' => $from,
            'hasta' => $to,
        ];
    }

    /**
     * @return array{periodo: string, fecha_inicio: string, fecha_fin: string}
     */
    public function filterSummary(): array
    {
        $range = $this->dateRange();

        return [
            'periodo' => $range['periodo'],
            'fecha_inicio' => $range['desde']->toDateString(),
            'fecha_fin' => $range['hasta']->toDateString(),
        ];
    }
}
