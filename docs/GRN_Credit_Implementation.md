# GRN Credit Implementation Documentation

## Overview
GRN Credit functionality allows users to create credit notes for goods received, enabling returns to suppliers after GRNs have been entered. This feature follows the same patterns as PO and GRN implementations with proper validation, status management, and UI features.

## Database Schema

### Tables Created

#### 1. `grn_credit_summaries` Table
```php
Schema::create('grn_credit_summaries', function (Blueprint $table) {
    $table->id();
    $table->date('grn_credit_date');
    $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
    $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
    $table->text('grn_credit_billing_address')->nullable();
    $table->text('grn_credit_delivery_address')->nullable();
    $table->foreignId('ap_account_id')->constrained('accounts')->onDelete('cascade');
    $table->string('grn_credit_status')->default('draft'); // draft, posted
    $table->decimal('total_amount', 15, 2)->default(0);
    $table->text('credit_reason')->nullable(); // Reason for returning goods
    $table->timestamps();
    $table->softDeletes();
});
```

#### 2. `grn_credit_details` Table
```php
Schema::create('grn_credit_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('grn_credit_summary_id')->constrained('grn_credit_summaries')->onDelete('cascade');
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
    $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
    $table->decimal('quantity', 15, 2);
    $table->decimal('cost', 15, 2);
    $table->decimal('total', 15, 2);
    $table->foreignId('grn_detail_id')->nullable()->constrained('grn_details')->onDelete('set null');
    $table->timestamps();
});
```

## Models

### 1. GrnCreditSummary Model
- **Location**: `app/Models/TenantModels/GrnCreditSummary.php`
- **Features**:
  - Soft deletes support
  - Relationships to supplier, location, account, and details
  - Date casting for grn_credit_date

### 2. GrnCreditDetail Model
- **Location**: `app/Models/TenantModels/GrnCreditDetail.php`
- **Features**:
  - Relationships to grn credit summary, product, location, and grn detail
  - Links back to original GRN detail for tracking

## Controllers

### GrnCreditController
- **Location**: `app/Http/Controllers/TenantControllers/GrnCreditController.php`
- **Key Methods**:
  - `index()` - List all GRN credits
  - `store()` - Create new GRN credit
  - `show()` - Display specific GRN credit
  - `update()` - Update existing GRN credit
  - `destroy()` - Delete GRN credit
  - `getGrnDetailsForCredit()` - Get available GRN details for credit creation

### Key Features:
1. **Supplier Validation**: Ensures all selected GRNs belong to the same supplier
2. **Quantity Validation**: Prevents over-crediting beyond available GRN quantities
3. **Status Management**: Updates GRN status based on credited amounts
4. **Transaction Safety**: Uses database transactions for data integrity

## Validation

### GrnCreditValidator
- **Location**: `app/Http/Requests/Tenant/GrnCreditValidator.php`
- **Validation Rules**:
  - Required fields: date, supplier_id, location_id, ap_account_id
  - Status validation: `draft` or `posted`
  - Details validation with quantity and cost constraints
  - Optional credit_reason field

## Frontend Implementation

### 1. GRN Credits Page
- **Location**: `resources/js/pages/tenant/transactions/GrnCredits.vue`
- **Features**:
  - List view with status badges
  - Create/Edit form with dynamic detail rows
  - Status filtering
  - Delete functionality with confirmation modal

### 2. GRN Selection Integration
- **Location**: `resources/js/pages/tenant/transactions/GoodsReceiveNotes.vue`
- **Features**:
  - Multiple GRN selection with checkboxes
  - Supplier validation (only same supplier GRNs can be selected)
  - "Create GRN Credit" button for selected GRNs
  - Visual indicators for selection state

### 3. Navigation
- **Location**: `resources/js/components/TenantAppSidebar.vue`
- **Added**: GRN Credits menu item under Transactions section

## Status Management

### GRN Status Logic
- **Open**: Default status for new GRNs
- **Paid**: When total credited amount >= GRN total amount

### GRN Credit Status
- **draft**: Initial status for new GRN credits
- **posted**: Final status after processing

## Key Features Implemented

### 1. Multiple GRN Selection
- Users can select multiple GRNs from the GRNs page
- Supplier validation ensures all selected GRNs belong to the same supplier
- Visual feedback for selection state

### 2. Quantity Validation
- Prevents over-crediting beyond available GRN quantities
- Shows available quantities for each GRN line
- Real-time validation during credit creation

### 3. Status Updates
- GRN status automatically updates when credits are created/deleted
- Uses direct database queries for accurate calculations
- Comprehensive logging for debugging

### 4. UI/UX Features
- Status badges with color coding
- Confirmation modals for deletions
- Dynamic form fields
- Responsive design

## API Endpoints

### GRN Credit Management
- `GET /api/grn-credits` - List all GRN credits
- `POST /api/grn-credits` - Create new GRN credit
- `GET /api/grn-credits/{id}` - Get specific GRN credit
- `PUT /api/grn-credits/{id}` - Update GRN credit
- `DELETE /api/grn-credits/{id}` - Delete GRN credit

### GRN Details for Credit
- `GET /api/grn-details-for-credit?grn_ids={ids}` - Get available GRN details for credit creation

## Issues Encountered and Resolutions

### 1. TypeScript Compilation Errors
- **Issue**: Vue files using TypeScript syntax without `lang="ts"` attribute
- **Resolution**: Added `lang="ts"` to script setup blocks in affected files

### 2. Validation Errors
- **Issue**: "total amount field is required" error during GRN credit creation
- **Resolution**: Changed validation rule from `required` to `nullable` for total_amount field

### 3. GRN Status Update Issues
- **Issue**: GRN status not updating to "Open" when GRN credits are deleted
- **Resolution**: 
  - Implemented direct database queries instead of Eloquent relationships
  - Added comprehensive logging for debugging
  - Improved transaction timing (status check after commit)

### 4. Duplicate Declarations
- **Issue**: Duplicate variable declarations in Vue components
- **Resolution**: Removed duplicate declarations of `canCreateGrnCredit` and `createGrnCreditFromSelectedGrns`

## Current Status

### ✅ Completed
- Database schema and migrations
- Models with relationships
- Controllers with full CRUD operations
- Validation rules
- Frontend pages and components
- Navigation integration
- Multiple GRN selection functionality
- Status management logic
- API endpoints

### 🔄 In Progress
- GRN status update issue when deleting GRN credits
- Debugging with comprehensive logging
- Testing and validation

### 📋 Next Steps
1. Test GRN credit deletion and status updates
2. Verify all validation scenarios
3. User acceptance testing
4. Performance optimization if needed

## Technical Notes

### Database Relationships
- `GrnCreditSummary` → `GrnCreditDetail` (one-to-many)
- `GrnDetail` → `GrnCreditDetail` (one-to-many)
- `GrnCreditDetail` → `GrnDetail` (many-to-one)

### Transaction Safety
- All GRN credit operations use database transactions
- Status updates happen after successful commits
- Rollback on errors ensures data consistency

### Validation Strategy
- Frontend validation for immediate feedback
- Backend validation for data integrity
- Quantity validation prevents over-crediting
- Supplier validation ensures data consistency

## File Structure
```
app/
├── Http/
│   ├── Controllers/TenantControllers/
│   │   └── GrnCreditController.php
│   └── Requests/Tenant/
│       └── GrnCreditValidator.php
├── Models/TenantModels/
│   ├── GrnCreditSummary.php
│   └── GrnCreditDetail.php
└── Validators/
    └── GrnCreditValidator.php

resources/js/
├── pages/tenant/transactions/
│   ├── GrnCredits.vue
│   └── GoodsReceiveNotes.vue
└── components/
    └── TenantAppSidebar.vue

database/migrations/tenant/
├── 2025_07_16_175749_create_grn_credit_summaries_table.php
└── 2025_07_16_175752_create_grn_credit_details_table.php
```

## Testing Checklist

- [ ] Create GRN credit from multiple GRNs
- [ ] Validate supplier consistency
- [ ] Test quantity validation
- [ ] Verify status updates on creation
- [ ] Test GRN credit deletion
- [ ] Verify GRN status updates on deletion
- [ ] Test form validation
- [ ] Verify UI responsiveness
- [ ] Test navigation integration
- [ ] Verify logging functionality 