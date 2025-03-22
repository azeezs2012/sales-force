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
        $validatedData = UserValidator::validate($request);

        $user = User::create($validatedData);

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
        $user = User::findOrFail($id);

        $validatedData = UserValidator::validate($request, $id);

        $user->update($validatedData);

        return response()->json($user);
    }

    // Remove the specified resource from storage.
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(null, 204);
    }
} 