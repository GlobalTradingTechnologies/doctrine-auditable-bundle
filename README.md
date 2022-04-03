Doctrine Auditable Bundle helps tracking changes and history of objects
===========================================

Auditable behavioral implementation helps tracking changes and history of objects.
**Fast** and **lightweight** alternative for DoctrineExtensions Loggable with some features.
Supports only ORM.

Features
===
- Group of changes
- Comments for changes
- Convenient store (tracked values before and after change stored in the separated columns, instead serialized entity data in the Loggable)
- Supports custom DBAL types
- Supports class inheritance configuration 

Installation
===
1. Install bundle
```
composer require "gtt/doctrine-auditable-bundle"
```

2. Add to Kernel.php
```php
public function registerBundles()
{
    $bundles = array(
        ...
        new Gtt\Bundle\DoctrineAuditableBundle\DoctrineAuditableBundle(),
    );
    ...
}
```
3. Create tables for changes storing
```
bin/console doctrine:schema:update --force
```
4. Configure mapping if needed.

Usage
=====

Add attributes for tracking property
```php
<?php

use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Annotation as Auditable;

/**
 * My entity
 */
 #[ORM\Entity]
 #[ORM\Table(name: 'entity')]
 #[Auditable\Entity]
class Entity
{
     #[ORM\Column(name: 'assigned_user', type: 'string', length: 255)]
     #[Auditable\Property]
    protected string $assignedUser;
    
    ...
}
```

Then somewhere in a service change an entity property and flush the changes.
```php
<?php

use Doctrine\ORM\EntityManagerInterface;
use Gtt\Bundle\DoctrineAuditableBundle as Auditable;

class PayloadService {
    private Auditable\Log\Store $auditable;
    
    private EntityManagerInterface $entityManager;

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
