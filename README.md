Introduction
============

This package allows for super fast model implementation that can port over the database model straight into your database manager.

Usage
=====

To use the mapperService, please create a model (you can call it whatever you want) that extends the BaseModel. Your model does not need to implement any getters and setters, these will be provided out of the box by the BaseModel. You will have to declare all your model properties as protected.

Creating a model
-----------------

```php

namespace myApp;

use Genesis\Services\Persistence\Model\BaseModel;

class MyItemModel extends BaseModel
{
    protected $name = 'text not null unique';

    protected $description = 'text default null';

    protected $userId = 'integer not null';
}

```

In the above example your model defines how the database is setup and will be communicated with. Each property will define what type and constraints it should have. Please note that the id property is inherited by the `BaseModel` and will be included in all tables as a must.

Using a model with the mapper
-----------------------------

The mapper provides a simple yet powerful layer of abstraction for you. It understands how your model needs to be saved and retrieved from the database.

Instantiation
-------------

```php

use Genesis\Services\Persistence;

// Configuration for the databaseService.
$params = ['dbEngine' => 'sqlite', 'dbPath' => BASE . '/db.sqlite'];

// Create a database service.
$databaseService = Persistence\DatabaseService($params);

// Create a mapper service which depends on the databaseService.
$mapperService = new Persistence\MapperService($databaseService);

```

Saving and retrieving data.
---------------------------

```php

namespace myApp;

class App
{
    /**
     * Inserting into the database.
     */
    public function insert()
    {
        $mapperService = ...

        // If say a form is submitted.
        if ($form->isSubmitted()) {
            // Create a new model object for insertion.
            $mySpecificItemModel = new MyItemModel();
            $mySpecificItemModel
                ->setName('whatever you want.')
                ->setDescription('A great description');

            // Insert new record in the database.
            $mapperService->persist($mySpecificItemModel);

            if ($mySpecificItemModel->getId()) {
                $this->message('Record saved successfully.');
            } else {
                $this->message('Failed saving record.');
            }
        }

        // Get all MyItemModels back.
        $myItemModels = $mapperService->get(MyItemModel::class);

        // Use the retrieved models somehow.
        ...
    }

    /**
     * Updating the database.
     */
    public function update()
    {
        $mapperService = ...

        // If say a form is submitted.
        if ($form->isSubmitted()) {
            // Get a specific record from the database mapped to your model object.
            $mySpecificItemModel = $mapperService->getSingle(MyItemModel::class, ['id' => $form->get('item_id')]);

            // Update model with desired data. Note the setters/getters are provided out of the box by just
            // extending the baseModel which are based on the properties your model has.
            $mySpecificItemModel
                ->setName('whatever you want.')
                ->setDescription('A great description');

            // Update the record in the database.
            $mapperService->persist($mySpecificItemModel);
        }

        // Get all MyItemModels back with the name `howdy`, order them by the id descending.
        $myItemModels = $mapperService->get(MyItemModel::class, ['name' => 'howdy'], ['id' => 'desc']);

        // Use the retrieved models somehow.
        ...
    }
}

```

The mapper exposes the databaseService which allows you to perform more complex queries while still binding objects back to the original models. Consider the following example

```php

namespace myApp;

use Genesis\Services\Persistence;

class SomeRepository
{
    public function getSomeItemsForPastSevenDays(Persistence\Contracts\MapperService $mapperService)
    {
        // Get the database table name based on the model.
        $table = $mapperService->getTableFromClass(Representations\Sale::class);

        // Prepare a more complex query, we can only bind objects back if we have all columns corresponding to the model.
        $query = "SELECT * FROM `{$table}` WHERE `dateSold` > (SELECT DATETIME('now', '-7 day')) AND `userId` = {$userId}";

        // Execute the query using the databaseService layer and get the data back.
        $data = $mapperService->getDatabaseService()->execute($query);

        // Bind the data to the model and return the collection.
        return $mapperService->bindToModel(Representations\Sale::class, $data);
    }
}

```

Deleting records
----------------

The mapper allows you to delete records in two ways. Consider the examples below.

```php

namespace myApp;

use Genesis\Services\Persistence;

class App
{
    public function deleteExampleOne()
    {
        $mapperService = ...;

        // If say a delete request comes in.
        if ($this->issetGet('delete')) {
            // Get the product Id from the request.
            $productId = $this->getParam('productId');

            // Delete the record.
            $mapperService->delete(Product::class, ['id' => $productId]);
        }

        ...
    }

    // If say the product object was passed into the method.
    public function deleteExampleTwo(Product $product)
    {
        $mapperService = ...;

        // This will delete the record from the database as long as the getId() method on the object returns
        // the id of the record.
        $mapperService->delete($product);
    }
}

```

Feel free to explore other calls provided by the mapperService. The mapper also allows you to create tables based on a model.