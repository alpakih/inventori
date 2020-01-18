<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OutcomingGoods;
use App\Models\OutcomingGoodsDetail;
use App\Models\Product;
use App\Response\ApiBaseResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OutcomingGoodsController extends Controller
{
    private $response;
    private $model;

    public function __construct()
    {
        $this->model = new OutcomingGoods();
        $this->response = new ApiBaseResponse();
    }

    public function index()
    {
        $data = $this->model->with(['outcomingGoodsDetail'])->orderBy('created_at', 'DESC')->paginate(10);
        return response()->json($this->response->singleData($data, []), 200);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $validationRules = [
                'invoice' => 'required',
                'customer_id' => 'required',
                'date' => 'required',
                'products' => 'required|array',
                'product.*.product_id' => 'required',
                'product.*.qty' => 'required|numeric',
            ];
            $validator = Validator::make($input, $validationRules);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            $data = $this->model;

            $data->invoice = $request->invoice;
            $data->customer_id = $request->customer_id;
            $data->date = $request->date;
            $data->user_id = Auth::user()->id;
            $data->save();

            foreach ($request->products as $product) {
               $incomingGoodsDetail = new OutcomingGoodsDetail();
               $incomingGoodsDetail->outcoming_goods_id = $data->id;
               $incomingGoodsDetail->product_id = $product['product_id'];
               $incomingGoodsDetail->qty = $product['qty'];
               $incomingGoodsDetail->save();

               $addStock = Product::where('id','=',$product['product_id'])->first();
               $addStock->stock = $addStock->stock - $product['qty'];
               $addStock->update();
            }

            DB::commit();
            return response()->json($this->response->singleData($data, []), 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json($this->response->status("ERROR", $e->getMessage(), null), 500);
        }
    }

    public function show($id)
    {
        $data = $this->model->with(['outcomingGoodsDetail'])->findOrFail($id);
        return response()->json($this->response->singleData($data, []), 200);
    }

    public function delete($id)
    {
        $data = $this->model->findOrFail($id);
        $data->delete();
        return response()->json($this->response->status(200, "Success Deleted", null), 200);
    }
}
