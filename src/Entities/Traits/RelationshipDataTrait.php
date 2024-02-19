<?php

namespace EMedia\QuickData\Entities\Traits;

trait RelationshipDataTrait
{

	/**
	 * @return array
	 * @deprecated 5.0.0 Will be removed as it doesn't belong in this trait
	 */
	public function getRules()
	{
		if (isset($this->rules)) return $this->rules;

		return [];
	}

	/**
	 *
	 * Keep track of Many to Many relations of this model
	 *
	 * @return array
	 * @deprecated 5.0.0 Will be removed as it's not used anymore
	 */
	public function getManyToManyRelations()
	{
		if (isset($this->manyToManyRelations)) return $this->manyToManyRelations;

		return [];
	}

	/**
	 * @return array
	 * @deprecated 5.0.0 Will be removed as it's not used anymore
	 */
	public function getHasManyRelations()
	{
		if (isset($this->hasManyRelations)) return $this->hasManyRelations;

		return [];
	}

	/**
	 * @return array
	 * @deprecated 5.0.0 Will be removed as it's not used anymore
	 */
	public function getFillablePivots()
	{
		if (isset($this->fillablePivots)) return $this->fillablePivots;

		return [];
	}

}