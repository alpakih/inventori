<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Response\ApiBaseResponse;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    private $response;
    private $model;

    public function __construct()
    {
        $this->model = new Category();
        $this->response = new ApiBaseResponse();
    }

    public function index()
    {
        $data = $this->model->orderBy('created_at', 'DESC')->paginate(10);
        return response()->json($this->response->singleData($data, []), 200);
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validationRules = [
            'code' => 'required',
            'name' => 'required',
        ];
        $validator = Validator::make($input, $validationRules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $data = $this->model->create($input);

        return response()->json($this->response->singleData($data, []), 200);
    }

    public function show($id)
    {
        $data = $this->model->findOrFail($id);
        return response()->json($this->response->singleData($data, []), 200);
    }

    public function update($id, Request $request)
    {
        $input = $request->all();
        $validationRules = [
            'code' => 'required',
            'name' => 'required',
        ];
        $validator = Validator::make($input, $validationRules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $data = $this->model->findOrFail($id);
        $data->fill($input);
        $data->save();

        return response()->json($this->response->singleData($data, []), 200);
    }

    public function delete($id)
    {
        $data = $this->model->findOrFail($id);
        $data->delete();
        return response()->json($this->response->status(200, "Success Deleted", null), 200);
    }
}
