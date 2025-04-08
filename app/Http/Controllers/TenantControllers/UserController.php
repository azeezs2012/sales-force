<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Validators\UserValidator;

class UserController extends Controller
{
    // Display a listing of the resource.
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        $validatedData = UserValidator::validate($request->all());

        $data = $request->all();
        $data['role'] = 'default';
        $data['is_admin'] = false;

        $user = User::create($data);

        return response()->json($user, 201);
    }

    // Display the specified resource.
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // Update the specified resource in storage.
    public function update(Request $request, $id)
    {
        $firstUser = User::orderBy('created_at', 'asc')->first();
        if ($firstUser && $firstUser->id == $id) {
            throw new \Exception('The first created user cannot be updated.');
        }

        $user = User::findOrFail($id);

        $validatedData = UserValidator::validate($request->all(), $id);

        $user->update($request->all());

        return response()->json($user);
    }

    // Remove the specified resource from storage.
    public function destroy($id)
    {
        $firstUser = User::orderBy('created_at', 'asc')->first();
        if ($firstUser && $firstUser->id == $id) {
            throw new \Exception('The first created user cannot be deleted.');
        }

        $user = User::findOrFail($id);

        if ($user->is_admin) {
            throw new \Exception('The first created user cannot be deleted.');
        }

        if (in_array($user->role, ['customer', 'sales_rep','supplier'])) {
            throw new \Exception('The user is associated with a '.$user->role.' and cannot be deleted.');
        }

        $user->delete();

        return response()->json(null, 204);
    }
} 