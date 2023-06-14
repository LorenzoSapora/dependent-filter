<?php
declare(strict_types=1);

namespace AwesomeNova\Filters;

use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Support\Str;
use Laravel\Nova\Filters\Filter;

class DependentFilter extends Filter
{
    protected $optionsCallback;

    protected $applyCallback;

    public $dependentOf = [];

    public $default = '';

    public $attribute;

    public $hideWhenEmpty = false;

    public $component = 'awesome-nova-dependent-filter';

    public function __construct($name = null, $attribute = null)
    {
        $this->name = $name ?? $this->name;
        $this->attribute = $attribute ?? $this->attribute ?? str_replace(' ', '_', Str::lower($this->name()));
    }

    public function apply(NovaRequest $request, $query, $value)
    {
        if ($this->applyCallback) {
            return call_user_func($this->applyCallback, $request, $query, $value);
        }

        return $query->whereIn($this->attribute, (array)$value);
    }

    public function key()
    {
        return $this->attribute;
    }

    public function options(NovaRequest $request, array $filters = [])
    {
        return call_user_func($this->optionsCallback, $request, $filters);
    }

    final public function dependentOf($filter)
    {
        if (! is_array($filter)) {
            $filter = func_get_args();
        }

        $this->dependentOf = $filter;

        return $this;
    }

    final public function getOptions(NovaRequest $request, array $filters = [])
    {
        return collect(
            $this->options($request, $filters + array_fill_keys($this->dependentOf, ''))
        )->map(function ($value, $key) {
            return is_array($value) ? ($value + ['value' => $key]) : ['label' => $value, 'value' => $key];
        })->values()->all();
    }

    final public function withOptions($callback, $dependentOf = null)
    {
        if (! is_callable($callback)) {
            $callback = function () use ($callback) {
                return $callback;
            };
        }

        $this->optionsCallback = $callback;

        if (! is_null($dependentOf)) {
            $this->dependentOf($dependentOf);
        }

        return $this;
    }

    final public function withDefault($value)
    {
        $this->default = $value;

        return $this;
    }

    public function default()
    {
        return $this->default;
    }

    final public function withApply(callable $callback)
    {
        $this->applyCallback = $callback;
        return $this;
    }

    public function hideWhenEmpty($value = true)
    {
        $this->hideWhenEmpty = $value;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return array_merge([
            'class' => $this->key(),
            'name' => $this->name(),
            'component' => $this->component(),
            'options' => count($this->dependentOf) === 0 ? $this->getOptions(app(NovaRequest::class)) : [],
            'currentValue' => $this->default() ?? '',
            'dependentOf' => $this->dependentOf,
            'hideWhenEmpty' => $this->hideWhenEmpty,
        ], $this->meta());
    }

    public static function make(...$args)
    {
        return new static(...$args);
    }
}
