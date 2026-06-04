# Frontend API Integration Guide

## Purpose

This document explains the API changes for products and member information, including the routes to use, request payloads, and expected response variables.

## Key concept

- `product.user_id` is the owner account for a product.
- A product may belong to a member user account or be created by/administered through a special role (`admin`, `super_admin`, `treasurer`).
- When matching products to members, use the product owner user ID (`product.user_id`) or the loaded owner object (`product.user.id`), not the current authenticated user ID alone.

---

## Routes

### 1. Authenticated product list

- `GET /api/v1/products`
- Authenticated route under `apiResource('v1/products', ProductController::class)`

Query parameters:

- `member_id` (optional)
  - Use this when you have a member record ID and want the products owned by that member.
  - The backend resolves the member to its user account and filters by `user_id`.
  - Example: `?member_id=66`
- `user_id` (optional)
  - Only `admin`, `super_admin`, or `treasurer` can use this.
  - Use this to fetch products for a specific user account directly.
  - Example: `?user_id=68`
- `status` (optional)
  - `active` or `inactive`

Behavior:

- Regular members get only their own products automatically.
- Elevated users can fetch all products or filter by `user_id`.

### 2. Create product

- `POST /api/v1/products`
- Authenticated route

Body payload:

- `name` (required)
- `description` (optional)
- `photo` (optional image file)
- `status` (optional, default `active`)
- `member_id` (optional)
  - Use this when you want to assign the product to a member record.
  - The backend resolves the member to its user account and stores the product against that user.
  - Example: `member_id=66`
- `user_id` (optional)
  - Allowed only for `admin`, `super_admin`, or `treasurer`.
  - Use this to assign the product to a specific user account directly.
  - If not provided, the product is assigned to the logged-in user.

### 3. Get specific product

- `GET /api/v1/products/{product}`
- Authenticated route

Behavior:

- Returns the product owner data with `user` when loaded.
- If the product is `inactive`, only its owner can view it.

### 4. Update product

- `PUT /api/v1/products/{product}`
- Authenticated route

Body payload:

- `name` (optional)
- `description` (optional)
- `photo` (optional)
- `status` (optional)
- `member_id` (optional)
  - Allowed for elevated roles, or for the member's own account if they are updating their own product.
  - The backend resolves the member to a user account and updates the stored owner.
- `user_id` (optional)
  - Only `admin`, `super_admin`, or `treasurer` can reassign ownership directly.

### 5. Delete product

- `DELETE /api/v1/products/{product}`
- Authenticated route

Behavior:

- The product can be deleted by its owner or by elevated roles (`admin`, `super_admin`, `treasurer`).

---

## Member endpoints

These are the routes to fetch member information.

### 1. Current member profile

- `GET /api/v1/member/profile`
- Member-only route

Response contains:

- `member` object
- `membershipType` / `membership_type`
- `membership_status`
- `membership_end_date`
- `has_active_dues`
- `has_overdue_dues`

### 2. Member list

- `GET /api/v1/members`
- Available to authenticated users in the current API setup
- Use this if you need the full member list.

---

## Response fields for products

The `ProductResource` response includes:

- `id`
- `user_id`
- `name`
- `description`
- `photo_url`
- `status`
- `created_at`
- `user` (owner info, when loaded)
  - `id`
  - `first_name`
  - `last_name`
  - `email`

### Important

If your UI uses product ownership to match products with member data, compare with:

- `product.user_id`
- or `product.user.id`
- and match against the member's user account ID (`member.user.id` or `member.user_id`), not the member record ID alone.

---

## What changed for the front end

1. Use `GET /api/v1/products?user_id=66` when an admin/treasurer wants products for a specific member account.
2. Do not assume the currently authenticated user is the same as the product owner.
3. On create, pass `user_id` only if the current frontend user is an elevated user and wants to assign the product to another user.
4. Expect the product response to include an owner object under `user`.

---

## Example requests

### Fetch products for user 66 as admin/treasurer

```
GET /api/v1/products?user_id=66
Authorization: Bearer <token>
```

### Create product for user 66 as admin/treasurer

```
POST /api/v1/products
Authorization: Bearer <token>
Content-Type: multipart/form-data

name=Example Product
user_id=66
photo=@/path/to/image.jpg
```

### Fetch current member profile

```
GET /api/v1/member/profile
Authorization: Bearer <token>
```

---

## Notes

- Public product listing is separate: `GET /api/v1/products/active`.
- The internal authenticated product API is `GET /api/v1/products`.
- If the frontend needs to display a member's products, request by the member's user ID and not by the member record ID.
