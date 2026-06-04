<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Member;
use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Public listing → only active products
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Product::with('user');

        $hasElevatedAccess = $user->hasAnyRole(['super_admin', 'admin', 'treasurer']);

        if ($request->filled('member_id')) {
            $member = Member::find($request->member_id);
            if (!$member) {
                return response()->json(['message' => 'Member not found.'], 404);
            }

            if (!$hasElevatedAccess && $member->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized to view products for this member.'], 403);
            }

            $query->where('user_id', $member->user_id);
        } elseif ($request->filled('user_id') && $hasElevatedAccess) {
            $query->where('user_id', $request->user_id);
        } elseif (!$hasElevatedAccess) {
            // Regular member → only their own products
            $query->where('user_id', $user->id);
        }

        // Optional status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(10);

        return ProductResource::collection($products);
    }


    /**
     * Owner listing → include inactive
     */
    public function myProducts(Request $request)
    {
        $products = $request->user()
            ->products()
            ->with('user')
            ->latest()
            ->paginate(10);

        return ProductResource::collection($products);
    }

    /**
     * Store product
     */
    public function store(ProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')
                ->store('products', 's3');
        }

        $data['status'] = $data['status'] ?? 'active';

        if ($request->filled('member_id')) {
            $member = Member::find($request->member_id);
            if (!$member) {
                return response()->json(['message' => 'Member not found.'], 404);
            }
            if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'treasurer']) && $member->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized to assign a product to another member.'
                ], 403);
            }
            $data['user_id'] = $member->user_id;
        } elseif ($request->filled('user_id')) {
            if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
                return response()->json([
                    'message' => 'Unauthorized to assign a product to another user.'
                ], 403);
            }
            $data['user_id'] = $request->user_id;
        } else {
            $data['user_id'] = auth()->id();
        }

        $product = Product::create($data);

        return new ProductResource($product->load('user'));
    }

    /**
     * Show product
     */
    public function show(Product $product)
    {
        // If inactive, only owner can see
        if (
            $product->status === 'inactive' &&
            $product->user_id !== auth()->id()
        ) {
            return response()->json([
                'message' => 'Not found.'
            ], 404);
        }

        return new ProductResource($product->load('user'));
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product)
    {
        $hasElevatedAccess = auth()->user()->hasAnyRole(['super_admin', 'admin', 'treasurer']);

        if ($product->user_id !== auth()->id() && !$hasElevatedAccess) {
            return response()->json([
                'message' => 'Unauthorized. You do not own this product.'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status' => 'sometimes|in:active,inactive',
            'user_id' => 'sometimes|integer|exists:users,id',
        ]);

        if ($request->filled('member_id')) {
            $member = Member::find($request->member_id);
            if (!$member) {
                return response()->json(['message' => 'Member not found.'], 404);
            }
            if (!$hasElevatedAccess && $member->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized to reassign product ownership.'
                ], 403);
            }
            $data['user_id'] = $member->user_id;
        }

        if ($request->filled('user_id')) {
            if (!$hasElevatedAccess) {
                return response()->json([
                    'message' => 'Unauthorized to reassign product ownership.'
                ], 403);
            }
            $data['user_id'] = $request->user_id;
        }

        if ($request->hasFile('photo')) {
            // Delete the old photo from S3 if it exists
            if ($product->photo_path) {
                Storage::disk('s3')->delete($product->photo_path);
            }

            // Store the new photo in S3
            $data['photo_path'] = $request->file('photo')->store('products', 's3');
        }

        $product->update($data);

        return new ProductResource($product->fresh());
    }

    /**
     * Delete product
     */
    public function destroy(Product $product)
    {
        if ($product->user_id !== auth()->id() && !auth()->user()->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json([
                'message' => 'Unauthorized. You do not own this product.'
            ], 403);
        }

        if ($product->photo_path) {
            Storage::disk('s3')->delete($product->photo_path);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.'
        ], 200);
    }
}
