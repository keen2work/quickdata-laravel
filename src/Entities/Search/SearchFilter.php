<?php


namespace EMedia\QuickData\Entities\Search;

use EMedia\QuickData\Entities\Traits\HasMagicMethods;

class SearchFilter
{

	use HasMagicMethods;

	protected $searchQuery;

	public function __construct()
	{
		// defaults
		$this->attributes['perPage'] = config('oxygen.dashboard.perPage', 20);
		$this->attributes['shouldPaginate'] = true;
		$this->attributes['where'] = [];
        $this->attributes['whereWithOperator'] = [];

    }

	public function orderBy($fields = [])
	{
		$this->attributes['orderBy'] = $fields;

		return $this;
	}

	public function with($relationships = [])
	{
		$this->attributes['with'] = $relationships;

		return $this;
	}

	public function withCounts($relationships = [])
	{
		$this->attributes['withCounts'] = $relationships;

		return $this;
	}

    public function where($column, $operator, $value = null)
    {
        $args = func_get_args();
        if(!is_null($column)){
            if(func_num_args() == 2) {
                $this->attributes['where'][] = ["key"=>$args[0],"value" => $args[1],'operator'=>"="];
            } elseif(func_num_args() == 3) {
                $this->attributes['where'][] = ["key"=>$args[0],"value" => $args[2],'operator'=> $args[1]];
            } elseif (func_num_args() == 1) {
                throw New \Exception("Must have at least 2 parameters");
            }
        }

    }

	public function getWhere()
	{
		return $this->attributes['where'];
	}


    /**
	 *
	 * Get or set items per page (for pagination)
	 *
	 * @param null $perPage
	 *
	 * @return $this
	 */
	public function perPage($perPage = null)
	{
		if ($perPage === null) return $this->attributes['perPage'];

		$this->attributes['perPage'] = $perPage;

		return $this;
	}

	public function shouldPaginate($value = null)
	{
		if ($value === null) return $this->attributes['shouldPaginate'];

		$this->attributes['shouldPaginate'] = $value;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getSearchQuery()
	{
		return $this->searchQuery;
	}

	/**
	 * @param mixed $searchQuery
	 */
	public function setSearchQuery($searchQuery)
	{
		$this->searchQuery = $searchQuery;

		return $this;
	}

	public function getOrderBy()
	{
		$this->validateProperty('orderBy', []);

		return $this->attributes['orderBy'];
	}

	public function getWith()
	{
		$this->validateProperty('with', []);

		return $this->attributes['with'];
	}

	public function getWithCounts()
	{
		$this->validateProperty('withCounts', []);

		return $this->attributes['withCounts'];
	}


}