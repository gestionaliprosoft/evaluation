<?php

namespace App\Traits;

use App\Libs\AutomationActionService;
use App\Models\Automation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

trait Automationable
{
    /**
     * Process automation
     */
    public function processAutomation(string $trigger): array
    {
        $model = self::class;

        // try to load automations if exists
        $automations = $this->getAutomation($model, $trigger);

        foreach ($automations as $automation) {
            if ($this->testConditions($automation)) {
                $this->executeActions($automation);
            }
        }

        return [];
    }

    /**
     * Get Model automation from DB
     */
    protected function getAutomation(string $model, string $trigger): Collection|array
    {
        $automation = Automation::where('target_model', $model)
            ->where('enabled', true)->where('trigger', $trigger)->get();

        return $automation ?? [];
    }

    /**
     * Test conditions
     */
    protected function testConditions(Automation $automation): bool
    {
        $test = true;

        foreach ($automation->automationConditions as $condition) {
            $test = match ($condition->condition) {
                'equal' => Str::of($this->{$condition->field})->exactly($condition->condition_text) ? true : false,
                'notEqual' => ! Str::of($this->{$condition->field})->exactly($condition->condition_text) ? true : false,
                'contain' => Str::of($this->{$condition->field})->contains($condition->condition_text) ? true : false,
                'notContain' => ! Str::of($this->{$condition->field})->contains($condition->condition_text) ? true : false,
            };
        }

        return $test;
    }

    /**
     * Execute actions
     *
     *
     * @return void
     */
    protected function executeActions(Automation $automation): bool
    {
        $automationActionService = new AutomationActionService;

        foreach ($automation->automationActions as $action) {
            $automationActionService->record = $this;
            $automationActionService->action = $action;
            $automationActionService->trigger = $automation->trigger;
            $automationActionService->{$action->action}();
        }

        return true;
    }
}
