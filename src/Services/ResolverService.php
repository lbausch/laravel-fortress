<?php

namespace Bausch\LaravelFortress\Services;

class ResolverService
{
    /**
     * Grants.
     *
     * @var \Illuminate\Support\Collection
     */
    private $grants;

    /**
     * Query parameters.
     *
     * @var array
     */
    private $queryParameters = [];

    /**
     * FortressResolverService constructor.
     *
     * @param \Illuminate\Support\Collection|null $grants
     */
    public function __construct($grants = null)
    {
        if (is_null($grants)) {
            $this->grants = collect();
        } else {
            $this->grants = $grants;
        }
    }

    /**
     * Set Grants.
     *
     * @param \Illuminate\Support\Collection $grants
     *
     * @return $this
     */
    public function grants($grants)
    {
        $this->grants = $grants;

        return $this;
    }

    /**
     * Filter model type.
     *
     * @param string $type
     *
     * @return $this;
     */
    public function filterModelType($type)
    {
        $this->grants = $this->grants->filter(function ($grant) use ($type) {
            return $grant->model_type == $type;
        });

        return $this;
    }

    /**
     * Filter resource type.
     *
     * @param string $type
     *
     * @return $this;
     */
    public function filterResourceType($type)
    {
        $this->grants = $this->grants->filter(function ($grant) use ($type) {
            return $grant->resource_type == $type;
        });

        return $this;
    }

    /**
     * With relationshis.
     *
     * @param mixed $relationships
     *
     * @return $this
     */
    public function with($relationships)
    {
        $this->queryParameters['with'] = $relationships;

        return $this;
    }

    /**
     * Order by.
     *
     * @param string $column
     * @param string $direction
     *
     * @return $this
     */
    public function orderby($column, $direction = 'asc')
    {
        $this->queryParameters['order_by_column'] = $column;
        $this->queryParameters['order_by_direction'] = $direction;

        return $this;
    }

    /**
     * Get Models.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getModels()
    {
        $types = [];

        foreach ($this->grants as $grant) {
            if (!isset($types[$grant->model_type])) {
                $types[$grant->model_type] = [];
            }

            $types[$grant->model_type][] = $grant->model_id;
        }

        return $this->fetch($types);
    }

    /**
     * Get resources.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getResources()
    {
        $types = [];

        foreach ($this->grants as $grant) {
            if (!isset($types[$grant->resource_type])) {
                $types[$grant->resource_type] = [];
            }

            $types[$grant->resource_type][] = $grant->resource_id;
        }

        return $this->fetch($types);
    }

    /**
     * Fetch.
     *
     * @param array $types
     *
     * @return \Illuminate\Support\Collection
     */
    private function fetch(array $types)
    {
        $result = collect();

        foreach ($types as $type => $ids) {
            $tmp = app($type)->whereIn('id', $ids);

            if (isset($this->queryParameters['with'])) {
                $tmp->with($this->queryParameters['with']);
            }

            if (isset($this->queryParameters['order_by_column'])) {
                $tmp->orderBy($this->queryParameters['order_by_column'], $this->queryParameters['order_by_direction']);
            }

            $tmp = $tmp->get();

            $result = $result->merge($tmp);
        }

        return $result;
    }
}
