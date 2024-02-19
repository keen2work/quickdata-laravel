<?php

namespace EMedia\QuickData\Entities\Search;

trait SearchableTrait
{

	public function searchable()
	{
		if (isset($this->searchable)) return $this->searchable;

		return [];
	}

	public function scopeSearch($query, $searchQuery)
	{
		$query->where(function ($query) use ($searchQuery) {
			foreach ($this->searchable() as $searchField) {
				$query->orWhere($searchField, 'LIKE', '%' . $searchQuery . '%');
			}
		});
	}

}