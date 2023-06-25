<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\InvoiceCreateRequest;
use App\Http\Requests\InvoiceUpdateRequest;

use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    use ErrorUtil, UserActivityUtil;

  /**
    *
 * @OA\Post(
 *      path="/v1.0/invoice-image",
 *      operationId="createInvoiceImage",
 *      tags={"property_management.invoice_management"},
 *       security={
 *           {"bearerAuth": {}}
 *       },
 *      summary="This method is to store invoice logo",
 *      description="This method is to store invoice logo",
 *
*  @OA\RequestBody(
    *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"image"},
*         @OA\Property(
*             description="image to upload",
*             property="image",
*             type="file",
*             collectionFormat="multi",
*         )
*     )
* )



 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *       @OA\JsonContent(),
 *       ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 * @OA\JsonContent(),
 *      ),
 *        @OA\Response(
 *          response=422,
 *          description="Unprocesseble Content",
 *    @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *   @OA\JsonContent()
 * ),
 *  * @OA\Response(
 *      response=400,
 *      description="Bad Request",
 *   *@OA\JsonContent()
 *   ),
 * @OA\Response(
 *      response=404,
 *      description="not found",
 *   *@OA\JsonContent()
 *   )
 *      )
 *     )
 */

public function createInvoiceImage(ImageUploadRequest $request)
{
    try{
        $this->storeActivity($request,"");

        $insertableData = $request->validated();

        $location =  config("setup-config.invoice_image");

        $new_file_name = time() . '_' . $insertableData["image"]->getClientOriginalName();

        $insertableData["image"]->move(public_path($location), $new_file_name);


        return response()->json(["image" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


    } catch(Exception $e){

        return $this->sendError($e,500,$request);
    }
}
/**
 *
 * @OA\Post(
 *      path="/v1.0/invoices",
 *      operationId="createInvoice",
 *      tags={"property_management.invoice_management"},
 *       security={
 *           {"bearerAuth": {}}
 *       },
 *      summary="This method is to store invoice",
 *      description="This method is to store invoice",
 *
 *  @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *            required={"name","description","logo"},
 *  *             @OA\Property(property="logo", type="string", format="string",example="image.jpg"),
  *             @OA\Property(property="invoice_title", type="string", format="string",example="invoice_title"),
 *            @OA\Property(property="invoice_summary", type="string", format="string",example="invoice_summary"),
 *            @OA\Property(property="business_name", type="string", format="string",example="business_name"),
 *  * *  @OA\Property(property="business_address", type="string", format="string",example="business_address"),
 *  * *  @OA\Property(property="total_amount", type="number", format="number",example="900"),
 *  * *  @OA\Property(property="invoice_date", type="string", format="string",example="12/12/2012"),
 *  *  * *  @OA\Property(property="invoice_number", type="string", format="string",example="57856465"),
 *
 *  * *  @OA\Property(property="footer_text", type="string", format="string",example="footer_text"),
 *  *  * *  @OA\Property(property="landlord_id", type="number", format="number",example="1"),
 *  * *  @OA\Property(property="property_id", type="number", format="number",example="1"),
 *  * *  @OA\Property(property="tenant_id", type="number", format="number",example="1"),
 *     *  * *  @OA\Property(property="number", type="number", format="string",example="1"),
 *     *  * *  @OA\Property(property="invoice_items", type="string", format="array",example={
 *{"name":"name","description":"description","quantity":"1","price":"1.1","tax":"20","amount":"300"},
  *{"name":"name","description":"description","quantity":"1","price":"1.1","tax":"20","amount":"300"}
 *
 * }),
 *
 *  *     *  * *  @OA\Property(property="invoice_payments", type="string", format="array",example={
 *{"amount":"10","payment_method":"payment_method","payment_date":"12/12/2012"},
 *{"amount":"10","payment_method":"payment_method","payment_date":"12/12/2012"}
 *
 * }),
 *
 *
 *         ),
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *       @OA\JsonContent(),
 *       ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 * @OA\JsonContent(),
 *      ),
 *        @OA\Response(
 *          response=422,
 *          description="Unprocesseble Content",
 *    @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *   @OA\JsonContent()
 * ),
 *  * @OA\Response(
 *      response=400,
 *      description="Bad Request",
 *   *@OA\JsonContent()
 *   ),
 * @OA\Response(
 *      response=404,
 *      description="not found",
 *   *@OA\JsonContent()
 *   )
 *      )
 *     )
 */

public function createInvoice(InvoiceCreateRequest $request)
{
    try {
        $this->storeActivity($request,"");
        return DB::transaction(function () use ($request) {


            $insertableData = $request->validated();
            $insertableData["created_by"] = $request->user()->id;
            $invoice =  Invoice::create($insertableData);
            if(!$invoice) {
                throw new Exception("something went wrong");
            }

            $invoiceItems = collect($insertableData["invoice_items"])->map(function ($item) {
                return [
                    "name" => $item["name"],
                    "description" => $item["description"],
                    "quantity" => $item["quantity"],
                    "price" => $item["price"],
                    "tax" => $item["tax"],
                    "amount" => $item["amount"],
                ];
            });

            $invoice->invoice_items()->createMany($invoiceItems->all());


            $invoicePayments = collect($insertableData["invoice_payments"])->map(function ($item) {
                return [
                    "amount" => $item["amount"],
                    "payment_method" => $item["payment_method"],
                    "payment_date" => $item["payment_date"],
                ];
            });
            $sum_payment_amounts = $invoicePayments->sum('amount');

            if($sum_payment_amounts > $invoice->total_amount) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["invoice_payments"=>["payment is more than total amount"]]
             ];
                throw new Exception(json_encode($error),422);
            }



            $invoice->invoice_payments()->createMany($invoicePayments->all());

            if($sum_payment_amounts == $invoice->total_amount) {
                $invoice->payment_status = "paid";
                $invoice->save();
             }
             else {
                $invoice->payment_status = "due";
                $invoice->save();
             }


            $invoice->load(["invoice_items","invoice_payments"]);
            return response($invoice, 201);





        });




    } catch (Exception $e) {

        return $this->sendError($e, 500,$request);
    }
}

/**
 *
 * @OA\Put(
 *      path="/v1.0/invoices",
 *      operationId="updateInvoice",
 *      tags={"property_management.invoice_management"},
 *       security={
 *           {"bearerAuth": {}}
 *       },
 *      summary="This method is to update invoice",
 *      description="This method is to update invoice",
 *
 *  @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *            required={"id","name","description","logo"},
 *     *             @OA\Property(property="id", type="number", format="number",example="1"),
  *  *             @OA\Property(property="logo", type="string", format="string",example="image.jpg"),
  *             @OA\Property(property="invoice_title", type="string", format="string",example="invoice_title"),
 *            @OA\Property(property="invoice_summary", type="string", format="string",example="invoice_summary"),
 *            @OA\Property(property="business_name", type="string", format="string",example="business_name"),
 *  * *  @OA\Property(property="business_address", type="string", format="string",example="business_address"),
 *  * *  @OA\Property(property="total_amount", type="number", format="number",example="900"),
 *  * *  @OA\Property(property="invoice_date", type="string", format="string",example="12/12/2012"),
 *  *  *  * *  @OA\Property(property="invoice_number", type="string", format="string",example="57856465"),
 *  * *  @OA\Property(property="footer_text", type="string", format="string",example="footer_text"),
 *  * *  @OA\Property(property="property_id", type="number", format="number",example="1"),
 *  *  *  * *  @OA\Property(property="landlord_id", type="number", format="number",example="1"),
 *  * *  @OA\Property(property="tenant_id", type="number", format="number",example="1"),
 *     *  * *  @OA\Property(property="number", type="number", format="string",example="1"),
 *     *  * *  @OA\Property(property="invoice_items", type="string", format="array",example={
 *{"id":"1","name":"name","description":"description","quantity":"1","price":"1.1","tax":"20","amount":"300"},
  *{"id":"","name":"name","description":"description","quantity":"1","price":"1.1","tax":"20","amount":"300"}
 *
 * }),
 *
 *
 *  *  *     *  * *  @OA\Property(property="invoice_payments", type="string", format="array",example={
 *{"id":"1","amount":"10","payment_method":"payment_method","payment_date":"12/12/2012"},
 *{"id":"","amount":"10","payment_method":"payment_method","payment_date":"12/12/2012"}
 *
 * }),
 *
 *         ),
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *       @OA\JsonContent(),
 *       ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 * @OA\JsonContent(),
 *      ),
 *        @OA\Response(
 *          response=422,
 *          description="Unprocesseble Content",
 *    @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *   @OA\JsonContent()
 * ),
 *  * @OA\Response(
 *      response=400,
 *      description="Bad Request",
 *   *@OA\JsonContent()
 *   ),
 * @OA\Response(
 *      response=404,
 *      description="not found",
 *   *@OA\JsonContent()
 *   )
 *      )
 *     )
 */

public function updateInvoice(InvoiceUpdateRequest $request)
{
    try {
        $this->storeActivity($request,"");
        return  DB::transaction(function () use ($request) {

            $updatableData = $request->validated();



            $invoice  =  tap(Invoice::where(["id" => $updatableData["id"]]))->update(
                collect($updatableData)->only([
                    "logo",
                    "invoice_title",
                    "invoice_summary",
                    "business_name",
                    "business_address",
                    "total_amount",
                    "invoice_date",
                    "footer_text",
                    "property_id",
                    "landlord_id",
                    "tenant_id",
                ])->toArray()
            )
                ->first();

                if(!$invoice) {
                    throw new Exception("something went wrong");
                }
                $invoiceItemsData = collect($updatableData["invoice_items"])->map(function ($item)use ($invoice) {
                    return [
                        "id" => $item["id"],
                        "name" => $item["name"],
                        "description" => $item["description"],
                        "quantity" => $item["quantity"],
                        "price" => $item["price"],
                        "tax" => $item["tax"],
                        "amount" => $item["amount"],
                        "invoice_id" => $invoice->id
                    ];
                });


                $invoice->invoice_items()->upsert($invoiceItemsData->all(), ['id',"invoice_id"], ['name', 'description', 'quantity', 'price', 'tax', 'amount',"invoice_id"]);


                $invoicePayments = collect($updatableData["invoice_payments"])->map(function ($item)use ($invoice) {
                    return [
                        "id" => $item["id"],
                        "amount" => $item["amount"],
                        "payment_method" => $item["payment_method"],
                        "payment_date" => $item["payment_date"],
                        "invoice_id" => $invoice->id
                    ];
                });
                $sum_payment_amounts = $invoicePayments->sum('amount');

                if($sum_payment_amounts > $invoice->total_amount) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["invoice_payments"=>["payment is more than total amount"]]
                 ];
                    throw new Exception(json_encode($error),422);
                }


                $invoice->invoice_items()->upsert($invoicePayments->all(), ['id',"invoice_id"], ['amount', 'payment_method', 'payment_date', 'invoice_id']);


                if($sum_payment_amounts == $invoice->total_amount) {
                   $invoice->payment_status = "paid";
                   $invoice->save();
                }
                else {
                    $invoice->payment_status = "due";
                    $invoice->save();
                 }




                $invoice->load(["invoice_items","invoice_payments"]);

            return response($invoice, 200);
        });
    } catch (Exception $e) {
        error_log($e->getMessage());
        return $this->sendError($e, 500,$request);
    }
}
/**
 *
 * @OA\Get(
 *      path="/v1.0/invoices/{perPage}",
 *      operationId="getInvoices",
 *      tags={"property_management.invoice_management"},
 *       security={
 *           {"bearerAuth": {}}
 *       },

 *              @OA\Parameter(
 *         name="perPage",
 *         in="path",
 *         description="perPage",
 *         required=true,
 *  example="6"
 *      ),
 *      * *  @OA\Parameter(
* name="start_date",
* in="query",
* description="start_date",
* required=true,
* example="2019-06-29"
* ),
 * *  @OA\Parameter(
* name="end_date",
* in="query",
* description="end_date",
* required=true,
* example="2019-06-29"
* ),
 * *  @OA\Parameter(
* name="search_key",
* in="query",
* description="search_key",
* required=true,
* example="search_key"
* ),
 *      summary="This method is to get invoices ",
 *      description="This method is to get invoices",
 *

 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *       @OA\JsonContent(),
 *       ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 * @OA\JsonContent(),
 *      ),
 *        @OA\Response(
 *          response=422,
 *          description="Unprocesseble Content",
 *    @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *   @OA\JsonContent()
 * ),
 *  * @OA\Response(
 *      response=400,
 *      description="Bad Request",
 *   *@OA\JsonContent()
 *   ),
 * @OA\Response(
 *      response=404,
 *      description="not found",
 *   *@OA\JsonContent()
 *   )
 *      )
 *     )
 */

public function getInvoices($perPage, Request $request)
{
    try {
        $this->storeActivity($request,"");

        // $automobilesQuery = AutomobileMake::with("makes");

        $invoiceQuery = new Invoice();

        if (!empty($request->search_key)) {
            $invoiceQuery = $invoiceQuery->where(function ($query) use ($request) {
                $term = $request->search_key;
                $query->where("name", "like", "%" . $term . "%");
            });
        }

        if (!empty($request->start_date)) {
            $invoiceQuery = $invoiceQuery->where('created_at', ">=", $request->start_date);
        }

        if (!empty($request->end_date)) {
            $invoiceQuery = $invoiceQuery->where('created_at', "<=", $request->end_date);
        }

        $invoices = $invoiceQuery->orderByDesc("id")->paginate($perPage);

        return response()->json($invoices, 200);
    } catch (Exception $e) {

        return $this->sendError($e, 500,$request);
    }
}



/**
 *
 * @OA\Get(
 *      path="/v1.0/invoices/get/single/{id}",
 *      operationId="getInvoiceById",
 *      tags={"property_management.invoice_management"},
 *       security={
 *           {"bearerAuth": {}}
 *       },

 *              @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="id",
 *         required=true,
 *  example="1"
 *      ),

 *      summary="This method is to get invoice by id",
 *      description="This method is to get invoice by id",
 *

 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *       @OA\JsonContent(),
 *       ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 * @OA\JsonContent(),
 *      ),
 *        @OA\Response(
 *          response=422,
 *          description="Unprocesseble Content",
 *    @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *   @OA\JsonContent()
 * ),
 *  * @OA\Response(
 *      response=400,
 *      description="Bad Request",
 *   *@OA\JsonContent()
 *   ),
 * @OA\Response(
 *      response=404,
 *      description="not found",
 *   *@OA\JsonContent()
 *   )
 *      )
 *     )
 */

public function getInvoiceById($id, Request $request)
{
    try {
        $this->storeActivity($request,"");


        $invoice = Invoice::with("invoice_items","invoice_payments")
        ->where([
            "id" => $id
        ])
        ->first();

        if(!$invoice) {
     return response()->json([
"message" => "no invoice found"
],404);
        }


        return response()->json($invoice, 200);
    } catch (Exception $e) {

        return $this->sendError($e, 500,$request);
    }
}










/**
 *
 *     @OA\Delete(
 *      path="/v1.0/invoices/{id}",
 *      operationId="deleteInvoiceById",
 *      tags={"property_management.invoice_management"},
 *       security={
 *           {"bearerAuth": {}}
 *       },
 *              @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="id",
 *         required=true,
 *  example="1"
 *      ),
 *      summary="This method is to delete invoice by id",
 *      description="This method is to delete invoice by id",
 *

 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *       @OA\JsonContent(),
 *       ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 * @OA\JsonContent(),
 *      ),
 *        @OA\Response(
 *          response=422,
 *          description="Unprocesseble Content",
 *    @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *   @OA\JsonContent()
 * ),
 *  * @OA\Response(
 *      response=400,
 *      description="Bad Request",
 *   *@OA\JsonContent()
 *   ),
 * @OA\Response(
 *      response=404,
 *      description="not found",
 *   *@OA\JsonContent()
 *   )
 *      )
 *     )
 */

public function deleteInvoiceById($id, Request $request)
{

    try {
        $this->storeActivity($request,"");



        $invoice = Invoice::where([
            "id" => $id
        ])
        ->first();

        if(!$invoice) {
     return response()->json([
"message" => "no invoice found"
],404);
        }
        $invoice->delete();

        return response()->json(["ok" => true], 200);
    } catch (Exception $e) {

        return $this->sendError($e, 500,$request);
    }
}

/**
 *
 *     @OA\Delete(
 *      path="/v1.0/invoice-items/{invoice_id}/{id}",
 *      operationId="deleteInvoiceItemById",
 *      tags={"property_management.invoice_management"},
 *       security={
 *           {"bearerAuth": {}}
 *       },
 *  *              @OA\Parameter(
 *         name="invoice_id",
 *         in="path",
 *         description="invoice_id",
 *         required=true,
 *  example="1"
 *      ),
 *              @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="id",
 *         required=true,
 *  example="1"
 *      ),
 *      summary="This method is to delete invoice item by id",
 *      description="This method is to delete invoice item by id",
 *

 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *       @OA\JsonContent(),
 *       ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 * @OA\JsonContent(),
 *      ),
 *        @OA\Response(
 *          response=422,
 *          description="Unprocesseble Content",
 *    @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *   @OA\JsonContent()
 * ),
 *  * @OA\Response(
 *      response=400,
 *      description="Bad Request",
 *   *@OA\JsonContent()
 *   ),
 * @OA\Response(
 *      response=404,
 *      description="not found",
 *   *@OA\JsonContent()
 *   )
 *      )
 *     )
 */

public function deleteInvoiceItemById($invoice_id,$id, Request $request)
{

    try {
        $this->storeActivity($request,"");



        $invoice_item = InvoiceItem::where([
            "invoice_id" => $invoice_id,
            "id" => $id
        ])
        ->first();

        if(!$invoice_id) {
     return response()->json([
"message" => "no invoice item found"
],404);
        }
        $invoice_id->delete();

        return response()->json(["ok" => true], 200);
    } catch (Exception $e) {

        return $this->sendError($e, 500,$request);
    }
}



}
