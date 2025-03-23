<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\AccountType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AccountTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accountTypes = AccountType::all();
        return response()->json($accountTypes);
    }
} 