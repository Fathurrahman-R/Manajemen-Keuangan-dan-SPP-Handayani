<?php

namespace App\Livewire\Concerns;

use App\Services\ApiService;
use Filament\Forms\Components\Select;

trait HasPeriodFilter
{
    public ?int $selectedTahunAjaranId = null;

    public array $tahunAjaranOptions = [];

    /**
     * Whether the period filter exposes a "Semua Periode" pseudo-option
     * that maps to `selectedTahunAjaranId = null` (no period filter).
     *
     * Default: true (semua halaman selain dashboard menggunakan ini).
     * Override per page dengan menyetel property `$allowAllPeriodsOption`
     * = false di kelas pemakai (mis. DashboardPage).
     *
     * Properti ini dideklarasi di sisi pemakai trait, bukan di trait,
     * supaya kelas seperti DashboardPage bisa override default value
     * (PHP melarang property override dengan default berbeda di trait).
     */
    public function mountHasPeriodFilter(?bool $allowAllPeriodsOption = null): void
    {
        // Resolusi default: parameter > property pemakai > true.
        if ($allowAllPeriodsOption !== null) {
            $allow = $allowAllPeriodsOption;
        } elseif (property_exists($this, 'allowAllPeriodsOption')) {
            $allow = (bool) $this->allowAllPeriodsOption;
        } else {
            $allow = true;
        }

        // Persist hasil resolusi ke property pemakai jika ada (supaya
        // updatedSelectedTahunAjaranId() bisa membacanya).
        if (property_exists($this, 'allowAllPeriodsOption')) {
            $this->allowAllPeriodsOption = $allow;
        }

        $this->loadTahunAjaranOptions();

        $sessionValue = session('selected_tahun_ajaran_id', '__missing__');

        if ($sessionValue === '__missing__') {
            // Pertama kali halaman dibuka di session ini — default by mode.
            $this->selectedTahunAjaranId = $allow
                ? null
                : $this->getAktifId();
        } else {
            $this->selectedTahunAjaranId = $sessionValue !== null ? (int) $sessionValue : null;
        }

        // Validate session value still exists in available options
        if ($this->selectedTahunAjaranId && ! $this->isValidOption($this->selectedTahunAjaranId)) {
            $this->selectedTahunAjaranId = $allow ? null : $this->getAktifId();
            session()->forget('selected_tahun_ajaran_id');
        }
    }

    public function updatedSelectedTahunAjaranId($value): void
    {
        // Nilai 0 / '0' / '' diperlakukan sebagai "Semua Periode" (null).
        $normalised = ($value === '' || $value === null || (int) $value === 0)
            ? null
            : (int) $value;

        $this->selectedTahunAjaranId = $normalised;
        session(['selected_tahun_ajaran_id' => $normalised]);

        // Refresh data — subclass should implement this
        if (method_exists($this, 'resetTable')) {
            $this->resetTable();
        } elseif (method_exists($this, 'loadData')) {
            $this->loadData();
        }
    }

    public function loadTahunAjaranOptions(): void
    {
        try {
            $response = ApiService::client()->get('/tahun-ajaran');
            if ($response->ok()) {
                $this->tahunAjaranOptions = $response->json()['data'] ?? [];
            } else {
                $this->tahunAjaranOptions = [];
            }
        } catch (\Throwable $e) {
            $this->tahunAjaranOptions = [];
        }
    }

    /**
     * Get Filament Select component for tahun ajaran period filter.
     * Use this in Blade views: {{ $this->tahunAjaranSelect }}
     */
    public function getTahunAjaranSelectComponent(): Select
    {
        return Select::make('selectedTahunAjaranId')
            ->label('Periode')
            ->options($this->getTahunAjaranSelectOptions())
            ->default($this->selectedTahunAjaranId)
            ->live()
            ->afterStateUpdated(fn ($state) => $this->updatedSelectedTahunAjaranId($state))
            ->placeholder('Pilih Periode')
            ->extraAttributes(['class' => 'w-48']);
    }

    /**
     * Get tahun ajaran options formatted for Filament Select.
     */
    public function getTahunAjaranSelectOptions(): array
    {
        return collect($this->tahunAjaranOptions)
            ->mapWithKeys(function ($option) {
                $label = $option['nama'].($option['status'] === 'Aktif' ? ' (Aktif)' : ' (Historis)');

                return [(int) $option['id'] => $label];
            })
            ->toArray();
    }

    public function getAktifId(): ?int
    {
        foreach ($this->tahunAjaranOptions as $option) {
            if (($option['status'] ?? '') === 'Aktif') {
                return (int) $option['id'];
            }
        }

        return null;
    }

    public function hasTahunAjaranOptions(): bool
    {
        return count($this->tahunAjaranOptions) > 0;
    }

    public function hasNoPeriodeAktif(): bool
    {
        return $this->getAktifId() === null;
    }

    public function getSelectedTahunAjaranLabel(): string
    {
        foreach ($this->tahunAjaranOptions as $option) {
            if ((int) $option['id'] === $this->selectedTahunAjaranId) {
                $badge = $option['status'] === 'Aktif' ? ' (Aktif)' : ' (Historis)';

                return $option['nama'].$badge;
            }
        }

        return 'Pilih Periode';
    }

    private function isValidOption(?int $id): bool
    {
        if ($id === null) {
            return false;
        }
        foreach ($this->tahunAjaranOptions as $option) {
            if ((int) $option['id'] === $id) {
                return true;
            }
        }

        return false;
    }
}
