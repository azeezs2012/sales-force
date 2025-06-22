<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\Account;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\AccountValidator;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accounts = Account::with(['accountType', 'parentAccount', 'childAccounts', 'creator', 'updater'])->get();
        return response()->json($accounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = AccountValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent_id && !$this->hasValidParentHierarchy($request->parent_id)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $account = new Account($request->all());
        $account->created_by = auth()->id();
        $account->created_at = now();
        $account->save();

        return response()->json($account, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $account = Account::with(['accountType', 'parentAccount', 'childAccounts', 'creator', 'updater'])->findOrFail($id);
        return response()->json($account);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $validator = AccountValidator::validate($request->all(), $account->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent_id && !$this->hasValidParentHierarchy($request->parent_id)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $account->fill($request->all());
        $account->updated_by = auth()->id();
        $account->updated_at = now();
        $account->save();

        return response()->json($account);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $account = Account::findOrFail($id);

        // Check if the account has child accounts
        $hasChildren = Account::where('parent_id', $id)->exists();
        if ($hasChildren) {
            throw new \Exception('Cannot delete account with child accounts.');
        }

        $account->deleted_by = auth()->id();
        $account->deleted_at = now();
        $account->save();
        $account->delete();

        return response()->json(null, 204);
    }

    /**
     * Check if the account has a valid parent hierarchy.
     *
     * @param  int  $accountId
     * @return bool
     */
    private function hasValidParentHierarchy($accountId)
    {
        $depth = 0;
        $currentAccount = Account::find($accountId);

        while ($currentAccount && $currentAccount->parent_id && $depth < 5) {
            $currentAccount = Account::find($currentAccount->parent_id);
            $depth++;
        }

        return $depth < 5;
    }
} 