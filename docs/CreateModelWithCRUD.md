# Creating a New Model with CRUD Functionality

This guide provides a step-by-step process to create a new model with CRUD (Create, Read, Update, Delete) functionality in a Laravel application.

## Steps

### 1. Define the Model
- Create a new model file in the `app/Models/TenantModels` directory.
- Use the `HasFactory` and `SoftDeletes` traits if needed.
- Define the `$fillable` array with the attributes that are mass assignable.
- Define relationships with other models if necessary, including parent-child relationships if applicable.

### 2. Create the Migration
- Create a new migration file in the `database/migrations/tenant` directory.
- Define the table structure using the `Schema::create` method, including a `parent` column if needed.
- Include necessary fields and foreign key constraints.

### 3. Create the Controller
- Create a new controller in the `app/Http/Controllers/TenantControllers` directory.
- Implement CRUD methods (`index`, `store`, `show`, `update`, `destroy`).
- Use a validator to ensure data integrity.
- Handle parent-child relationships, ensuring valid hierarchy if applicable.

### 4. Create the Validator
- Create a new validator class in the `app/Validators` directory.
- Define validation rules for the model's attributes.
- Use the `Validator::make` method to validate data.

### 5. Define API Routes
- Add API routes in the `routes/tenant.php` file.
- Define routes for each CRUD operation, pointing to the corresponding controller methods.

### 6. Create the Vue Component
- Create a new Vue component in the `resources/js/pages/tenant/lists` directory.
- Set up the template to display and manage the model's data, including a dropdown for selecting a parent if applicable, and display child items in a tree view structure.
- Implement methods to handle data fetching, creation, updating, and deletion, including calculating indentation for tree view.
- Use toast notifications for success and error messages.

### 7. Add Sidebar Menu Item
- Add a menu item in the `TenantAppSidebar.vue` file for navigation.
- Ensure it links to the new Vue component.

### 8. Define Web Routes
- Add a web route in the `routes/tenant.php` file to render the Vue component using Inertia.

### 9. Define Web Routes for Vue Components
- Add a web route in the `routes/tenant.php` file to render the Vue component using Inertia.
- Ensure the route is within the `auth` and `verified` middleware group to restrict access to authenticated users.

## Example Prompt

"Create a new model called `