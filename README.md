# QuickData for Laravel

Basic functions for easier CRUD management with entities, controllers and repositories for Laravel.

##### Version Compatibility

| Laravel Version | QuickData Version        |
| --------------- |:------------------------:|
| 7               | 4.0.x                    |
| 6               | 3.0.x                    |
| 5.7             | 2.0.x                    |


## GeoLocations

When you need to save an address, use this feature.

```
// creating Database columns
Schema::table('venues', function (Blueprint $table) {
    $table->location();
});
```

```
// removing Database columns
Schema::table('venues', function (Blueprint $table) {
    $table->dropLocation();
});
```

To search for a geo-location, add `FiltersByLatLngTrait` to your file. To filter locations by distance, call the following.

```
$this->filterByLatLng(request(), $eloquentQuery, $defaultDistance, $unit);
```

Unit can be 'km' or 'miles'. Request must have `latitude` and `longitude` parameters.

## Search

```
use SearchableTrait;
```
Use this trait to make models searchable by given fields.

#### Search Filters
Where Clause

Where clause is capable of creating eloquent query by passing operator as the 2nd parameter. As following code,
```
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
```
Here the column is table column, operator can be one of `=`, `<`, `>`, `<=`, `>=`, `IN`, `NOT IN` and the value can be an array for operators `IN` and `NOT IN`.

### Controllers
`use HasJsonCRUD;`
JSON response methods for CRUD, filter, and auto-relationship sync.

### Repositories
`DataRepositoryInterface`
The interface to be implemented by data repositories.

`BaseRepository`
Default implementation of `DataRepositoryInterface`. Extend your repositories from this abstract class or create your own.

### Models
```
use RelationshipDataTrait;
```
Models must use this trait for the controller to auto-sync related models.
