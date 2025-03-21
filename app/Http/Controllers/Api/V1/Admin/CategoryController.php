<?php

namespace App\Http\Controllers\api\v1\admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;


class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/categories",
     *     summary="List all categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all categories",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="PC Games"),
     *                     @OA\Property(property="slug", type="string", example="pc-games"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to view categories")
     *         )
     *     )
     * )
     */
    public function index(){
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json([
                'message' => 'You do not have permission to view categories'
            ], 403);
        }
        $categories = Category::all();
        return response()->json([
            'message' => 'Success',
            'data' => $categories
        ], 200);
    }
    /**
     * @OA\Post(
     *     path="/api/v1/admin/categories",
     *     summary="Create a new category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Console Games"),
     *                 @OA\Property(property="slug", type="string", example="console-games")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Console Games"),
     *                 @OA\Property(property="slug", type="string", example="console-games"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to create categories")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required.")),
     *                 @OA\Property(property="slug", type="array", @OA\Items(type="string", example="The slug field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request){
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json([
                'message' => 'You do not have permission to create categories'
            ], 403);
        }
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'slug' => 'required|string|max:255|unique:categories,slug',
        ]);
        $category = Category::create($data);
        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }
    /**
     * @OA\Put(
     *     path="/api/v1/admin/categories/{category}",
     *     summary="Update an existing category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Updated Category Name"),
     *                 @OA\Property(property="slug", type="string", example="updated-category-slug")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated Category Name"),
     *                 @OA\Property(property="slug", type="string", example="updated-category-slug"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to update categories")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found"),
     *             @OA\Property(property="status", type="string", example="error 404")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required.")),
     *                 @OA\Property(property="slug", type="array", @OA\Items(type="string", example="The slug field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json([
                'message' => 'You do not have permission to update categories'
            ], 403);
        }

        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
                'status' => 'error 404'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
            'slug' => 'required|string|max:255|unique:categories,slug,'.$category->id,
        ]);

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category
        ], 200);
    }
    /**
     * @OA\Delete(
     *     path="/api/v1/admin/categories/{category}",
     *     summary="Delete a category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete categories")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found"),
     *             @OA\Property(property="status", type="string", example="error 404")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete category with products",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete category with associated products"),
     *             @OA\Property(property="status", type="string", example="error 400")
     *         )
     *     )
     * )
     */

    public function destroy($id)
    {
        if (!auth()->user()->can('view_categories')) {
            return response()->json([
                'message' => 'You do not have permission to delete categories'
            ], 403);
        }

        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
                'status' => 'error 404'
            ], 404);
        }


        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with associated products',
                'status' => 'error 400'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ], 200);
    }
}
