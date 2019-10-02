## logger

Store (*noun*) component is a alternative solution for storing changelog 
comment value against retrieving it from virtual property of handled entity.

### Problem

That entity virtual property from above is pure application layer violation,
because it's forces your application domain entity know about this bundle.

### Usage example

```php
<?php

use Doctrine\ORM\EntityManagerInterface;
use Gtt\Bundle\DoctrineAuditableBundle as Auditable;

class PayloadService {
    /**
     * @var Auditable\Log\Store
     */
    private $auditable;
    
    /**
     * @var EntityManagerInterface;
     */
    private $entityManager;

    /**
     * Operate!
     */
    public function payloadMethod(YourDomain\Entity $entity): void 
    {
        // 1. change some property that supposed to be logged to changelog
        $entity->updateProperty();  // ... just dummy example
        
        // 2. describe this change
        $this->auditable->describe($entity, 'Change description');
      
        // 3. perform update 
        $this->entityManager->flush();
    }
}
```
