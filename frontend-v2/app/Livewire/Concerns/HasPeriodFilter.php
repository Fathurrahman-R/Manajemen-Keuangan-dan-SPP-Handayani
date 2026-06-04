<?php

namespace App\Livewire\Concerns;

use App\Services\ApiService;
use Filament\Forms\Components\Select;

trait HasPeriodFilter
{
    public ?int $selectedTahunAjaranId = null;
    public array $tahunAjaranOptions = [];

    public function mountHasPeriodFilter(): void
    {
        $this->loadTahunAjaranOptions();

        // Restore from session or default to Periode_Aktif
        $this->selectedTahunAjaranId = session('selected_tahun_ajaran_id', $this->getAktifId());

        // Validate session value still exists in available options
        if ($this->selectedTahunAjaranId && !$this->isValidOption($this->selectedTahunAjaranId)) {
            $this->selectedTahunAjaranId = $this->getAktifId();
            session()->forget('selected_tahun_ajaran_id');
        }
    }

    public function updatedSelectedTahunAjaranId($value): void
    {
        session(['selected_tahun_ajaran_id' => $value ? (int) $value : null]);

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
                $label = $option['nama'] . ($option['status'] === 'Aktif' ? ' (Aktif)' : ' (Historis)');
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
                return $option['nama'] . $badge;
            }
        }
        return 'Pilih Periode';
    }

    private function isValidOption(?int $id): bool
    {
        if ($id === null) return false;
        foreach ($this->tahunAjaranOptions as $option) {
            if ((int) $option['id'] === $id) {
                return true;
            }
        }
        return false;
    }
}
