# EmaAccessBundle

A powerful Symfony bundle for implementing attribute-based access control in your applications. This bundle provides an elegant way to secure your controllers and methods using PHP attributes while managing roles through a centralized store.

## Features

- **Attribute-based Security**: Secure controllers and methods using PHP attributes like `#[Access]`, `#[AccessGroup]`, and `#[AccessPreset]`
- **Automatic Role Registration**: Roles are automatically registered during compilation based on your attributes
- **Flexible Permission System**: Supports role hierarchies with advanced role inheritance and fine-grained access control
- **Database Persistence**: Roles stored in database with migration command for synchronization
- **Form Integration**: Built-in form type for managing access roles with groups and presets
- **Interactive UI**: Frontend components with JavaScript for easy role management
- **Expression Language Support**: Advanced subject evaluation using Symfony's expression language

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

If you extend the role entity with additional Doctrine fields or unmapped runtime props, pass them through `props`. The bundle will write mapped values into the entity with Symfony's `PropertyAccessor` and keep the rest as role metadata:

```php
#[Access(title: "Manage users", props: ['scope' => 'admin'])]
class UserController
{
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

Create a custom role store by extending the `AbstractAccessRoleStore`. The bundle uses the configured store directly, so custom roles should be defined in the `configure()` method rather than the constructor. `configure()` is called automatically during container compilation:

```php
use Ema\AccessBundle\Attribute\AsAccessRoleStore;
use Ema\AccessBundle\Role\AbstractAccessRoleStore;

#[AsAccessRoleStore]
class CustomAccessRoleStore extends AbstractAccessRoleStore
{    
    public function configure(): void
    {
        // Define additional roles
        $this->addRole('CUSTOM_ROLE', 'Custom Role Title', ['scope' => 'admin'], 'custom_group', ['preset1']);
    }
    
    public function getEntityClass(): string
    {
        return CustomAccessRole::class;
    }
    
    public function getRoleHierarchy(): array
    {
        return [
            'ROLE_SUPER_ADMIN' => '/PREFIX_.*/',  // Regex to match all PREFIX roles
        ];
    }
}
```

### Role Hierarchy Configuration

The bundle supports flexible role hierarchies using either explicit role lists or regular expressions:

```php
public function getRoleHierarchy(): array
{
    return [
        'ROLE_SUPER_ADMIN' => '/PREFIX_.*/',  // Regex pattern matching
        'ROLE_ADMIN' => ['PREFIX_MANAGE_USERS', 'PREFIX_MANAGE_CONTENT'],  // Explicit roles
    ];
}
```

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
                'role_filter' => static fn ($role) => $role->getGroup() === 'users',
                'toggle_attributes' => [
                    'data-custom-attr' => 'value'
                ]
            ]);
    }
}
```

Available form options:

- `role_filter`: a callable or an array of property criteria. For arrays, the keys are property paths on the role DTO, for example `['group' => 'users', 'title' => 'List users']`, `['props[scope]' => 'admin']`, or `['presets' => ['admin']]`.
- `show_presets`: set to `false` to hide preset buttons in the UI.
- `toggle_attributes`: additional HTML attributes passed to the toggle controls.

## Database Migration

After defining your roles, run the migration command to synchronize them with the database:

```bash
bin/console emaemasc:access:sync
```

This command will:
- Create any new roles that don't exist in the database
- Remove roles that are no longer defined in your code
- Update existing roles so their titles and `props` match your role store configuration

## Configuration

The bundle automatically configures itself, but you can customize it further by creating configuration classes as shown above. The bundle also automatically registers a Twig form theme for the access form type and resolves the active role store directly, without an additional cache wrapper.

## Frontend Components

The bundle includes CSS and JavaScript for an interactive access management interface:

- Group headers to organize related permissions
- Preset buttons to quickly select common role combinations
- Visual feedback showing which presets match currently selected roles

---

**Note**: This bundle requires PHP 8.1+ and is compatible with Symfony 5.4+. For more information about implementation details, please refer to the source code and tests.
