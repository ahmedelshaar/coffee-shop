<?php

class Products extends Controller
{
    public $productModel;

    public function __construct()
    {
        $this->productModel = $this->model('Product');
    }

    public function index()
    {
        $data = $this->productModel->getProducts();
        return $this->view('products/index', $data);
    }

    public function create()
    {
        $request_data = $errors = [
            'name' => '',
            'price' => '',
            'avatar' => 'default.jpg',
            'status' => '',
            'cat_id' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // update request data
            $request_data = array_merge($request_data, $_POST);
            $request_data['avatar'] = $_FILES['avatar']['name'];
            $errs = $this->productModel->addProduct($request_data);
            if (is_array($errs)) {
                $errors = array_merge($errors, $errs);
            } else {
                if ($errs === true) {
                    flash('product_message', 'Product added', 'success');
                    return redirect('products/index');
                } else {
                    flash('product_message', 'Something went wrong', 'danger');
                }
            }
        }

        $data = [
            'errors' => $errors,
            'data' => $request_data,
        ];

        return $this->view('products/create', $data);
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $product = $this->productModel->find($_POST['id']);
            if ($product) {
                $data = $this->productModel->deleteProduct($_POST['id']);
                if ($data) {
                    flash('product_message', 'Product Deleted', 'success');
                } else {
                    flash('product_message', 'Something went wrong', 'danger');
                }
                return redirect('products');
            } else {
                flash('product_message', 'Products Not Found', 'danger');
                return redirect('products');
            }
        }
    }

    public function update($id)
    {
        $request_data = $errors = [
            'name' => '',
            'price' => '',
            'avatar' => '',
            'status' => '',
            'cat_id' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // update request data
            $request_data = array_merge($request_data, $_POST);
            if ($_FILES['avatar']['name'])
                $request_data['avatar'] = $_FILES['avatar']['name'];
            $errs = $this->productModel->updateProduct($request_data);
            if (is_array($errs)) {
                $errors = array_merge($errors, $errs);
            } else {
                if ($errs === true) {
                    flash('product_update', 'Product Updated', 'success');
                    return redirect('products');
                } else {
                    flash('product_update', 'Something went wrong', 'danger');
                }
            }
        }
        $data = [
            'errors' => $errors,
            'data' => $request_data,
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $data = [
                'errors' => [
                    'name' => '',
                    'price' => '',
                    'avatar' => '',
                    'status' => '',
                    'cat_id' => '',
                ],
                'data' => []
            ];
            $data['data'] = $this->productModel->getProductById($id);
        }
        return $this->view('products/update', $data);
    }
}
