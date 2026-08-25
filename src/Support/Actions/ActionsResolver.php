<?php

namespace PowerComponents\Turbine\Support\Actions;

use Closure;
use PowerComponents\Turbine\Button;
use PowerComponents\Turbine\Components\Rules\BaseRule;
use PowerComponents\Turbine\Contracts\Context;
use PowerComponents\Turbine\Response\ActionDescriptor;

class ActionsResolver
{
    public function __construct(private Context $context) {}

    /** @return list<ActionDescriptor> */
    public function forRow(object $row): array
    {
        if (! method_exists($this->context, 'actions')) {
            return [];
        }

        /** @var list<Button> $buttons */
        $buttons = (array) $this->context->actions($row);

        /** @var list<BaseRule> $rules */
        $rules = method_exists($this->context, 'actionRules')
            ? array_filter((array) $this->context->actionRules($row), fn ($rule) => $rule instanceof BaseRule)
            : [];

        $descriptors = [];

        foreach ($buttons as $button) {
            if (! $button instanceof Button) {
                continue;
            }

            $descriptors[] = $this->createDescriptor(
                $this->describe($button, $row, $rules),
            );
        }

        return $descriptors;
    }

    public function isRowSelectable(mixed $row, string $forAction = 'checkbox'): bool
    {
        $rules = (array) data_get($row, '__turbine_rules', []);
        $rule = collect($rules)
            ->where('apply', true)
            ->where('forAction', $forAction)
            ->last();

        if ($rule === null) {
            return true;
        }

        return ! ((bool) data_get($rule, 'hide') || (bool) data_get($rule, 'disable'));
    }

    protected function createDescriptor(DescriptorData $desc): ActionDescriptor
    {
        return new ActionDescriptor(
            id: $desc->id,
            label: $desc->label,
            icon: $desc->icon,
            tag: $desc->tag,
            visible: $desc->visible,
            disabled: $desc->disabled,
            attributes: $desc->attributes,
        );
    }

    /** @param  list<BaseRule>  $rules */
    protected function describe(Button $button, object $row, array $rules): DescriptorData
    {
        $can = $button->can;
        $visible = $can instanceof Closure ? (bool) $can($row) : (bool) $can;

        $attributes = $button->attributes;
        $label = $button->slot;

        foreach ($rules as $rule) {
            if ($rule->forAction !== $button->action || ! $this->conditionPasses($rule, $row)) {
                continue;
            }

            if (data_get($rule->rule, 'hide')) {
                $visible = false;
            }

            $slot = data_get($rule->rule, 'slot');

            if (is_string($slot)) {
                $label = $slot;
            }

            foreach ((array) data_get($rule->rule, 'setAttribute', []) as $set) {
                $attribute = data_get($set, 'attribute');

                if (is_string($attribute)) {
                    $attributes[$attribute] = data_get($set, 'value');
                }
            }
        }

        $attributes = $this->publicAttributes($attributes);

        if ($button->eventMeta !== []) {
            $attributes['event'] = $button->eventMeta;
        }

        return new DescriptorData(
            id: $button->action,
            label: $label,
            icon: $button->icon ?: null,
            tag: $button->tag,
            visible: $visible,
            disabled: isset($attributes['disabled']),
            attributes: $attributes,
        );
    }

    private function conditionPasses(BaseRule $rule, object $row): bool
    {
        $when = data_get($rule->rule, 'when');

        if ($when instanceof Closure) {
            return (bool) $when($row);
        }

        $unless = data_get($rule->rule, 'unless');

        if ($unless instanceof Closure) {
            return ! (bool) $unless($row);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function publicAttributes(array $attributes): array
    {
        $stripped = [];

        foreach ($attributes as $key => $value) {
            $key = (string) $key;

            if (in_array($key, ['href', 'target', 'disabled'], true) || str_starts_with($key, 'wire:')) {
                continue;
            }

            $stripped[$key] = $value;
        }

        return $stripped;
    }
}
