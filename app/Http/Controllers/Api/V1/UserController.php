<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/users",
     *      operationId="getUsersList",
     *      tags={"Users"},
     *      summary="Get list of users",
     *      @OA\Response(response=200, description="Successful operation"),
     * )
     */
    public function index()
    {
        return response()->json(User::all(), 200);
    }

    /**
     * @OA\Get(
     *      path="/api/users/{id}",
     *      operationId="getUserById",
     *      tags={"Users"},
     *      summary="Get user information",
     *      @OA\Parameter(
     *          name="id",
     *          description="User ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="User not found"),
     * )
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * @OA\Put(
     *      path="/api/users/{id}",
     *      operationId="updateUser",
     *      tags={"Users"},
     *      summary="Update user information",
     *      @OA\Parameter(
     *          name="id",
     *          description="User ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","email"},
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", format="email", example="john@gmail.com"),
     *              @OA\Property(property="phone", type="string", example="+1234567890"),
     *              @OA\Property(property="bio", type="string", example="This is my bio"),
     *              @OA\Property(property="address", type="string", example="123 Main St, City, Country"),
     *              @OA\Property(property="profile_picture", type="string", example="http://example.com/profile.jpg"),
     *          )
     *      ),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="User not found"),
     * )
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','email','max:255',Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string',
            'address' => 'nullable|string',
            'profile_picture' => 'nullable|string',
        ]);

        $user->update($data);
        return response()->json($user);
    }

    /**
     * @OA\Delete(
     *      path="/api/users/{id}",
     *      operationId="deleteUser",
     *      tags={"Users"},
     *      summary="Delete a user",
     *      @OA\Parameter(
     *          name="id",
     *          description="User ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="User not found"),
     * )
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
