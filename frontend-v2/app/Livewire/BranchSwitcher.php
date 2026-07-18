<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Livewire\Component;

class BranchSwitcher extends Component
{
    public $branches = [];
    public $activeBranchId;

    public function mount()
    {
        if (!PermissionHelper::hasResource('ui.branch_switcher.view')) {
            return;
        }

        try {
            $response = ApiService::client()->get('/branches/switcher-options');
            if ($response->successful()) {
                $this->branches = $response->json('data') ?? [];
            }
        } catch (\Exception $e) {
            $this->branches = [];
        }

        // Initialize active branch ID from session or user's default branch
        $this->activeBranchId = session('active_branch_id', session('data.user.branch_id'));
    }

    public function updatedActiveBranchId($value)
    {
        if ($value) {
            session(['active_branch_id' => $value]);
            
            // Reload halaman secara instan dari sisi client
            // agar tidak menunggu Livewire roundtrip selesai.
            $this->js('window.location.reload()');
        }
    }

    public function render()
    {
        if (!PermissionHelper::hasResource('ui.branch_switcher.view')) {
            return <<<'HTML'
                <div></div>
            HTML;
        }

        return view('livewire.branch-switcher');
    }
}
