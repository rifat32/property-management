<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

            "logo"=>"nullable|string",
            "invoice_title"=>"required|string",
            "invoice_summary"=>"nullable|string",

            "invoice_reference" => "required|string",
            "business_name"=>"required|string",
            "business_address"=>"required|string",

           "send_reminder"=>"nullable|boolean",

            "invoice_date"=>"required|date",
            "footer_text"=>"nullable|string",
            "shareable_link" =>"nullable|string",

            "property_id"=>"nullable|numeric|exists:properties,id",



            "discount_description"=>"nullable|string",
            "discound_type"=>"nullable|string|in:fixed,percentage",
            "discount_amount"=>"nullable|numeric",
            "due_date"=>"nullable|date",
            "status"=>"required|string|in:draft,unsent",

            "reminder_dates" => "nullable|array",
            "reminder_dates.*" => "nullable|string",




            "business_type" => "required|string|in:other,property_dealer",
            "client_id" => [
                "nullable",
                "numeric",
                "exists:tenants,id",
                function ($attribute, $value, $fail) {
                    $landlordId = request()->input('landlord_id');
                    $tenantId = request()->input('tenant_id');

                    $business_type = request()->input('business_type');
                    if($business_type == "property_dealer") {
                        if (!empty($value)) {
                            $fail('for business type property dealer you can not select client.');
                        }

                    } else {
                        if (empty($value)) {
                            $fail('for business type other you must select a client.');
                        }

                    }

                },
            ],
            "tenant_id" => [
                "nullable",
                "numeric",
                "exists:tenants,id",
                function ($attribute, $value, $fail) {
                    $client_id = request()->input('client_id');
                    $landlordId = request()->input('landlord_id');
                    $business_type = request()->input('business_type');
                    if($business_type == "property_dealer") {
                        if (empty($value) && empty($landlordId)) {
                            $fail('Either tenant_id or landlord_id is required.');
                        }
                        if (!empty($value) && !empty($landlordId)) {
                            $fail('Only one of tenant_id or landlord_id should have a value.');
                        }
                    }
                    else {
                        if (!empty($value)) {
                            $fail('for business type other you can not select a tenant.');
                        }
                    }

                },
            ],
            "landlord_id" => [
                "nullable",
                "numeric",
                "exists:landlords,id",
                function ($attribute, $value, $fail) {
                    $client_id = request()->input('client_id');
                    $tenantId = request()->input('tenant_id');
                    $business_type = request()->input('business_type');
                    if($business_type == "property_dealer") {

                        if (empty($value) && empty($tenantId)) {
                            $fail('Either tenant_id or landlord_id is required.');
                        }
                        if (!empty($value) && !empty($tenantId)) {
                            $fail('Only one of tenant_id or landlord_id should have a value.');
                        }
                    }
                    else {
                        if (!empty($value)) {
                            $fail('for business type other you can not select a landlord.');
                        }
                    }


                },
            ],


            "invoice_items" => "required|array",
            "invoice_items.*.name" => "required|string",
            "invoice_items.*.description" => "nullable|string",
            "invoice_items.*.quantity" => "required|numeric",
            "invoice_items.*.price" => "required|numeric",
            "invoice_items.*.tax" => "required|numeric",
            "invoice_items.*.amount" => "required|numeric",
            "invoice_items.*.repair_id" => "nullable|numeric",


            "sub_total"=>"required|numeric",
            "total_amount"=>"required|numeric",

            "invoice_payments"=>"nullable|array",
            "invoice_payments.*.amount"=>"required|numeric",
            "invoice_payments.*.payment_method"=>"required|string",
            "invoice_payments.*.payment_date"=>"required|date",

            "note" => "nullable|string",
        ];
    }
}
