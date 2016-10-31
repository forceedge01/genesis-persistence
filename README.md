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

```php

namespace myApp;

use Genesis\Services\Persistence;

class App
{
    public function display()
    {
        $params = [
            'databaseEngine' => 'sqlite',
            'path' => BASE . '/db.sqlite'
        ];

        $mapperService = new Persistence\MapperService(Persistence\DatabaseService($params));

        // If say a form is submitted.
        if ($form->isSubmitted()) {
            // Get a specific record from the database mapped to your model object.
            $mySpecificItemModel = $mapperService->getSingle(MyItemModel::class, ['id' => $form->get('item_id')]);

            // Update model with desired data. Note the setters/getters are provided out of the box by just
            // extending the baseModel.
            $mySpecificItemModel
                ->setName('whatever you want.')
                ->setDescription('A great description');

            // Update the record in the database.
            $mapperService->persist($mySpecificItemModel);
        }

        // Get all MyItemModels back.
        $myItemModels = $mapperService->get(MyItemModel::class);

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
    public function getSomeBasedForPastSevenDays(Persistence\Contracts\MapperService $mapperService)
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

The mapper allows you to delete records in two ways. Consider the example below.

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

        // This will delete the record from the database as long as the object can give back the id of the
        // record using the getId() getter.
        $mapperService->delete($product);
    }
}

```

Feel free to explore other calls provided by the mapperService. The mapper also allows you to create tables based on a model.