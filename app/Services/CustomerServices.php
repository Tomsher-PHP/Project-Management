<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerServices
{
    public function createUser(array $data)
    {
        return DB::transaction(function () use ($data) {


            $customer = [];
            return $customer;
        });
    }

    public function updateUser(Customer $customer, array $data)
    {
        return DB::transaction(function () use ($customer, $data) {

            $customer = [];
            return $customer;
        });
    }
}
