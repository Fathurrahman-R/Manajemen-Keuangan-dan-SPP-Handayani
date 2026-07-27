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
        if (! PermissionHelper::hasResource('ui.branch_switcher.view')) {
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

            // Livewire navigate (not a full browser reload) — remounts the
            // current page (and every widget on it) against the new branch.
            // `wire:target="activeBranchId"` on the spinner in this component's
            // view covers the AJAX phase; the navigate swap itself is near-
            // instant once the response lands, so no separate handoff is needed.
            //
            // NOT url()->full() — this component's action runs as part of the
            // POST to /livewire/update, so url()->full() resolves to THAT
            // endpoint, not the page the browser is showing (redirects the
            // whole tab to /livewire/update, a 405). The Referer header on that
            // POST is the actual page; url()->previous() (session-tracked, GET
            // requests only) is the fallback if it's ever missing.
            $this->redirect(request()->header('referer') ?? url()->previous(), navigate: true);
        }
    }

    public function render()
    {
        if (! PermissionHelper::hasResource('ui.branch_switcher.view')) {
            return <<<'HTML'
                <div></div>
            HTML;
        }

        return view('livewire.branch-switcher');
    }
}
