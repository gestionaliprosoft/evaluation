<?php

namespace App\Livewire;

use App\Models\Team;
use App\Models\User;
use App\Models\Workflow;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Workflows extends Component
{
    public $workFlowsItems = [];

    public $elementArray = [];

    public $roles;

    public $role;

    public $teams;

    public $team;

    public $workFlows = '';

    public $checkedArray = [];

    public $checkAll = false;

    public function render()
    {
        return view('livewire.workflows');
    }

    public function mount()
    {
        $tenantId = auth()->user()->tenant_id;
        $teamIds = User::where('tenant_id', $tenantId)->pluck('team_id')->unique();

        if (auth()->user()->hasRole(['super_admin'])) {
            $allTeams = Team::whereIn('id', $teamIds)->orWhereNull('id')->get();
        } else {
            $allTeams = Team::where('id', auth()->user()->team_id)->get();
        }

        $this->teams[] = [
            'value' => '',
            'label' => __('automation.Select an item'),
        ];
        $this->team = '';
        foreach ($allTeams as $allTeam) {
            $this->teams[] = [
                'value' => $allTeam->id,
                'label' => $allTeam->name,
            ];
        }

        $allRoles = Role::all()->pluck('name');
        $allRoles = $allRoles->filter(function (string $value, int $key) {
            return $value !== 'super_admin';
        });

        $this->roles[] = [
            'value' => '',
            'label' => __('automation.Select an item'),
        ];
        $this->role = '';
        $userRoles = auth()->user()->roles->pluck('name');
        foreach ($allRoles as $allRole) {
            if ($userRoles->contains('super_admin') || $userRoles->contains($allRole)) {
                $this->roles[] = [
                    'value' => $allRole,
                    'label' => $allRole,
                ];
            }
        }

        $allModulesWorkflows = config('workflows.modules');

        $this->workFlowsItems[] = [
            'value' => '',
            'label' => __('automation.Select an item'),
        ];

        foreach ($allModulesWorkflows as $modulesWorkflow) {
            if (auth()->user()->hasPermissionTo('update_ticket::status') && $modulesWorkflow) {
                $this->workFlowsItems[] = [
                    'value' => $modulesWorkflow['class'],
                    'label' => $modulesWorkflow['name'],
                ];
            }
        }
    }

    public function loadFirstRole()
    {
        $this->loadWorkFlowsItemsRole();

    }

    public function resetSelectRole()
    {
        $this->role = '';
        $this->loadWorkFlowsItemsRole();
    }

    public function loadWorkFlowsItemsRole()
    {
        $this->elementArray = [];
        $this->checkedArray = [];

        if ($this->role) {
            $model = $this->workFlows;
            $records = Workflow::where('model_type', $model)
                ->where('role', $this->role)
                ->where('team_id', $this->team)
                ->get();

            if ($model) {
                $elements = $model::where('team_id', $this->team)
                    ->orderBy('sorting', 'asc')
                    ->get();

                foreach ($elements as $element) {
                    $this->elementArray[] = [
                        'id' => $element->id,
                        'label' => $element->status,
                        'team_id' => $element->team->id,
                        'checked' => '',
                    ];
                    $existWorkFlows = $records->where('model_id', $element->id);

                    foreach ($existWorkFlows as $existWorkFlow) {
                        $this->checkedArray[$element->id][$existWorkFlow->to] = true;
                    }
                }
            }
        }
    }

    public function checkAllStatuses()
    {
        foreach ($this->elementArray as $valueX) {
            foreach ($this->elementArray as $valueY) {
                $this->checkedArray[$valueX['id']][$valueY['id']] = $this->checkAll;
            }
        }
    }

    public function submit()
    {
        $model = $this->workFlows;

        try {
            $teamId = collect($this->elementArray)->first()['team_id'];
            Workflow::where('model_type', $this->workFlows)
                ->where('role', $this->role)
                ->where('team_id', $teamId)
                ->delete();

            foreach ($this->checkedArray as $fromY => $toX) {
                $input = [
                    'team_id' => $teamId,
                    'model_type' => $this->workFlows,
                    'model_id' => $fromY,
                    'role' => $this->role,
                ];

                foreach ($toX as $toId => $to) {
                    if ($to) {
                        $input['to'] = $toId;
                        Workflow::create($input);
                    }
                }
            }

            Notification::make()
                ->title(__('automation.Updated with Success'))
                ->success()
                ->send();
        } catch (QueryException $th) {
            Notification::make()
                ->title(__('Error!').' '.$th->getMessage())
                ->danger()
                ->send();
        }
    }
}
