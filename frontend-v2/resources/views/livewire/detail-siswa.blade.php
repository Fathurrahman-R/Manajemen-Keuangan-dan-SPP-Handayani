<div class="flex flex-col gap-y-4">
    {{ $this->infolistSiswa }}

    {{ $this->jenjang !== 'MI' ? $this->infolistWali : null}}

    {{ $this->jenjang === 'MI' ? $this->infolistAyah : null}}

    {{ $this->jenjang === 'MI' ? $this->infolistIbu : null}}
</div>