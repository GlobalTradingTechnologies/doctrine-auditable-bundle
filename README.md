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

2. Add to AppKernel.php
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
app/console doctrine:schema:update --force
```
4. Configure mapping if needed.

Usage
===
Add annotation for tracking property
```php
<?php

use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Annotation as Auditable;

/**
 * My entity
 *
 * @ORM\Entity
 * @ORM\Table(name="entity")
 *
 * @Auditable\Entity
 */
class Entity
{
    /**
     * Name
     *
     * @var string
     *
     * @ORM\Column(type="string", name="name", length=255)
     *
     * @Auditable\Property
     */
    protected $assignedUser;
    
    ...
}
```

If you need comment changes then add property for comment and add annotation attribute to the entity
```php
/**
 * My entity
 *
 * @ORM\Entity
 * @ORM\Table(name="entity")
 *
 * @Auditable\Entity(commentProperty="comment")
 */
class Entity
{
    ...
    private $comment;
    ...
    
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }
}
```

Set you comment
```php
...
$entity->setName('Any name');
$entity->setComment('Set any name to the entity');
$entityManager->flush();
...
```
