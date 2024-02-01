UPGRADE from 5.x to 6.0
-----------------------

### Minimal PHP version is bumped to 8.3

[BC Break] Removed `Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Annotation`. Now only the attribute is used

Added symfony 7 support

UPGRADE from 3.x to 4.0
-----------------------

### Minimal PHP version is bumped to 7.4

Type hints were added in all places wherever possible.

### Removed property Entity::$commentProperty

Annotation property `Gtt\Bundle\DoctrineAuditableBundle\Mapping\Annotation\Entity::$commentProperty` is removed. Use `Gtt\Bundle\DoctrineAuditableBundle\Log\Store` service to audit entity changes. See usage section in [readme](/README.md) for details

### Method "AuditableListener::setStore" is removed

The `Gtt\Bundle\DoctrineAuditableBundle\Event\AuditableListener::setStore` has been removed. If you subclassed `AuditableListener` class, use constructor injection instead.

### Class AuditableListener is renamed to AuditableListener

The `Gtt\Bundle\DoctrineAuditableBundle\Event\AuditableListener` class is renamed to `Gtt\Bundle\DoctrineAuditableBundle\Event\AuditableListener`

### All DateTime objects are now immutable

Property type `Gtt\Bundle\DoctrineAuditableBundle\Entity\GroupSuperClass::$createdTs` is changed from `DateTime` to `DateTimeImmutable`

UPGRADE from 2.x to 3.0
-----------------------

### Namespace changed

For upgrading to version 3.0 you should replace all bundle namespaces:

Old:
```
Gtt\Bundle\DoctrineAdapterBundle\
```

New:
```
Gtt\Bundle\DoctrineAuditableBundle\
```
