# EmaAccessBundle

A powerful Symfony bundle for implementing attribute-based access control in your applications. This bundle provides an elegant way to secure your controllers and methods using PHP attributes while managing roles through a centralized store.

## Features

- **Attribute-based Security**: Secure controllers and methods using PHP attributes like `#[Access]`, `#[AccessGroup]`, and `#[AccessPreset]`
- **Automatic Role Registration**: Roles are automatically registered during compilation based on your attributes
- **Flexible Permission System**: Supports role hierarchies with super roles and fine-grained access control
- **Database Persistence**: Roles stored in database with migration command for synchronization
- **Form Integration**: Built-in form type for managing access roles with groups and presets
- **Interactive UI**: Frontend components with JavaScript for easy role management
- **Expression Language Support**: Advanced subject evaluation using Symfony's expression language
- **Performance Optimized**: Includes caching layer to improve performance in high-traffic applications

## Installation

Install the bundle using Composer:

```bash
composer require emaemasc/access-bundle
```

Register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    Ema\AccessBundle\EmaAccessBundle::class => ['all' => true],
];
```

## Basic Usage

### Securing Controllers

Use the `#[Access]` attribute to protect your controllers and methods:

```php
use Ema\AccessBundle\Attribute\Access;
use Ema\AccessBundle\Attribute\AccessGroup;
use Ema\AccessBundle\Attribute\AccessPreset;

#[Access(title: "Manage users", message: "You need user management permissions")]
#[AccessGroup(name: "users")]
class UserController 
{
    #[Access(title: "List users")]
    public function list(): Response 
    {
        // This method requires specific access role
    }
    
    #[Access(title: "Edit user", subject: "user")]
    public function edit(User $user): Response 
    {
        // This method checks access to specific user object
    }
    
    #[AccessPreset("admin")] // Assign this role to admin preset
    public function delete(User $user): Response 
    {
        // Method with preset role
    }
}
```

### Using Expression Language

For more complex subject evaluation:

```php
#[Access(
    title: "Edit article",
    subject: new Expression("args['article'].owner == request.attributes.get('_user')"),
    message: "You can only edit your own articles"
)]
public function edit(Article $article): Response
{
    // Controller logic here
}
```

### Custom Access Role Store

Create a custom role store by implementing the `AccessRoleStore` interface:

```php
use Ema\AccessBundle\Attribute\AsAccessRoleStore;
use Ema\AccessBundle\Role\AbstractAccessRoleStore;

#[AsAccessRoleStore]
class CustomAccessRoleStore extends AbstractAccessRoleStore
{
    public function __construct()
    {
        $this->setEntityClass(CustomAccessRole::class);
        $this->setSuperRoles(['ROLE_SUPER_ADMIN']);
        
        // Define additional roles programmatically
        $this->addRole('CUSTOM_ROLE', 'Custom Role Title', ['option' => 'value'], 'custom_group', ['preset1']);
    }
}
```

### Group and Preset Configuration

Define access groups and presets by implementing the respective interfaces:

```php
use Ema\AccessBundle\Attribute\AsAccessGroupConfig;
use Ema\AccessBundle\Group\AbstractAccessGroupConfig;

#[AsAccessGroupConfig]
class MyAccessGroupConfig extends AbstractAccessGroupConfig
{
    public function __construct()
    {
        $this->set('users', AccessGroupDto::new('users', 'User Management', 10));
        $this->set('content', AccessGroupDto::new('content', 'Content Management', 20));
    }
}
```

```php
use Ema\AccessBundle\Attribute\AsAccessPresetConfig;
use Ema\AccessBundle\Preset\AbstractAccessPresetConfig;

#[AsAccessPresetConfig]
class MyAccessPresetConfig extends AbstractAccessPresetConfig
{
    public function __construct()
    {
        $this->set('admin', AccessPresetDto::new('admin', 'Administrator', 10));
        $this->set('editor', AccessPresetDto::new('editor', 'Editor', 20));
    }
}
```

## Performance Optimization with Caching

The bundle includes a caching layer to improve performance in high-traffic applications. Caching is implemented using a decorator pattern that wraps the role store with caching functionality when Symfony's Cache component is available.

By default, caching is automatically enabled when a PSR-6 compatible cache pool is available in the service container. The cache stores roles and super roles with a default TTL of 1 hour.

To customize caching behavior, you can:

```php
use Ema\AccessBundle\Role\CachedAccessRoleStore;
use Symfony\Component\Cache\Adapter\RedisAdapter;

// Get the cached role store instance
$cachedRoleStore = $container->get(AccessRoleStore::class); // This will be the CachedAccessRoleStore

if ($cachedRoleStore instanceof CachedAccessRoleStore) {
    $cachedRoleStore->setCachePrefix('my_custom_prefix.')
                   ->setDefaultTtl(7200); // 2 hours
}
```

To manually clear the cache when needed:

```php
$roleStore->clearCache();
```

The caching decorator ensures that the core role store implementation remains clean and focused on its primary responsibility while providing transparent caching capabilities.

## Form Integration

Use the provided form type to manage access roles in your administration panel:

```php
use Ema\AccessBundle\Form\AccessType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserRoleManagementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('accessRoles', AccessType::class, [
                'toggle_attributes' => [
                    'data-custom-attr' => 'value'
                ]
            ]);
    }
}
```

## Database Migration

After defining your roles, run the migration command to synchronize them with the database:

```bash
bin/console emaemasc:access:migrate
```

This command will:
- Create any new roles that don't exist in the database
- Remove roles that are no longer defined in your code

## Configuration

The bundle automatically configures itself, but you can customize it further by creating configuration classes as shown above. The bundle also automatically registers a Twig form theme for the access form type.

## Frontend Components

The bundle includes CSS and JavaScript for an interactive access management interface:

- Group headers to organize related permissions
- Preset buttons to quickly select common role combinations
- Visual feedback showing which presets match currently selected roles

---

**Note**: This bundle requires PHP 8.1+ and is compatible with Symfony 5.4+. For more information about implementation details, please refer to the source code and tests.