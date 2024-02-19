<?php


namespace EMedia\QuickData;


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class DatabaseBlueprintServiceProvider extends ServiceProvider
{

	public function register()
	{
		// when adding new fields, make them compatible with the Graph API's field names
		// https://developers.facebook.com/docs/graph-api/reference/location/

		Blueprint::macro('location', function () {
			// location
			/** @var Blueprint $this */
			$this->string('name')->nullable();
			$this->string('long_name')->nullable();
			$this->string('venue')->nullable();
			$this->string('address')->nullable();
			$this->string('formatted_address')->nullable();
			$this->string('street')->nullable();
			$this->string('street_2')->nullable();
			$this->string('city')->nullable();
			$this->string('state')->nullable();
			$this->string('state_iso_code')->nullable();
			$this->string('zip')->nullable();
			$this->string('country')->nullable();
			$this->string('country_iso_code')->nullable()->index();
			$this->float('latitude', 10, 6)->nullable()->index();
			$this->float('longitude', 10, 6)->nullable()->index();
			$this->string('phone')->nullable();
			$this->string('phone_iso')->nullable();
			$this->string('email')->nullable();
			$this->string('website')->nullable();
			$this->string('location_type')->nullable();
		});

		Blueprint::macro('dropLocation', function () {
			/** @var Blueprint $this */
			$this->dropColumn('name');
			$this->dropColumn('long_name');
			$this->dropColumn('venue');
			$this->dropColumn('address');
			$this->dropColumn('formatted_address');
			$this->dropColumn('street');
			$this->dropColumn('street_2');
			$this->dropColumn('city');
			$this->dropColumn('state_iso_code');
			$this->dropColumn('state');
			$this->dropColumn('zip');
			$this->dropColumn('country');
			$this->dropColumn('country_iso_code');
			$this->dropColumn('latitude');
			$this->dropColumn('longitude');
			$this->dropColumn('phone');
			$this->dropColumn('phone_iso');
			$this->dropColumn('email');
			$this->dropColumn('website');
			$this->dropColumn('location_type');
		});
	}

}
